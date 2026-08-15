<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('email_verifications', 'failed_attempts')) {
            Schema::table('email_verifications', function (Blueprint $table) {
                $table->unsignedTinyInteger('failed_attempts')->default(0)->after('resend_count');
            });
        }

        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE email_verifications MODIFY expires_at DATETIME NOT NULL');
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        if (Schema::hasColumn('email_verifications', 'failed_attempts')) {
            Schema::table('email_verifications', function (Blueprint $table) {
                $table->dropColumn('failed_attempts');
            });
        }
    }
};
