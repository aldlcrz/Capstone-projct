<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement("ALTER TABLE `email_verifications` MODIFY `type` VARCHAR(50) NOT NULL DEFAULT 'registration'");
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE `email_verifications` MODIFY `type` ENUM('registration', 'password_reset') NOT NULL DEFAULT 'registration'");
        } catch (\Throwable $e) {}
    }
};
