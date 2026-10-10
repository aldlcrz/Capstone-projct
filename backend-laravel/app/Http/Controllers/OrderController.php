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
use App\Models\PaymentTransaction;
use App\Models\ShippingProvider;
use App\Models\OrderShipping;
use App\Services\CreateOrderService;
use App\Helpers\ValidationHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\RefundTransaction;
use App\Services\Messaging\LumbarongSystemMessageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected CreateOrderService $createOrderService;

    public function __construct(CreateOrderService $createOrderService)
    {
        $this->createOrderService = $createOrderService;
    }

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
            ->with(['seller:id,name,email,profilePhoto', 'items.product', 'statusHistories', 'shipping'])
            ->orderBy('createdAt', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Get orders for the authenticated seller.
     */
    public function getSellerOrders(Request $request)
    {
        $sellerId = (in_array($request->user()->role, ['admin', 'superadmin'], true) && $request->has('sellerId')) 
            ? $request->sellerId 
            : $request->user()->id;

        $orders = Order::where('sellerId', $sellerId)
            ->with(['customer:id,name,email,profilePhoto', 'items.product', 'statusHistories', 'shipping'])
            ->orderBy('createdAt', 'desc')
            ->get();

        return response()->json($orders);
    }

    /**
     * Create a new order (delegates to canonical CreateOrderService).
     */
    public function createOrder(Request $request)
    {
        try {
            $user = $request->user() ?: Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $order = $this->createOrderService->createOrder([
                'customer' => $user,
                'items' => $request->items,
                'paymentMethod' => $request->paymentMethod ?? 'GCash',
                'paymentReference' => $request->paymentReference,
                'paymentProof' => $request->paymentProof,
                'address_id' => $request->addressId ?? $request->address_id,
                'shippingAddress' => $request->shippingAddress,
                'quoteToken' => $request->quoteToken ?? $request->shipping_token,
                'selectedProviderId' => $request->selectedProviderId ?? $request->provider_id,
                'idempotencyKey' => $request->header('X-Idempotency-Key') ?? $request->idempotencyKey,
                'visitorSessionId' => $request->header('x-visitor-session'),
            ]);

            return response()->json($order->load(['seller', 'items.product', 'shipping', 'latestPaymentTransaction']), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('API Order creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage() ?: 'Failed to create order.'], 500);
        }
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, string $id)
    {
        $order = Order::find($id);
        if (!$order) return response()->json(['message' => 'Order not found'], 404);

        $targetStatus = trim($request->status ?? '') ?: $order->status;
        $normalizedTarget = strtolower($targetStatus);

        if ($normalizedTarget === 'cancelled') {
            return $this->cancelOrder($request, $id);
        }

        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $currentStatus = $order->status;
        $normalizedCurrent = strtolower($currentStatus);

        // Permissions
        if ($user->role === 'customer') {
            if ($order->customerId !== $user->id) return response()->json(['message' => 'Unauthorized'], 403);
            if (!in_array($normalizedTarget, ['received by buyer', 'completed'], true)) {
                return response()->json(['message' => 'Customers can only confirm receipt.'], 403);
            }
            // Problem 06: Customers can only confirm receipt once order has been shipped or delivered
            if (!in_array($normalizedCurrent, ['delivered', 'shipped', 'in transit', 'in_transit'], true)) {
                return response()->json(['message' => 'Cannot confirm delivery until the order has been shipped or delivered.'], 400);
            }
        } elseif ($user->role === 'seller') {
            if ($order->sellerId !== $user->id && $user->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        }

        $currentStatus = $order->status;
        $normalizedCurrent = strtolower($currentStatus);

        // Edit locking for shipping details
        if (in_array($normalizedCurrent, ['in transit', 'in_transit', 'delivered', 'completed', 'cancelled'], true)) {
            $courier = trim($request->courierName ?? '');
            $trackingNum = trim($request->trackingNumber ?? '');
            $trackingLink = trim($request->trackingLink ?? '');
            if (!$order->isStorePickup() && !$order->isSpecialDelivery()) {
                if (($courier && $courier !== $order->courierName) || ($trackingNum && $trackingNum !== $order->trackingNumber) || ($trackingLink && $trackingLink !== $order->trackingLink)) {
                    return response()->json(['message' => 'Shipping information is locked and cannot be edited after order is in transit or delivered.'], 400);
                }
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

        // Shipping info: courier and tracking assignment
        $shippingUpdated = false;
        $isStorePickup = $order->isStorePickup();
        $isSpecialDelivery = $order->isSpecialDelivery();

        if ($isStorePickup) {
            // Guard: Store pickup orders cannot transition to courier-only in-transit states
            if (in_array($canonicalTarget, ['In Transit', 'Out for Delivery'], true)) {
                return response()->json(['message' => 'Store pickup orders do not use physical courier shipment and cannot transition to in-transit states.'], 400);
            }

            // Strictly sanitize & nullify courier data for Store Pickup orders regardless of what was submitted
            $courier = 'Store Pickup';
            $trackingNum = null;
            $trackingLink = null;
            if ($order->courierName !== 'Store Pickup') {
                $order->courierName = 'Store Pickup';
                $shippingUpdated = true;
            }
            if ($order->trackingNumber !== null) {
                $order->trackingNumber = null;
                $shippingUpdated = true;
            }
            if ($order->trackingLink !== null) {
                $order->trackingLink = null;
                $shippingUpdated = true;
            }
        } elseif ($isSpecialDelivery) {
            // Special Delivery workflow: local direct / nearby artisan rider delivery
            // No third-party courier tracking numbers or URLs are required or accepted.
            $courier = 'Special Delivery (Local Artisan Rider)';
            $trackingNum = null;
            $trackingLink = null;

            if ($order->courierName !== $courier) {
                $order->courierName = $courier;
                $shippingUpdated = true;
            }
            if ($order->trackingNumber !== null) {
                $order->trackingNumber = null;
                $shippingUpdated = true;
            }
            if ($order->trackingLink !== null) {
                $order->trackingLink = null;
                $shippingUpdated = true;
            }
        } else {
            // Standard Third-Party Courier Workflow (J&T, SPX, LBC, etc.)
            $courier = trim($request->courierName ?? $order->courierName ?? 'J&T Express');
            $trackingNum = trim($request->trackingNumber ?? $order->trackingNumber ?? '');
            $trackingLink = trim($request->trackingLink ?? $order->trackingLink ?? '');

            // If tracking number was provided, validate its format against selected courier
            if ($trackingNum !== '') {
                $valResult = ValidationHelper::validateCourierTrackingNumber($courier, $trackingNum);
                if (!$valResult['valid']) {
                    return response()->json(['message' => $valResult['error']], 422);
                }
                $trackingNum = $valResult['cleaned'];
            }

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

            // Strictly require valid manual tracking number before moving to In Transit (only for standard courier delivery)
            if (in_array($canonicalTarget, ['In Transit'], true)) {
                $effectiveTracking = $trackingNum ?: $order->trackingNumber;
                if (empty($effectiveTracking)) {
                    return response()->json(['message' => 'Please enter the official courier tracking number before moving to In Transit.'], 422);
                }
                $valResult = ValidationHelper::validateCourierTrackingNumber($courier, $effectiveTracking);
                if (!$valResult['valid']) {
                    return response()->json(['message' => $valResult['error']], 422);
                }
                $order->trackingNumber = $valResult['cleaned'];
            }
        }

        $order->status = $canonicalTarget;
        if ($canonicalTarget === 'To Ship') {
            if (strcasecmp($order->paymentMethod, 'COD') === 0 || strcasecmp($order->paymentMethod, 'Cash on Delivery') === 0) {
                // COD payment remains pending until delivered/claimed
                $order->paymentStatus = 'Pending';
            } else {
                // If payment was rejected, block transition to To Ship without re-verification
                if ($order->paymentStatus === 'Rejected') {
                    return response()->json(['message' => 'Cannot move order to To Ship because payment proof was rejected. Payment must be re-verified.'], 400);
                }
                $order->paymentStatus = 'Verified';
                $order->paymentRejectionReason = null;

                // Seller verification authority: officially marks transaction attempt as VERIFIED
                try {
                    $tx = $order->latestPaymentTransaction;
                    if ($tx) {
                        $tx->update([
                            'status' => 'VERIFIED',
                            'verified_at' => now(),
                            'notes' => 'Payment verified and accepted by seller.',
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Could not mark PaymentTransaction as VERIFIED for order {$order->id}: " . $e->getMessage());
                }
            }
        } elseif ($canonicalTarget === 'Delivered') {
            if (in_array(strtoupper($order->paymentMethod ?? ''), ['COD', 'CASH ON DELIVERY', 'PAY IN SHOP', 'PAY ON CLAIM'], true) || $isStorePickup) {
                if (!in_array(strtolower($order->paymentStatus ?? ''), ['paid', 'verified'], true)) {
                    $order->paymentStatus = 'Paid';
                }
            }
        }
        $order->save();

        // Synchronize mutable fulfillment state on OrderShipping snapshot
        if ($order->shipping) {
            $shippingAttrs = ['shipping_status' => $canonicalTarget];
            if ($isStorePickup) {
                $shippingAttrs['tracking_number'] = null;
                $shippingAttrs['fulfillment_provider_name'] = 'Store Pickup';
            } elseif ($isSpecialDelivery) {
                $shippingAttrs['tracking_number'] = null;
                $shippingAttrs['fulfillment_provider_name'] = 'Special Delivery (Local Artisan Rider)';
            } else {
                if ($order->trackingNumber) {
                    $shippingAttrs['tracking_number'] = $order->trackingNumber;
                }
                if (!empty($order->courierName)) {
                    $shippingAttrs['fulfillment_provider_name'] = $order->courierName;
                    $matchedProvider = ShippingProvider::where('name', $order->courierName)
                        ->orWhere('code', strtolower(str_replace([' ', '&'], ['_', 'and'], $order->courierName)))
                        ->first();
                    if ($matchedProvider) {
                        $shippingAttrs['fulfillment_provider_id'] = $matchedProvider->id;
                    }
                }
            }
            $order->shipping->update($shippingAttrs);
        }

        if ($canonicalCurrent !== $canonicalTarget || $shippingUpdated) {
            OrderStatusHistory::create([
                'orderId' => $order->id,
                'previousStatus' => $canonicalCurrent,
                'newStatus' => $canonicalTarget,
                'updatedBy' => $user->id,
                'userRole' => $user->role,
                'notes' => $request->notes ?? ($canonicalTarget === 'To Ship' ? "Payment verified and order accepted for preparation." : ($canonicalCurrent !== $canonicalTarget ? "Status updated to {$canonicalTarget}." : "Shipping information updated.")),
            ]);

            try {
                \App\Services\Financial\FinancialLedgerService::reconcileSellerSettlementForOrder($order->fresh());
            } catch (\Throwable $e) {
                Log::warning("Could not reconcile seller settlement for order {$order->id}: " . $e->getMessage());
            }
        }

        if ($isStorePickup) {
            $statusMsgMap = [
                'To Ship' => 'Your order is being processed and prepared for in-shop pickup.',
                'Shipped' => 'Your handcrafted order is packed and ready for in-shop pickup at our Lumban workshop!',
                'Delivered' => 'Your order has been claimed and picked up. Please inspect your item and rate your purchase.',
                'Completed' => 'Your store pickup order has been marked as completed.',
            ];
        } elseif ($isSpecialDelivery) {
            $statusMsgMap = [
                'To Ship' => 'Your order is being processed and prepared for special artisan delivery.',
                'Shipped' => 'Your handcrafted order is packed and being prepared for dispatch via local artisan rider.',
                'In Transit' => 'Your order is out for special delivery via local artisan rider.',
                'Delivered' => 'Your order has been delivered by our artisan rider. Please inspect your item and rate your purchase.',
                'Completed' => 'Your special delivery order has been marked as completed.',
            ];
        } else {
            $statusMsgMap = [
                'To Ship' => 'Your order is being processed and prepared for shipping.',
                'Shipped' => "Your order has been shipped via {$order->courierName} (Tracking: {$order->trackingNumber}).",
                'In Transit' => 'Your order is in transit with the courier.',
                'Out for Delivery' => 'Your order is out for delivery today!',
                'Delivered' => 'Your order has been delivered. Please inspect your item and rate your purchase.',
                'Completed' => 'Your order has been marked as completed.',
            ];
        }

        if ($canonicalCurrent === $canonicalTarget && $shippingUpdated) {
            $notifTitle = "Shipping Info Updated";
            $statusMsg = $isStorePickup 
                ? "Your order fulfillment method is confirmed for Store Pickup."
                : ($isSpecialDelivery
                    ? "Your order is scheduled for Special Delivery via local artisan rider."
                    : "Your order shipping details have been updated: {$order->courierName} (Tracking: {$order->trackingNumber}).");
        } else {
            $notifTitle = match(true) {
                $isStorePickup && $canonicalTarget === 'Shipped' => "Ready for Pickup",
                $isStorePickup && $canonicalTarget === 'Delivered' => "Order Picked Up",
                $isSpecialDelivery && $canonicalTarget === 'In Transit' => "Out for Special Delivery",
                $isSpecialDelivery && $canonicalTarget === 'Shipped' => "Special Delivery Processing",
                default => "Order {$canonicalTarget}"
            };
            $statusMsg = $statusMsgMap[$canonicalTarget] ?? "Your order status is now {$canonicalTarget}.";
        }

        $this->sendNotification($order->customerId, $notifTitle, $statusMsg, 'order', '/orders', 'customer');

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
            $refDisplay = in_array(strtoupper($o->paymentMethod ?? ''), ['GCASH', 'MAYA']) && !empty($o->paymentReference) && !str_starts_with($o->paymentReference, 'COD-') ? " | Ref: {$o->paymentReference}" : '';
            fputcsv($handle, [
                'ORDER',
                $o->id,
                "Order from " . ($o->customer->name ?? 'Unknown'),
                "Pay: {$o->paymentMethod}{$refDisplay}",
                number_format($o->totalAmount, 2),
                $o->status,
                $o->createdAt
            ]);

            foreach ($o->items as $item) {
                fputcsv($handle, [
                    'ITEM',
                    '',
                    "  > " . ($item->product_name ?? ($item->product->name ?? 'Archived Heritage Piece')),
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
     * Verify customer claim code and mark Store Pickup order as Claimed/Delivered.
     */
    public function verifyClaimCode(Request $request, string $id)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $order = Order::with(['seller', 'customer', 'shipping', 'items.product', 'statusHistories'])->findOrFail($id);

        if ($user->role !== 'admin' && $order->sellerId !== $user->id) {
            return response()->json(['message' => 'Unauthorized action on this order.'], 403);
        }

        if (!$order->isStorePickup()) {
            return response()->json(['message' => 'Claim code verification is only applicable for Store Pickup orders.'], 400);
        }

        $statusLower = strtolower(trim($order->status ?? ''));
        if (!in_array($statusLower, ['shipped', 'ready for pickup', 'ready_for_pickup'], true)) {
            return response()->json(['message' => 'Order must be in "Ready for Pickup" status before verifying the claim code.'], 400);
        }

        $enteredCode = strtoupper(trim($request->input('claimCode', '')));
        $cleanEntered = str_replace(['#', 'LB-OR-', 'LB-', ' '], '', $enteredCode);
        $expectedSuffix = strtoupper(substr($order->id, -8));
        $fullExpectedCode = 'LB-OR-' . $expectedSuffix;

        if (empty($cleanEntered) || ($cleanEntered !== $expectedSuffix && $enteredCode !== $fullExpectedCode && $enteredCode !== '#' . $fullExpectedCode && $enteredCode !== $order->id)) {
            return response()->json(['message' => 'Invalid claim code. Please ask the customer for their official #LB-OR-' . $expectedSuffix . ' pickup code.'], 422);
        }

        // Mark as Delivered (Claimed)
        $previousStatus = $order->status;
        $order->status = 'Delivered';
        if (strcasecmp($order->paymentMethod ?? '', 'COD') === 0 || strcasecmp($order->paymentMethod ?? '', 'Pay in Shop') === 0 || strcasecmp($order->paymentMethod ?? '', 'Pay on Claim') === 0) {
            $order->paymentStatus = 'Paid';
        }
        $order->save();

        if ($order->shipping) {
            $order->shipping->update([
                'shipping_status' => 'Delivered',
                'fulfillment_provider_name' => 'Store Pickup',
            ]);
        }

        OrderStatusHistory::create([
            'orderId' => $order->id,
            'previousStatus' => $previousStatus,
            'newStatus' => 'Delivered',
            'updatedBy' => $user->id,
            'userRole' => $user->role,
            'notes' => 'Customer claim code verified (#LB-OR-' . $expectedSuffix . '). Order handed over at workshop.',
        ]);

        $statusMsg = 'Your order has been claimed and picked up at our Lumban workshop! Please inspect your item and rate your purchase.';
        $this->sendNotification($order->customerId, 'Order Picked Up', $statusMsg, 'order', '/orders/' . $order->id, 'customer');

        return response()->json([
            'success' => true,
            'message' => '✓ Claim code verified successfully! Order marked as Claimed.',
            'order' => $order->fresh(['customer', 'seller', 'items.product', 'statusHistories', 'shipping']),
        ]);
    }

    /**
     * Dispatch local artisan rider for Special Delivery order.
     */
    public function dispatchSpecialDelivery(Request $request, string $id)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $order = Order::with(['seller', 'customer', 'shipping', 'items.product', 'statusHistories'])->findOrFail($id);

        if ($user->role !== 'admin' && $order->sellerId !== $user->id) {
            return response()->json(['message' => 'Unauthorized action on this order.'], 403);
        }

        if (!$order->isSpecialDelivery()) {
            return response()->json(['message' => 'Rider dispatch is only applicable for Special Delivery orders.'], 400);
        }

        $riderName = trim($request->input('riderName', ''));
        $riderPhone = trim($request->input('riderPhone', ''));
        $riderNotes = trim($request->input('riderNotes', ''));

        $noteParts = array_filter([
            $riderName ? "Rider: {$riderName}" : "Artisan Rider",
            $riderPhone ? "Contact: {$riderPhone}" : null,
            $riderNotes ?: null,
        ]);
        $combinedNotes = implode(' · ', $noteParts);

        $previousStatus = $order->status;
        $order->status = 'In Transit';
        $order->courierName = 'Special Delivery (Local Artisan Rider)';
        $order->trackingNumber = null;
        $order->trackingLink = null;
        $order->save();

        if ($order->shipping) {
            $order->shipping->update([
                'shipping_status' => 'In Transit',
                'fulfillment_provider_name' => 'Special Delivery (Local Artisan Rider)',
            ]);
        }

        OrderStatusHistory::create([
            'orderId' => $order->id,
            'previousStatus' => $previousStatus,
            'newStatus' => 'In Transit',
            'updatedBy' => $user->id,
            'userRole' => $user->role,
            'notes' => 'Dispatched via local artisan rider. ' . $combinedNotes,
        ]);

        $statusMsg = 'Your order is out for special delivery via our dedicated local artisan rider.' . ($riderName ? " (Rider: {$riderName})" : '');
        $this->sendNotification($order->customerId, 'Out for Special Delivery', $statusMsg, 'order', '/orders/' . $order->id, 'customer');

        return response()->json([
            'success' => true,
            'message' => '✓ Rider dispatched! Order is now Out for Special Delivery.',
            'order' => $order->fresh(['customer', 'seller', 'items.product', 'statusHistories', 'shipping']),
        ]);
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

        if ($order->shipping) {
            $order->shipping->update(['shipping_status' => 'Completed']);
        }

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
     * Download or view Store Pickup Receipt PDF.
     */
    public function pickupReceipt(Request $request, string $id)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $order = Order::with(['customer', 'seller', 'items.product', 'shipping', 'statusHistories'])->findOrFail($id);

        // Security check: Buyer can only access their own receipt, seller their own orders, admin/superadmin anytime
        $isBuyer = ($order->customerId === $user->id);
        $isSeller = ($order->sellerId === $user->id);
        $isAdmin = in_array(strtolower($user->role), ['admin', 'superadmin'], true);

        if (!$isBuyer && !$isSeller && !$isAdmin) {
            abort(403, 'Unauthorized. You do not have permission to view or download this order pickup receipt.');
        }

        if (!$order->isStorePickup()) {
            abort(400, 'Pickup receipts are only available for Store Pickup orders.');
        }

        $readyStatusHistory = $order->statusHistories
            ->whereIn('newStatus', ['Shipped', 'Ready for Pickup', 'ready to ship', 'shipped'])
            ->first();

        $readyDate = $readyStatusHistory?->createdAt ?? $order->updatedAt ?? $order->createdAt;
        $pickupCode = 'LB-PU-' . strtoupper(substr(hash('crc32b', 'LUMBAN_PICKUP_' . $order->id), 0, 6));
        $generatedAt = now();

        $pdf = Pdf::loadView('orders.pickup-receipt-pdf', [
            'order'       => $order,
            'pickupCode'  => $pickupCode,
            'readyDate'   => $readyDate,
            'generatedAt' => $generatedAt,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'Pickup-Receipt-LB-OR-' . strtoupper(substr($order->id, -8)) . '.pdf';

        if ($request->query('download') === '1' || $request->is('*download*')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Download or view customer payment receipt proof image securely.
     */
    public function paymentProof(Request $request, string $id)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $order = Order::findOrFail($id);

        // Security check: Buyer, Seller of the order, or Admin
        $isBuyer = ($order->customerId === $user->id);
        $isSeller = ($order->sellerId === $user->id);
        $isAdmin = in_array(strtolower($user->role), ['admin', 'superadmin'], true);

        if (!$isBuyer && !$isSeller && !$isAdmin) {
            abort(403, 'Unauthorized to view this payment receipt.');
        }

        if (empty($order->paymentProof)) {
            abort(404, 'No payment proof uploaded for this order.');
        }

        $proof = trim($order->paymentProof);
        $cleanProof = ltrim($proof, '/');
        $filename = basename($cleanProof);

        // Check possible paths across both local and public disks
        $candidates = [
            storage_path('app/' . $cleanProof),
            storage_path('app/private/' . $cleanProof),
            storage_path('app/private/' . str_replace('private/', '', $cleanProof)),
            storage_path('app/payments/' . $filename),
            storage_path('app/private/payments/' . $filename),
            storage_path('app/public/' . $cleanProof),
            storage_path('app/public/' . str_replace('private/', '', $cleanProof)),
            storage_path('app/public/payments/' . $filename),
            public_path('storage/' . $cleanProof),
            public_path('storage/' . str_replace('private/', '', $cleanProof)),
            public_path('uploads/' . $cleanProof),
            public_path('uploads/payments/' . $filename),
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand) && is_file($cand)) {
                $mimeType = mime_content_type($cand) ?: 'image/jpeg';
                return response()->file($cand, [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'private, max-age=86400',
                ]);
            }
        }

        abort(404, 'Payment proof image file not found on server.');
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
        $ext = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'jpg');
        $filename = time() . '_' . uniqid() . '.' . $ext;
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

            if ($order->shipping) {
                $order->shipping->update(['shipping_status' => 'Cancelled']);
            }

            // Release active reference claim by transitioning PaymentTransaction to REJECTED
            try {
                $tx = $order->latestPaymentTransaction;
                if ($tx) {
                    $tx->update([
                        'status' => 'REJECTED',
                        'active_reference' => null,
                        'notes' => "Rejected by seller. Reason: {$reason}",
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Could not update PaymentTransaction to REJECTED for order {$order->id}: " . $e->getMessage());
            }

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

            // Send official LumBarong inbox message
            LumbarongSystemMessageService::sendOrderCancelledMessage(
                $order,
                "Payment rejected: {$reason}",
                $user->role === 'seller' ? ($user->shopName ?: 'Artisan Seller') : ($user->name ?: 'LumBarong Administration')
            );

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
            'paymentReference'  => 'nullable|string',
            'paymentScreenshot' => 'required|image|max:10240',
        ]);

        $screening = null;
        $path = null;
        if ($request->hasFile('paymentScreenshot')) {
            $tempPath = $request->file('paymentScreenshot')->getRealPath();
            $origName = $request->file('paymentScreenshot')->getClientOriginalName();
            $screening = \App\Services\AiService::verifyReceipt(
                $tempPath,
                (string) $request->input('paymentReference', ''),
                $order->paymentMethod,
                (float) $order->totalAmount,
                $origName,
                $order->id
            );

            if (($screening['status'] ?? '') === 'REJECT' || !($screening['is_receipt'] ?? true)) {
                return response()->json([
                    'message' => $screening['message'] ?? 'The uploaded file does not appear to be a valid payment receipt screenshot.'
                ], 422);
            }

            $path = $request->file('paymentScreenshot')->store('payments', 'public');
            $order->paymentProof = $path;
        }

        $detectedRef = !empty($screening['detected_ref']) ? preg_replace('/\D/', '', (string)$screening['detected_ref']) : null;
        $rawRef = $detectedRef ?: preg_replace('/\D/', '', (string) $request->input('paymentReference', ''));

        if (empty($rawRef)) {
            return response()->json([
                'message' => 'Could not detect a valid transaction reference number from the uploaded receipt. Please attach a clear payment confirmation screenshot.'
            ], 422);
        }

        if (preg_match('/^(\d)\1+$/', $rawRef)) {
            return response()->json(['message' => 'Invalid payment reference number detected. Repeated digit sequences are not allowed.'], 422);
        }
        if ($isGcash && strlen($rawRef) !== 13) {
            return response()->json(['message' => 'GCash reference number must be exactly 13 digits.'], 422);
        } elseif ($isMaya && strlen($rawRef) !== 12) {
            return response()->json(['message' => 'Maya reference number must be exactly 12 digits.'], 422);
        }

        // Duplicate check: cannot use a reference that is active or verified in another transaction
        $isDuplicate = PaymentTransaction::where('active_reference', $rawRef)
            ->where('order_id', '!=', $order->id)
            ->exists();
        if ($isDuplicate) {
            return response()->json([
                'message' => 'This payment reference number is already tied to an active or verified order. Please provide a new and unique payment reference.'
            ], 422);
        }

        $order->paymentReference = $rawRef;
        $order->paymentStatus = 'Payment Submitted';
        $order->paymentRejectionReason = null;
        $order->save();

        // Multi-attempt audit trail: Create a new PaymentTransaction attempt for this resubmission
        try {
            $detectedAmt = isset($screening['detected_amount']) && is_numeric($screening['detected_amount'])
                ? (float) $screening['detected_amount']
                : null;
            $tier = in_array(($screening['status'] ?? ''), ['PASS', 'REVIEW', 'REJECT'])
                ? $screening['status']
                : 'REVIEW';

            PaymentTransaction::create([
                'order_id' => $order->id,
                'customer_id' => $user->id,
                'seller_id' => $order->sellerId,
                'reference_number' => $rawRef,
                'active_reference' => $rawRef,
                'wallet_type' => $order->paymentMethod,
                'expected_amount' => (float) $order->totalAmount,
                'detected_amount' => $detectedAmt,
                'amount_confidence' => $screening['amount_confidence'] ?? null,
                'reference_confidence' => $screening['reference_confidence'] ?? null,
                'confidence' => $screening['confidence'] ?? null,
                'status' => 'UNVERIFIED',
                'verification_tier' => $tier,
                'receipt_path' => $path,
                'notes' => $screening['message'] ?? 'Customer payment proof resubmission',
            ]);
        } catch (\Throwable $e) {
            Log::warning("Could not record resubmitted PaymentTransaction attempt for order {$order->id}: " . $e->getMessage());
        }

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
            'order' => $order->load(['seller', 'items.product', 'statusHistories', 'paymentTransactions'])
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
        $isSeller = ($user->id === $order->sellerId || in_array($user->role, ['admin', 'superadmin'], true));

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
        if (in_array($currentStatus, ['cancellation pending', 'cancellation requested'], true)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Your cancellation request is already pending artisan approval.'], 400)
                : redirect()->back()->with('info', 'Your cancellation request is already pending artisan approval.');
        }
        if ($currentStatus === 'completed' || $currentStatus === 'delivered') {
            return $request->expectsJson()
                ? response()->json(['message' => 'Delivered or completed orders cannot be cancelled.'], 400)
                : redirect()->back()->with('error', 'Delivered or completed orders cannot be cancelled.');
        }

        // Customer constraint: only while status is Pending.
        // Sets status to 'Cancellation Pending' awaiting artisan approval. Stock remains reserved.
        if ($isCustomer && !$isSeller) {
            if ($currentStatus !== 'pending') {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Orders that have already been accepted or prepared cannot be cancelled directly. Please message the artisan.'], 400)
                    : redirect()->back()->with('error', 'Orders in progress cannot be cancelled directly. Please contact the artisan.');
            }

            $reason = trim($request->input('cancellationReason') ?? $request->input('reason') ?? 'Need to change order details');

            DB::beginTransaction();
            try {
                $prevStatus = $order->status;
                $order->status = 'Cancellation Pending';
                $order->cancellationReason = $reason;
                $order->save();

                OrderStatusHistory::create([
                    'orderId' => $order->id,
                    'previousStatus' => $prevStatus,
                    'newStatus' => 'Cancellation Pending',
                    'updatedBy' => $user->id,
                    'userRole' => 'customer',
                    'notes' => "Cancellation requested by customer. Reason: {$reason}",
                ]);

                // Notify artisan of cancellation request
                $this->sendNotification(
                    $order->sellerId,
                    'Cancellation Request Received',
                    "Buyer requested cancellation for order #" . substr($order->id, 0, 8) . ". Reason: {$reason}. Please review and approve or decline.",
                    'order',
                    "/seller/orders?order_id={$order->id}",
                    'seller'
                );

                $sellerUser = User::find($order->sellerId);
                if ($sellerUser && $sellerUser->email) {
                    $mail = new \App\Mail\OrderStatusUpdatedMail(
                        $sellerUser->name,
                        $order->id,
                        'Cancellation Requested',
                        "Buyer requested cancellation for order #{$order->id}. Reason: {$reason}. Please review this request in your artisan dashboard."
                    );
                    \App\Services\EmailNotificationService::sendNotification($sellerUser->email, $mail, 'order_cancellation_requested', $sellerUser->id, 'Order', $order->id);
                }

                DB::commit();

                if ($request->expectsJson() || $request->is('api/*') || $request->is('seller/api/*')) {
                    return response()->json([
                        'message' => 'Cancellation request submitted. Awaiting artisan approval.',
                        'order' => $order->load(['seller', 'customer', 'items.product', 'statusHistories'])
                    ]);
                }

                return redirect()->back()->with('success', 'Cancellation request submitted. Awaiting artisan approval.');

            } catch (\Exception $e) {
                DB::rollBack();
                if ($request->expectsJson() || $request->is('api/*') || $request->is('seller/api/*')) {
                    return response()->json(['message' => 'Failed to submit cancellation request: ' . $e->getMessage()], 500);
                }
                return redirect()->back()->with('error', 'Failed to submit cancellation request: ' . $e->getMessage());
            }
        }

        // Seller / Admin constraint: only while status is Pending (before fulfillment starts)
        if ($isSeller) {
            if (!in_array($currentStatus, ['pending', 'cancellation pending', 'cancellation requested'], true)) {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Orders that have already been accepted or shipped cannot be cancelled directly.'], 400)
                    : redirect()->back()->with('error', 'Orders that have already been accepted or shipped cannot be cancelled directly.');
            }
        }

        $reason = trim($request->input('cancellationReason') ?? $request->input('reason') ?? 'Cancelled by artisan');

        DB::beginTransaction();
        try {
            $prevStatus = $order->status;
            // Restore inventory stock for each product & size exactly once
            if ($prevStatus !== 'Cancelled') {
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock', $item->quantity);
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
            $order->cancellationReason = $reason;
            $order->save();

            if ($order->shipping) {
                $order->shipping->update(['shipping_status' => 'Cancelled']);
            }

            // Release active reference claim for unverified transactions when order is cancelled
            try {
                $tx = $order->latestPaymentTransaction;
                if ($tx && $tx->status === 'UNVERIFIED') {
                    $tx->update([
                        'status' => 'VOID',
                        'active_reference' => null,
                        'notes' => "Order cancelled: {$reason}",
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Could not release active reference for cancelled order {$order->id}: " . $e->getMessage());
            }

            OrderStatusHistory::create([
                'orderId' => $order->id,
                'previousStatus' => $prevStatus,
                'newStatus' => 'Cancelled',
                'updatedBy' => $user->id,
                'userRole' => 'seller',
                'notes' => "Order cancelled by artisan. Reason: {$reason}",
            ]);

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

            // Send official LumBarong inbox message
            LumbarongSystemMessageService::sendOrderCancelledMessage(
                $order,
                $reason,
                $user->role === 'seller' ? ($user->shopName ?: 'Artisan Seller') : ($user->name ?: 'LumBarong Administration')
            );

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*') || $request->is('seller/api/*')) {
                return response()->json([
                    'message' => 'Order has been successfully cancelled and stock restored.',
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

    /**
     * Seller or Admin approves a customer's cancellation request.
     * Restores inventory stock and transitions status to 'Cancelled'.
     */
    public function approveCancellation(Request $request, string $id)
    {
        $order = Order::with('items.product')->find($id);
        if (!$order) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Order not found.'], 404)
                : redirect()->back()->with('error', 'Order not found.');
        }

        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        $isSeller = ($user->id === $order->sellerId || in_array($user->role, ['admin', 'superadmin'], true));
        if (!$isSeller) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthorized.'], 403)
                : redirect()->back()->with('error', 'Unauthorized.');
        }

        $statusLower = strtolower(trim($order->status));
        if (!in_array($statusLower, ['cancellation pending', 'cancellation requested'], true)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'This order does not have an active cancellation request.'], 400)
                : redirect()->back()->with('error', 'This order does not have an active cancellation request.');
        }

        DB::beginTransaction();
        try {
            $prevStatus = $order->status;
            // Restore inventory stock exactly once if not already cancelled
            if ($prevStatus !== 'Cancelled') {
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock', $item->quantity);
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
            $order->save();

            // Release active reference claim for unverified transactions upon cancellation approval
            try {
                $tx = $order->latestPaymentTransaction;
                if ($tx && $tx->status === 'UNVERIFIED') {
                    $tx->update([
                        'status' => 'VOID',
                        'active_reference' => null,
                        'notes' => 'Order cancellation approved by artisan.',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning("Could not release active reference for approved cancellation of order {$order->id}: " . $e->getMessage());
            }

            OrderStatusHistory::create([
                'orderId' => $order->id,
                'previousStatus' => $prevStatus,
                'newStatus' => 'Cancelled',
                'updatedBy' => $user->id,
                'userRole' => 'seller',
                'notes' => 'Cancellation request approved by artisan. Product stock restored to inventory.',
            ]);

            // Notify customer
            $this->sendNotification(
                $order->customerId,
                'Cancellation Approved',
                "Your cancellation request for order #" . substr($order->id, 0, 8) . " has been approved by the artisan. The order has been cancelled and stock replenished.",
                'order',
                '/orders/' . $order->id,
                'customer'
            );

            $customerUser = User::find($order->customerId);
            if ($customerUser && $customerUser->email) {
                $mail = new \App\Mail\OrderStatusUpdatedMail(
                    $customerUser->name,
                    $order->id,
                    'Cancellation Approved',
                    "Your cancellation request for order #{$order->id} has been approved by the artisan. The order is now cancelled."
                );
                \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mail, 'order_cancellation_approved', $customerUser->id, 'Order', $order->id);
            }

            // Send official LumBarong inbox message
            LumbarongSystemMessageService::sendOrderCancelledMessage(
                $order,
                $order->cancellationReason ?: 'Cancellation request approved by artisan',
                $user->role === 'seller' ? ($user->shopName ?: 'Artisan Seller') : ($user->name ?: 'LumBarong Administration')
            );

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*') || $request->is('seller/api/*')) {
                return response()->json([
                    'message' => 'Cancellation approved. Order is cancelled and stock has been restored.',
                    'order' => $order->load(['seller', 'customer', 'items.product', 'statusHistories'])
                ]);
            }

            return redirect()->back()->with('success', 'Cancellation approved. Order is cancelled and stock has been restored.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson() || $request->is('api/*') || $request->is('seller/api/*')) {
                return response()->json(['message' => 'Failed to approve cancellation: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to approve cancellation: ' . $e->getMessage());
        }
    }

    /**
     * Seller or Admin declines a customer's cancellation request.
     * Order reverts to 'Pending' (stock remains reserved).
     */
    public function rejectCancellation(Request $request, string $id)
    {
        $order = Order::with('items.product')->find($id);
        if (!$order) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Order not found.'], 404)
                : redirect()->back()->with('error', 'Order not found.');
        }

        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        $isSeller = ($user->id === $order->sellerId || in_array($user->role, ['admin', 'superadmin'], true));
        if (!$isSeller) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthorized.'], 403)
                : redirect()->back()->with('error', 'Unauthorized.');
        }

        $statusLower = strtolower(trim($order->status));
        if (!in_array($statusLower, ['cancellation pending', 'cancellation requested'], true)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'This order does not have an active cancellation request.'], 400)
                : redirect()->back()->with('error', 'This order does not have an active cancellation request.');
        }

        $reason = trim($request->input('reason') ?? $request->input('declineReason') ?? 'Order is already being prepared or crafted.');

        DB::beginTransaction();
        try {
            $prevStatus = $order->status;
            $order->status = 'Pending';
            $order->save();

            OrderStatusHistory::create([
                'orderId' => $order->id,
                'previousStatus' => $prevStatus,
                'newStatus' => 'Pending',
                'updatedBy' => $user->id,
                'userRole' => 'seller',
                'notes' => "Cancellation request declined by artisan. Reason: {$reason}",
            ]);

            // Notify customer
            $this->sendNotification(
                $order->customerId,
                'Cancellation Request Declined',
                "Your cancellation request for order #" . substr($order->id, 0, 8) . " was declined by the artisan. Reason: {$reason}. Order will proceed.",
                'order',
                '/orders/' . $order->id,
                'customer'
            );

            $customerUser = User::find($order->customerId);
            if ($customerUser && $customerUser->email) {
                $mail = new \App\Mail\OrderStatusUpdatedMail(
                    $customerUser->name,
                    $order->id,
                    'Cancellation Declined',
                    "Your cancellation request for order #{$order->id} was declined by the artisan. Reason: {$reason}. Your order will proceed as scheduled."
                );
                \App\Services\EmailNotificationService::sendNotification($customerUser->email, $mail, 'order_cancellation_declined', $customerUser->id, 'Order', $order->id);
            }

            DB::commit();

            if ($request->expectsJson() || $request->is('api/*') || $request->is('seller/api/*')) {
                return response()->json([
                    'message' => 'Cancellation request declined. Order returned to Pending status.',
                    'order' => $order->load(['seller', 'customer', 'items.product', 'statusHistories'])
                ]);
            }

            return redirect()->back()->with('success', 'Cancellation request declined. Order returned to Pending status.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson() || $request->is('api/*') || $request->is('seller/api/*')) {
                return response()->json(['message' => 'Failed to decline cancellation: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to decline cancellation: ' . $e->getMessage());
        }
    }

    /**
     * Securely stream / download the outgoing refund proof receipt.
     * Authorization strictly enforced: only Customer, Seller, or Admin/SuperAdmin can access.
     */
    public function viewRefundProof(Request $request, string $orderId, string $refundId)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        $order = Order::find($orderId);
        if (!$order) {
            abort(404, 'Order not found.');
        }

        $refund = RefundTransaction::where('order_id', $order->id)
            ->where('id', $refundId)
            ->first();

        if (!$refund) {
            abort(404, 'Refund transaction record not found.');
        }

        $isCustomer = ((string) $user->id === (string) $order->customerId);
        $isSeller = ((string) $user->id === (string) $order->sellerId);
        $isAdmin = in_array($user->role, ['admin', 'superadmin'], true);

        if (!$isCustomer && !$isSeller && !$isAdmin) {
            abort(403, 'Unauthorized. You do not have permission to access this refund proof document.');
        }

        if (empty($refund->transfer_proof_path)) {
            abort(404, 'No refund proof document attached to this transaction.');
        }

        $proofPath = $refund->transfer_proof_path;

        // Check disks
        $diskPath = null;
        if (Storage::disk('public')->exists($proofPath)) {
            $diskPath = Storage::disk('public')->path($proofPath);
        } elseif (Storage::disk('local')->exists($proofPath)) {
            $diskPath = Storage::disk('local')->path($proofPath);
        } elseif (file_exists(storage_path('app/public/' . $proofPath))) {
            $diskPath = storage_path('app/public/' . $proofPath);
        } elseif (file_exists(storage_path('app/' . $proofPath))) {
            $diskPath = storage_path('app/' . $proofPath);
        } elseif (file_exists(public_path('storage/' . $proofPath))) {
            $diskPath = public_path('storage/' . $proofPath);
        }

        if (!$diskPath || !file_exists($diskPath)) {
            abort(404, 'Refund proof file not found on disk.');
        }

        $mimeType = mime_content_type($diskPath) ?: 'image/jpeg';
        $filename = 'Refund_Proof_' . strtoupper(substr($order->id, -8)) . '.' . pathinfo($diskPath, PATHINFO_EXTENSION);

        return response()->file($diskPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Seller records an actual direct payment received for Store Pickup, Special Delivery, or COD.
     */
    public function recordSellerDirectPayment(Request $request, $id, \App\Services\Orders\RecordSellerPaymentService $service)
    {
        $request->validate([
            'payment_method'   => 'required|string|max:50',
            'amount_received'  => 'required|numeric|min:0.01',
            'received_at'      => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'payment_proof'    => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $order = Order::findOrFail($id);
        $seller = Auth::user();

        $updatedOrder = $service->recordPayment(
            $order,
            $seller,
            $request->input('payment_method'),
            (float) $request->input('amount_received'),
            $request->filled('received_at') ? new \DateTime($request->input('received_at')) : now(),
            $request->input('reference_number'),
            $request->file('payment_proof'),
            $request->input('notes')
        );

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Direct payment successfully recorded.',
                'order'   => $updatedOrder->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}

