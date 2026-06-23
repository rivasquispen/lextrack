<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_version_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_version_histories', 'is_error')) {
                $table->boolean('is_error')->default(false)->after('is_ready_for_vendor_review');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_version_histories', function (Blueprint $table) {
            if (Schema::hasColumn('contract_version_histories', 'is_error')) {
                $table->dropColumn('is_error');
            }
        });
    }
};
