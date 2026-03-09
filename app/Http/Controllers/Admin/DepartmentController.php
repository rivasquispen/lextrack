<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::with('company:id,nombre')
            ->withCount('users')
            ->orderBy('nombre')
            ->get();

        $companies = Company::orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.departments.index', compact('departments', 'companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        Department::create($data);

        return Redirect::route('admin.departments.index')->with('status', 'Departamento creado correctamente.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        $department->update($data);

        return Redirect::route('admin.departments.index')->with('status', 'Departamento actualizado.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('manage-users');

        if ($department->users()->exists()) {
            return back()->withErrors(['department' => 'No se puede eliminar un departamento con usuarios asociados.']);
        }

        $department->delete();

        return Redirect::route('admin.departments.index')->with('status', 'Departamento eliminado.');
    }
}
