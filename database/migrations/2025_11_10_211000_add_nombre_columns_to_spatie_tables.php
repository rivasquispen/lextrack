<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions') && ! Schema::hasColumn('permissions', 'nombre')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('nombre', 150)->nullable()->after('id');
            });
        }

        if (Schema::hasTable('roles') && ! Schema::hasColumn('roles', 'nombre')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('nombre', 120)->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions') && Schema::hasColumn('permissions', 'nombre')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropColumn('nombre');
            });
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'nombre')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('nombre');
            });
        }
    }
};
