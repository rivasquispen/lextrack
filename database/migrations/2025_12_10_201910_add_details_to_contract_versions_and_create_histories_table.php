<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_versions', 'template_id')) {
                $table->foreignId('template_id')->nullable()->after('contract_id')->constrained('templates')->nullOnDelete();
            }

            if (! Schema::hasColumn('contract_versions', 'form_payload')) {
                $table->json('form_payload')->nullable()->after('documento');
            }

            if (! Schema::hasColumn('contract_versions', 'estado')) {
                $table->string('estado', 40)->default('borrador')->after('comentarios');
            }
        });

        if (! Schema::hasTable('contract_version_histories')) {
            Schema::create('contract_version_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_version_id')->constrained('contract_versions')->cascadeOnDelete();
                $table->unsignedInteger('revision');
                $table->string('documento', 2048);
                $table->json('form_payload')->nullable();
                $table->json('attachments')->nullable();
                $table->text('comentarios')->nullable();
                $table->boolean('es_actual')->default(true);
                $table->foreignId('creado_por')->constrained('users');
                $table->timestamps();

                $table->unique(['contract_version_id', 'revision']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('contract_versions', function (Blueprint $table) {
            if (Schema::hasColumn('contract_versions', 'estado')) {
                $table->dropColumn('estado');
            }

            if (Schema::hasColumn('contract_versions', 'form_payload')) {
                $table->dropColumn('form_payload');
            }

            if (Schema::hasColumn('contract_versions', 'template_id')) {
                $table->dropConstrainedForeignId('template_id');
            }
        });

        Schema::dropIfExists('contract_version_histories');
    }
};
