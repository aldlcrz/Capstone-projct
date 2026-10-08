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
        // 1. Ensure returnrequests table exists and has all workflow columns
        if (!Schema::hasTable('returnrequests')) {
            Schema::create('returnrequests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('orderId')->index();
                $table->uuid('customer_id')->nullable()->index();
                $table->uuid('seller_id')->nullable()->index();
                $table->uuid('order_item_id')->nullable()->index();
                $table->string('return_status', 50)->default('submitted')->index();
                $table->string('physical_return_status', 50)->default('not_required');
                $table->string('refund_status', 50)->default('not_applicable');
                $table->string('dispute_status', 50)->default('none');
                $table->string('resolution_type', 50)->default('refund');
                $table->decimal('requested_amount', 10, 2)->default(0.00);
                $table->decimal('approved_amount', 10, 2)->default(0.00);
                $table->text('reason')->nullable();
                $table->longText('proofImages')->nullable();
                $table->string('status')->default('Pending')->index();
                $table->text('adminComment')->nullable();
                $table->string('seller_assessment', 50)->nullable();
                $table->text('seller_notes')->nullable();
                $table->string('admin_decision', 50)->nullable();
                $table->text('admin_notes')->nullable();
                $table->uuid('resolved_by')->nullable()->index();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('createdAt')->nullable();
                $table->timestamp('updatedAt')->nullable();
            });
        } else {
            Schema::table('returnrequests', function (Blueprint $table) {
                if (!Schema::hasColumn('returnrequests', 'customer_id')) {
                    $table->uuid('customer_id')->nullable()->after('orderId')->index();
                }
                if (!Schema::hasColumn('returnrequests', 'seller_id')) {
                    $table->uuid('seller_id')->nullable()->after('customer_id')->index();
                }
                if (!Schema::hasColumn('returnrequests', 'order_item_id')) {
                    $table->uuid('order_item_id')->nullable()->after('seller_id')->index();
                }
                if (!Schema::hasColumn('returnrequests', 'return_status')) {
                    $table->string('return_status', 50)->default('submitted')->after('order_item_id')->index();
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
                    $table->uuid('resolved_by')->nullable()->after('admin_notes')->index();
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
                $table->uuid('return_request_id')->index();
                $table->uuid('uploaded_by')->index();
                $table->string('type', 50)->default('photo'); // photo, video, unboxing, receipt, transfer_proof
                $table->string('storage_path', 255);
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('checksum', 64)->nullable();
                $table->timestamps();
            });
        }

        // 3. Create refund_transactions table
        if (!Schema::hasTable('refund_transactions')) {
            Schema::create('refund_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('return_request_id')->index();
                $table->uuid('order_id')->index();
                $table->uuid('payment_transaction_id')->nullable()->index();
                
                $table->string('payment_method', 50); // gcash, maya, cash, cod
                $table->string('refund_method', 50);  // gcash, maya, cash, store_credit, replacement
                $table->decimal('refund_amount', 10, 2)->default(0.00);
                
                // Sensitive PII (Encrypted & Masked)
                $table->text('destination_account_encrypted')->nullable();
                $table->string('destination_account_masked', 50)->nullable();
                $table->string('destination_account_name', 150)->nullable();

                $table->string('status', 50)->default('pending')->index(); // pending, processing, transferred, failed, cancelled
                $table->string('transfer_reference', 100)->nullable()->index();
                $table->string('transfer_proof_path', 255)->nullable();
                $table->uuid('processed_by')->nullable()->index();
                $table->timestamp('processed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamps();
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

