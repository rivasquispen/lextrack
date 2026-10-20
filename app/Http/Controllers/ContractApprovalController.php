<?php

namespace App\Http\Controllers;

use App\Mail\ApproverAssignedMail;
use App\Mail\ContractApprovedMail;
use App\Models\Approval;
use App\Models\Contract;
use App\Models\User;
use App\Services\ApprovalReopeningService;
use App\Services\ContractMailRecipients;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ContractApprovalController extends Controller
{
    public function __construct(private ContractMailRecipients $mailRecipients) {}

    public function update(Request $request, Contract $contract): RedirectResponse
    {
        $this->ensureLawyerAccess($request->user(), $contract);

        $version = $contract->versions()->orderByDesc('numero_version')->first();

        if (! $version) {
            throw ValidationException::withMessages([
                'approvers' => 'No se encontró una versión para configurar aprobadores.',
            ]);
        }

        $approverIds = collect($request->input('approvers', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($approverIds->isNotEmpty()) {
            $validApprovers = User::role('aprobador')
                ->whereIn('id', $approverIds)
                ->pluck('id')
                ->all();

            if (count($validApprovers) !== $approverIds->count()) {
                throw ValidationException::withMessages([
                    'approvers' => 'Uno o más usuarios seleccionados no tienen el rol de aprobador.',
                ]);
            }
        }

        $signerIds = collect($request->input('signers', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($signerIds->isNotEmpty()) {
            $validSigners = User::role('firmante')
                ->whereIn('id', $signerIds)
                ->pluck('id')
                ->all();

            if (count($validSigners) !== $signerIds->count()) {
                throw ValidationException::withMessages([
                    'signers' => 'Uno o más usuarios seleccionados no tienen el rol de firmante.',
                ]);
            }
        }

        $newApproverIds = collect();

        DB::transaction(function () use ($version, $contract, $approverIds, $signerIds, &$newApproverIds) {
            $existingApproverIds = $version->approvals()->pluck('user_id')->all();
            $newApproverIds = $approverIds->diff($existingApproverIds)->values();

            foreach ($newApproverIds as $userId) {
                $version->approvals()->updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'estado' => 'pendiente',
                        'aprobado_at' => null,
                        'assigned_at' => now(),
                    ]
                );
            }

            $contract->signers()
                ->whereNotIn('user_id', $signerIds)
                ->delete();

            foreach ($signerIds as $userId) {
                $contract->signers()->updateOrCreate(
                    ['user_id' => $userId],
                    ['estado' => 'pendiente', 'firmado_at' => null]
                );
            }
        });

        $hasApprovals = $version->approvals()->exists();

        $contract->estado = $hasApprovals
            ? Contract::STATUS_APPROVAL
            : ($contract->abogado_id ? Contract::STATUS_ASSIGNED : Contract::STATUS_CREATED);
        $contract->save();

        if ($newApproverIds->isNotEmpty()) {
            $contract->load(['approvals.user:id,nombre,email', 'creator:id,nombre,email', 'category:id,nombre', 'advisor:id,nombre,email']);
            $ctaUrl = route('contracts.show', $contract);

            foreach ($contract->approvals->whereIn('user_id', $newApproverIds->all()) as $approval) {
                if ($approval->user?->email) {
                    Mail::to($approval->user->email)
                        ->send(new ApproverAssignedMail($contract, 'approver', $ctaUrl));
                }
            }

            if ($contract->creator?->email) {
                Mail::to($contract->creator->email)
                    ->send(new ApproverAssignedMail($contract, 'creator', $ctaUrl));
            }

            $lawyers = User::role('abogado')
                ->whereNotIn('id', $newApproverIds->push($contract->creator?->id)->filter()->all())
                ->get(['id', 'nombre', 'email']);

            foreach ($lawyers as $lawyer) {
                if (! $lawyer->email) {
                    continue;
                }

                Mail::to($lawyer->email)
                    ->send(new ApproverAssignedMail($contract, 'lawyer', $ctaUrl));
            }
        }

        return back()->with('status', 'Flujo de aprobaciones y firmantes actualizado correctamente.');
    }

    public function approve(Request $request, Contract $contract, Approval $approval): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'No autenticado.');
        }

        $approval->loadMissing('version');

        if (! $approval->version || (int) $approval->version->contract_id !== (int) $contract->id) {
            abort(404);
        }

        $isOwner = (int) $user->id === (int) $approval->user_id;
        $isAdmin = $user->hasRole('admin');

        if (! $isOwner && ! $isAdmin) {
            abort(403, 'No estás autorizado para aprobar este contrato.');
        }

        $contractJustApproved = false;
        $approvalChanged = false;

        DB::transaction(function () use ($approval, $contract, &$contractJustApproved, &$approvalChanged) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $version = $contract->versions()->orderByDesc('numero_version')->lockForUpdate()->first();
            $approval = Approval::query()->lockForUpdate()->findOrFail($approval->id);

            if (! $version || (int) $version->id !== (int) $approval->version_id) {
                throw ValidationException::withMessages(['approval' => 'Solo puedes aprobar la versión vigente del contrato.']);
            }

            if (app(ApprovalReopeningService::class)->hasSignatureEvidence($contract)) {
                throw ValidationException::withMessages(['approval' => 'No se pueden modificar aprobaciones de un contrato con evidencia de firma.']);
            }

            if ($approval->estado === 'aprobado') {
                return;
            }

            if (! in_array($contract->estado, [Contract::STATUS_APPROVAL, Contract::STATUS_OBSERVED], true)) {
                throw ValidationException::withMessages(['approval' => 'El contrato no está disponible para registrar aprobaciones.']);
            }

            $approval->estado = 'aprobado';
            $approval->aprobado_at = now();
            $approval->save();
            $approvalChanged = true;

            if ($version && ! $version->approvals()->where('estado', '!=', 'aprobado')->exists()) {
                if ($version->estado !== 'aprobado') {
                    $version->estado = 'aprobado';
                    $version->save();
                }

                if ($contract->estado !== Contract::STATUS_APPROVED) {
                    $contract->estado = Contract::STATUS_APPROVED;
                    $contract->document = $version->documento;
                    $contract->document_signed = null;
                    $contract->save();
                    $contractJustApproved = true;
                }
            }
        });

        if (! $approvalChanged) {
            return back()->with('status', 'Ya registraste tu aprobación para este contrato.');
        }

        if ($contractJustApproved) {
            $contract->refresh();
            $contract->loadMissing(['creator:id,nombre,email', 'lawyer:id,nombre,email', 'advisor:id,nombre,email', 'category:id,nombre']);
            $version = $contract->versions()
                ->with(['approvals.user:id,nombre,email'])
                ->orderByDesc('numero_version')
                ->first();
            $ctaUrl = route('contracts.show', $contract);
            $recipients = $this->mailRecipients->forContract($contract, $version, $user->id);

            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->send(new ContractApprovedMail($contract, $recipient, $ctaUrl));
            }
        }

        return back()->with('status', 'Has aprobado el contrato correctamente.');
    }

    public function destroy(Request $request, Contract $contract, Approval $approval): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('abogado')) {
            abort(403, 'No estás autorizado para eliminar aprobadores.');
        }

        $approval->loadMissing('version');

        if (! $approval->version || (int) $approval->version->contract_id !== (int) $contract->id) {
            abort(404);
        }

        if ($approval->estado === 'aprobado') {
            return back()->with('status', 'No puedes eliminar un aprobador que ya registró su decisión.');
        }

        $approval->delete();

        if (! $contract->approvals()->exists()) {
            $contract->estado = $contract->abogado_id ? Contract::STATUS_ASSIGNED : Contract::STATUS_CREATED;
            $contract->save();
        }

        return back()->with('status', 'Aprobador eliminado del flujo.');
    }

    private function ensureLawyerAccess(?User $user, Contract $contract): void
    {
        if (! $user) {
            abort(403, 'No autenticado.');
        }

        if ($user->hasRole('admin') || $user->hasRole('abogado')) {
            return;
        }

        abort(403, 'No estás autorizado para configurar este contrato.');
    }
}
