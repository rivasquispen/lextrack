<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('contract_versions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('firmado')->default(false);
            $table->timestamp('firmado_at')->nullable();
            $table->string('via', 80)->default('docusign');
            $table->string('documento_firmado', 2048)->nullable();
            $table->timestamps();

            $table->unique(['version_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
