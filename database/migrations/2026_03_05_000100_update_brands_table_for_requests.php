<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (! Schema::hasColumn('brands', 'brand_country_id')) {
                $table->foreignId('brand_country_id')
                    ->nullable()
                    ->after('pais_id')
                    ->constrained('brand_countries')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('brands', 'brand_type_id')) {
                $table->foreignId('brand_type_id')
                    ->nullable()
                    ->after('brand_country_id')
                    ->constrained('brand_types')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('brands', 'holder')) {
                $table->string('holder', 180)->nullable()->after('brand_type_id');
            }

            if (! Schema::hasColumn('brands', 'image_path')) {
                $table->string('image_path')->nullable()->after('holder');
            }

            if (! Schema::hasColumn('brands', 'certificate_number')) {
                $table->string('certificate_number', 120)->nullable()->after('image_path');
            }

            if (! Schema::hasColumn('brands', 'process_start_date')) {
                $table->date('process_start_date')->nullable()->after('fecha_registro');
            }

            if (! Schema::hasColumn('brands', 'usage_start_date')) {
                $table->date('usage_start_date')->nullable()->after('process_start_date');
            }

            if (! Schema::hasColumn('brands', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('status')
                    ->constrained('users')
                    ->nullOnDelete();
            }

        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }

            if (Schema::hasColumn('brands', 'usage_start_date')) {
                $table->dropColumn('usage_start_date');
            }

            if (Schema::hasColumn('brands', 'process_start_date')) {
                $table->dropColumn('process_start_date');
            }

            if (Schema::hasColumn('brands', 'certificate_number')) {
                $table->dropColumn('certificate_number');
            }

            if (Schema::hasColumn('brands', 'image_path')) {
                $table->dropColumn('image_path');
            }

            if (Schema::hasColumn('brands', 'holder')) {
                $table->dropColumn('holder');
            }

            if (Schema::hasColumn('brands', 'brand_type_id')) {
                $table->dropConstrainedForeignId('brand_type_id');
            }

            if (Schema::hasColumn('brands', 'brand_country_id')) {
                $table->dropConstrainedForeignId('brand_country_id');
            }

        });
    }
};
