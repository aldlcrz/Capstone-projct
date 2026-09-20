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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'registration_expires_at')) {
                    $table->timestamp('registration_expires_at')->nullable()->after('isVerified');
                }
            });

            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->index(['role', 'status', 'registration_expires_at'], 'users_seller_lifecycle_idx');
                } catch (\Throwable $e) {
                    // Index already exists or DB doesn't support composite
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropIndex('users_seller_lifecycle_idx');
                } catch (\Throwable $e) {}

                if (Schema::hasColumn('users', 'registration_expires_at')) {
                    $table->dropColumn('registration_expires_at');
                }
            });
        }
    }
};
