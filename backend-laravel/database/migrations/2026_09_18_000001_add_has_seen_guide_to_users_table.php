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
        if (!Schema::hasColumn('users', 'has_seen_guide')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('has_seen_guide')->default(false)->after('is_onboarded');
            });

            // Mark existing users as having seen the guide so they are not interrupted
            DB::table('users')->update(['has_seen_guide' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'has_seen_guide')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('has_seen_guide');
            });
        }
    }
};
