<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('templates', 'descripcion')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->text('descripcion')->nullable()->after('nombre');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('templates', 'descripcion')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->dropColumn('descripcion');
            });
        }
    }
};
