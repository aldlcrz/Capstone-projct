<?php

namespace App\Http\Controllers;

use App\Models\ReturnRequest;
use App\Models\Order;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnRequestController extends Controller
{
    /**
     * Create a new return request.
     */
    /**
     * Create a new return request.
     */
    public function store(Request $request, $id = null)
    {
        if (!$request->filled('orderId') && $id) {
            $request->merge(['orderId' => $id]);
        }

        $request->validate([
            'orderId'     => 'required|exists:orders,id',
            'reason'      => 'required|string',
            'message'     => 'nullable|string',
            'proofImages' => 'nullable',
            'proof_files.*' => 'nullable|file|image|max:10240',
        ]);

        $userId = Auth::id();
        $order = Order::findOrFail($request->orderId);

        if ($order->customerId !== $userId) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'You can only request returns for your own orders'], 403);
            }
            return back()->with('error', 'You can only request returns for your own orders.');
        }

        $allowedStatuses = ['delivered', 'completed', 'received by buyer'];
        if (!in_array(strtolower(trim($order->status)), $allowedStatuses, true)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Only delivered or completed orders can be returned'], 400);
            }
            return back()->with('error', 'Only delivered or completed orders can be returned.');
        }

        $existing = ReturnRequest::where('orderId', $order->id)
            ->whereIn('status', ['Pending', 'Approved'])
            ->first();
        if ($existing) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'A return request is already pending or approved for this order.'], 400);
            }
            return back()->with('error', 'A return request has already been submitted for this order.');
        }

        $proofPaths = [];
        if ($request->hasFile('proof_files')) {
            $dest = public_path('uploads/returns');
            if (!file_exists($dest)) {
                @mkdir($dest, 0777, true);
            }
            foreach ($request->file('proof_files') as $file) {
                if ($file->isValid()) {
                    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move($dest, $filename);
                    $proofPaths[] = '/uploads/returns/' . $filename;
                }
            }
        } elseif (is_array($request->proofImages)) {
            $proofPaths = $request->proofImages;
        }

        $reasonText = trim($request->reason);
        if ($request->filled('message')) {
            $reasonText .= " - " . trim($request->message);
        }

        $returnRequest = ReturnRequest::create([
            'orderId'     => $order->id,
            'reason'      => $reasonText,
            'proofImages' => json_encode($proofPaths),
            'status'      => 'Pending',
        ]);

        // Notify seller
        Notification::create([
            'userId'     => $order->sellerId,
            'title'      => 'New Return Request',
            'message'    => "Customer has requested a return for order #LB-OR-" . strtoupper(substr($order->id, -8)),
            'targetRole' => 'seller',
        ]);

        $sellerUser = \App\Models\User::find($order->sellerId);
        if ($sellerUser && $sellerUser->email) {
            $mailable = new \App\Mail\ReturnRefundRequestMail($sellerUser->name, $order->id, $reasonText, 'Return');
            \App\Services\EmailNotificationService::sendNotification($sellerUser->email, $mailable, 'return_refund_request', $sellerUser->id, 'Order', $order->id);
        }

        if ($request->wantsJson()) {
            return response()->json($returnRequest, 201);
        }

        return back()->with('success', 'Your return request has been submitted to the artisan for review.');
    }

    /**
     * Get return requests.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ReturnRequest::with('order');

        if ($user->role === 'customer') {
            $query->whereHas('order', function($q) use ($user) {
                $q->where('customerId', $user->id);
            });
        } elseif ($user->role === 'seller') {
            $query->whereHas('order', function($q) use ($user) {
                $q->where('sellerId', $user->id);
            });
        }

        $requests = $query->orderBy('createdAt', 'desc')->get();
        return response()->json($requests);
    }

    /**
     * Update return status.
     */
    public function update(Request $request, string $id)
    {
        $returnRequest = ReturnRequest::with('order')->findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'seller' && $returnRequest->order->sellerId !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|string',
            'adminComment' => 'nullable|string',
        ]);

        $returnRequest->update([
            'status' => $request->status,
            'adminComment' => $request->adminComment,
        ]);

        // Notify customer
        Notification::create([
            'userId' => $returnRequest->order->customerId,
            'title' => 'Return Request Update',
            'message' => "Your return request for order #{$returnRequest->order->id} has been {$request->status}.",
            'targetRole' => 'customer',
        ]);

        $customerUser = \App\Models\User::find($returnRequest->order->customerId);
        if ($customerUser && $customerUser->email) {
            $mailable = new \App\Mail\ReturnRefundStatusMail($customerUser->name, $returnRequest->order->id, $request->status, $request->adminComment, 'Return');
            \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mailable, 'return_refund_update', $customerUser->id, 'Order', $returnRequest->order->id);
        }

        return response()->json($returnRequest);
    }

    /**
     * Seller approves a customer return request.
     */
    public function sellerApproveReturn(Request $request, $orderId, $returnId)
    {
        $seller = Auth::user();
        if (!$seller || $seller->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized. Seller access required.'], 403);
        }

        $order = Order::findOrFail($orderId);
        if ($order->sellerId !== $seller->id) {
            return response()->json(['message' => 'Unauthorized for this order.'], 403);
        }

        $returnRequest = ReturnRequest::where('id', $returnId)
            ->where('orderId', $order->id)
            ->firstOrFail();

        $comment = trim($request->input('comment', $request->input('seller_notes', '')));

        $returnRequest->update([
            'status' => 'Approved',
            'adminComment' => $comment ?: 'Approved by artisan seller.',
        ]);

        // Record in OrderStatusHistory
        try {
            \App\Models\OrderStatusHistory::create([
                'orderId' => $order->id,
                'previousStatus' => $order->status,
                'newStatus' => 'Return Approved',
                'updatedBy' => $seller->id,
                'userRole' => 'seller',
                'notes' => 'Return request approved by artisan.' . ($comment ? " Instructions: {$comment}" : ''),
            ]);
        } catch (\Throwable $e) {}

        // Notify customer
        try {
            Notification::send(
                $order->customerId,
                'Return Request Approved',
                "Your return request for order #LB-" . strtoupper(substr($order->id, -8)) . " has been approved by the artisan." . ($comment ? " Instructions: {$comment}" : ''),
                'order',
                route('orders.show', $order->id),
                'customer'
            );
        } catch (\Throwable $e) {}

        // Send email notification
        try {
            $customerUser = \App\Models\User::find($order->customerId);
            if ($customerUser && $customerUser->email) {
                $mailable = new \App\Mail\ReturnRefundStatusMail($customerUser->name, $order->id, 'Approved', $comment, 'Return');
                \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mailable, 'return_refund_update', $customerUser->id, 'Order', $order->id);
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Return request approved successfully.',
            'returnRequest' => $returnRequest,
        ]);
    }

    /**
     * Seller rejects a customer return request.
     */
    public function sellerRejectReturn(Request $request, $orderId, $returnId)
    {
        $seller = Auth::user();
        if (!$seller || $seller->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized. Seller access required.'], 403);
        }

        $order = Order::findOrFail($orderId);
        if ($order->sellerId !== $seller->id) {
            return response()->json(['message' => 'Unauthorized for this order.'], 403);
        }

        $returnRequest = ReturnRequest::where('id', $returnId)
            ->where('orderId', $order->id)
            ->firstOrFail();

        $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        $reason = trim($request->input('reason'));

        $returnRequest->update([
            'status' => 'Rejected',
            'adminComment' => $reason,
        ]);

        // Record in OrderStatusHistory
        try {
            \App\Models\OrderStatusHistory::create([
                'orderId' => $order->id,
                'previousStatus' => $order->status,
                'newStatus' => 'Return Rejected',
                'updatedBy' => $seller->id,
                'userRole' => 'seller',
                'notes' => 'Return request declined by artisan. Reason: ' . $reason,
            ]);
        } catch (\Throwable $e) {}

        // Notify customer
        try {
            Notification::send(
                $order->customerId,
                'Return Request Declined',
                "Your return request for order #LB-" . strtoupper(substr($order->id, -8)) . " was declined: {$reason}",
                'order',
                route('orders.show', $order->id),
                'customer'
            );
        } catch (\Throwable $e) {}

        // Send email notification
        try {
            $customerUser = \App\Models\User::find($order->customerId);
            if ($customerUser && $customerUser->email) {
                $mailable = new \App\Mail\ReturnRefundStatusMail($customerUser->name, $order->id, 'Rejected', $reason, 'Return');
                \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mailable, 'return_refund_update', $customerUser->id, 'Order', $order->id);
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Return request declined.',
            'returnRequest' => $returnRequest,
        ]);
    }
}
