<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('nombre', 180);
            $table->timestamps();

            $table->unique(['company_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
