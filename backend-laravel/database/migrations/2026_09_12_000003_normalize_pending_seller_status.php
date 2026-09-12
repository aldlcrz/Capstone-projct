<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalize any sellers who are unverified but have status = 'active' to status = 'pending'
        DB::table('users')
            ->where('role', 'seller')
            ->where('isVerified', false)
            ->where('status', 'active')
            ->update(['status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op or revert if necessary
    }
};
