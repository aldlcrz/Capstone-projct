<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Notification;
use App\Utils\SocketUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefundController extends Controller
{
    public function createRefundRequest(Request $request)
    {
        try {
            $orderId = $request->input('orderId');
            $orderItemId = $request->input('orderItemId');
            $reason = $request->input('reason');
            $message = $request->input('message');
            $customerId = Auth::id();

            $order = Order::where('id', $orderId)->where('customerId', $customerId)->first();
            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            if ($order->status !== 'Received by Buyer' && $order->status !== 'Delivered') {
                return response()->json(['message' => 'Refunds are only available after receiving the item.'], 400);
            }

            $orderItem = OrderItem::where('id', $orderItemId)->where('orderId', $orderId)->first();
            if (!$orderItem) {
                return response()->json(['message' => 'Order item not found'], 404);
            }

            $existingRequest = RefundRequest::where('orderItemId', $orderItemId)->where('customerId', $customerId)->first();
            if ($existingRequest) {
                return response()->json(['message' => 'A refund request already exists for this item.'], 400);
            }

            if (!$request->hasFile('video')) {
                return response()->json(['message' => 'Video proof is required for refund requests.'], 400);
            }

            $file = $request->file('video');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/products'), $fileName);
            $videoProofUrl = url("uploads/products/{$fileName}");

            $refundRequest = RefundRequest::create([
                'orderId' => $orderId,
                'orderItemId' => $orderItemId,
                'customerId' => $customerId,
                'sellerId' => $order->sellerId,
                'reason' => $reason,
                'message' => $reason === 'Other' ? $message : null,
                'videoProof' => $videoProofUrl,
                'status' => 'Pending',
            ]);

            // Notify seller
            Notification::create([
                'userId' => $order->sellerId,
                'title' => 'New Refund Request',
                'message' => "A customer has requested a refund for an item in Order #" . substr($order->id, 0, 8),
                'type' => 'system',
                'link' => "/seller/refunds?id={$refundRequest->id}",
                'targetRole' => 'seller'
            ]);
            try {
                SocketUtility::emitToUser($order->sellerId, 'new_notification', [
                    'title' => 'New Refund Request',
                    'message' => "A customer has requested a refund for an item in Order #" . substr($order->id, 0, 8)
                ]);
            } catch (\Exception $broadcastErr) {
                \Illuminate\Support\Facades\Log::warning('Broadcast failed in createRefundRequest: ' . $broadcastErr->getMessage());
            }

            return response()->json([
                'message' => 'Refund request submitted successfully.',
                'refundRequest' => $refundRequest
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function getSellerRefundRequests()
    {
        try {
            $sellerId = Auth::id();
            $requests = RefundRequest::where('sellerId', $sellerId)
                ->with([
                    'order:id,status,totalAmount',
                    'orderItem:id,productId,price,quantity,size,variation',
                    'orderItem.product:id,name,image',
                    'customer:id,name,email'
                ])
                ->orderBy('createdAt', 'DESC')
                ->get();

            return response()->json($requests);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function getCustomerRefundRequests()
    {
        try {
            $customerId = Auth::id();
            $requests = RefundRequest::where('customerId', $customerId)
                ->with([
                    'order:id,status',
                    'orderItem:id,productId,price'
                ])
                ->orderBy('createdAt', 'DESC')
                ->get();

            return response()->json($requests);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    public function updateRefundStatus(Request $request, string $id)
    {
        try {
            $status = $request->input('status');
            $sellerComment = $request->input('sellerComment');
            $sellerId = Auth::id();

            $refundRequest = RefundRequest::where('id', $id)->where('sellerId', $sellerId)->first();
            if (!$refundRequest) {
                return response()->json(['message' => 'Refund request not found'], 404);
            }

            $refundRequest->update([
                'status' => $status,
                'sellerComment' => $sellerComment
            ]);

            // Notify Customer
            Notification::create([
                'userId' => $refundRequest->customerId,
                'title' => 'Refund Request Updated',
                'message' => "Your refund request has been " . strtolower($status) . ".",
                'type' => 'system',
                'link' => "/orders?id={$refundRequest->orderId}",
                'targetRole' => 'customer'
            ]);
            try {
                SocketUtility::emitToUser($refundRequest->customerId, 'new_notification', [
                    'title' => 'Refund Request Updated',
                    'message' => "Your refund request has been " . strtolower($status) . "."
                ]);
            } catch (\Exception $broadcastErr) {
                \Illuminate\Support\Facades\Log::warning('Broadcast failed in updateRefundStatus: ' . $broadcastErr->getMessage());
            }

            return response()->json([
                'message' => 'Refund request updated successfully.',
                'refundRequest' => $refundRequest
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
