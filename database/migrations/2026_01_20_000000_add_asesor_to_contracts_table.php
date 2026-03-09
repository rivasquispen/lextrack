<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'asesor_id')) {
                $table->foreignId('asesor_id')->nullable()->after('abogado_id');
                $table->foreign('asesor_id')->references('id')->on('users');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'asesor_id')) {
                $table->dropForeign(['asesor_id']);
                $table->dropColumn('asesor_id');
            }
        });
    }
};
