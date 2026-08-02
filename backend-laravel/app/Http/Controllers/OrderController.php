<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Address;
use App\Models\User;
use App\Models\SystemSetting;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Helper to send notifications.
     */
    private function sendNotification($userId, $title, $message, $type = 'system', $link = null, $role = 'customer')
    {
        try {
            Notification::create([
                'userId' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'link' => $link,
                'targetRole' => $role,
                'isRead' => false
            ]);
        } catch (\Exception $e) {
            \Log::error('Notification error: ' . $e->getMessage());
        }
    }

    /**
     * Get orders for the authenticated customer.
     */
    public function getMyOrders(Request $request)
    {
        $orders = Order::where('customerId', $request->user()->id)
            ->with(['seller:id,name,email,profilePhoto', 'items.product'])
            ->orderBy('createdAt', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Get orders for the authenticated seller.
     */
    public function getSellerOrders(Request $request)
    {
        $sellerId = ($request->user()->role === 'admin' && $request->has('sellerId')) 
            ? $request->sellerId 
            : $request->user()->id;

        $orders = Order::where('sellerId', $sellerId)
            ->with(['customer:id,name,email,profilePhoto', 'items.product'])
            ->orderBy('createdAt', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Create a new order.
     */
    public function createOrder(Request $request)
    {
        // Maintenance Mode Guard
        $maintenance = SystemSetting::where('key', 'maintenanceMode')->first();
        if ($maintenance && ($maintenance->value === 'true' || $maintenance->value === true) && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => "Transactions are temporarily paused for maintenance. Please try again later.",
                'maintenanceMode' => true 
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'paymentMethod' => 'required|string|in:GCash,Maya',
            'addressId' => 'nullable|string',
            'shippingAddress' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
        }

        try {
            return DB::transaction(function () use ($request) {
                $items = $request->items;
                $customerId = $request->user()->id;
                $shippingAddress = $request->shippingAddress;

                if ($request->has('addressId')) {
                    $addressRecord = Address::where('id', $request->addressId)
                        ->where('userId', $customerId)
                        ->first();
                    if ($addressRecord) {
                        $shippingAddress = $addressRecord->toArray();
                    }
                }

                if (!$shippingAddress) {
                    throw new \Exception('Shipping address is required', 400);
                }

                $calculatedTotal = 0;
                $sellerId = null;
                $preparedItems = [];

                foreach ($items as $item) {
                    $productId = $item['productId'] ?? $item['id'];
                    $product = Product::lockForUpdate()->find($productId);
                    if (!$product) {
                        throw new \Exception("Product not found: " . $productId, 404);
                    }

                    if ($product->stock < $item['quantity']) {
                        throw new \Exception("Not enough stock for: " . $product->name, 400);
                    }

                    if ($sellerId && $sellerId !== $product->sellerId) {
                        throw new \Exception("Orders can only contain products from one seller", 400);
                    }

                    $sellerId = $product->sellerId;
                    $calculatedTotal += $product->price * $item['quantity'];

                    $preparedItems[] = [
                        'product' => $product,
                        'productId' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                        'size' => $item['size'] ?? 'M',
                        'variation' => $item['variation'] ?? 'Original',
                    ];
                }

                $order = Order::create([
                    'customerId' => $customerId,
                    'sellerId' => $sellerId,
                    'totalAmount' => $calculatedTotal,
                    'shippingAddress' => $shippingAddress,
                    'paymentMethod' => $request->paymentMethod,
                    'paymentReference' => $request->paymentReference,
                    'paymentProof' => $request->paymentProof,
                    'status' => 'Pending',
                    'visitorSessionId' => $request->header('x-visitor-session'),
                ]);

                foreach ($preparedItems as $pItem) {
                    $pItem['product']->decrement('stock', $pItem['quantity']);

                    OrderItem::create([
                        'orderId' => $order->id,
                        'productId' => $pItem['productId'],
                        'quantity' => $pItem['quantity'],
                        'price' => $pItem['price'],
                        'size' => $pItem['size'],
                        'variation' => $pItem['variation'],
                    ]);

                    // Stock notifications
                    if ($pItem['product']->fresh()->stock <= 0) {
                        $this->sendNotification($sellerId, '⚠️ Out of Stock', "\"{$pItem['product']->name}\" is now out of stock.", 'system', '/seller/products', 'seller');
                    } elseif ($pItem['product']->fresh()->stock <= 5) {
                        $this->sendNotification($sellerId, '🔔 Low Stock Alert', "\"{$pItem['product']->name}\" has only {$pItem['product']->stock} items left.", 'system', '/seller/products', 'seller');
                    }
                }

                $this->sendNotification($customerId, 'Order placed', 'Your order has been placed successfully and is awaiting confirmation.', 'order', '/orders', 'customer');
                $this->sendNotification($sellerId, 'New order received', 'A customer has placed a new order in your shop.', 'order', '/seller/orders', 'seller');

                return response()->json($order->load(['seller', 'items.product']), 201);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) return response()->json(['message' => 'Order not found'], 404);

        $blockedStatuses = ['cancelled', 'cancellation pending', 'cancellation requested'];
        if (in_array(strtolower($request->status), $blockedStatuses, true)) {
            return response()->json(['message' => 'Paid orders cannot be cancelled. Contact the seller or platform support for refund disputes.'], 400);
        }

        // Permissions
        if ($request->user()->role === 'customer') {
            if ($order->customerId !== $request->user()->id) return response()->json(['message' => 'Unauthorized'], 403);
            if (!in_array($request->status, ['Received by Buyer', 'Completed'])) {
                return response()->json(['message' => 'Customers can only confirm receipt'], 403);
            }
        } elseif ($request->user()->role === 'seller') {
            if ($order->sellerId !== $request->user()->id) return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->status = $request->status;
        $order->save();

        $statusMsg = [
            'Processing' => 'Your order is now being prepared for shipment.',
            'Shipped' => 'Your order has been shipped and is on the way.',
            'Delivered' => 'Your order has been marked as delivered.',
            'Completed' => 'Your order has been completed.',
        ][$request->status] ?? "Your order status is now {$request->status}.";

        $this->sendNotification($order->customerId, "Order {$request->status}", $statusMsg, 'order', '/orders', 'customer');

        return response()->json($order->load(['customer', 'seller', 'items.product']));
    }

    /**
     * Export Seller Report (CSV).
     */
    public function exportSellerReport(Request $request)
    {
        $sellerId = $request->user()->id;
        $orders = Order::where('sellerId', $sellerId)
            ->with(['customer', 'items.product'])
            ->orderBy('createdAt', 'desc')
            ->get();

        $filename = "seller_report_" . time() . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['Type', 'ID', 'Title', 'Details', 'Amount', 'Status', 'Date']);

        foreach ($orders as $o) {
            fputcsv($handle, [
                'ORDER',
                $o->id,
                "Order from " . ($o->customer->name ?? 'Unknown'),
                "Pay: {$o->paymentMethod} | Ref: {$o->paymentReference}",
                number_format($o->totalAmount, 2),
                $o->status,
                $o->createdAt
            ]);

            foreach ($o->items as $item) {
                fputcsv($handle, [
                    'ITEM',
                    '',
                    "  > " . ($item->product->name ?? 'Deleted Product'),
                    "Qty: {$item->quantity} @ " . number_format($item->price, 2),
                    number_format($item->quantity * $item->price, 2),
                    '',
                    ''
                ]);
            }
            fputcsv($handle, ['', '', '', '', '', '', '']); // Spacer
        }

        fclose($handle);
        exit;
    }

    /**
     * Confirm order received from the customer-facing Blade form (PATCH).
     */
    public function confirmReceived($id)
    {
        $order = Order::where('id', $id)->where('customerId', auth()->id())->firstOrFail();

        $receivable = ['to receive', 'shipped'];
        if (!in_array(strtolower($order->status), $receivable)) {
            return redirect()->back()->with('error', 'You cannot confirm this order at this stage.');
        }

        $order->status = 'Completed';
        $order->save();

        $this->sendNotification(
            $order->sellerId,
            'Order Completed',
            'A customer has confirmed receipt of their order.',
            'order', '/seller/orders', 'seller'
        );

        return redirect()->route('orders.show', $id)->with('success', 'Order marked as received. Thank you!');
    }
}
