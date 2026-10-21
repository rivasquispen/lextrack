<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->index();
            $table->unsignedBigInteger('version_id');
            $table->unsignedBigInteger('approval_id');
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('recipient_id');
            $table->unsignedInteger('pending_days');
            $table->string('status', 20);
            $table->string('failure_type', 255)->nullable();
            $table->json('snapshot');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['approval_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_reminders');
    }
};
