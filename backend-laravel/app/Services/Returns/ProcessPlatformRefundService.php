<?php

namespace App\Services\Returns;

use App\Models\Notification;
use App\Models\Order;
use App\Models\RefundTransaction;
use App\Models\ReturnRefundEvidence;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessPlatformRefundService
{
    /**
     * Admin/SuperAdmin processes and transfers a centralized platform refund (GCash / Maya).
     */
    public function processPlatformRefund(
        ReturnRequest $returnRequest,
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
                'admin' => ['Unauthorized. Only Platform Administrators can disburse refunds.'],
            ]);
        }

        return DB::transaction(function () use (
            $returnRequest,
            $admin,
            $refundAmount,
            $transferReference,
            $transferProofFile,
            $destinationAccount,
            $destinationName,
            $adminNotes
        ) {
            // Row-level locks to prevent concurrency race conditions
            $lockedRequest = ReturnRequest::where('id', $returnRequest->id)->lockForUpdate()->firstOrFail();
            $order = Order::where('id', $lockedRequest->orderId)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->refund_status === 'transferred' || $lockedRequest->return_status === 'resolved') {
                throw ValidationException::withMessages([
                    'refund' => ['This return/refund case has already been resolved.'],
                ]);
            }

            // Platform Administrators only disburse refunds for platform-held funds (GCash / Maya platform prepayments)
            if ($order->isSellerHeldPayment()) {
                throw ValidationException::withMessages([
                    'order' => ['This order is a Store Pickup, Special Delivery, or seller-collected transaction where funds were received directly by the seller. Platform financial disbursement is not applicable; the seller handles the direct refund.'],
                ]);
            }

            // Check for duplicate transfer reference idempotency
            $cleanRef = trim($transferReference);
            $existingTx = RefundTransaction::where('transfer_reference', $cleanRef)
                ->where('status', 'transferred')
                ->first();
            if ($existingTx) {
                throw ValidationException::withMessages([
                    'transfer_reference' => ['This transfer reference number has already been processed.'],
                ]);
            }

            // Server-authoritative remaining balance check
            $remainingRefundable = $order->remainingRefundableAmount();
            if ($refundAmount <= 0 || $refundAmount > $remainingRefundable) {
                throw ValidationException::withMessages([
                    'refund_amount' => [
                        "The refund amount (₱{$refundAmount}) exceeds the maximum eligible balance of ₱{$remainingRefundable}."
                    ],
                ]);
            }

            // Upload proof screenshot if provided
            $proofPath = null;
            if ($transferProofFile && $transferProofFile->isValid()) {
                $proofPath = $transferProofFile->store('refund_proofs', 'public');
                ReturnRefundEvidence::create([
                    'return_request_id' => $lockedRequest->id,
                    'uploaded_by'       => $admin->id,
                    'type'              => 'transfer_proof',
                    'storage_path'      => $proofPath,
                    'mime_type'         => $transferProofFile->getClientMimeType(),
                    'file_size'         => $transferProofFile->getSize(),
                    'checksum'          => md5_file($transferProofFile->getRealPath()),
                ]);
            }

            // Mask destination phone/account
            $maskedDest = null;
            if ($destinationAccount) {
                $cleanAcc = preg_replace('/\s+/', '', $destinationAccount);
                $len = strlen($cleanAcc);
                $maskedDest = $len > 4 ? substr($cleanAcc, 0, 2) . str_repeat('*', max(2, $len - 6)) . substr($cleanAcc, -4) : $cleanAcc;
            }

            // Link to original PaymentTransaction
            $paymentTransactionId = $order->latestPaymentTransaction?->id;

            // Create financial refund transaction record
            $refundTransaction = RefundTransaction::create([
                'return_request_id'            => $lockedRequest->id,
                'order_id'                     => $order->id,
                'payment_transaction_id'       => $paymentTransactionId,
                'payment_method'               => strtolower($order->paymentMethod ?? 'gcash'),
                'refund_method'                => strtolower($order->paymentMethod ?? 'gcash'),
                'refund_amount'                => $refundAmount,
                'destination_account_encrypted'=> $destinationAccount,
                'destination_account_masked'   => $maskedDest,
                'destination_account_name'     => $destinationName,
                'status'                       => 'transferred',
                'transfer_reference'           => $cleanRef,
                'transfer_proof_path'          => $proofPath,
                'processed_by'                 => $admin->id,
                'processed_at'                 => now(),
            ]);

            // Update canonical ReturnRequest state
            $lockedRequest->update([
                'return_status'    => 'resolved',
                'refund_status'    => 'transferred',
                'approved_amount'  => $refundAmount,
                'admin_decision'   => 'approved',
                'admin_notes'      => $adminNotes,
                'adminComment'     => $adminNotes ?: "Refund of ₱" . number_format($refundAmount, 2) . " disbursed via " . strtoupper($order->paymentMethod ?? 'GCash') . " (Ref: {$cleanRef}).",
                'status'           => 'Resolved',
                'resolved_by'      => $admin->id,
                'resolved_at'      => now(),
            ]);

            // Notify Customer
            Notification::send(
                $lockedRequest->customer_id ?: $order->customerId,
                'Refund Transferred',
                "Your refund of ₱" . number_format($refundAmount, 2) . " for order #LB-" . strtoupper(substr($order->id, -8)) . " has been transferred to your " . strtoupper($order->paymentMethod ?? 'account') . " (Ref: {$cleanRef}).",
                'order',
                route('orders.show', $order->id),
                'customer'
            );

            // Notify Seller
            Notification::create([
                'userId'     => $order->sellerId,
                'title'      => 'Platform Refund Disbursed',
                'message'    => "Platform Admin disbursed a refund of ₱" . number_format($refundAmount, 2) . " for order #LB-OR-" . strtoupper(substr($order->id, -8)),
                'targetRole' => 'seller',
            ]);

            return $refundTransaction;
        });
    }
}
