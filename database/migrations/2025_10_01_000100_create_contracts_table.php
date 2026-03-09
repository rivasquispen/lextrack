<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 220);
            $table->foreignId('categoria_id')->constrained('categories');
            $table->foreignId('subcategoria_id')->nullable()->constrained('subcategories')->nullOnDelete();
            $table->foreignId('creado_por')->constrained('users');
            $table->string('estado', 40)->default('iniciado');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
