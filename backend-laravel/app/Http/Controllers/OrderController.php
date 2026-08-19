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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Helper to send notifications.
     */
    private function sendNotification(mixed $userId, string $title, string $message, string $type = 'system', ?string $link = null, string $role = 'customer')
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
            Log::error('Notification error: ' . $e->getMessage());
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

                $this->sendNotification($customerId, 'Order placed', 'Your order has been placed successfully and is awaiting confirmation.', 'order', "/orders/{$order->id}", 'customer');
                $this->sendNotification($sellerId, 'New order received', "A customer placed a new order (#LB-" . strtoupper(substr($order->id, -8)) . ") in your shop.", 'order', "/seller/orders?order_id={$order->id}", 'seller');

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
    public function updateOrderStatus(Request $request, string $id)
    {
        $order = Order::find($id);
        if (!$order) return response()->json(['message' => 'Order not found'], 404);

        $targetStatus = trim($request->status ?? '');
        $normalizedTarget = strtolower($targetStatus);

        if ($normalizedTarget === 'cancelled') {
            return $this->cancelOrder($request, $id);
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
            'to ship' => 'To Ship',
            'to_ship' => 'To Ship',
            'ready to ship' => 'To Ship',
            'ready_to_ship' => 'To Ship',
            'shipped' => 'Shipped',
            'to receive' => 'Shipped',
            'in transit' => 'In Transit',
            'in_transit' => 'In Transit',
            'out for delivery' => 'In Transit',
            'out_for_delivery' => 'In Transit',
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
            'To Ship' => 1,
            'Shipped' => 2,
            'In Transit' => 3,
            'Delivered' => 4,
            'Completed' => 5,
            'Cancelled' => -1,
        ];

        $currRank = $statusRank[$canonicalCurrent] ?? 0;
        $targetRank = $statusRank[$canonicalTarget] ?? 0;

        if ($targetRank >= 0 && $currRank >= 0 && $targetRank < $currRank) {
            return response()->json(['message' => "Invalid status transition from {$canonicalCurrent} to {$canonicalTarget}."], 400);
        }

        // Sellers cannot manually set Completed (Only customer delivery confirmation marks Completed)
        if ($canonicalTarget === 'Completed' && strtolower($user->role) === 'seller') {
            return response()->json(['message' => 'Sellers cannot manually mark orders as Completed. Order completion is triggered when the customer confirms delivery.'], 403);
        }

        // Packing proof handling when marking as Shipped
        if ($request->hasFile('packingPhoto')) {
            $file = $request->file('packingPhoto');
            $destDir = public_path('uploads/packing-proofs');
            if (!file_exists($destDir)) {
                @mkdir($destDir, 0777, true);
            }
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($destDir, $filename);
            $order->packingProof = 'uploads/packing-proofs/' . $filename;
        }

        // Shipping info: courier and tracking assignment (must be manually entered by seller)
        $shippingUpdated = false;
        $courier = trim($request->courierName ?? $order->courierName ?? 'J&T Express');
        $trackingNum = trim($request->trackingNumber ?? $order->trackingNumber ?? '');
        $trackingLink = trim($request->trackingLink ?? $order->trackingLink ?? '');

        if (!$trackingLink && $courier === 'J&T Express') {
            $trackingLink = 'https://www.jtexpress.ph/track';
        }

        if ($courier && $order->courierName !== $courier) {
            $order->courierName = $courier;
            $shippingUpdated = true;
        }
        if ($trackingNum !== '' && $order->trackingNumber !== $trackingNum) {
            $order->trackingNumber = $trackingNum;
            $shippingUpdated = true;
        }
        if ($trackingLink && $order->trackingLink !== $trackingLink) {
            $order->trackingLink = $trackingLink;
            $shippingUpdated = true;
        }

        // Strictly require manual tracking number before moving to In Transit
        if (in_array($canonicalTarget, ['In Transit'], true) && empty($order->trackingNumber)) {
            return response()->json(['message' => 'Please enter the official courier tracking number before moving to In Transit.'], 422);
        }

        $order->status = $canonicalTarget;
        if ($canonicalTarget === 'To Ship') {
            $order->paymentStatus = 'Verified';
            $order->paymentRejectionReason = null;
        }
        $order->save();

        if ($canonicalCurrent !== $canonicalTarget || $shippingUpdated) {
            OrderStatusHistory::create([
                'orderId' => $order->id,
                'previousStatus' => $canonicalCurrent,
                'newStatus' => $canonicalTarget,
                'updatedBy' => $user->id,
                'userRole' => $user->role,
                'notes' => $request->notes ?? ($canonicalTarget === 'To Ship' ? "Payment verified and order accepted for preparation." : ($canonicalCurrent !== $canonicalTarget ? "Status updated to {$canonicalTarget}." : "Shipping information updated.")),
            ]);
        }

        $statusMsgMap = [
            'To Ship' => 'Your order is being processed and prepared for shipping.',
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
    public function confirmReceived(string $id)
    {
        $order = Order::where('id', $id)->where('customerId', Auth::id())->firstOrFail();

        $receivable = ['shipped', 'to receive', 'in transit', 'in_transit', 'out for delivery', 'out_for_delivery', 'delivered'];
        if (!in_array(strtolower(trim($order->status)), $receivable, true)) {
            return redirect()->back()->with('error', 'You cannot confirm this order at this stage.');
        }

        $prevStatus = $order->status;
        $order->status = 'Completed';
        $order->save();

        OrderStatusHistory::create([
            'orderId'        => $order->id,
            'previousStatus' => $prevStatus,
            'newStatus'      => 'Completed',
            'updatedBy'      => Auth::id(),
            'userRole'       => 'customer',
            'notes'          => 'Order confirmed delivered & completed by customer.',
        ]);

        $this->sendNotification(
            $order->sellerId,
            'Order Completed',
            "Customer has confirmed delivery for order #LB-OR-" . strtoupper(substr($order->id, -8)) . ". Status updated to Completed.",
            'order', '/seller/orders', 'seller'
        );

        return redirect()->back()->with('success', 'Thank you! Delivery confirmed and order marked as Completed. You can now rate your purchase.');
    }

    /**
     * Upload a packing proof photo for a Ready to Ship order.
     * Accessible by the seller only.
     */
    public function uploadPackingProof(Request $request, string $id)
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role, ['seller', 'admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($user->role === 'seller' && $order->sellerId !== $user->id) {
            return response()->json(['message' => 'You do not own this order.'], 403);
        }

        $request->validate([
            'packingPhoto' => ['required', 'file', 'image', 'max:8192', 'mimes:jpg,jpeg,png,webp,heic'],
        ]);

        $destDir = public_path('uploads/packing-proofs');
        if (!file_exists($destDir)) {
            @mkdir($destDir, 0777, true);
        }

        $file = $request->file('packingPhoto');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($destDir, $filename);

        $path = 'uploads/packing-proofs/' . $filename;
        $order->packingProof = $path;
        $order->save();

        return response()->json([
            'message' => 'Packing proof uploaded successfully.',
            'packingProof' => $path,
            'packingProofUrl' => $order->packing_proof_url ?? asset($path),
        ]);
    }

    /**
     * Reject order payment (Seller or Admin only).
     */
    public function rejectPayment(Request $request, string $id)
    {
        $order = Order::with('items.product')->find($id);
        if (!$order) return response()->json(['message' => 'Order not found'], 404);

        $user = $request->user();
        if ($user->role === 'seller' && $order->sellerId !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'A clear rejection reason is required.', 'errors' => $validator->errors()], 422);
        }

        $reason = trim($request->reason);
        $prevStatus = $order->status;

        DB::beginTransaction();
        try {
            // Restore inventory stock since rejection cancels the order
            if ($prevStatus !== 'Cancelled') {
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock', $item->quantity);
                        
                        // Restore size stock if available
                        if (!empty($item->product->size_stocks) && !empty($item->size)) {
                            $sizeStocks = $item->product->size_stocks;
                            if (isset($sizeStocks[$item->size])) {
                                $sizeStocks[$item->size] = (int)$sizeStocks[$item->size] + (int)$item->quantity;
                                $item->product->size_stocks = $sizeStocks;
                                $item->product->save();
                            }
                        }
                    }
                }
            }

            $order->status = 'Cancelled';
            $order->paymentStatus = 'Payment Rejected';
            $order->paymentRejectionReason = $reason;
            $order->cancellationReason = "Payment rejected: {$reason}";
            $order->save();

            OrderStatusHistory::create([
                'orderId' => $order->id,
                'previousStatus' => $prevStatus,
                'newStatus' => 'Cancelled',
                'updatedBy' => $user->id,
                'userRole' => $user->role,
                'notes' => "Order cancelled due to rejected payment. Reason: {$reason}",
            ]);

            $this->sendNotification(
                $order->customerId,
                'Order Cancelled (Payment Rejected)',
                "Your order #" . substr($order->id, 0, 8) . " was cancelled because the payment proof was rejected: {$reason}. Product stock has been returned to inventory.",
                'order',
                '/orders/' . $order->id,
                'customer'
            );

            $customerUser = User::find($order->customerId);
            if ($customerUser && $customerUser->email) {
                $mail = new \App\Mail\OrderStatusUpdatedMail(
                    $customerUser->name,
                    $order->id,
                    'Order Cancelled',
                    "Your order #" . substr($order->id, 0, 8) . " was cancelled because your payment proof was rejected by the artisan. Reason: {$reason}."
                );
                \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mail, 'order_status_updated', $customerUser->id, 'Order', $order->id);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment rejected. Order has been cancelled and stock restored.',
                'order' => $order->load(['seller', 'items.product', 'statusHistories'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to reject and cancel order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Customer resubmit payment proof for a rejected order payment.
     */
    public function resubmitPayment(Request $request, string $id)
    {
        $order = Order::find($id);
        if (!$order) return response()->json(['message' => 'Order not found'], 404);

        $user = $request->user();
        if ($order->customerId !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $isGcash = strcasecmp($order->paymentMethod, 'GCash') === 0;
        $isMaya  = strcasecmp($order->paymentMethod, 'Maya') === 0;

        $request->validate([
            'paymentReference' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($isGcash, $isMaya, $order) {
                    $raw = trim((string)$value);
                    if (preg_match('/^(\d)\1+$/', $raw)) {
                        $fail('Invalid payment reference number. Repeated digit sequences are not allowed.');
                        return;
                    }
                    if ($isGcash && !preg_match('/^\d{13}$/', $raw)) {
                        $fail('GCash reference number must be exactly 13 digits.');
                        return;
                    } elseif ($isMaya && !preg_match('/^\d{12}$/', $raw)) {
                        $fail('Maya reference number must be exactly 12 digits.');
                        return;
                    }
                    $isDuplicate = Order::where('paymentReference', $raw)->where('id', '!=', $order->id)->exists();
                    if ($isDuplicate) {
                        $fail('This payment reference number has already been used in another order.');
                        return;
                    }
                }
            ],
            'paymentScreenshot' => 'required|image|max:10240',
        ]);

        if ($request->hasFile('paymentScreenshot')) {
            $tempPath = $request->file('paymentScreenshot')->getRealPath();
            $screening = \App\Services\AiService::verifyReceipt(
                $tempPath,
                $request->paymentReference,
                $order->paymentMethod,
                (float) $order->totalAmount
            );

            if (($screening['status'] ?? '') === 'REJECT' || !($screening['is_receipt'] ?? true)) {
                return response()->json([
                    'message' => $screening['message'] ?? 'The uploaded file does not appear to be a valid payment receipt screenshot.'
                ], 422);
            }

            $path = $request->file('paymentScreenshot')->store('payments', 'public');
            $order->paymentProof = $path;
        }

        $order->paymentReference = trim($request->paymentReference);
        $order->paymentStatus = 'Payment Submitted';
        $order->paymentRejectionReason = null;
        $order->save();

        OrderStatusHistory::create([
            'orderId' => $order->id,
            'previousStatus' => $order->status,
            'newStatus' => $order->status,
            'updatedBy' => $user->id,
            'userRole' => 'customer',
            'notes' => 'Customer resubmitted payment proof with reference: ' . $order->paymentReference,
        ]);

        $this->sendNotification(
            $order->sellerId,
            'Payment Proof Resubmitted',
            "Customer resubmitted payment for Order #" . substr($order->id, 0, 8) . ". Please verify in your wallet.",
            'order',
            '/seller/orders',
            'seller'
        );

        return response()->json([
            'message' => 'Payment proof resubmitted successfully. Awaiting artisan verification.',
            'order' => $order->load(['seller', 'items.product', 'statusHistories'])
        ]);
    }

    /**
     * Cancel an order (Customer or Seller/Admin) with stock restoration.
     */
    public function cancelOrder(Request $request, string $id)
    {
        $order = Order::with('items.product')->find($id);
        if (!$order) {
            return $request->expectsJson() 
                ? response()->json(['message' => 'Order not found.'], 404)
                : redirect()->back()->with('error', 'Order not found.');
        }

        $user = $request->user();
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user) {
            return $request->expectsJson() 
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        $isCustomer = ($user->id === $order->customerId);
        $isSeller = ($user->id === $order->sellerId || $user->role === 'admin');

        if (!$isCustomer && !$isSeller) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthorized.'], 403)
                : redirect()->back()->with('error', 'Unauthorized to cancel this order.');
        }

        $currentStatus = strtolower(trim($order->status));

        // Order already cancelled or completed
        if ($currentStatus === 'cancelled') {
            return $request->expectsJson()
                ? response()->json(['message' => 'Order is already cancelled.'], 400)
                : redirect()->back()->with('info', 'Order is already cancelled.');
        }
        if ($currentStatus === 'completed' || $currentStatus === 'delivered') {
            return $request->expectsJson()
                ? response()->json(['message' => 'Delivered or completed orders cannot be cancelled.'], 400)
                : redirect()->back()->with('error', 'Delivered or completed orders cannot be cancelled.');
        }

        // Customer constraint: only while status is Pending
        if ($isCustomer && !$isSeller) {
            if ($currentStatus !== 'pending') {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Orders that have already been accepted or prepared cannot be cancelled directly. Please message the artisan.'], 400)
                    : redirect()->back()->with('error', 'Orders in progress cannot be cancelled directly. Please contact the artisan.');
            }
        }

        // Seller constraint: only while status is Pending (before it is accepted)
        if ($isSeller) {
            if (!in_array($currentStatus, ['pending'], true)) {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Orders that have already been accepted cannot be cancelled directly.'], 400)
                    : redirect()->back()->with('error', 'Orders that have already been accepted cannot be cancelled directly.');
            }
        }

        $reason = trim($request->input('cancellationReason') ?? $request->input('reason') ?? '');
        if (!$reason) {
            $reason = $isCustomer ? 'Cancelled by customer' : 'Cancelled by artisan';
        }

        DB::beginTransaction();
        try {
            // Restore inventory stock for each product
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            $prevStatus = $order->status;
            $order->status = 'Cancelled';
            $order->cancellationReason = $reason;
            $order->save();

            OrderStatusHistory::create([
                'orderId' => $order->id,
                'previousStatus' => $prevStatus,
                'newStatus' => 'Cancelled',
                'updatedBy' => $user->id,
                'userRole' => $isCustomer ? 'customer' : 'seller',
                'notes' => ($isCustomer ? "Order cancelled by customer. Reason: " : "Order cancelled by artisan. Reason: ") . $reason,
            ]);

            // Notify counterpart
            if ($isCustomer) {
                $this->sendNotification(
                    $order->sellerId,
                    'Order Cancelled by Customer',
                    "Order #" . substr($order->id, 0, 8) . " was cancelled by the buyer. Reason: {$reason}. Inventory has been restored.",
                    'order',
                    "/seller/orders?order_id={$order->id}",
                    'seller'
                );

                $sellerUser = User::find($order->sellerId);
                if ($sellerUser && $sellerUser->email) {
                    $mail = new \App\Mail\OrderStatusUpdatedMail(
                        $sellerUser->name,
                        $order->id,
                        'Cancelled',
                        "Buyer cancelled order #{$order->id}. Reason: {$reason}. Inventory has been replenished."
                    );
                    \App\Services\EmailNotificationService::sendNotification($sellerUser->email, $mail, 'order_cancelled', $sellerUser->id, 'Order', $order->id);
                }
            } else {
                $this->sendNotification(
                    $order->customerId,
                    'Order Cancelled by Artisan',
                    "Your order #" . substr($order->id, 0, 8) . " was cancelled by the artisan. Reason: {$reason}.",
                    'order',
                    '/orders/' . $order->id,
                    'customer'
                );

                $customerUser = User::find($order->customerId);
                if ($customerUser && $customerUser->email) {
                    $mail = new \App\Mail\OrderStatusUpdatedMail(
                        $customerUser->name,
                        $order->id,
                        'Cancelled',
                        "Your order has been cancelled by the artisan. Reason: {$reason}."
                    );
                    \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mail, 'order_cancelled', $customerUser->id, 'Order', $order->id);
                }
            }

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*') || $request->is('seller/api/*')) {
                return response()->json([
                    'message' => 'Order has been successfully cancelled.',
                    'order' => $order->load(['seller', 'customer', 'items.product', 'statusHistories'])
                ]);
            }

            return redirect()->back()->with('success', 'Order has been successfully cancelled.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson() || $request->is('api/*') || $request->is('seller/api/*')) {
                return response()->json(['message' => 'Failed to cancel order: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }
}
