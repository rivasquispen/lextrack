<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_signers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('estado', 30)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamp('firmado_at')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_signers');
    }
};
