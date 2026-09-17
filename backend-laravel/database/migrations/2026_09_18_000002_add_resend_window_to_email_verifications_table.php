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
        if (!Schema::hasColumn('email_verifications', 'resend_window_started_at')) {
            Schema::table('email_verifications', function (Blueprint $table) {
                $table->timestamp('resend_window_started_at')->nullable()->after('last_sent_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('email_verifications', 'resend_window_started_at')) {
            Schema::table('email_verifications', function (Blueprint $table) {
                $table->dropColumn('resend_window_started_at');
            });
        }
    }
};
