<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandStatusController extends Controller
{
    public function index(): View
    {
        $statuses = BrandStatus::ordered()
            ->withCount('brands')
            ->get();

        return view('admin.brands.statuses.index', compact('statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash', 'unique:brand_statuses,slug'],
            'color' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'description' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $status = BrandStatus::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'], '_'),
            'color' => $data['color'] ?? 'slate',
            'sort_order' => $data['sort_order'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        if ($status->is_default) {
            BrandStatus::where('id', '!=', $status->id)->update(['is_default' => false]);
        }

        return Redirect::route('admin.brand-statuses.index')->with('status', 'Estado creado correctamente.');
    }

    public function update(Request $request, BrandStatus $brandStatus): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash', Rule::unique('brand_statuses', 'slug')->ignore($brandStatus->id)],
            'color' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'description' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $brandStatus->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'], '_'),
            'color' => $data['color'] ?? 'slate',
            'sort_order' => $data['sort_order'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        if ($brandStatus->is_default) {
            BrandStatus::where('id', '!=', $brandStatus->id)->update(['is_default' => false]);
        }

        return Redirect::route('admin.brand-statuses.index')->with('status', 'Estado actualizado.');
    }

    public function destroy(BrandStatus $brandStatus): RedirectResponse
    {
        $this->authorize('manage-users');

        if ($brandStatus->brands()->exists()) {
            return back()->withErrors(['brandStatus' => 'No se puede eliminar un estado con marcas asociadas.']);
        }

        if ($brandStatus->is_default) {
            return back()->withErrors(['brandStatus' => 'No se puede eliminar el estado por defecto.']);
        }

        $brandStatus->delete();

        return Redirect::route('admin.brand-statuses.index')->with('status', 'Estado eliminado.');
    }
}
