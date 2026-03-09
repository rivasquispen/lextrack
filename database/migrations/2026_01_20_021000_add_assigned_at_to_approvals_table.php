<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('approvals', 'assigned_at')) {
            Schema::table('approvals', function (Blueprint $table) {
                $table->timestamp('assigned_at')->nullable()->after('aprobado_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('approvals', 'assigned_at')) {
            Schema::table('approvals', function (Blueprint $table) {
                $table->dropColumn('assigned_at');
            });
        }
    }
};
