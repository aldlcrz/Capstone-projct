<?php

namespace App\Services\Returns;

use App\Models\CommissionRecord;
use App\Models\Notification;
use App\Models\Order;
use App\Models\RefundTransaction;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordCashRefundService
{
    /**
     * Record a cash refund performed directly by the seller/artisan (Store Pickup / COD).
     */
    public function recordCashRefund(
        ReturnRequest $returnRequest,
        User $seller,
        float $refundAmount,
        string $resolutionType = 'refund', // 'refund' | 'exchange' | 'replacement'
        ?string $notes = null
    ): RefundTransaction {
        if ($returnRequest->seller_id !== $seller->id && $returnRequest->order->sellerId !== $seller->id) {
            throw ValidationException::withMessages([
                'seller' => ['Unauthorized. You are not the seller for this order.'],
            ]);
        }

        return DB::transaction(function () use ($returnRequest, $seller, $refundAmount, $resolutionType, $notes) {
            $lockedRequest = ReturnRequest::where('id', $returnRequest->id)->lockForUpdate()->firstOrFail();
            $order = Order::where('id', $lockedRequest->orderId)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->return_status === 'resolved') {
                throw ValidationException::withMessages([
                    'return_status' => ['This case has already been resolved.'],
                ]);
            }

            $remainingRefundable = $order->remainingRefundableAmount();
            if ($refundAmount < 0 || $refundAmount > $remainingRefundable) {
                throw ValidationException::withMessages([
                    'refund_amount' => ["Refund amount cannot exceed remaining order balance of ₱{$remainingRefundable}."],
                ]);
            }

            // Create refund transaction for platform audit
            $refundTx = RefundTransaction::create([
                'return_request_id'     => $lockedRequest->id,
                'order_id'              => $order->id,
                'payment_transaction_id'=> null,
                'payment_method'        => 'cash',
                'refund_method'         => $resolutionType === 'refund' ? 'cash' : $resolutionType,
                'refund_amount'         => $refundAmount,
                'status'                => 'transferred',
                'transfer_reference'    => 'CASH-' . strtoupper(substr(uniqid(), -6)),
                'processed_by'          => $seller->id,
                'processed_at'          => now(),
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
                        $rate = (float) ($activeCommission->commissionRate ?: 0.05);
                        $adjustedSales = max(0, (float) $activeCommission->totalSales - $refundAmount);
                        $adjustedComm = round($adjustedSales * $rate, 2);
                        
                        $activeCommission->update([
                            'totalSales'       => $adjustedSales,
                            'commissionAmount' => $adjustedComm,
                            'notes'            => trim(($activeCommission->notes ?? '') . " | Refund adjustment: -₱{$refundAmount}"),
                        ]);
                    }
                } catch (\Throwable $e) {}
            }

            // Notify Customer
            Notification::send(
                $lockedRequest->customer_id ?: $order->customerId,
                'Cash Resolution Completed',
                "The artisan recorded a completed {$resolutionType}" . ($refundAmount > 0 ? " of ₱" . number_format($refundAmount, 2) : "") . " for order #LB-" . strtoupper(substr($order->id, -8)) . ".",
                'order',
                route('orders.show', $order->id),
                'customer'
            );

            return $refundTx;
        });
    }
}
