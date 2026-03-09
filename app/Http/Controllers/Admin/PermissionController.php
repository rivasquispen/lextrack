<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()->withCount('roles')->orderBy('name')->get();

        return view('admin.permissions.index', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:permissions,name'],
        ]);

        Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        return Redirect::route('admin.permissions.index')->with('status', 'Permiso creado correctamente.');
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $this->authorize('manage-users');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ]);

        $permission->update([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        return Redirect::route('admin.permissions.index')->with('status', 'Permiso actualizado correctamente.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->authorize('manage-users');

        if ($permission->roles()->exists()) {
            return back()->withErrors(['permission' => 'No puedes eliminar un permiso asignado a roles.']);
        }

        $permission->delete();

        return Redirect::route('admin.permissions.index')->with('status', 'Permiso eliminado correctamente.');
    }
}
