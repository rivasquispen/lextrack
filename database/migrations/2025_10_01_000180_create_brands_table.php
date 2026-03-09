<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 180);
            $table->string('numero_registro', 150)->unique();
            $table->foreignId('pais_id')->constrained('countries');
            $table->date('fecha_registro')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('status', 80)->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
