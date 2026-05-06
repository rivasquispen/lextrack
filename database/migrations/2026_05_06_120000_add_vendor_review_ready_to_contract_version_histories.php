<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_version_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_version_histories', 'is_ready_for_vendor_review')) {
                $table->boolean('is_ready_for_vendor_review')->default(false)->after('uploaded_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_version_histories', function (Blueprint $table) {
            if (Schema::hasColumn('contract_version_histories', 'is_ready_for_vendor_review')) {
                $table->dropColumn('is_ready_for_vendor_review');
            }
        });
    }
};
