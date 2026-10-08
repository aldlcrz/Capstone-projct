<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'total_verified_payments')) {
                    $table->decimal('total_verified_payments', 10, 2)->nullable()->after('totalAmount');
                }
                if (!Schema::hasColumn('orders', 'overpayment_amount')) {
                    $table->decimal('overpayment_amount', 10, 2)->default(0.00)->after('total_verified_payments');
                }
                if (!Schema::hasColumn('orders', 'refund_mobile_number')) {
                    $table->string('refund_mobile_number', 32)->nullable()->after('overpayment_amount');
                }
            });
        }

        if (!Schema::hasTable('receipt_verifications_cache')) {
            Schema::create('receipt_verifications_cache', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('image_hash', 64)->unique();
                $table->string('wallet_type', 32)->default('GCash');
                $table->string('detected_reference', 64)->nullable()->index();
                $table->decimal('detected_amount', 10, 2)->nullable();
                $table->json('verification_data');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('orders', 'refund_mobile_number')) {
                    $columns[] = 'refund_mobile_number';
                }
                if (Schema::hasColumn('orders', 'overpayment_amount')) {
                    $columns[] = 'overpayment_amount';
                }
                if (Schema::hasColumn('orders', 'total_verified_payments')) {
                    $columns[] = 'total_verified_payments';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::dropIfExists('receipt_verifications_cache');
    }
};
