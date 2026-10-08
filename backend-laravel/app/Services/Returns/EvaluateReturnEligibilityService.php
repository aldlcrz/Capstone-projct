<?php

namespace App\Services\Returns;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\User;
use InvalidArgumentException;

class EvaluateReturnEligibilityService
{
    /**
     * Check if a customer can request a return/refund for an order or item.
     */
    public function evaluateEligibility(Order $order, User $customer, ?string $orderItemId = null): array
    {
        if ($order->customerId !== $customer->id) {
            return [
                'eligible' => false,
                'reason'   => 'You can only request returns for your own orders.',
                'code'     => 403,
            ];
        }

        if (!$order->isEligibleForReturnOrRefund()) {
            return [
                'eligible' => false,
                'reason'   => 'Only delivered or claimed orders are eligible for return/refund.',
                'code'     => 400,
            ];
        }

        if ($order->reviews()->exists()) {
            return [
                'eligible' => false,
                'reason'   => 'This order has already been reviewed and finalized.',
                'code'     => 422,
            ];
        }

        // Active request check
        $query = ReturnRequest::where('orderId', $order->id)
            ->whereNotIn('return_status', ['rejected', 'resolved', 'cancelled']);

        if ($orderItemId) {
            $query->where('order_item_id', $orderItemId);
        }

        $activeRequest = $query->first();
        if ($activeRequest) {
            return [
                'eligible' => false,
                'reason'   => 'An active return or refund request already exists for this order.',
                'code'     => 400,
            ];
        }

        $remainingRefundable = $order->remainingRefundableAmount();
        $targetAmount = $remainingRefundable;

        if ($orderItemId) {
            $item = OrderItem::where('id', $orderItemId)->where('orderId', $order->id)->first();
            if (!$item) {
                return [
                    'eligible' => false,
                    'reason'   => 'Selected item does not belong to this order.',
                    'code'     => 422,
                ];
            }
            $itemTotal = (float) ($item->price * $item->quantity);
            $targetAmount = min($itemTotal, $remainingRefundable);
        }

        return [
            'eligible'            => true,
            'remaining_refundable'=> $remainingRefundable,
            'max_allowed_amount'  => $targetAmount,
        ];
    }
}
