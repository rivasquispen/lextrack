<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'document')) {
                $table->string('document', 2048)->nullable()->after('estado');
            }

            if (! Schema::hasColumn('contracts', 'document_signed')) {
                $table->string('document_signed', 2048)->nullable()->after('document');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'document_signed')) {
                $table->dropColumn('document_signed');
            }

            if (Schema::hasColumn('contracts', 'document')) {
                $table->dropColumn('document');
            }
        });
    }
};
