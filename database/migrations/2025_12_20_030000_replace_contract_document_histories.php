<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $legacyRows = collect();

        if (Schema::hasTable('contract_document_histories')) {
            $legacyRows = DB::table('contract_document_histories')->get();
            Schema::drop('contract_document_histories');
        }

        Schema::create('contract_version_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_version_id')->constrained('contract_versions')->cascadeOnDelete();
            $table->string('document_path', 2048);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        if ($legacyRows->isNotEmpty()) {
            $payload = $legacyRows->map(function ($row) {
                return [
                    'contract_version_id' => $row->contract_version_id,
                    'document_path' => $row->document_path,
                    'uploaded_by' => $row->uploaded_by,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            })->all();

            DB::table('contract_version_histories')->insert($payload);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_version_histories');

        Schema::create('contract_document_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_version_id')->constrained('contract_versions')->cascadeOnDelete();
            $table->string('document_path', 2048);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });
    }
};
