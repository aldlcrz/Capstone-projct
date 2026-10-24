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
        if (!Schema::hasTable('notification_dismissals')) {
            Schema::create('notification_dismissals', function (Blueprint $table) {
                $table->id();
                $table->uuid('user_id');
                $table->uuid('notification_id');
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['user_id', 'notification_id'], 'user_notif_unique');
                $table->index(['user_id', 'created_at']);
                $table->index('notification_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_dismissals');
    }
};
