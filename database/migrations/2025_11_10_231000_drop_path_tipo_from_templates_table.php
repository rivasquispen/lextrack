<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            if (Schema::hasColumn('templates', 'path')) {
                $table->dropColumn('path');
            }
            if (Schema::hasColumn('templates', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            if (! Schema::hasColumn('templates', 'path')) {
                $table->string('path', 2048)->nullable();
            }
            if (! Schema::hasColumn('templates', 'tipo')) {
                $table->string('tipo', 80)->default('docx');
            }
        });
    }
};
