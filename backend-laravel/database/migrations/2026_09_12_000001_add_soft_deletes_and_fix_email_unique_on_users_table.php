<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add deleted_at soft delete column if not present
        if (!Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes()->after('updatedAt');
            });
        }

        // 2. Drop legacy single email unique index and create composite unique index on (email, deleted_at)
        // In MySQL, composite index allows multiple NULL deleted_at records to not conflict with non-null deleted_at records
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_unique');
            });
        } catch (\Throwable $e) {
            // Index might have different name or was already dropped
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['email', 'deleted_at'], 'users_email_deleted_at_unique');
            });
        } catch (\Throwable $e) {
            // Index already exists
        }

        // 3. Normalize legacy 'blocked' seller/user status to 'suspended'
        DB::table('users')
            ->where('status', 'blocked')
            ->update(['status' => 'suspended']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_deleted_at_unique');
                $table->unique('email', 'users_email_unique');
                $table->dropSoftDeletes();
            });
        } catch (\Throwable $e) {
            // Fallback
        }
    }
};
