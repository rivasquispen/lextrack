<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('tipo', 100);
            $table->text('mensaje');
            $table->timestamp('fecha');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
