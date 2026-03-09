<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandClass;
use App\Models\BrandCountry;
use App\Models\BrandStatus;
use App\Models\BrandType;
use App\Models\User;
use App\Notifications\BrandCreatedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:180'],
            'status' => ['nullable', 'string', Rule::exists('brand_statuses', 'slug')],
            'brand_type_id' => ['nullable', 'integer', 'exists:brand_types,id'],
            'brand_country_id' => ['nullable', 'integer', 'exists:brand_countries,id'],
        ]);

        $brands = Brand::query()
            ->with([
                'brandCountry:id,name',
                'brandType:id,name',
                'classes:id,number,description',
                'creator:id,nombre,email',
                'statusDefinition:id,name,color,slug',
            ])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('holder', 'like', "%{$search}%")
                        ->orWhere('certificate_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['brand_type_id'] ?? null, fn ($query, $brandTypeId) => $query->where('brand_type_id', $brandTypeId))
            ->when($filters['brand_country_id'] ?? null, fn ($query, $brandCountryId) => $query->where('brand_country_id', $brandCountryId))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $statusSummary = BrandStatus::ordered()
            ->withCount('brands')
            ->get()
            ->map(fn ($status) => [
                'label' => $status->name,
                'count' => $status->brands_count,
            ])
            ->values();

        $unmappedCount = Brand::query()->whereNull('brand_status_id')->count();
        if ($unmappedCount > 0) {
            $statusSummary->push([
                'label' => 'Sin estado',
                'count' => $unmappedCount,
            ]);
        }

        $statusOptions = Brand::statusOptions();
        $brandCountries = BrandCountry::orderBy('name')->get(['id', 'name']);
        $brandTypes = BrandType::orderBy('name')->get(['id', 'name']);

        return view('brands.index', [
            'brands' => $brands,
            'statusSummary' => $statusSummary,
            'statusOptions' => $statusOptions,
            'brandCountries' => $brandCountries,
            'brandTypes' => $brandTypes,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $brandCountries = BrandCountry::orderBy('name')->get(['id', 'name']);
        $brandTypes = BrandType::orderBy('name')->get(['id', 'name']);
        $brandClasses = BrandClass::orderBy('number')->get(['id', 'number', 'description']);

        return view('brands.create', compact('brandCountries', 'brandTypes', 'brandClasses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'brand_country_id' => ['required', 'exists:brand_countries,id'],
            'brand_type_id' => ['required', 'exists:brand_types,id'],
            'holder' => ['required', 'string', 'max:180'],
            'certificate_number' => ['nullable', 'string', 'max:120'],
            'registration_date' => ['nullable', 'date'],
            'process_start_date' => ['nullable', 'date'],
            'usage_start_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:registration_date'],
            'brand_class_ids' => ['array'],
            'brand_class_ids.*' => ['integer', 'exists:brand_classes,id'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $defaultStatus = BrandStatus::default() ?? BrandStatus::ordered()->first();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('brands', 'public');
        }

        $brand = Brand::create([
            'name' => $data['name'],
            'brand_country_id' => $data['brand_country_id'],
            'brand_type_id' => $data['brand_type_id'],
            'holder' => $data['holder'],
            'certificate_number' => $data['certificate_number'] ?? null,
            'registration_date' => $data['registration_date'] ?? null,
            'process_start_date' => $data['process_start_date'] ?? null,
            'usage_start_date' => $data['usage_start_date'] ?? null,
            'expiration_date' => $data['expiration_date'] ?? null,
            'status' => $defaultStatus->slug ?? Brand::STATUS_DEFAULT,
            'brand_status_id' => $defaultStatus->id ?? null,
            'image_path' => $imagePath,
            'created_by' => $request->user()->id,
        ]);

        $brand->classes()->sync($data['brand_class_ids'] ?? []);

        $recipients = User::role('marcas')->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new BrandCreatedNotification($brand));
        }

        return redirect()->route('brands.index')->with('status', 'Solicitud registrada correctamente y notificada al equipo de marcas.');
    }

    public function updateStatus(Request $request, Brand $brand): RedirectResponse
    {
        if (! $brand->created_by) {
            $brand->forceFill(['created_by' => $request->user()->id])->save();
        }

        abort_unless($brand->created_by === $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::exists('brand_statuses', 'slug')],
        ]);

        $status = BrandStatus::where('slug', $data['status'])->firstOrFail();

        $brand->update([
            'status' => $status->slug,
            'brand_status_id' => $status->id,
        ]);

        return back()->with('status', 'Estado actualizado correctamente.');
    }

    public function show(Brand $brand): View
    {
        $brand->load([
            'brandCountry',
            'brandType',
            'classes',
            'creator',
            'statusDefinition',
            'comments' => fn ($query) => $query->with('user')->latest()->take(50),
        ]);

        $brand->setRelation('comments', $brand->comments->sortBy('created_at'));

        $statusOptions = Brand::statusOptions();

        return view('brands.show', compact('brand', 'statusOptions'));
    }

    public function storeComment(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $brand->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Comentario agregado correctamente.');
    }

    public function export(Request $request)
    {
        $filters = $request->only(['search', 'status', 'brand_type_id', 'brand_country_id']);

        $brands = Brand::query()
            ->with(['brandCountry', 'brandType', 'statusDefinition'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('holder', 'like', "%{$search}%")
                        ->orWhere('certificate_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['brand_type_id'] ?? null, fn ($query, $brandTypeId) => $query->where('brand_type_id', $brandTypeId))
            ->when($filters['brand_country_id'] ?? null, fn ($query, $brandCountryId) => $query->where('brand_country_id', $brandCountryId))
            ->latest('created_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="marcas-'.now()->format('Ymd_His').'.csv"',
        ];

        $columns = ['Nombre', 'Titular', 'País', 'Tipo', 'Estado', 'N° certificado', 'Registro', 'Vencimiento'];

        $callback = static function () use ($brands, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($brands as $brand) {
                fputcsv($handle, [
                    $brand->display_name,
                    $brand->display_holder,
                    $brand->display_country,
                    $brand->display_type,
                    $brand->display_status,
                    $brand->display_registration_number,
                    $brand->display_registration_date,
                    $brand->display_expiration_date,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'marcas-'.now()->format('Ymd_His').'.csv', $headers);
    }
}
