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
        // 1. Ensure `status` column on `users` is VARCHAR(50) so it accepts 'pending', 'active', 'suspended', 'frozen', 'rejected'
        try {
            DB::statement("ALTER TABLE `users` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
        } catch (\Throwable $e) {}

        // 2. Normalize any sellers who are unverified but have status = 'active' to status = 'pending'
        try {
            DB::table('users')
                ->where('role', 'seller')
                ->where('isVerified', false)
                ->where('status', 'active')
                ->update(['status' => 'pending']);
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op or revert if necessary
    }
};
