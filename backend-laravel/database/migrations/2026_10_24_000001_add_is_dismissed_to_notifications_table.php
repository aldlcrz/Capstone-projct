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
        if (Schema::hasTable('notifications') && !Schema::hasColumn('notifications', 'is_dismissed')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->boolean('is_dismissed')->default(false)->after('isRead');
                $table->index(['userId', 'is_dismissed']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'is_dismissed')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex(['userId', 'is_dismissed']);
                $table->dropColumn('is_dismissed');
            });
        }
    }
};
