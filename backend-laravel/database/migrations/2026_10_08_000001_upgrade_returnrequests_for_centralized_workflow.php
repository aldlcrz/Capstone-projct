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
        // 1. Upgrade returnrequests table
        if (Schema::hasTable('returnrequests')) {
            Schema::table('returnrequests', function (Blueprint $table) {
                if (!Schema::hasColumn('returnrequests', 'customer_id')) {
                    $table->uuid('customer_id')->nullable()->after('orderId');
                }
                if (!Schema::hasColumn('returnrequests', 'seller_id')) {
                    $table->uuid('seller_id')->nullable()->after('customer_id');
                }
                if (!Schema::hasColumn('returnrequests', 'order_item_id')) {
                    $table->uuid('order_item_id')->nullable()->after('seller_id');
                }
                if (!Schema::hasColumn('returnrequests', 'return_status')) {
                    $table->string('return_status', 50)->default('submitted')->after('order_item_id');
                }
                if (!Schema::hasColumn('returnrequests', 'physical_return_status')) {
                    $table->string('physical_return_status', 50)->default('not_required')->after('return_status');
                }
                if (!Schema::hasColumn('returnrequests', 'refund_status')) {
                    $table->string('refund_status', 50)->default('not_applicable')->after('physical_return_status');
                }
                if (!Schema::hasColumn('returnrequests', 'dispute_status')) {
                    $table->string('dispute_status', 50)->default('none')->after('refund_status');
                }
                if (!Schema::hasColumn('returnrequests', 'resolution_type')) {
                    $table->string('resolution_type', 50)->default('refund')->after('dispute_status');
                }
                if (!Schema::hasColumn('returnrequests', 'requested_amount')) {
                    $table->decimal('requested_amount', 10, 2)->default(0.00)->after('resolution_type');
                }
                if (!Schema::hasColumn('returnrequests', 'approved_amount')) {
                    $table->decimal('approved_amount', 10, 2)->default(0.00)->after('requested_amount');
                }
                if (!Schema::hasColumn('returnrequests', 'seller_assessment')) {
                    $table->string('seller_assessment', 50)->nullable()->after('approved_amount');
                }
                if (!Schema::hasColumn('returnrequests', 'seller_notes')) {
                    $table->text('seller_notes')->nullable()->after('seller_assessment');
                }
                if (!Schema::hasColumn('returnrequests', 'admin_decision')) {
                    $table->string('admin_decision', 50)->nullable()->after('seller_notes');
                }
                if (!Schema::hasColumn('returnrequests', 'admin_notes')) {
                    $table->text('admin_notes')->nullable()->after('admin_decision');
                }
                if (!Schema::hasColumn('returnrequests', 'resolved_by')) {
                    $table->uuid('resolved_by')->nullable()->after('admin_notes');
                }
                if (!Schema::hasColumn('returnrequests', 'resolved_at')) {
                    $table->timestamp('resolved_at')->nullable()->after('resolved_by');
                }
            });
        }

        // 2. Create return_refund_evidences table
        if (!Schema::hasTable('return_refund_evidences')) {
            Schema::create('return_refund_evidences', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('return_request_id');
                $table->uuid('uploaded_by');
                $table->string('type', 50)->default('photo'); // photo, video, unboxing, receipt, transfer_proof
                $table->string('storage_path', 255);
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('checksum', 64)->nullable();
                $table->timestamps();

                $table->foreign('return_request_id')->references('id')->on('returnrequests')->onDelete('cascade');
                $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
                $table->index('return_request_id');
            });
        }

        // 3. Create refund_transactions table
        if (!Schema::hasTable('refund_transactions')) {
            Schema::create('refund_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('return_request_id');
                $table->uuid('order_id');
                $table->uuid('payment_transaction_id')->nullable();
                
                $table->string('payment_method', 50); // gcash, maya, cash, cod
                $table->string('refund_method', 50);  // gcash, maya, cash, store_credit, replacement
                $table->decimal('refund_amount', 10, 2)->default(0.00);
                
                // Sensitive PII (Encrypted & Masked)
                $table->text('destination_account_encrypted')->nullable();
                $table->string('destination_account_masked', 50)->nullable();
                $table->string('destination_account_name', 150)->nullable();

                $table->string('status', 50)->default('pending'); // pending, processing, transferred, failed, cancelled
                $table->string('transfer_reference', 100)->nullable();
                $table->string('transfer_proof_path', 255)->nullable();
                $table->uuid('processed_by')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamps();

                $table->foreign('return_request_id')->references('id')->on('returnrequests')->onDelete('cascade');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->foreign('payment_transaction_id')->references('id')->on('payment_transactions')->nullOnDelete();
                $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();

                $table->index('order_id');
                $table->index('return_request_id');
                $table->index('status');
                $table->index('transfer_reference');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_transactions');
        Schema::dropIfExists('return_refund_evidences');
    }
};
