<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('active')->default(false)->after('remember_token');
            });
        }

        if (Schema::hasColumn('users', 'active')) {
            DB::table('users')->update(['active' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }
    }
};
