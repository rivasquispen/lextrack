<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('contract_versions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('estado', 30)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamp('aprobado_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['version_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
