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
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'commission_rate')) {
                    $table->decimal('commission_rate', 5, 2)->nullable()->after('totalAmount');
                }
                if (!Schema::hasColumn('orders', 'commission_amount')) {
                    $table->decimal('commission_amount', 12, 2)->nullable()->after('commission_rate');
                }
            });
        }

        if (Schema::hasTable('seller_payouts')) {
            Schema::table('seller_payouts', function (Blueprint $table) {
                if (!Schema::hasColumn('seller_payouts', 'commission_rate')) {
                    $table->decimal('commission_rate', 5, 2)->nullable()->after('commission_deducted');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'commission_amount')) {
                    $table->dropColumn('commission_amount');
                }
                if (Schema::hasColumn('orders', 'commission_rate')) {
                    $table->dropColumn('commission_rate');
                }
            });
        }

        if (Schema::hasTable('seller_payouts')) {
            Schema::table('seller_payouts', function (Blueprint $table) {
                if (Schema::hasColumn('seller_payouts', 'commission_rate')) {
                    $table->dropColumn('commission_rate');
                }
            });
        }
    }
};
