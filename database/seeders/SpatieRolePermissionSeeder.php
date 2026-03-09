<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SpatieRolePermissionSeeder extends Seeder
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
                []
            );

            $role->syncPermissions($rolePermissions);
        }

        // La asignación de roles se realizará durante el primer inicio de sesión vía Microsoft.
    }
}
