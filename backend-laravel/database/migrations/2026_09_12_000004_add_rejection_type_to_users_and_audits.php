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
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'rejection_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('rejection_type', 50)->nullable()->after('rejectionReason');
            });
        }

        if (Schema::hasTable('seller_status_audits') && !Schema::hasColumn('seller_status_audits', 'rejection_type')) {
            Schema::table('seller_status_audits', function (Blueprint $table) {
                $table->string('rejection_type', 50)->nullable()->after('reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'rejection_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('rejection_type');
            });
        }

        if (Schema::hasTable('seller_status_audits') && Schema::hasColumn('seller_status_audits', 'rejection_type')) {
            Schema::table('seller_status_audits', function (Blueprint $table) {
                $table->dropColumn('rejection_type');
            });
        }
    }
};
