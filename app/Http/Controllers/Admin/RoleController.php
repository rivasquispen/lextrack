<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->with(['permissions'])
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $permissions = Permission::orderBy('name')->get();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $permissionNames = Permission::pluck('name')->toArray();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($permissionNames)],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return Redirect::route('admin.roles.index')->with('status', 'Rol creado correctamente.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('manage-users');

        $permissionNames = Permission::pluck('name')->toArray();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($permissionNames)],
        ]);

        $role->update([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return Redirect::route('admin.roles.index')->with('status', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('manage-users');

        if ($role->name === 'admin') {
            return back()->withErrors(['role' => 'El rol administrador no puede eliminarse.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'No puedes eliminar un rol asignado a usuarios.']);
        }

        $role->delete();

        return Redirect::route('admin.roles.index')->with('status', 'Rol eliminado correctamente.');
    }
}
