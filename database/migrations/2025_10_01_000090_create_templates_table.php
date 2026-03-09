<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 180);
            $table->string('slug', 200)->unique();
            $table->foreignId('categoria_id')->constrained('categories');
            $table->foreignId('subcategoria_id')->nullable()->constrained('subcategories')->nullOnDelete();
            $table->string('path', 2048);
            $table->string('tipo', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
