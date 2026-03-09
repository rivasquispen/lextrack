<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (! Schema::hasColumn('permissions', 'name')) {
                    $table->string('name', 150)->default('')->after('slug');
                }
                if (! Schema::hasColumn('permissions', 'guard_name')) {
                    $table->string('guard_name', 50)->default('web')->after('name');
                }
            });

            DB::table('permissions')->update([
                'name' => DB::raw("CASE WHEN name = '' OR name IS NULL THEN COALESCE(slug, nombre, '') ELSE name END"),
                'guard_name' => DB::raw("CASE WHEN guard_name = '' OR guard_name IS NULL THEN 'web' ELSE guard_name END"),
            ]);

            Schema::table('permissions', function (Blueprint $table) {
                $table->unique(['name', 'guard_name']);
            });
        }

        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (! Schema::hasColumn('roles', 'name')) {
                    $table->string('name', 120)->default('')->after('descripcion');
                }
                if (! Schema::hasColumn('roles', 'guard_name')) {
                    $table->string('guard_name', 50)->default('web')->after('name');
                }
            });

            DB::table('roles')->update([
                'name' => DB::raw("CASE WHEN name = '' OR name IS NULL THEN COALESCE(descripcion, nombre, '') ELSE name END"),
                'guard_name' => DB::raw("CASE WHEN guard_name = '' OR guard_name IS NULL THEN 'web' ELSE guard_name END"),
            ]);

            Schema::table('roles', function (Blueprint $table) {
                $table->unique(['name', 'guard_name']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (Schema::hasColumn('permissions', 'guard_name')) {
                    $table->dropColumn('guard_name');
                }
                if (Schema::hasColumn('permissions', 'name')) {
                    $table->dropColumn('name');
                }
            });
        }

        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (Schema::hasColumn('roles', 'guard_name')) {
                    $table->dropColumn('guard_name');
                }
                if (Schema::hasColumn('roles', 'name')) {
                    $table->dropColumn('name');
                }
            });
        }
    }
};
