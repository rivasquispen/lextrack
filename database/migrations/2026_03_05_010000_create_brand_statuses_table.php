<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('color', 40)->default('slate');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->text('description')->nullable();
            $table->timestampsTz();
        });

        Schema::table('brands', function (Blueprint $table) {
            if (! Schema::hasColumn('brands', 'brand_status_id')) {
                $table->foreignId('brand_status_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('brand_statuses')
                    ->nullOnDelete();
            }
        });

        $now = now();
        $statuses = [
            ['name' => 'En trámite', 'slug' => 'en_tramite', 'color' => 'sky', 'sort_order' => 10, 'is_default' => true],
            ['name' => 'En oposición', 'slug' => 'en_oposicion', 'color' => 'orange', 'sort_order' => 20],
            ['name' => 'Bajo examen formal', 'slug' => 'bajo_examen_formal', 'color' => 'amber', 'sort_order' => 30],
            ['name' => 'Bajo examen de fondo', 'slug' => 'bajo_examen_fondo', 'color' => 'amber', 'sort_order' => 40],
            ['name' => 'Aceptada en registro', 'slug' => 'aceptada_registro', 'color' => 'emerald', 'sort_order' => 50],
            ['name' => 'Aceptada en pago de aprobación', 'slug' => 'aceptada_pago_aprobacion', 'color' => 'emerald', 'sort_order' => 60],
            ['name' => 'Aprobada', 'slug' => 'aprobada', 'color' => 'emerald', 'sort_order' => 70],
            ['name' => 'Rechazada', 'slug' => 'rechazada', 'color' => 'rose', 'sort_order' => 80],
        ];

        foreach ($statuses as $status) {
            DB::table('brand_statuses')->insert(array_merge($status, [
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        foreach ($statuses as $status) {
            $statusId = DB::table('brand_statuses')->where('slug', $status['slug'])->value('id');
            if ($statusId) {
                DB::table('brands')
                    ->where('status', $status['slug'])
                    ->update(['brand_status_id' => $statusId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'brand_status_id')) {
                $table->dropConstrainedForeignId('brand_status_id');
            }
        });

        Schema::dropIfExists('brand_statuses');
    }
};

