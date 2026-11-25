<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->boolean('is_unlocked')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->index(['version_id', 'position', 'id'], 'approvals_version_position_index');
        });
        // Only pre-existing assignments are enabled. New records retain DEFAULT 0.
        DB::table('approvals')->update(['is_unlocked' => true]);
        DB::table('approvals')->select('version_id')->distinct()->orderBy('version_id')->chunk(200, function ($versions) {
            foreach ($versions as $version) {
                $ids = DB::table('approvals')->where('version_id', $version->version_id)->orderBy('id')->pluck('id');
                foreach ($ids as $index => $id) {
                    DB::table('approvals')->where('id', $id)->update(['position' => $index + 1]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropIndex('approvals_version_position_index');
            $table->dropColumn(['is_unlocked', 'position']);
        });
    }
};
