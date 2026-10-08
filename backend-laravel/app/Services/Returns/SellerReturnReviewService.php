<?php

namespace App\Services\Returns;

use App\Models\Notification;
use App\Models\OrderStatusHistory;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SellerReturnReviewService
{
    /**
     * Seller reviews the product issue (assessment only; seller cannot disburse platform online funds).
     */
    public function reviewAssessment(
        ReturnRequest $returnRequest,
        User $seller,
        string $assessment, // 'accepted' | 'rejected'
        ?string $notes = null,
        bool $requiresPhysicalReturn = true
    ): ReturnRequest {
        if ($returnRequest->seller_id !== $seller->id && $returnRequest->order->sellerId !== $seller->id) {
            throw ValidationException::withMessages([
                'seller' => ['Unauthorized. You are not the artisan seller for this order.'],
            ]);
        }

        if (in_array($returnRequest->return_status, ['resolved', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'return_status' => ['This case has already been finalized.'],
            ]);
        }

        return DB::transaction(function () use ($returnRequest, $seller, $assessment, $notes, $requiresPhysicalReturn) {
            $isAccepted = strtolower($assessment) === 'accepted';
            $order = $returnRequest->order;
            $isOnlinePayment = in_array(strtolower($order->paymentMethod ?? ''), ['gcash', 'maya'], true);

            if ($isAccepted) {
                $returnStatus = $requiresPhysicalReturn ? 'awaiting_return' : ($isOnlinePayment ? 'admin_review' : 'approved');
                $physicalStatus = $requiresPhysicalReturn ? 'awaiting_customer' : 'not_required';
                $legacyStatus = 'Approved';
            } else {
                $returnStatus = 'rejected';
                $physicalStatus = 'not_required';
                $legacyStatus = 'Rejected';
            }

            $returnRequest->update([
                'seller_assessment'      => $isAccepted ? 'accepted' : 'rejected',
                'seller_notes'           => $notes,
                'status'                 => $legacyStatus,
                'return_status'          => $returnStatus,
                'physical_return_status' => $physicalStatus,
                'adminComment'           => $notes ?: ($isAccepted ? 'Accepted by artisan.' : 'Declined by artisan.'),
            ]);

            // Track OrderStatusHistory
            try {
                OrderStatusHistory::create([
                    'orderId'        => $order->id,
                    'previousStatus' => $order->status,
                    'newStatus'      => $isAccepted ? 'Return Approved' : 'Return Rejected',
                    'updatedBy'      => $seller->id,
                    'userRole'       => 'seller',
                    'notes'          => 'Artisan assessment: ' . ($isAccepted ? 'Accepted' : 'Declined') . ($notes ? " - {$notes}" : ''),
                ]);
            } catch (\Throwable $e) {}

            // Customer in-app notification
            Notification::send(
                $returnRequest->customer_id ?: $order->customerId,
                $isAccepted ? 'Return Request Accepted' : 'Return Request Declined',
                $isAccepted 
                    ? "The artisan accepted your return request." . ($requiresPhysicalReturn ? " Please ship the item back to the seller." : "")
                    : "The artisan declined your return request: " . ($notes ?: 'No specific reason provided.') . " You can dispute this decision if you believe it is in error.",
                'order',
                route('orders.show', $order->id),
                'customer'
            );

            // Customer email notification
            try {
                $customerUser = User::find($returnRequest->customer_id ?: $order->customerId);
                if ($customerUser && $customerUser->email) {
                    $mailable = new \App\Mail\ReturnRefundStatusMail(
                        $customerUser->name,
                        $order->id,
                        $isAccepted ? 'Approved' : 'Rejected',
                        $notes,
                        'Return'
                    );
                    \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mailable, 'return_refund_update', $customerUser->id, 'Order', $order->id);
                }
            } catch (\Throwable $e) {}

            return $returnRequest->fresh(['evidences', 'order']);
        });
    }

    /**
     * Seller confirms receipt of physically returned item.
     */
    public function confirmPhysicalReceived(
        ReturnRequest $returnRequest,
        User $seller,
        ?string $inspectionNotes = null
    ): ReturnRequest {
        if ($returnRequest->seller_id !== $seller->id && $returnRequest->order->sellerId !== $seller->id) {
            throw ValidationException::withMessages([
                'seller' => ['Unauthorized.'],
            ]);
        }

        $order = $returnRequest->order;
        $isOnlinePayment = in_array(strtolower($order->paymentMethod ?? ''), ['gcash', 'maya'], true);

        return DB::transaction(function () use ($returnRequest, $seller, $inspectionNotes, $order, $isOnlinePayment) {
            $returnRequest->update([
                'physical_return_status' => 'received',
                'return_status'          => $isOnlinePayment ? 'admin_review' : 'return_received',
                'seller_notes'           => $inspectionNotes ? ($returnRequest->seller_notes . ' | Item Received: ' . $inspectionNotes) : $returnRequest->seller_notes,
            ]);

            // Notify Customer
            Notification::send(
                $returnRequest->customer_id ?: $order->customerId,
                'Returned Item Received',
                "The artisan confirmed receipt of the returned item for order #LB-" . strtoupper(substr($order->id, -8)) . ($isOnlinePayment ? ". Admin is now processing the platform refund." : "."),
                'order',
                route('orders.show', $order->id),
                'customer'
            );

            return $returnRequest->fresh(['evidences', 'order']);
        });
    }
}
