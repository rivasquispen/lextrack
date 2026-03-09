<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_versions', 'sharepoint_link')) {
                $table->string('sharepoint_link', 2048)->nullable()->after('file_document');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_versions', function (Blueprint $table) {
            if (Schema::hasColumn('contract_versions', 'sharepoint_link')) {
                $table->dropColumn('sharepoint_link');
            }
        });
    }
};
