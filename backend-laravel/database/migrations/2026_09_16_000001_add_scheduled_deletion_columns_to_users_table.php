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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'deletion_scheduled_at')) {
                $table->timestamp('deletion_scheduled_at')->nullable()->after('deleted_at');
            }
            if (!Schema::hasColumn('users', 'permanent_deletion_at')) {
                $table->timestamp('permanent_deletion_at')->nullable()->after('deletion_scheduled_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'permanent_deletion_at')) {
                $table->dropColumn('permanent_deletion_at');
            }
            if (Schema::hasColumn('users', 'deletion_scheduled_at')) {
                $table->dropColumn('deletion_scheduled_at');
            }
        });
    }
};
