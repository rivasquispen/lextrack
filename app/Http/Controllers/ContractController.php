<?php

namespace App\Http\Controllers;

use App\Mail\AdvisorAssignedMail;
use App\Mail\ContractRevisionPublishedMail;
use App\Mail\ContractObservedMail;
use App\Models\Contract;
use App\Models\ContractVersionHistory;
use App\Models\ContractVersion;
use App\Models\User;
use App\Services\ContractMailRecipients;
use App\Services\ContractDocumentStorage;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Illuminate\Support\Str;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ContractController extends Controller
{
    public function __construct(
        private ContractDocumentStorage $documentStorage,
        private ContractMailRecipients $mailRecipients
    )
    {
    }

    public function show(Request $request, Contract $contract, ?ContractVersion $version = null): View
    {
        $user = $request->user();

        if (! $this->canViewContract($user, $contract)) {
            abort(403);
        }

        $contract->load([
            'category:id,nombre',
            'subcategory:id,nombre',
            'creator:id,nombre,email',
            'lawyer:id,nombre,email',
            'signers.user:id,nombre,email',
            'advisor:id,nombre,email',
        ]);

        $versionList = $contract->versions()
            ->orderByDesc('numero_version')
            ->get(['id', 'numero_version', 'created_at']);

        $latestVersionRecord = $versionList->first();

        if ($version) {
            if ((int) $version->contract_id !== (int) $contract->id) {
                abort(404);
            }
            $version->load(['creator:id,nombre,email', 'approvals.user:id,nombre,email']);
            $currentVersion = $version;
        } else {
            $currentVersion = $contract->versions()
                ->with(['creator:id,nombre,email', 'approvals.user:id,nombre,email'])
                ->orderByDesc('numero_version')
                ->first();
        }

        $isViewingLatest = $currentVersion && $latestVersionRecord
            ? (int) $currentVersion->id === (int) $latestVersionRecord->id
            : true;

        $latestApprovals = $currentVersion?->approvals ?? collect();
        $attachments = $currentVersion?->attachments ?? [];
        $histories = ContractVersionHistory::whereHas('version', function ($query) use ($contract) {
            $query->where('contract_id', $contract->id);
        })
            ->when($currentVersion, function ($query) use ($currentVersion) {
                $query->where('contract_version_id', $currentVersion->id);
            })
            ->with(['uploader:id,nombre,email', 'version:id,numero_version,contract_id'])
            ->orderByDesc('created_at')
            ->get();

        $latestDocumentHistory = $histories->firstWhere('contract_version_id', $currentVersion?->id)
            ?? $histories->first();

        if (! $latestDocumentHistory && $currentVersion && $currentVersion->documento && Storage::exists($currentVersion->documento)) {
            $historyPath = $this->documentStorage->archiveVersionDocument(
                $contract->codigo,
                $currentVersion->numero_version,
                $currentVersion->documento
            );

            if ($historyPath) {
                ContractVersionHistory::create([
                    'contract_version_id' => $currentVersion->id,
                    'document_path' => $historyPath,
                    'uploaded_by' => $currentVersion->creado_por ?? $contract->creado_por,
                ]);
            } else {
                ContractVersionHistory::firstOrCreate(
                    [
                        'contract_version_id' => $currentVersion->id,
                        'document_path' => $currentVersion->documento,
                    ],
                    [
                        'uploaded_by' => $currentVersion->creado_por ?? $contract->creado_por,
                    ]
                );
            }

            $histories = ContractVersionHistory::whereHas('version', function ($query) use ($contract) {
                $query->where('contract_id', $contract->id);
            })
                ->when($currentVersion, function ($query) use ($currentVersion) {
                    $query->where('contract_version_id', $currentVersion->id);
                })
                ->with(['uploader:id,nombre,email', 'version:id,numero_version,contract_id'])
                ->orderByDesc('created_at')
                ->get();

            $latestDocumentHistory = $histories->firstWhere('contract_version_id', $currentVersion?->id)
                ?? $histories->first();
        }

        $userCanConfigure = $this->canConfigureApprovals($user, $contract) && $isViewingLatest;

        $advisors = collect();
        $approverOptions = collect();
        $selectedApprovers = $latestApprovals->pluck('user_id')->all();
        $canAssignAdvisor = $user && $user->hasRole('abogado') && $isViewingLatest;

        if ($canAssignAdvisor) {
            $advisors = User::role('asesor')
                ->orderByRaw("COALESCE(nombre, email)")
                ->get(['id', 'nombre', 'email']);
        }

        if ($userCanConfigure) {
            $approverOptions = User::role('aprobador')
                ->whereNotIn('id', $selectedApprovers)
                ->with('department.company:id,nombre')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'email', 'department_id']);
        }

        return view('contracts.show', [
            'contract' => $contract,
            'latestVersion' => $currentVersion,
            'latestApprovals' => $latestApprovals,
            'attachments' => $attachments,
            'userCanConfigure' => $userCanConfigure,
            'canUploadDocument' => $isViewingLatest && $this->canUploadDocument($user, $contract),
            'canDownloadDocument' => $this->canDownloadDocument($user, $contract),
            'canUploadSignedDocument' => $isViewingLatest && $this->canUploadSignedDocument($user, $contract),
            'documentHistories' => $histories,
            'latestDocumentHistory' => $latestDocumentHistory,
            'canAssignAdvisor' => $canAssignAdvisor,
            'advisors' => $advisors,
            'approverOptions' => $approverOptions,
            'hasFinalDocument' => $contract->document && Storage::exists($contract->document),
            'hasSignedDocument' => $contract->document_signed && Storage::exists($contract->document_signed),
            'versions' => $versionList,
            'isViewingLatestVersion' => $isViewingLatest,
            'canObserveContract' => $isViewingLatest && $this->canStartObservation($user, $contract),
            'selectedVersionId' => $currentVersion?->id,
        ]);
    }

    public function storeVersion(Request $request, Contract $contract): RedirectResponse
    {
        $user = $request->user();

        if (! $this->canUploadDocument($user, $contract)) {
            abort(403);
        }

        $validated = $request->validate([
            'document' => ['required', File::types(['doc', 'docx'])->max(10 * 1024)],
        ]);

        $latestVersion = $contract->versions()->orderByDesc('numero_version')->first();
        $storedPaths = [];

        DB::beginTransaction();

        try {
            $documentPath = $this->documentStorage->storeUploadedDocument(
                $request->file('document'),
                $contract->codigo,
                $latestVersion?->numero_version ?? 1
            );
            $storedPaths[] = $documentPath;

            $attachmentsMeta = $latestVersion?->attachments ?? [];

            $version = $latestVersion;

            if (! $version) {
                $version = ContractVersion::create([
                    'contract_id' => $contract->id,
                    'template_id' => $latestVersion?->template_id,
                    'numero_version' => 1,
                    'documento' => $documentPath,
                    'creado_por' => $user->id,
                    'comentarios' => null,
                    'estado' => 'borrador',
                    'form_payload' => $latestVersion?->form_payload,
                    'attachments' => $attachmentsMeta,
                ]);
            } else {
                $version->documento = $documentPath;
                $version->attachments = $attachmentsMeta;
                $version->save();
            }

            $historyPath = $this->documentStorage->archiveVersionDocument(
                $contract->codigo,
                $version->numero_version,
                $documentPath
            );

            ContractVersionHistory::create([
                'contract_version_id' => $version->id,
                'document_path' => $historyPath ?? $documentPath,
                'uploaded_by' => $user->id,
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->cleanupStoredFiles($storedPaths);

            throw $exception;
        }

        $contract->loadMissing(['category:id,nombre']);
        $version->loadMissing(['creator:id,nombre,email', 'approvals.user:id,nombre,email']);

        $ctaUrl = route('contracts.show', $contract);
        $recipients = $this->mailRecipients->forContract($contract, $version, $user->id);

        if ($recipients->isEmpty()) {
            $monitoringBcc = config('mail.monitoring_bcc');

            if (is_string($monitoringBcc) && trim($monitoringBcc) !== '') {
                Mail::to($monitoringBcc)
                    ->send(new ContractRevisionPublishedMail($contract, $version, $user, $user, $ctaUrl));
            }
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)
                ->send(new ContractRevisionPublishedMail($contract, $version, $recipient, $user, $ctaUrl));
        }

        return redirect()
            ->route('contracts.show', $contract)
            ->with('status', 'Se guardó una nueva versión del documento.');
    }

    public function downloadDocument(Request $request, Contract $contract, ContractVersionHistory $history): StreamedResponse
    {
        $user = $request->user();

        if (! $this->canDownloadDocument($user, $contract)) {
            abort(403);
        }

        $history = ContractVersionHistory::with(['version' => function ($query) {
            $query->withTrashed();
        }])->findOrFail($history->getKey());

        if ((int) ($history->version?->contract_id) !== (int) $contract->id) {
            abort(404);
        }

        if (! $history->document_path || ! Storage::exists($history->document_path)) {
            abort(404);
        }

        return Storage::download($history->document_path, basename($history->document_path));
    }

    public function downloadFinalDocument(Request $request, Contract $contract): StreamedResponse
    {
        if (! $this->canDownloadDocument($request->user(), $contract)) {
            abort(403);
        }

        if (! $contract->document || ! Storage::exists($contract->document)) {
            abort(404);
        }

        return Storage::download($contract->document, basename($contract->document));
    }

    public function downloadApprovedDocument(Request $request, Contract $contract): StreamedResponse
    {
        if (! $this->canDownloadDocument($request->user(), $contract)) {
            abort(403);
        }

        $latestVersion = $contract->versions()->latest('numero_version')->first();

        if (! $latestVersion || ! $latestVersion->documento || ! Storage::exists($latestVersion->documento)) {
            abort(404);
        }

        return Storage::download($latestVersion->documento, basename($latestVersion->documento));
    }

    public function downloadSignedDocument(Request $request, Contract $contract): StreamedResponse
    {
        if (! $this->canDownloadDocument($request->user(), $contract)) {
            abort(403);
        }

        if (! $contract->document_signed || ! Storage::exists($contract->document_signed)) {
            abort(404);
        }

        return Storage::download($contract->document_signed, basename($contract->document_signed));
    }

    public function uploadSignedDocument(Request $request, Contract $contract): RedirectResponse
    {
        if (! $this->canUploadSignedDocument($request->user(), $contract)) {
            abort(403);
        }

        $validated = $request->validate([
            'signed_document' => ['required', File::types(['pdf'])->max(10 * 1024)],
        ]);

        $latestVersion = $contract->versions()->orderByDesc('numero_version')->first();

        if (! $latestVersion) {
            return back()->withErrors([
                'signed_document' => 'No se encontró una versión para adjuntar el documento firmado.',
            ]);
        }

        $path = $this->documentStorage->storeSignedDocument(
            $request->file('signed_document'),
            $contract->codigo,
            $latestVersion->numero_version
        );

        $contract->document_signed = $path;
        $contract->estado = Contract::STATUS_SIGNED;
        $contract->save();

        return back()->with('status', 'Contrato firmado cargado correctamente.');
    }

    public function observe(Request $request, Contract $contract): RedirectResponse
    {
        $user = $request->user();

        if (! $this->canStartObservation($user, $contract)) {
            abort(403);
        }

        $latestVersion = $contract->versions()
            ->with(['approvals:user_id'])
            ->orderByDesc('numero_version')
            ->first();

        if (! $latestVersion) {
            return back()->withErrors([
                'observe' => 'No existe una versión para observar.',
            ]);
        }

        DB::beginTransaction();
        $newVersion = null;

        try {
            $newVersionNumber = $latestVersion->numero_version + 1;

            $newDocumentPath = $latestVersion->documento
                ? $this->documentStorage->copyDocument($latestVersion->documento, $contract->codigo, $newVersionNumber)
                : null;

            $newAttachments = collect($latestVersion->attachments ?? [])
                ->map(function ($attachment) use ($contract, $newVersionNumber) {
                    $path = $attachment['path'] ?? null;

                    if (! $path) {
                        return null;
                    }

                    return $this->documentStorage->copyAttachment(
                        $path,
                        $contract->codigo,
                        $newVersionNumber,
                        $attachment['name'] ?? 'Adjunto',
                        $attachment['type'] ?? 'general',
                        $attachment['original_name'] ?? null
                    );
                })
                ->filter()
                ->values()
                ->all();

            $newVersion = ContractVersion::create([
                'contract_id' => $contract->id,
                'template_id' => $latestVersion->template_id,
                'numero_version' => $newVersionNumber,
                'documento' => $newDocumentPath,
                'creado_por' => $latestVersion->creado_por,
                'comentarios' => $latestVersion->comentarios,
                'estado' => 'observado',
                'form_payload' => $latestVersion->form_payload,
                'attachments' => $newAttachments,
            ]);

            if ($newDocumentPath) {
                $historyPath = $this->documentStorage->archiveVersionDocument(
                    $contract->codigo,
                    $newVersion->numero_version,
                    $newDocumentPath
                );

                ContractVersionHistory::create([
                    'contract_version_id' => $newVersion->id,
                    'document_path' => $historyPath ?? $newDocumentPath,
                    'uploaded_by' => $user?->id ?? $latestVersion->creado_por,
                ]);
            }

            $latestVersion->approvals->each(function ($approval) use ($newVersion) {
                $newVersion->approvals()->create([
                    'user_id' => $approval->user_id,
                    'estado' => 'pendiente',
                    'aprobado_at' => null,
                    'assigned_at' => now(),
                ]);
            });

            $contract->estado = Contract::STATUS_OBSERVED;
            $contract->document_signed = null;
            $contract->save();

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        if ($newVersion) {
            $contract->loadMissing(['creator:id,nombre,email', 'lawyer:id,nombre,email', 'advisor:id,nombre,email', 'category:id,nombre']);
            $newVersion->loadMissing(['approvals.user:id,nombre,email']);

            $observerName = $user?->nombre
                ?? $user?->name
                ?? $user?->email
                ?? 'Equipo legal';

            $ctaUrl = route('contracts.versions.show', [$contract, $newVersion]);
            $recipients = $this->mailRecipients->forContract($contract, $newVersion, $user?->id);

            foreach ($recipients as $recipient) {
                $mail = Mail::to($recipient->email);
                $monitoringBcc = config('mail.monitoring_bcc');

                if (is_string($monitoringBcc) && trim($monitoringBcc) !== '') {
                    $mail->bcc($monitoringBcc);
                }

                $mail->send(new ContractObservedMail($contract, $newVersion, $recipient, $ctaUrl, $observerName));
            }
        }

        return redirect()
            ->route('contracts.show', $contract)
            ->with('status', 'Se creó una nueva versión observada.');
    }

    public function storeAttachment(Request $request, Contract $contract): RedirectResponse
    {
        if (! $this->canUploadDocument($request->user(), $contract)) {
            abort(403);
        }

        $validated = $request->validate([
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*.name' => ['required', 'string', 'max:180'],
            'attachments.*.file' => ['required', File::types(['pdf','jpg','jpeg','png','tiff'])->max(10 * 1024)],
        ]);

        $version = $contract->versions()->orderByDesc('numero_version')->first();

        if (! $version) {
            throw ValidationException::withMessages([
                'attachments' => 'No se encontró una versión para adjuntar archivos.',
            ]);
        }

        $attachmentsMeta = $version->attachments ?? [];
        $storedPaths = [];

        DB::beginTransaction();

        try {
            foreach ($validated['attachments'] as $index => $data) {
                /** @var UploadedFile|null $file */
                $file = $request->file("attachments.$index.file");

                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $stored = $this->documentStorage->storeAttachment(
                    $file,
                    $contract->codigo,
                    $version->numero_version,
                    'adjunto',
                    $data['name']
                );

                $attachmentsMeta[] = $stored;
                $storedPaths[] = $stored['path'];
            }

            $version->attachments = $attachmentsMeta;
            $version->save();

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->cleanupStoredFiles($storedPaths);

            throw $exception;
        }

        return back()->with('status', 'Adjuntos agregados correctamente.');
    }

    public function destroyAttachment(Request $request, Contract $contract): RedirectResponse
    {
        if (! $this->canUploadDocument($request->user(), $contract)) {
            abort(403);
        }

        $validated = $request->validate([
            'attachment_path' => ['required', 'string'],
        ]);

        $version = $contract->versions()->orderByDesc('numero_version')->first();

        if (! $version) {
            return back()->withErrors(['attachment' => 'No se encontró una versión para editar adjuntos.']);
        }

        $attachments = collect($version->attachments ?? []);
        $index = $attachments->search(fn ($item) => ($item['path'] ?? null) === $validated['attachment_path']);

        if ($index === false) {
            return back()->withErrors(['attachment' => 'El adjunto seleccionado no existe.']);
        }

        $removed = $attachments->pull($index);

        $version->attachments = $attachments->values()->all();
        $version->save();

        if (! empty($removed['path']) && Storage::exists($removed['path'])) {
            Storage::delete($removed['path']);
        }

        return back()->with('status', 'Adjunto eliminado correctamente.');
    }


  public function downloadAttachment(Request $request, Contract $contract): StreamedResponse
  {
      if (! $this->canDownloadDocument($request->user(), $contract)) {
          abort(403);
      }

      $path = $request->query('path');

      if (! $path) {
          abort(404);
      }

      $version = $contract->versions()->orderByDesc('numero_version')->first();

      if (! $version) {
          abort(404);
      }

      $attachment = collect($version->attachments ?? [])
          ->firstWhere('path', $path);

      if (! $attachment || ! Storage::exists($path)) {
          abort(404);
      }

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'dat';
        $slugName = Str::slug($attachment['name'] ?? 'adjunto') ?: 'adjunto';
        $code = $contract->codigo ?? 'LEX';
        $versionNumber = $version->numero_version ?? 1;

        $filename = sprintf('%s-%s-%s.%s', $code, $versionNumber, $slugName, $extension);

        return Storage::download($path, $filename);
  }


    public function updateAdvisor(Request $request, Contract $contract): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('abogado')) {
            abort(403);
        }

        $data = $request->validate([
            'asesor_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $advisorId = $data['asesor_id'] ?? null;

        if ($advisorId) {
            $isAdvisor = User::role('asesor')->whereKey($advisorId)->exists();

            if (! $isAdvisor) {
                return back()->withErrors([
                    'asesor_id' => 'El usuario seleccionado no tiene el rol de asesor.',
                ]);
            }
        }

        $contract->asesor_id = $advisorId;

        if ($advisorId && in_array($contract->estado, [Contract::STATUS_CREATED, Contract::STATUS_ASSIGNED], true)) {
            $contract->estado = Contract::STATUS_ASSIGNED;
        } elseif (! $advisorId && $contract->estado === Contract::STATUS_ASSIGNED && ! $contract->abogado_id) {
            $contract->estado = Contract::STATUS_CREATED;
        }
        $contract->save();

        $contract->load(['advisor:id,nombre,email', 'creator:id,nombre,email', 'category:id,nombre']);
        $ctaUrl = route('contracts.show', $contract);

        if ($contract->advisor && $contract->advisor->email) {
            Mail::to($contract->advisor->email)
                ->send(new AdvisorAssignedMail($contract, 'advisor', $ctaUrl));
        }

        if ($contract->creator && $contract->creator->email) {
            Mail::to($contract->creator->email)
                ->send(new AdvisorAssignedMail($contract, 'creator', $ctaUrl));
        }

        $lawyers = User::role('abogado')
            ->whereNotIn('id', array_filter([$contract->advisor?->id, $contract->creator?->id]))
            ->get(['id', 'nombre', 'email']);

        foreach ($lawyers as $lawyer) {
            if (! $lawyer->email) {
                continue;
            }

            Mail::to($lawyer->email)
                ->send(new AdvisorAssignedMail($contract, 'lawyer', $ctaUrl));
        }

        return back()->with('status', 'Asesor actualizado correctamente.');
    }

    public function canViewContract($user, Contract $contract): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin') || $user->hasRole('abogado')) {
            return true;
        }

        if ((int) $contract->creado_por === (int) $user->id) {
            return true;
        }

        //if ($contract->abogado_id && (int) $contract->abogado_id === (int) $user->id) {
        //    return true;
        //}

        if ($contract->asesor_id && (int) $contract->asesor_id === (int) $user->id) {
            return true;
        }

        $isApprover = $contract->approvals()
            ->where('approvals.user_id', $user->id)
            ->exists();

        if ($isApprover) {
            return true;
        }

        $isSigner = $contract->signers()
            ->where('contract_signers.user_id', $user->id)
            ->exists();

        return $isSigner;
    }

    private function canConfigureApprovals($user, Contract $contract): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('abogado');
    }

    private function canUploadDocument($user, Contract $contract): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin') || $user->hasRole('abogado')) {
            return true;
        }

        //|| ($contract->abogado_id && (int) $contract->abogado_id === (int) $user->id)
        if ((int) $contract->creado_por === (int) $user->id
            || ($contract->asesor_id && (int) $contract->asesor_id === (int) $user->id)) {
            return true;
        }

        return $contract->approvals()->where('approvals.user_id', $user->id)->exists();
    }

    private function canDownloadDocument($user, Contract $contract): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin') || $user->hasRole('abogado')) {
            return true;
        }

        //|| ($contract->abogado_id && (int) $contract->abogado_id === (int) $user->id)
        if ((int) $contract->creado_por === (int) $user->id
            || ($contract->asesor_id && (int) $contract->asesor_id === (int) $user->id)) {
            return true;
        }

        return $contract->approvals()->where('approvals.user_id', $user->id)->exists();
    }

    private function canUploadSignedDocument($user, Contract $contract): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('abogado')
            && in_array($contract->estado, [Contract::STATUS_APPROVED, Contract::STATUS_OBSERVED], true);
    }

    private function canStartObservation($user, Contract $contract): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('abogado')
            && in_array($contract->estado, [Contract::STATUS_APPROVED, Contract::STATUS_OBSERVED], true);
    }

    private function cleanupStoredFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path) {
                Storage::delete($path);
            }
        }
    }
}
