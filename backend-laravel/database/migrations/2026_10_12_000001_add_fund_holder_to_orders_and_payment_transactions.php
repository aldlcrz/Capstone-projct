<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'fund_holder')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('fund_holder', 32)->default('seller')->after('paymentMethod')->index();
            });
        }

        if (Schema::hasTable('payment_transactions') && !Schema::hasColumn('payment_transactions', 'fund_holder')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->string('fund_holder', 32)->default('seller')->after('status')->index();
                $table->string('recipient_id', 64)->nullable()->after('fund_holder');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'fund_holder')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('fund_holder');
            });
        }

        if (Schema::hasTable('payment_transactions')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('payment_transactions', 'recipient_id')) {
                    $table->dropColumn('recipient_id');
                }
                if (Schema::hasColumn('payment_transactions', 'fund_holder')) {
                    $table->dropColumn('fund_holder');
                }
            });
        }
    }
};
