<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class BrandTypeController extends Controller
{
    public function index(): View
    {
        $brandTypes = BrandType::query()
            ->orderBy('name')
            ->get();

        return view('admin.brands.types.index', compact('brandTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:brand_types,name'],
        ]);

        BrandType::create($data);

        return Redirect::route('admin.brand-types.index')->with('status', 'Tipo de marca registrado correctamente.');
    }

    public function update(Request $request, BrandType $brandType): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('brand_types', 'name')->ignore($brandType->id)],
        ]);

        $brandType->update($data);

        return Redirect::route('admin.brand-types.index')->with('status', 'Tipo de marca actualizado correctamente.');
    }

    public function destroy(BrandType $brandType): RedirectResponse
    {
        $this->authorize('manage-users');

        $brandType->delete();

        return Redirect::route('admin.brand-types.index')->with('status', 'Tipo de marca eliminado.');
    }
}

