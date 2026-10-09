<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('seller_payouts')) {
            Schema::create('seller_payouts', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->uuid('order_id')->nullable();
                $table->uuid('seller_id');
                $table->uuid('processed_by')->nullable();

                $table->decimal('gross_sales', 12, 2)->default(0.00);
                $table->decimal('shipping_amount', 12, 2)->default(0.00);
                $table->decimal('discount_amount', 12, 2)->default(0.00);
                $table->decimal('commission_deducted', 12, 2)->default(0.00);
                $table->decimal('net_settlement_amount', 12, 2)->default(0.00);

                // Settlement lifecycle: PENDING_ELIGIBILITY, AVAILABLE_FOR_PAYOUT, PAYOUT_PROCESSING, PAID, PAYOUT_FAILED, ON_HOLD
                $table->string('status', 50)->default('PENDING_ELIGIBILITY');

                $table->string('payout_method', 50)->nullable(); // GCash, Maya, Bank Transfer
                $table->text('payout_destination_account')->nullable(); // Account number or phone
                $table->string('payout_destination_name', 150)->nullable();
                $table->string('transfer_reference', 100)->nullable();
                $table->string('transfer_proof_path', 255)->nullable();
                $table->text('hold_reason')->nullable();
                $table->text('admin_notes')->nullable();

                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['seller_id', 'status']);
                $table->index(['order_id']);
                $table->index(['transfer_reference']);
            });

            try {
                Schema::table('seller_payouts', function (Blueprint $table) {
                    $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('seller_payouts', function (Blueprint $table) {
                    $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('seller_payouts', function (Blueprint $table) {
                    $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
                });
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_payouts');
    }
};
