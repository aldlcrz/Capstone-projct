<?php

namespace App\Services\Returns;

use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\RefundTransaction;
use App\Models\User;
use App\Services\Messaging\LumbarongSystemMessageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProcessCancellationRefundService
{
    /**
     * Admin or SuperAdmin processes and records a full cancellation refund transfer.
     */
    public function processCancellationRefund(
        string $orderId,
        User $admin,
        float $refundAmount,
        string $transferReference,
        ?UploadedFile $transferProofFile = null,
        ?string $destinationAccount = null,
        ?string $destinationName = null,
        ?string $adminNotes = null
    ): RefundTransaction {
        if (!in_array($admin->role, ['admin', 'superadmin'], true)) {
            throw ValidationException::withMessages([
                'admin' => ['Unauthorized. Only Platform Administrators can process cancellation refunds.'],
            ]);
        }

        return DB::transaction(function () use (
            $orderId,
            $admin,
            $refundAmount,
            $transferReference,
            $transferProofFile,
            $destinationAccount,
            $destinationName,
            $adminNotes
        ) {
            // Row-level lock to prevent concurrent double-refunding race conditions
            /** @var Order $order */
            $order = Order::with(['paymentTransactions', 'refundTransactions', 'customer'])
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Verify that order is cancelled or cancellation pending
            if (!in_array(strtolower($order->status), ['cancelled', 'cancellation_pending'], true) && empty($order->cancellationReason)) {
                throw ValidationException::withMessages([
                    'order' => ['Refunds in this tab are only applicable to cancelled or cancellation-pending orders.'],
                ]);
            }

            // Platform Administrators only disburse refunds for platform-held funds (GCash / Maya platform prepayments)
            if ($order->isSellerHeldPayment()) {
                throw ValidationException::withMessages([
                    'order' => ['This order is a Store Pickup, Special Delivery, or seller-collected transaction where funds were received directly by the seller. Platform financial disbursement is not applicable; the seller handles the direct refund.'],
                ]);
            }

            // 2. Prevent duplicate transfer reference (idempotency)
            $cleanRef = trim($transferReference);
            if (empty($cleanRef)) {
                throw ValidationException::withMessages([
                    'transfer_reference' => ['A valid outgoing transaction reference number is required.'],
                ]);
            }

            $duplicateTx = RefundTransaction::where('transfer_reference', $cleanRef)
                ->whereIn('status', ['transferred', 'completed', 'refunded'])
                ->first();

            if ($duplicateTx) {
                throw ValidationException::withMessages([
                    'transfer_reference' => ['This transfer reference number has already been recorded in the refund ledger.'],
                ]);
            }

            // 3. Validate authoritative eligible refund balance
            $remainingRefund = $order->remainingCancellationRefundAmount();
            if ($remainingRefund <= 0) {
                throw ValidationException::withMessages([
                    'refund' => ['This cancelled order has no outstanding refundable balance or has already been fully refunded.'],
                ]);
            }

            if ($refundAmount <= 0 || round($refundAmount, 2) > round($remainingRefund, 2)) {
                throw ValidationException::withMessages([
                    'refund_amount' => [
                        "The refund amount (₱" . number_format($refundAmount, 2) . ") exceeds the remaining eligible refund balance of ₱" . number_format($remainingRefund, 2) . "."
                    ],
                ]);
            }

            // 4. Determine destination account
            $rawDest = $destinationAccount;
            if (empty($rawDest)) {
                $rawDest = $order->decrypted_refund_mobile_number ?? ($order->refund_mobile_number ?? ($order->refundMobileNumber ?? ($order->customer?->mobileNumber ?? ($order->customer?->phone ?? null))));
            }

            if (!empty($rawDest)) {
                try {
                    $destAcc = trim(Crypt::decryptString($rawDest));
                } catch (\Throwable $e) {
                    $destAcc = trim((string) $rawDest);
                }
            } else {
                $destAcc = '';
            }

            if (empty($destAcc)) {
                throw ValidationException::withMessages([
                    'destination_account'  => ['Customer refund destination account/mobile number is required.'],
                ]);
            }

            $cleanAcc = preg_replace('/\s+/', '', $destAcc);
            $len = strlen($cleanAcc);
            $maskedDest = $len > 4
                ? substr($cleanAcc, 0, 4) . str_repeat('*', max(2, $len - 7)) . substr($cleanAcc, -3)
                : $cleanAcc;

            $destName = trim($destinationName ?: ($order->customer?->name ?: 'Customer'));

            // 5. Store proof file if uploaded
            $proofPath = null;
            if ($transferProofFile && $transferProofFile->isValid()) {
                $proofPath = $transferProofFile->store('refund_proofs', 'public');
            }

            // 6. Create financial RefundTransaction
            $refundTransaction = RefundTransaction::create([
                'id'                            => (string) Str::uuid(),
                'order_id'                      => $order->id,
                'payment_transaction_id'        => $order->latestPaymentTransaction?->id,
                'payment_method'                => strtolower($order->paymentMethod ?? 'gcash'),
                'refund_method'                 => strtolower($order->paymentMethod ?? 'gcash'),
                'refund_amount'                 => $refundAmount,
                'destination_account_encrypted' => $destAcc,
                'destination_account_masked'    => $maskedDest,
                'destination_account_name'      => $destName,
                'status'                        => 'transferred',
                'transfer_reference'            => $cleanRef,
                'transfer_proof_path'           => $proofPath,
                'processed_by'                  => $admin->id,
                'processed_at'                  => now(),
                'notes'                         => $adminNotes,
            ]);

            // Update order payment status if fully refunded
            $newRemaining = $remainingRefund - $refundAmount;
            if ($newRemaining <= 0.009) {
                $order->paymentStatus = 'refunded';
                $order->save();
            }

            // 7. Audit log in OrderStatusHistory
            OrderStatusHistory::create([
                'orderId'        => $order->id,
                'previousStatus' => $order->status,
                'newStatus'      => $order->status,
                'updatedBy'      => $admin->id,
                'userRole'       => $admin->role ?? 'admin',
                'notes'          => "Cancellation refund of ₱" . number_format($refundAmount, 2) . " disbursed via " . strtoupper($order->paymentMethod ?? 'GCash') . " to {$maskedDest} (Ref: {$cleanRef}).",
            ]);

            // 8. Notify Customer
            Notification::send(
                $order->customerId,
                'Cancellation Refund Disbursed',
                "Your cancellation refund of ₱" . number_format($refundAmount, 2) . " for Order #LB-" . strtoupper(substr($order->id, -8)) . " has been processed via " . strtoupper($order->paymentMethod ?? 'GCash') . " to {$maskedDest} (Ref: {$cleanRef}).",
                'order',
                "/orders/{$order->id}",
                'customer'
            );

            // 9. Send Official LumBarong Inbox Message with Attached Refund Proof
            LumbarongSystemMessageService::sendCancellationRefundCompletedMessage(
                $order,
                $refundTransaction,
                $admin
            );

            return $refundTransaction;
        });
    }
}
