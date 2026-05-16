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
    public function store(Request $request)
    {
        $request->validate([
            'orderId' => 'required|exists:orders,id',
            'reason' => 'required|string',
            'proofImages' => 'nullable|array',
        ]);

        $userId = Auth::id();
        $order = Order::findOrFail($request->orderId);

        if ($order->customerId !== $userId) {
            return response()->json(['message' => 'You can only request returns for your own orders'], 403);
        }

        if ($order->status !== 'Delivered') {
            return response()->json(['message' => 'Only delivered orders can be returned'], 400);
        }

        $returnRequest = ReturnRequest::create([
            'orderId' => $request->orderId,
            'reason' => $request->reason,
            'proofImages' => json_encode($request->proofImages ?: []),
            'status' => 'Pending'
        ]);

        // Notify seller
        Notification::create([
            'userId' => $order->sellerId,
            'title' => 'New Return Request',
            'message' => "A customer has requested a return for order #{$order->id}",
            'targetRole' => 'seller',
        ]);

        return response()->json($returnRequest, 201);
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
    public function update(Request $request, $id)
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

        return response()->json($returnRequest);
    }
}
