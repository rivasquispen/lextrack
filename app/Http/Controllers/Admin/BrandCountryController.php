<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandCountry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class BrandCountryController extends Controller
{
    public function index(): View
    {
        $brandCountries = BrandCountry::query()
            ->orderBy('name')
            ->get();

        return view('admin.brands.countries.index', compact('brandCountries'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:brand_countries,name'],
        ]);

        BrandCountry::create($data);

        return Redirect::route('admin.brand-countries.index')->with('status', 'País de marca creado correctamente.');
    }

    public function update(Request $request, BrandCountry $brandCountry): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('brand_countries', 'name')->ignore($brandCountry->id)],
        ]);

        $brandCountry->update($data);

        return Redirect::route('admin.brand-countries.index')->with('status', 'País de marca actualizado correctamente.');
    }

    public function destroy(BrandCountry $brandCountry): RedirectResponse
    {
        $this->authorize('manage-users');

        $brandCountry->delete();

        return Redirect::route('admin.brand-countries.index')->with('status', 'País de marca eliminado.');
    }
}

