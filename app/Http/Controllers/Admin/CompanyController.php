<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->withCount('users')
            ->orderBy('nombre')
            ->get();

        return view('admin.companies.index', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'razon_social' => ['nullable', 'string', 'max:220'],
            'ruc' => ['required', 'string', 'max:50', 'unique:companies,ruc'],
        ]);

        Company::create($data);

        return Redirect::route('admin.companies.index')->with('status', 'Empresa registrada correctamente.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'razon_social' => ['nullable', 'string', 'max:220'],
            'ruc' => ['required', 'string', 'max:50', Rule::unique('companies', 'ruc')->ignore($company->id)],
        ]);

        $company->update($data);

        return Redirect::route('admin.companies.index')->with('status', 'Empresa actualizada.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('manage-users');

        if ($company->users()->exists()) {
            return back()->withErrors(['company' => 'No se puede eliminar una empresa con usuarios asociados.']);
        }

        $company->delete();

        return Redirect::route('admin.companies.index')->with('status', 'Empresa eliminada.');
    }
}
