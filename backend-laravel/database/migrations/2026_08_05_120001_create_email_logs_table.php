<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('recipient_email')->index();
            $table->string('user_id')->nullable()->index();
            $table->string('notification_type')->index();
            $table->string('subject');
            $table->enum('delivery_status', ['sent', 'failed'])->default('sent');
            $table->text('error_message')->nullable();
            $table->string('related_type')->nullable();
            $table->string('related_id')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
