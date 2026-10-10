<?php

namespace App\Services\Returns;

use App\Models\CommissionRecord;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\RefundTransaction;
use App\Models\ReturnRefundEvidence;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\Financial\FinancialLedgerService;
use App\Services\Messaging\LumbarongSystemMessageService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordCashRefundService
{
    /**
     * Record a cash refund performed directly by the seller/artisan for a return request (Store Pickup / COD).
     */
    public function recordCashRefund(
        ReturnRequest $returnRequest,
        User $seller,
        float $refundAmount,
        string $resolutionType = 'refund', // 'refund' | 'exchange' | 'replacement'
        ?string $notes = null,
        ?UploadedFile $proofFile = null,
        ?string $refundDate = null
    ): RefundTransaction {
        $order = $returnRequest->order ?: Order::findOrFail($returnRequest->orderId);

        if ($returnRequest->seller_id !== $seller->id && $order->sellerId !== $seller->id) {
            throw ValidationException::withMessages([
                'seller' => ['Unauthorized. You are not the seller for this order.'],
            ]);
        }

        // Sellers CANNOT process refunds for platform-held online payments (prepaid via LumBarong official platform QR)
        if ($order->isPlatformHeldPayment()) {
            throw ValidationException::withMessages([
                'payment_method' => ['Unauthorized. Sellers cannot process refunds for platform-held online payments (GCash/Maya). Online refunds must be processed by Super Admin.'],
            ]);
        }

        return DB::transaction(function () use ($returnRequest, $order, $seller, $refundAmount, $resolutionType, $notes, $proofFile, $refundDate) {
            $lockedRequest = ReturnRequest::where('id', $returnRequest->id)->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::where('id', $lockedRequest->orderId)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->return_status === 'resolved') {
                throw ValidationException::withMessages([
                    'return_status' => ['This case has already been resolved.'],
                ]);
            }

            $remainingRefundable = $lockedOrder->remainingRefundableAmount();
            if ($refundAmount < 0 || $refundAmount > $remainingRefundable) {
                throw ValidationException::withMessages([
                    'refund_amount' => ["Refund amount cannot exceed remaining order balance of ₱{$remainingRefundable}."],
                ]);
            }

            $proofPath = null;
            if ($proofFile && $proofFile->isValid()) {
                $proofPath = $proofFile->store('refund_proofs', 'public');
                try {
                    ReturnRefundEvidence::create([
                        'return_request_id' => $lockedRequest->id,
                        'uploaded_by'       => $seller->id,
                        'type'              => 'transfer_proof',
                        'storage_path'      => $proofPath,
                        'mime_type'         => $proofFile->getClientMimeType(),
                        'file_size'         => $proofFile->getSize(),
                        'checksum'          => md5_file($proofFile->getRealPath()),
                    ]);
                } catch (\Throwable $e) {}
            }

            // Create refund transaction for platform audit
            $refundTx = RefundTransaction::create([
                'return_request_id'     => $lockedRequest->id,
                'order_id'              => $lockedOrder->id,
                'payment_transaction_id'=> null,
                'payment_method'        => 'cash',
                'refund_method'         => $resolutionType === 'refund' ? 'cash' : $resolutionType,
                'refund_amount'         => $refundAmount,
                'status'                => 'transferred',
                'transfer_reference'    => 'CASH-' . strtoupper(substr(uniqid(), -6)),
                'transfer_proof_path'   => $proofPath,
                'notes'                 => $notes ?: "Cash {$resolutionType} completed by artisan",
                'processed_by'          => $seller->id,
                'processed_at'          => $refundDate ? Carbon::parse($refundDate) : now(),
            ]);

            // Update ReturnRequest
            $lockedRequest->update([
                'return_status'          => 'resolved',
                'physical_return_status' => 'completed',
                'refund_status'          => 'not_applicable',
                'resolution_type'        => $resolutionType,
                'approved_amount'        => $refundAmount,
                'seller_notes'           => $notes,
                'adminComment'           => "Cash {$resolutionType} of ₱" . number_format($refundAmount, 2) . " completed by artisan.",
                'status'                 => 'Resolved',
                'resolved_by'            => $seller->id,
                'resolved_at'            => now(),
            ]);

            // Adjust commission records non-destructively for pending seller periods if applicable
            if ($refundAmount > 0) {
                try {
                    $activeCommission = CommissionRecord::where('sellerId', $seller->id)
                        ->whereIn('status', ['unpaid', 'Pending', 'pending', 'verification_pending'])
                        ->orderBy('dueDate', 'desc')
                        ->first();

                    if ($activeCommission) {
                        $rate = (float) ($activeCommission->commissionRate ?? \App\Services\Financial\FinancialLedgerService::getCommissionRate());
                        $rateMultiplier = $rate > 1.0 ? ($rate / 100.0) : $rate;
                        $adjustedSales = max(0, (float) $activeCommission->totalSales - $refundAmount);
                        $adjustedComm = round($adjustedSales * $rateMultiplier, 2);
                        
                        $activeCommission->update([
                            'totalSales'       => $adjustedSales,
                            'commissionAmount' => $adjustedComm,
                            'notes'            => trim(($activeCommission->notes ?? '') . " | Refund adjustment: -₱{$refundAmount}"),
                        ]);
                    }
                } catch (\Throwable $e) {}
            }

            // Send official LumBarong inbox message
            LumbarongSystemMessageService::sendCashRefundCompletedMessage($lockedOrder, $refundTx, $seller, $notes ?: "Cash {$resolutionType} completed directly with customer.");

            // Notify Customer via general notification
            Notification::send(
                $lockedRequest->customer_id ?: $lockedOrder->customerId,
                'Cash Resolution Completed',
                "The artisan recorded a completed {$resolutionType}" . ($refundAmount > 0 ? " of ₱" . number_format($refundAmount, 2) : "") . " for order #LB-" . strtoupper(substr($lockedOrder->id, -8)) . ".",
                'order',
                route('orders.show', $lockedOrder->id),
                'customer'
            );

            return $refundTx;
        });
    }

    /**
     * Record a cash refund performed directly by the seller/artisan for an order (Store Pickup cash / Special Delivery cash / COD).
     */
    public function recordOrderCashRefund(
        Order $order,
        User $seller,
        float $refundAmount,
        ?string $reason = null,
        ?UploadedFile $proofFile = null,
        ?string $notes = null,
        ?string $refundDate = null,
        ?string $paymentMethod = 'cash'
    ): RefundTransaction {
        if ($order->sellerId !== $seller->id) {
            throw ValidationException::withMessages([
                'seller' => ['Unauthorized. You are not the seller for this order.'],
            ]);
        }

        // Sellers CANNOT process refunds for platform-held online payments (prepaid via LumBarong official platform QR)
        if ($order->isPlatformHeldPayment()) {
            throw ValidationException::withMessages([
                'payment_method' => ['Unauthorized. Sellers cannot process refunds for platform-held online payments (GCash/Maya). Online refunds must be processed by Super Admin.'],
            ]);
        }

        return DB::transaction(function () use ($order, $seller, $refundAmount, $reason, $proofFile, $notes, $refundDate, $paymentMethod) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            $remainingRefundable = $lockedOrder->remainingRefundableAmount();
            if ($refundAmount <= 0 || $refundAmount > $remainingRefundable) {
                throw ValidationException::withMessages([
                    'refund_amount' => ["Refund amount (₱{$refundAmount}) cannot exceed remaining order balance of ₱{$remainingRefundable}."],
                ]);
            }

            $proofPath = null;
            if ($proofFile && $proofFile->isValid()) {
                $proofPath = $proofFile->store('refund_proofs', 'public');
            }

            $cleanMethod = strtolower(trim((string)($paymentMethod ?: 'cash')));
            $prefix = strtoupper(substr($cleanMethod, 0, 5));

            $refundTx = RefundTransaction::create([
                'order_id'              => $lockedOrder->id,
                'return_request_id'     => null,
                'payment_transaction_id'=> null,
                'payment_method'        => $cleanMethod,
                'refund_method'         => $cleanMethod,
                'refund_amount'         => $refundAmount,
                'status'                => 'transferred',
                'transfer_reference'    => $prefix . '-' . strtoupper(substr(uniqid(), -6)),
                'transfer_proof_path'   => $proofPath,
                'notes'                 => $notes ?: ($reason ? "Direct refund ({$cleanMethod}): {$reason}" : "Direct refund recorded by artisan"),
                'processed_by'          => $seller->id,
                'processed_at'          => $refundDate ? Carbon::parse($refundDate) : now(),
            ]);

            if ($lockedOrder->remainingCancellationRefundAmount() <= 0) {
                $lockedOrder->paymentStatus = 'Refunded';
            }
            $lockedOrder->save();

            OrderStatusHistory::create([
                'orderId'        => $lockedOrder->id,
                'previousStatus' => $lockedOrder->status,
                'newStatus'      => $lockedOrder->status,
                'updatedBy'      => $seller->id,
                'userRole'       => 'seller',
                'notes'          => "Cash refund of ₱" . number_format($refundAmount, 2) . " recorded by artisan ({$seller->name}). Reason: " . ($reason ?: 'N/A'),
            ]);

            // Adjust commission records non-destructively for pending seller periods if applicable
            if ($refundAmount > 0) {
                try {
                    $activeCommission = CommissionRecord::where('sellerId', $seller->id)
                        ->whereIn('status', ['unpaid', 'Pending', 'pending', 'verification_pending'])
                        ->orderBy('dueDate', 'desc')
                        ->first();

                    if ($activeCommission) {
                        $rate = (float) ($activeCommission->commissionRate ?? \App\Services\Financial\FinancialLedgerService::getCommissionRate());
                        $rateMultiplier = $rate > 1.0 ? ($rate / 100.0) : $rate;
                        $adjustedSales = max(0, (float) $activeCommission->totalSales - $refundAmount);
                        $adjustedComm = round($adjustedSales * $rateMultiplier, 2);

                        $activeCommission->update([
                            'totalSales'       => $adjustedSales,
                            'commissionAmount' => $adjustedComm,
                            'notes'            => trim(($activeCommission->notes ?? '') . " | Refund adjustment: -₱{$refundAmount}"),
                        ]);
                    }
                } catch (\Throwable $e) {}
            }

            // Send official LumBarong customer inbox message
            LumbarongSystemMessageService::sendCashRefundCompletedMessage($lockedOrder, $refundTx, $seller, $reason);

            // General notification
            Notification::send(
                $lockedOrder->customerId,
                'Cash Refund Recorded',
                "The artisan recorded a completed cash refund of ₱" . number_format($refundAmount, 2) . " for order #LB-" . strtoupper(substr($lockedOrder->id, -8)) . ".",
                'order',
                route('orders.show', $lockedOrder->id),
                'customer'
            );

            return $refundTx;
        });
    }
}
