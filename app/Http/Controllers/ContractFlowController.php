<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contract;
use App\Models\ContractVersionHistory;
use App\Models\ContractVersion;
use App\Models\Template;
use App\Models\User;
use App\Notifications\NewContractNotification;
use App\Services\ContractDocumentStorage;
use App\Services\WordExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContractFlowController extends Controller
{
    public function __construct(
        private ContractDocumentStorage $documentStorage,
        private WordExporter $wordExporter
    ) {
    }

    public function create(): View
    {
        $categories = Category::with('subcategories:id,nombre,category_id')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug']);

        $categoryMatrix = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'subs' => $category->subcategories->map(fn ($sub) => [
                    'id' => $sub->id,
                    'nombre' => $sub->nombre,
                ])->values(),
            ];
        })->values();

        $templates = Template::orderBy('nombre')
            ->get(['id', 'nombre', 'subcategoria_id', 'descripcion', 'forms']);

        $templatesLookup = $templates->mapWithKeys(function ($template) {
            return [$template->id => [
                'id' => $template->id,
                'nombre' => $template->nombre,
                'subcategoria_id' => $template->subcategoria_id,
                'descripcion' => $template->descripcion,
                'forms' => $template->forms,
            ]];
        })->toArray();

        $templatesBySubcategory = $templates->groupBy('subcategoria_id')->map(function ($group) {
            return $group->map(fn ($template) => [
                'id' => $template->id,
                'nombre' => $template->nombre,
            ])->values();
        })->toArray();

        $lawyers = User::role('abogado')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'email']);

        return view('contracts.create-template', [
            'categories' => $categories,
            'categoryMatrix' => $categoryMatrix,
            'templatesLookup' => $templatesLookup,
            'templatesBySubcategory' => $templatesBySubcategory,
            'lawyers' => $lawyers,
        ]);
    }

    public function createZero(): View
    {
        $categories = Category::with('subcategories:id,nombre,category_id')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $categoryMatrix = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'subs' => $category->subcategories->map(fn ($sub) => [
                    'id' => $sub->id,
                    'nombre' => $sub->nombre,
                ])->values(),
            ];
        })->values();

        $lawyers = User::role('abogado')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'email']);

        $defaultForm = [
            [
                'subtitulo' => 'Datos de la Contraparte',
                'campos' => [
                    ['label' => 'Nombre de la empresa', 'name' => 'laboratorio', 'input' => 'text', 'required' => true],
                    ['label' => 'RUC / NIF / CIF', 'name' => 'rucContraparte', 'input' => 'text', 'required' => true],
                    ['label' => 'Representante legal', 'name' => 'representante', 'input' => 'text', 'required' => true],
                    ['label' => 'DNI / Identificación del representante', 'name' => 'dni_representante', 'input' => 'text', 'required' => true],
                    ['label' => 'País de la empresa', 'name' => 'pais_empresa', 'input' => 'text', 'required' => true],
                    ['label' => 'Servicios a contratar', 'name' => 'servicios_contratados', 'input' => 'text', 'required' => true],
                ],
            ],
            [
                'subtitulo' => 'Datos del Acuerdo',
                'campos' => [
                    ['label' => 'Fecha del acuerdo', 'name' => 'fecha_acuerdo', 'input' => 'date', 'required' => true],
                    ['label' => 'Fecha de caducidad', 'name' => 'fecha_caducidad', 'input' => 'date', 'required' => false],
                ],
            ],
        ];

        return view('contracts.create-zero', [
            'categories' => $categories,
            'categoryMatrix' => $categoryMatrix,
            'lawyers' => $lawyers,
            'defaultForm' => $defaultForm,
        ]);
    }

    public function storeZero(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categoria_id' => ['required', 'exists:categories,id'],
            'subcategoria_id' => ['required', 'exists:subcategories,id'],
            'titulo' => ['required', 'string', 'max:220'],
            'abogado_id' => ['nullable', 'integer', 'exists:users,id'],
            'form' => ['required', 'array'],
            'form.*' => ['nullable', 'string', 'max:500'],
            'external_document' => ['required', File::types(['doc', 'docx'])->max(15 * 1024)],
            'attachments' => ['nullable', 'array'],
            'attachments.*.name' => ['required_with:attachments.*.file', 'string', 'max:180'],
            'attachments.*.file' => ['nullable', File::types(['pdf', 'doc', 'docx', 'jpg', 'png', 'jpeg', 'tiff'])->max(10 * 1024)],
        ]);

        $formPayload = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $validated['form'] ?? []);

        $selectedLawyerId = $validated['abogado_id'] ?? null;

        if ($selectedLawyerId) {
            $lawyerExists = User::role('abogado')->whereKey($selectedLawyerId)->exists();

            if (! $lawyerExists) {
                throw ValidationException::withMessages([
                    'abogado_id' => 'El usuario seleccionado no tiene el rol de abogado.',
                ]);
            }
        }

        $contract = null;
        $storedPaths = [];

        DB::beginTransaction();

        try {
            $code = $this->generateContractCode();

            $contract = new Contract();
            $contract->codigo = $code;
            $contract->titulo = $validated['titulo'];
            $contract->categoria_id = $validated['categoria_id'];
            $contract->subcategoria_id = $validated['subcategoria_id'];
            $contract->creado_por = $request->user()->id;
            $contract->abogado_id = $selectedLawyerId;
            $contract->estado = $selectedLawyerId
                ? Contract::STATUS_ASSIGNED
                : Contract::STATUS_CREATED;
            $contract->save();

            /** @var UploadedFile $uploadedDocument */
            $uploadedDocument = $request->file('external_document');
            $documentPath = $this->documentStorage->storeUploadedDocument(
                $uploadedDocument,
                $code,
                1
            );
            $storedPaths[] = $documentPath;

            $attachmentsMeta = $this->storeAdditionalAttachments($request, $code, 1, $storedPaths);

            $version = ContractVersion::create([
                'contract_id' => $contract->id,
                'template_id' => null,
                'numero_version' => 1,
                'documento' => $documentPath,
                'creado_por' => $request->user()->id,
                'comentarios' => null,
                'estado' => 'borrador',
                'form_payload' => $formPayload,
                'attachments' => $attachmentsMeta,
            ]);

            $historyPath = $this->documentStorage->archiveVersionDocument(
                $contract->codigo,
                $version->numero_version,
                $documentPath
            );

            ContractVersionHistory::create([
                'contract_version_id' => $version->id,
                'document_path' => $historyPath ?? $documentPath,
                'uploaded_by' => $request->user()->id,
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->cleanupStoredFiles($storedPaths);

            throw $exception;
        }

        $this->notifyLawyers($contract);

        return redirect()->route('dashboard')->with('status', 'Contrato '.$contract->codigo.' creado correctamente.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categoria_id' => ['required', 'exists:categories,id'],
            'subcategoria_id' => ['required', 'exists:subcategories,id'],
            'template_id' => ['required', 'exists:templates,id'],
            'titulo' => ['required', 'string', 'max:220'],
            'abogado_id' => ['nullable', 'integer', 'exists:users,id'],
            'borrador' => ['nullable', File::types(['doc', 'docx'])->max(10 * 1024)],
            'template_payload' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.name' => ['required_with:attachments.*.file', 'string', 'max:180'],
            'attachments.*.file' => ['nullable', File::types(['pdf', 'doc', 'docx', 'jpg', 'png', 'jpeg', 'tiff'])->max(10 * 1024)],
        ]);

        $template = Template::findOrFail($validated['template_id']);
        $formPayload = $this->decodePayload($validated['template_payload'] ?? null);
        $renderedHtml = $this->renderTemplate($template->descripcion ?? '', $formPayload);

        $selectedLawyerId = $validated['abogado_id'] ?? null;

        if ($selectedLawyerId) {
            $lawyerExists = User::role('abogado')->whereKey($selectedLawyerId)->exists();

            if (! $lawyerExists) {
                throw ValidationException::withMessages([
                    'abogado_id' => 'El usuario seleccionado no tiene el rol de abogado.',
                ]);
            }
        }

        $contract = null;
        $storedPaths = [];

        DB::beginTransaction();

        try {
            $code = $this->generateContractCode();

            $contract = new Contract();
            $contract->codigo = $code;
            $contract->titulo = $validated['titulo'];
            $contract->categoria_id = $validated['categoria_id'];
            $contract->subcategoria_id = $validated['subcategoria_id'];
            $contract->creado_por = $request->user()->id;
            $contract->abogado_id = $selectedLawyerId;
            $contract->estado = $selectedLawyerId
                ? Contract::STATUS_ASSIGNED
                : Contract::STATUS_CREATED;
            $contract->save();

            [$documentPath, $attachmentsMeta, $storedPaths] = $this->storeContractDocuments(
                $code,
                1,
                $renderedHtml,
                $request,
                $storedPaths
            );

            $version = ContractVersion::create([
                'contract_id' => $contract->id,
                'template_id' => $template->id,
                'numero_version' => 1,
                'documento' => $documentPath,
                'creado_por' => $request->user()->id,
                'comentarios' => null,
                'estado' => 'borrador',
                'form_payload' => $formPayload,
                'attachments' => $attachmentsMeta,
            ]);

            $historyPath = $this->documentStorage->archiveVersionDocument($contract->codigo, $version->numero_version, $documentPath);

            ContractVersionHistory::create([
                'contract_version_id' => $version->id,
                'document_path' => $historyPath ?? $documentPath,
                'uploaded_by' => $request->user()->id,
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->cleanupStoredFiles($storedPaths);

            throw $exception;
        }

        $this->notifyLawyers($contract);

        return redirect()->route('dashboard')->with('status', 'Contrato '.$contract->codigo.' creado correctamente.');
    }

    private function decodePayload(?string $payload): array
    {
        if (! $payload) {
            return [];
        }

        $decoded = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw ValidationException::withMessages([
                'template_payload' => 'El formulario enviado no es un JSON válido.',
            ]);
        }

        return $decoded;
    }

    private function renderTemplate(?string $content, array $values): string
    {
        if (! $content) {
            return '';
        }

        $compiled = $content;
        foreach ($values as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $formatted = $this->formatValue($value);
            $pattern = '/\{\{\s*'.preg_quote((string) $key, '/').'\s*\}\}/u';
            $compiled = preg_replace($pattern, $formatted, $compiled);
        }

        return $compiled;
    }

    private function formatValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if ($value === null) {
            return '';
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                return Carbon::parse($value)
                    ->locale('es')
                    ->translatedFormat('d \d\e F \d\e Y');
            } catch (\Throwable $exception) {
                // Fall through to default casting when parsing fails.
            }
        }

        return (string) $value;
    }

    private function generateContractCode(): string
    {
        $lastCode = Contract::withTrashed()
            ->whereNotNull('codigo')
            ->orderByDesc('codigo')
            ->lockForUpdate()
            ->value('codigo');

        $nextNumber = 1;
        if ($lastCode && preg_match('/LEX-(\d+)/', $lastCode, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return sprintf('LEX-%08d', $nextNumber);
    }

    private function storeContractDocuments(string $contractCode, int $versionNumber, string $renderedHtml, Request $request, array $tracked = []): array
    {
        $documentPath = $this->documentStorage->storeGeneratedDocument(
            $this->wordExporter,
            $contractCode,
            $versionNumber,
            $renderedHtml
        );
        $tracked[] = $documentPath;

        $attachmentsMeta = $this->storeAdditionalAttachments($request, $contractCode, $versionNumber, $tracked);

        return [$documentPath, $attachmentsMeta, $tracked];
    }

    private function storeAdditionalAttachments(Request $request, string $contractCode, int $versionNumber, array &$tracked): array
    {
        $attachmentsMeta = [];

        if ($request->hasFile('borrador')) {
            $contraparte = $this->documentStorage->storeAttachment(
                $request->file('borrador'),
                $contractCode,
                $versionNumber,
                'contraparte',
                'Contrato de la contraparte'
            );
            $attachmentsMeta[] = $contraparte;
            $tracked[] = $contraparte['path'];
        }

        foreach ($request->input('attachments', []) as $index => $attachment) {
            /** @var UploadedFile|null $file */
            $file = $request->file("attachments.$index.file");

            if (! $file instanceof UploadedFile) {
                continue;
            }

            $adjunto = $this->documentStorage->storeAttachment(
                $file,
                $contractCode,
                $versionNumber,
                'adjunto',
                $attachment['name'] ?? $file->getClientOriginalName()
            );
            $attachmentsMeta[] = $adjunto;
            $tracked[] = $adjunto['path'];
        }

        return $attachmentsMeta;
    }

    private function cleanupStoredFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path) {
                Storage::delete($path);
            }
        }
    }

    private function notifyLawyers(Contract $contract): void
    {
        $lawyers = User::role('abogado')->get();

        if ($lawyers->isEmpty()) {
            return;
        }

        Notification::send($lawyers, new NewContractNotification($contract));
    }
}
