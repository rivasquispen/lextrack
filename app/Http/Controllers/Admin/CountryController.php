<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class CountryController extends Controller
{
    public function index(): View
    {
        $countries = Country::query()
            ->withCount('users')
            ->orderBy('nombre')
            ->get();

        return view('admin.countries.index', compact('countries'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150', 'unique:countries,nombre'],
        ]);

        Country::create($data);

        return Redirect::route('admin.countries.index')->with('status', 'País agregado correctamente.');
    }

    public function update(Request $request, Country $country): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150', Rule::unique('countries', 'nombre')->ignore($country->id)],
        ]);

        $country->update($data);

        return Redirect::route('admin.countries.index')->with('status', 'País actualizado correctamente.');
    }

    public function destroy(Country $country): RedirectResponse
    {
        $this->authorize('manage-users');

        if ($country->users()->exists()) {
            return back()->withErrors(['country' => 'No se puede eliminar un país con usuarios asignados.']);
        }

        $country->delete();

        return Redirect::route('admin.countries.index')->with('status', 'País eliminado correctamente.');
    }
}
