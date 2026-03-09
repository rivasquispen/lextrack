<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->update([
                'nombre' => DB::raw("COALESCE(nombre, name)"),
                'name' => DB::raw("COALESCE(name, nombre, slug)")
            ]);
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->update([
                'nombre' => DB::raw("COALESCE(nombre, name)"),
                'name' => DB::raw("COALESCE(name, nombre, descripcion)")
            ]);
        }
    }

    public function down(): void
    {
        // No revert: nombre ya existía previamente
    }
};
