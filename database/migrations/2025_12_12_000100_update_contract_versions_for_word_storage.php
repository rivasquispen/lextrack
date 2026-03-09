<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_versions', 'attachments')) {
                $table->json('attachments')->nullable()->after('form_payload');
            }

            if (! Schema::hasColumn('contract_versions', 'file_init')) {
                $table->string('file_init', 2048)->nullable()->after('attachments');
            }

            if (! Schema::hasColumn('contract_versions', 'file_track')) {
                $table->string('file_track', 2048)->nullable()->after('file_init');
            }

            if (! Schema::hasColumn('contract_versions', 'file_document')) {
                $table->string('file_document', 2048)->nullable()->after('file_track');
            }

            if (! Schema::hasColumn('contract_versions', 'html_snapshot')) {
                $table->longText('html_snapshot')->nullable()->after('file_document');
            }
        });

        Schema::dropIfExists('contract_version_histories');
    }

    public function down(): void
    {
        Schema::table('contract_versions', function (Blueprint $table) {
            if (Schema::hasColumn('contract_versions', 'html_snapshot')) {
                $table->dropColumn('html_snapshot');
            }

            if (Schema::hasColumn('contract_versions', 'file_document')) {
                $table->dropColumn('file_document');
            }

            if (Schema::hasColumn('contract_versions', 'file_track')) {
                $table->dropColumn('file_track');
            }

            if (Schema::hasColumn('contract_versions', 'file_init')) {
                $table->dropColumn('file_init');
            }

            if (Schema::hasColumn('contract_versions', 'attachments')) {
                $table->dropColumn('attachments');
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
};
