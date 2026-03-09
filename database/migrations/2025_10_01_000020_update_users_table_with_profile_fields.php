<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'nombre');
            $table->string('cargo', 120)->nullable()->after('password');
            $table->foreignId('pais_id')->nullable()->after('cargo')->constrained('countries')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->after('pais_id')->constrained('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('nombre', 'name');
            $table->dropForeign(['pais_id']);
            $table->dropForeign(['empresa_id']);
            $table->dropColumn(['cargo', 'pais_id', 'empresa_id']);
        });
    }
};
