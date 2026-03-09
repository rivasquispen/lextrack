<?php

  use Illuminate\Database\Migrations\Migration;
  use Illuminate\Database\Schema\Blueprint;
  use Illuminate\Support\Facades\Schema;

  return new class extends Migration
  {
      public function up(): void
      {
          Schema::create('brand_classes', function (Blueprint $table) {
              $table->id();
              $table->tinyInteger('number')->unsigned();
              $table->string('description', 255);
              $table->timestampsTz();
              $table->unique('number');
          });

          Schema::create('brands', function (Blueprint $table) {
              $table->id();
              $table->string('name', 180);
              $table->foreignId('brand_country_id')->constrained('brand_countries');
              $table->foreignId('brand_type_id')->constrained('brand_types');
              $table->string('holder', 180);
              $table->string('image_path')->nullable();
              $table->string('certificate_number', 120)->nullable();
              $table->string('status', 40)->default('solicitado');
              $table->date('registration_date')->nullable();
              $table->date('process_start_date')->nullable();
              $table->date('usage_start_date')->nullable();
              $table->date('expiration_date')->nullable();
              $table->timestampsTz();
          });

          Schema::create('brand_class_relations', function (Blueprint $table) {
              $table->foreignId('brand_id')
                  ->constrained('brands')
                  ->cascadeOnDelete();
              $table->foreignId('brand_class_id')
                  ->constrained('brand_classes');
              $table->primary(['brand_id', 'brand_class_id']);
          });
      }

      public function down(): void
      {
          Schema::dropIfExists('brand_class_relations');
          Schema::dropIfExists('brands');
          Schema::dropIfExists('brand_classes');
      }
  };