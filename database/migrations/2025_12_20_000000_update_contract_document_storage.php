<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_versions', function (Blueprint $table) {
            if (Schema::hasColumn('contract_versions', 'file_init')) {
                $table->dropColumn('file_init');
            }

            if (Schema::hasColumn('contract_versions', 'file_track')) {
                $table->dropColumn('file_track');
            }

            if (Schema::hasColumn('contract_versions', 'file_document')) {
                $table->dropColumn('file_document');
            }

            if (Schema::hasColumn('contract_versions', 'html_snapshot')) {
                $table->dropColumn('html_snapshot');
            }

            if (Schema::hasColumn('contract_versions', 'sharepoint_link')) {
                $table->dropColumn('sharepoint_link');
            }
        });

        Schema::create('contract_document_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_version_id')->constrained('contract_versions')->cascadeOnDelete();
            $table->string('document_path', 2048);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_document_histories');

        Schema::table('contract_versions', function (Blueprint $table) {
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

            if (! Schema::hasColumn('contract_versions', 'sharepoint_link')) {
                $table->string('sharepoint_link', 2048)->nullable()->after('html_snapshot');
            }
        });
    }
};
