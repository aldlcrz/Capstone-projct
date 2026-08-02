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
            'reason' => 'required|string',
            'videoProof' => 'required|file',
        ]);

        $customerId = Auth::id();
        $order = Order::where('id', $request->orderId)->where('customerId', $customerId)->firstOrFail();

        if ($order->status !== 'Received by Buyer' && $order->status !== 'Delivered') {
            return response()->json(['message' => 'Refunds are only available after receiving the item.'], 400);
        }

        $existingRequest = RefundRequest::where('orderItemId', $request->orderItemId)
            ->where('customerId', $customerId)
            ->first();

        if ($existingRequest) {
            return response()->json(['message' => 'A refund request already exists for this item.'], 400);
        }

        $videoPath = $request->file('videoProof')->store('refunds', 'public');

        $refundRequest = RefundRequest::create([
            'orderId' => $request->orderId,
            'orderItemId' => $request->orderItemId,
            'customerId' => $customerId,
            'sellerId' => $order->sellerId,
            'reason' => $request->reason,
            'message' => $request->message,
            'videoProof' => $videoPath,
            'status' => 'Pending',
        ]);

        // Notify Seller
        Notification::create([
            'userId' => $order->sellerId,
            'title' => 'New Refund Request',
            'message' => "A customer has requested a refund for an item in Order #{$order->id}",
            'targetRole' => 'seller',
        ]);

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
        $requests = RefundRequest::where('sellerId', Auth::id())
            ->orderBy('createdAt', 'desc')
            ->get();
            
        return response()->json($requests);
    }

    /**
     * Get refund requests for a customer.
     */
    public function customerIndex()
    {
        $requests = RefundRequest::where('customerId', Auth::id())
            ->orderBy('createdAt', 'desc')
            ->get();
            
        return response()->json($requests);
    }

    /**
     * Update refund status.
     */
    public function updateStatus(Request $request, $id)
    {
        $refundRequest = RefundRequest::where('id', $id)->where('sellerId', Auth::id())->firstOrFail();
        
        $request->validate([
            'status' => 'required|string',
            'sellerComment' => 'nullable|string',
        ]);

        $refundRequest->update([
            'status' => $request->status,
            'sellerComment' => $request->sellerComment,
        ]);

        // Notify Customer
        Notification::create([
            'userId' => $refundRequest->customerId,
            'title' => 'Refund Request Updated',
            'message' => "Your refund request has been {$request->status}.",
            'targetRole' => 'customer',
        ]);

        return response()->json([
            'message' => 'Refund request updated successfully.',
            'refundRequest' => $refundRequest,
        ]);
    }
}
