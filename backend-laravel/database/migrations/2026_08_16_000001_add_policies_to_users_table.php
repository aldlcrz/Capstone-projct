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
            if (!Schema::hasColumn('users', 'cancellation_policy')) {
                $table->text('cancellation_policy')->nullable()->after('shopDescription');
            }
            if (!Schema::hasColumn('users', 'refund_policy')) {
                $table->text('refund_policy')->nullable()->after('cancellation_policy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cancellation_policy')) {
                $table->dropColumn('cancellation_policy');
            }
            if (Schema::hasColumn('users', 'refund_policy')) {
                $table->dropColumn('refund_policy');
            }
        });
    }
};
