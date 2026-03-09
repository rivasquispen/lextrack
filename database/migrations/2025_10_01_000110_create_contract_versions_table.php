<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->unsignedInteger('numero_version');
            $table->string('documento', 2048);
            $table->foreignId('creado_por')->constrained('users');
            $table->text('comentarios')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['contract_id', 'numero_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_versions');
    }
};
