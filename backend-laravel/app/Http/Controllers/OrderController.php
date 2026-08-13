<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Address;
use App\Models\User;
use App\Models\SystemSetting;
use App\Models\Notification;
use App\Models\OrderStatusHistory;
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
            ->with(['seller:id,name,email,profilePhoto', 'items.product', 'statusHistories'])
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
            ->with(['customer:id,name,email,profilePhoto', 'items.product', 'statusHistories'])
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

                // Record initial OrderStatusHistory
                OrderStatusHistory::create([
                    'orderId' => $order->id,
                    'previousStatus' => null,
                    'newStatus' => 'Pending',
                    'updatedBy' => $customerId,
                    'userRole' => 'customer',
                    'notes' => 'Order placed by customer.',
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

                // Gmail Notifications
                $customerUser = User::find($customerId);
                $sellerUser   = User::find($sellerId);

                if ($customerUser && $customerUser->email) {
                    $cMail = new \App\Mail\OrderStatusUpdatedMail($customerUser->name, $order->id, 'Order Confirmed', 'Your order has been placed successfully.');
                    \App\Services\EmailNotificationService::sendNotification($customerUser->email, $cMail, 'order_status_updated', $customerUser->id, 'Order', $order->id);
                }

                if ($sellerUser && $sellerUser->email) {
                    $sMail = new \App\Mail\NewOrderSellerMail($sellerUser->name, $order->id, (float) $calculatedTotal);
                    \App\Services\EmailNotificationService::sendNotification($sellerUser->email, $sMail, 'new_order', $sellerUser->id, 'Order', $order->id);
                }

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

        $targetStatus = trim($request->status ?? '');
        $normalizedTarget = strtolower($targetStatus);

        $blockedStatuses = ['cancelled', 'cancellation pending', 'cancellation requested'];
        if (in_array($normalizedTarget, $blockedStatuses, true)) {
            return response()->json(['message' => 'Paid orders cannot be cancelled. Contact the seller or platform support for refund disputes.'], 400);
        }

        $user = $request->user();
        // Permissions
        if ($user->role === 'customer') {
            if ($order->customerId !== $user->id) return response()->json(['message' => 'Unauthorized'], 403);
            if (!in_array($normalizedTarget, ['received by buyer', 'completed'], true)) {
                return response()->json(['message' => 'Customers can only confirm receipt.'], 403);
            }
        } elseif ($user->role === 'seller') {
            if ($order->sellerId !== $user->id && $user->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        }

        $currentStatus = $order->status;
        $normalizedCurrent = strtolower($currentStatus);

        // Edit locking for shipping details
        if (in_array($normalizedCurrent, ['delivered', 'completed', 'cancelled'], true)) {
            if ($request->has('courierName') || $request->has('trackingNumber') || $request->has('trackingLink')) {
                return response()->json(['message' => 'Shipping information is locked and cannot be edited after order is delivered or completed.'], 400);
            }
            if (($normalizedCurrent === 'completed' || $normalizedCurrent === 'cancelled') && $normalizedTarget !== $normalizedCurrent) {
                return response()->json(['message' => "Order is already {$order->status} and cannot be modified."], 400);
            }
        }

        // Status mapping to canonical names
        $statusKeyMap = [
            'pending' => 'Pending',
            'to ship' => 'Ready to Ship',
            'ready to ship' => 'Ready to Ship',
            'ready_to_ship' => 'Ready to Ship',
            'shipped' => 'Shipped',
            'to receive' => 'Shipped',
            'in transit' => 'In Transit',
            'in_transit' => 'In Transit',
            'out for delivery' => 'Out for Delivery',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'received by buyer' => 'Completed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $canonicalTarget = $statusKeyMap[$normalizedTarget] ?? $targetStatus;
        $canonicalCurrent = $statusKeyMap[$normalizedCurrent] ?? $currentStatus;

        // Transition rank checks (blocks backward status regressions)
        $statusRank = [
            'Pending' => 0,
            'Ready to Ship' => 1,
            'Shipped' => 2,
            'In Transit' => 3,
            'Out for Delivery' => 4,
            'Delivered' => 5,
            'Completed' => 6,
            'Cancelled' => -1,
        ];

        $currRank = $statusRank[$canonicalCurrent] ?? 0;
        $targetRank = $statusRank[$canonicalTarget] ?? 0;

        if ($targetRank >= 0 && $currRank >= 0 && $targetRank < $currRank) {
            return response()->json(['message' => "Invalid status transition from {$canonicalCurrent} to {$canonicalTarget}."], 400);
        }

        // Shipping validation when marking as Shipped
        $shippingUpdated = false;
        if ($canonicalTarget === 'Shipped') {
            $courier = trim($request->courierName ?? $order->courierName ?? '');
            $trackingNum = trim($request->trackingNumber ?? $order->trackingNumber ?? '');
            $trackingLink = trim($request->trackingLink ?? $order->trackingLink ?? '');

            if (empty($courier) || empty($trackingNum) || empty($trackingLink)) {
                return response()->json(['message' => 'Please provide courier, tracking number, and tracking link before marking this order as Shipped.'], 422);
            }

            if (!filter_var($trackingLink, FILTER_VALIDATE_URL)) {
                return response()->json(['message' => 'Please enter a valid tracking URL (e.g. https://www.jtexpress.ph/track).'], 422);
            }

            if ($order->courierName !== $courier || $order->trackingNumber !== $trackingNum || $order->trackingLink !== $trackingLink) {
                $shippingUpdated = true;
            }

            $order->courierName = $courier;
            $order->trackingNumber = $trackingNum;
            $order->trackingLink = $trackingLink;
        } else {
            if ($request->filled('courierName') && $order->courierName !== trim($request->courierName)) {
                $order->courierName = trim($request->courierName);
                $shippingUpdated = true;
            }
            if ($request->filled('trackingNumber') && $order->trackingNumber !== trim($request->trackingNumber)) {
                $order->trackingNumber = trim($request->trackingNumber);
                $shippingUpdated = true;
            }
            if ($request->filled('trackingLink')) {
                if (!filter_var($request->trackingLink, FILTER_VALIDATE_URL)) {
                    return response()->json(['message' => 'Please enter a valid tracking URL.'], 422);
                }
                if ($order->trackingLink !== trim($request->trackingLink)) {
                    $order->trackingLink = trim($request->trackingLink);
                    $shippingUpdated = true;
                }
            }
        }

        $order->status = $canonicalTarget;
        $order->save();

        if ($canonicalCurrent !== $canonicalTarget || $shippingUpdated) {
            OrderStatusHistory::create([
                'orderId' => $order->id,
                'previousStatus' => $canonicalCurrent,
                'newStatus' => $canonicalTarget,
                'updatedBy' => $user->id,
                'userRole' => $user->role,
                'notes' => $request->notes ?? ($canonicalCurrent !== $canonicalTarget ? "Status updated to {$canonicalTarget}." : "Shipping information updated."),
            ]);
        }

        $statusMsgMap = [
            'Ready to Ship' => 'Your order is packed and ready to ship.',
            'Shipped' => "Your order has been shipped via {$order->courierName} (Tracking: {$order->trackingNumber}).",
            'In Transit' => 'Your order is in transit with the courier.',
            'Out for Delivery' => 'Your order is out for delivery today!',
            'Delivered' => 'Your order has been delivered. Please inspect your item and rate your purchase.',
            'Completed' => 'Your order has been marked as completed.',
        ];

        $statusMsg = $statusMsgMap[$canonicalTarget] ?? "Your order status is now {$canonicalTarget}.";

        $this->sendNotification($order->customerId, "Order {$canonicalTarget}", $statusMsg, 'order', '/orders', 'customer');

        $customerUser = User::find($order->customerId);
        if ($customerUser && $customerUser->email) {
            $mailable = new \App\Mail\OrderStatusUpdatedMail($customerUser->name, $order->id, $canonicalTarget, $statusMsg);
            \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mailable, 'order_status_updated', $customerUser->id, 'Order', $order->id);
        }

        return response()->json($order->load(['customer', 'seller', 'items.product', 'statusHistories']));
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

        $receivable = ['shipped', 'to receive', 'in transit', 'in_transit', 'out for delivery', 'out_for_delivery', 'delivered'];
        if (!in_array(strtolower($order->status), $receivable, true)) {
            return redirect()->back()->with('error', 'You cannot confirm this order at this stage.');
        }

        $prevStatus = $order->status;
        $order->status = 'Completed';
        $order->save();

        OrderStatusHistory::create([
            'orderId' => $order->id,
            'previousStatus' => $prevStatus,
            'newStatus' => 'Completed',
            'updatedBy' => auth()->id(),
            'userRole' => 'customer',
            'notes' => 'Receipt confirmed by customer.',
        ]);

        $this->sendNotification(
            $order->sellerId,
            'Order Completed',
            'A customer has confirmed receipt of their order.',
            'order', '/seller/orders', 'seller'
        );

        return redirect()->route('orders.show', $id)->with('success', 'Order marked as received. Thank you!');
    }
}
