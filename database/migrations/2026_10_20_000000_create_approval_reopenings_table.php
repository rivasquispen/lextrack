<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_reopenings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->index();
            $table->unsignedBigInteger('version_id')->index();
            $table->unsignedBigInteger('approval_id')->index();
            $table->unsignedBigInteger('approver_id');
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('lawyer_id');
            $table->unsignedInteger('version_number');
            $table->string('previous_status', 30);
            $table->timestamp('previous_approved_at');
            $table->text('reason');
            $table->text('coordination_reference');
            $table->json('snapshot');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_reopenings');
    }
};
