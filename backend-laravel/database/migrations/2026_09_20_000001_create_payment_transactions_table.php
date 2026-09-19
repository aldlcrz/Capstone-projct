<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('order_id')->nullable()->index();
                $table->uuid('customer_id')->index();
                $table->uuid('seller_id')->nullable()->index();
                $table->string('reference_number', 64)->index();
                $table->string('active_reference', 64)->nullable()->unique();
                $table->string('wallet_type', 32)->default('GCash');
                $table->decimal('expected_amount', 10, 2)->default(0);
                $table->decimal('detected_amount', 10, 2)->nullable();
                $table->decimal('amount_confidence', 4, 2)->nullable();
                $table->decimal('reference_confidence', 4, 2)->nullable();
                $table->decimal('confidence', 4, 2)->nullable();
                $table->string('status', 32)->default('UNVERIFIED')->index(); // UNVERIFIED, VERIFIED, REJECTED, VOID
                $table->string('verification_tier', 32)->default('REVIEW');  // PASS, REVIEW, REJECT
                $table->string('receipt_path', 255)->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Safe backfill from existing orders
        $this->backfillHistoricalOrders();
    }

    private function backfillHistoricalOrders(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('payment_transactions')) {
            return;
        }

        $existingCount = DB::table('payment_transactions')->count();
        if ($existingCount > 0) {
            return;
        }

        $orders = DB::table('orders')
            ->whereNotNull('paymentReference')
            ->where('paymentReference', '!=', '')
            ->orderBy('createdAt', 'asc')
            ->get();

        $claimedActiveRefs = [];

        foreach ($orders as $order) {
            $ref = trim((string) $order->paymentReference);
            if (!$ref) continue;

            $pStatus = strtolower(trim((string) ($order->paymentStatus ?? '')));
            $oStatus = trim((string) ($order->status ?? ''));

            $isVerified = in_array($pStatus, ['verified', 'paid', 'confirmed'], true) 
                || in_array($oStatus, ['To Ship', 'In Transit', 'Delivered', 'Completed'], true);

            $isRejected = in_array($pStatus, ['rejected', 'payment rejected', 'payment_rejected', 'failed'], true)
                || $oStatus === 'Cancelled';

            if ($isVerified) {
                $status = 'VERIFIED';
                $verifiedAt = $order->updatedAt ?? now();
            } elseif ($isRejected) {
                $status = 'REJECTED';
                $verifiedAt = null;
            } else {
                $status = 'UNVERIFIED';
                $verifiedAt = null;
            }

            // active_reference is set only for active claims (UNVERIFIED or VERIFIED)
            // and must be unique. If duplicate legacy records exist, only oldest gets active_reference.
            $activeRef = null;
            if (in_array($status, ['UNVERIFIED', 'VERIFIED'], true)) {
                if (!isset($claimedActiveRefs[$ref])) {
                    $activeRef = $ref;
                    $claimedActiveRefs[$ref] = true;
                }
            }

            $walletType = (str_contains(strtolower((string)$order->paymentMethod), 'maya')) ? 'Maya' : 'GCash';

            DB::table('payment_transactions')->insert([
                'id' => (string) Str::uuid(),
                'order_id' => $order->id,
                'customer_id' => $order->customerId ?? (string) Str::uuid(),
                'seller_id' => $order->sellerId ?? null,
                'reference_number' => $ref,
                'active_reference' => $activeRef,
                'wallet_type' => $walletType,
                'expected_amount' => (float) ($order->totalAmount ?? 0),
                'detected_amount' => (float) ($order->totalAmount ?? 0),
                'amount_confidence' => 1.00,
                'reference_confidence' => 1.00,
                'confidence' => 1.00,
                'status' => $status,
                'verification_tier' => $isVerified ? 'PASS' : ($isRejected ? 'REJECT' : 'REVIEW'),
                'receipt_path' => $order->paymentProof ?? null,
                'verified_at' => $verifiedAt,
                'notes' => 'Backfilled from historical order.',
                'created_at' => $order->createdAt ?? now(),
                'updated_at' => $order->updatedAt ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
