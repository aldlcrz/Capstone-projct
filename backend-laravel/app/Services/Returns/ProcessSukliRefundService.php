<?php

namespace App\Services\Returns;

use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\RefundTransaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProcessSukliRefundService
{
    /**
     * Admin or SuperAdmin processes and records a sukli / overpayment refund transfer.
     */
    public function processSukliRefund(
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
                'admin' => ['Unauthorized. Only Platform Administrators can process sukli refunds.'],
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
            $order = Order::with(['paymentTransactions', 'refundTransactions', 'customer'])
                ->where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Verify that incoming payment is confirmed before disbursing sukli
            $isPaymentVerified = in_array(strtolower($order->paymentStatus ?? ''), ['paid', 'verified'], true)
                || ($order->latestPaymentTransaction && $order->latestPaymentTransaction->status === 'VERIFIED');

            if (!$isPaymentVerified) {
                throw ValidationException::withMessages([
                    'payment' => ['Incoming payment must be verified before processing a sukli refund.'],
                ]);
            }

            // 2. Validate authoritative eligible sukli balance
            $authoritativeSukli = $order->authoritativeSukliAmount();
            if ($authoritativeSukli <= 0) {
                throw ValidationException::withMessages([
                    'refund' => ['This order does not have an overpayment balance eligible for a sukli refund.'],
                ]);
            }

            $remainingSukli = $order->remainingSukliRefundAmount();
            if ($remainingSukli <= 0) {
                throw ValidationException::withMessages([
                    'refund' => ['The sukli overpayment for this order has already been fully refunded.'],
                ]);
            }

            if ($refundAmount <= 0 || round($refundAmount, 2) > round($remainingSukli, 2)) {
                throw ValidationException::withMessages([
                    'refund_amount' => [
                        "The refund amount (₱" . number_format($refundAmount, 2) . ") exceeds the remaining eligible sukli balance of ₱" . number_format($remainingSukli, 2) . "."
                    ],
                ]);
            }

            // 3. Prevent duplicate transfer reference (idempotency)
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

            // 4. Determine destination account
            $rawDest = $destinationAccount;
            if (empty($rawDest)) {
                $rawDest = $order->decrypted_refund_mobile_number ?? ($order->refund_mobile_number ?? ($order->refundMobileNumber ?? ($order->customer?->mobileNumber ?? ($order->customer?->phone ?? null))));
            }

            if (!empty($rawDest)) {
                try {
                    $destAcc = trim(\Illuminate\Support\Facades\Crypt::decryptString($rawDest));
                } catch (\Throwable $e) {
                    $destAcc = trim((string) $rawDest);
                }
            } else {
                $destAcc = '';
            }

            if (empty($destAcc)) {
                throw ValidationException::withMessages([
                    'refund_mobile_number' => ['Customer refund destination account/mobile number is required.'],
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

            // 7. Audit log in OrderStatusHistory
            OrderStatusHistory::create([
                'orderId'        => $order->id,
                'previousStatus' => $order->status,
                'newStatus'      => $order->status,
                'updatedBy'      => $admin->id,
                'userRole'       => $admin->role ?? 'admin',
                'notes'          => "Sukli refund of ₱" . number_format($refundAmount, 2) . " disbursed via " . strtoupper($order->paymentMethod ?? 'GCash') . " to {$maskedDest} (Ref: {$cleanRef}).",
            ]);

            // 8. Notify Customer
            Notification::send(
                $order->customerId,
                'Sukli Refund Processed',
                "Your ₱" . number_format($refundAmount, 2) . " sukli refund for Order #LB-" . strtoupper(substr($order->id, -8)) . " has been processed via " . strtoupper($order->paymentMethod ?? 'GCash') . " to {$maskedDest} (Ref: {$cleanRef}).",
                'order',
                "/orders/{$order->id}",
                'customer'
            );

            return $refundTransaction;
        });
    }
}
