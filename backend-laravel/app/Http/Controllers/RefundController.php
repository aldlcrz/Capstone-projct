<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefundController extends Controller
{
    /**
     * Create a new refund request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'orderId' => 'required|exists:orders,id',
            'orderItemId' => 'required|exists:order_items,id',
            'reason' => 'required|string|in:Damaged Item,Wrong Size,Other',
            'message' => 'nullable|string|max:1000',
            'videoProof' => 'required|file|mimes:mp4,mov,avi,webm,mkv,jpg,jpeg,png,webp|max:51200',
        ]);

        $customerId = Auth::id();
        $order = Order::where('id', $request->orderId)->where('customerId', $customerId)->firstOrFail();

        if (!$order->isEligibleForReturnOrRefund()) {
            return response()->json(['message' => 'Refunds are only available after receiving the item.'], 400);
        }

        // Constrain the requested item directly to the authenticated order (Problem 30)
        $orderItem = OrderItem::where('id', $request->orderItemId)
            ->where('orderId', $order->id)
            ->first();

        if (!$orderItem) {
            return response()->json(['message' => 'The selected item does not belong to the specified order.'], 422);
        }

        $existingRequest = RefundRequest::where('order_item_id', $request->orderItemId)
            ->where('customer_id', $customerId)
            ->first();

        if ($existingRequest) {
            return response()->json(['message' => 'A refund request already exists for this item.'], 400);
        }

        $videoPath = $request->file('videoProof')->store('refunds', 'public');

        $refundRequest = RefundRequest::create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'customer_id' => $customerId,
            'seller_id' => $order->sellerId,
            'reason' => $request->reason,
            'message' => $request->message,
            'video_proof' => $videoPath,
            'status' => 'Pending',
        ]);

        // Notify Seller
        Notification::create([
            'userId' => $order->sellerId,
            'title' => 'New Refund Request',
            'message' => "A customer has requested a refund for an item in Order #{$order->id}",
            'targetRole' => 'seller',
        ]);

        $sellerUser = \App\Models\User::find($order->sellerId);
        if ($sellerUser && $sellerUser->email) {
            $mailable = new \App\Mail\ReturnRefundRequestMail($sellerUser->name, $order->id, $request->reason, 'Refund');
            \App\Services\EmailNotificationService::sendNotification($sellerUser->email, $mailable, 'return_refund_request', $sellerUser->id, 'Order', $order->id);
        }

        return response()->json([
            'message' => 'Refund request submitted successfully.',
            'refundRequest' => $refundRequest,
        ], 201);
    }

    /**
     * Get refund requests for a seller.
     */
    public function sellerIndex()
    {
        $requests = RefundRequest::where('seller_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($requests);
    }

    /**
     * Get refund requests for a customer.
     */
    public function customerIndex()
    {
        $requests = RefundRequest::where('customer_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($requests);
    }

    /**
     * Update refund status.
     */
    public function updateStatus(Request $request, $id)
    {
        $refundRequest = RefundRequest::where('id', $id)->where('seller_id', Auth::id())->firstOrFail();
        
        $request->validate([
            'status' => 'required|string|in:Pending,Approved,Rejected,Resolved',
            'sellerComment' => 'nullable|string|max:1000',
        ]);

        $refundRequest->update([
            'status' => $request->status,
            'seller_comment' => $request->sellerComment,
        ]);

        // Notify Customer
        Notification::create([
            'userId' => $refundRequest->customer_id,
            'title' => 'Refund Request Updated',
            'message' => "Your refund request has been {$request->status}.",
            'targetRole' => 'customer',
        ]);

        $customerUser = \App\Models\User::find($refundRequest->customer_id);
        if ($customerUser && $customerUser->email) {
            $mailable = new \App\Mail\ReturnRefundStatusMail($customerUser->name, $refundRequest->order_id, $request->status, $request->seller_comment, 'Refund');
            \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mailable, 'return_refund_update', $customerUser->id, 'Order', $refundRequest->order_id);
        }

        return response()->json([
            'message' => 'Refund request updated successfully.',
            'refundRequest' => $refundRequest,
        ]);
    }
}
