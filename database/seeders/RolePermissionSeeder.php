<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage-users',
            'manage-categories',
            'view-admin-dashboard',
        ];

        foreach ($permissions as $permission) {
            $slug = Str::slug($permission);

            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                [
                    'nombre' => $permission,
                    'name' => $permission,
                    'guard_name' => 'web',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $roles = [
            'admin' => ['manage-users', 'manage-categories', 'view-admin-dashboard'],
            'abogado' => ['view-admin-dashboard'],
            'aprobador' => ['view-admin-dashboard'],
            'colaborador' => [],
            'auditor' => [],
            'marcas' => [],
            'asesor' => [],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['nombre' => $roleName, 'descripcion' => $roleName]
            );
            $role->givePermissionTo($rolePermissions);
        }

        $adminEmail = 'nrivasq@medifarma.com.pe';
        $adminUser = User::where('email', $adminEmail)->first() ?? User::find(1);

        if ($adminUser) {
            if (isset($adminUser->active) && ! $adminUser->active) {
                $adminUser->active = true;
                $adminUser->save();
            }

            if (! $adminUser->hasRole('admin')) {
                $adminUser->syncRoles(['admin']);
            }
        }
    }
}
