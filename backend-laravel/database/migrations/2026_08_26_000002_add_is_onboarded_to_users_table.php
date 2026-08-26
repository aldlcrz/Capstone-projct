<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'is_onboarded')) {
                    $table->boolean('is_onboarded')->default(false)->after('isVerified');
                }
            });

            // Mark existing users created more than 10 minutes ago as onboarded so only fresh accounts are prompted
            try {
                DB::table('users')
                    ->whereNull('is_onboarded')
                    ->orWhere('is_onboarded', false)
                    ->update(['is_onboarded' => true]);
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'is_onboarded')) {
                    $table->dropColumn('is_onboarded');
                }
            });
        }
    }
};
