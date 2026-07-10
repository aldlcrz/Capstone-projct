<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Address;
use App\Models\SystemSetting;
use App\Models\Notification;
use App\Utils\SocketUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    private function parseStoredList(mixed $value)
    {
        if (is_array($value)) return $value;
        if (!is_string($value)) return [];
        $trimmed = trim($value);
        if (!$trimmed) return [];
        try {
            $parsed = json_decode($trimmed, true);
            if (is_array($parsed)) return $parsed;
        } catch (\Exception $e) {}
        return [];
    }

    public function createOrder(Request $request)
    {
        $user = Auth::user();
        
        // Global Maintenance Mode Guard
        $maintenanceSetting = SystemSetting::where('key', 'maintenanceMode')->first();
        $isMaintenance = $maintenanceSetting && ($maintenanceSetting->value === true || $maintenanceSetting->value === "true");
        
        if ($isMaintenance && $user->role !== 'admin') {
            return response()->json([
                'message' => "Transactions are temporarily paused for maintenance. Please try again later.",
                'maintenanceMode' => true
            ], 403);
        }

        if ($user->status === 'blocked' || $user->status === 'frozen') {
            return response()->json(['message' => "This account is {$user->status} and cannot place new orders."], 403);
        }

        if ($user->role === 'admin') {
            return response()->json(['message' => 'Admins are not allowed to place orders'], 403);
        }

        $items = $request->input('items');
        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        if (!is_array($items) || count($items) === 0) {
            return response()->json(['message' => 'Order items are required'], 400);
        }

        $shippingAddress = $request->input('shippingAddress');
        if (is_string($shippingAddress)) {
            $shippingAddress = json_decode($shippingAddress, true);
        }

        $addressId = $request->input('addressId');
        if ($addressId && !$shippingAddress) {
            $addressRecord = Address::where('id', $addressId)->where('userId', $user->id)->first();
            if (!$addressRecord) {
                return response()->json(['message' => 'Selected address not found'], 404);
            }
            $shippingAddress = $addressRecord->toArray();
        }

        if (!$shippingAddress) {
            return response()->json(['message' => 'Shipping address or addressId is required'], 400);
        }

        $paymentMethod = $request->input('paymentMethod');
        $paymentReference = $request->input('paymentReference');
        $paymentProof = $request->input('paymentProof');

        $validPaymentMethods = ['GCash', 'Maya'];
        if (!in_array($paymentMethod, $validPaymentMethods)) {
            return response()->json(['message' => 'Invalid payment method selected. Only GCash and Maya are supported.'], 400);
        }

        // Full state snapshot
        $shippingAddress['customerName'] = $user->name;
        $shippingAddress['contactNumber'] = $user->mobileNumber ?: ($shippingAddress['phone'] ?? '');

        try {
            $order = DB::transaction(function () use ($user, $items, $shippingAddress, $paymentMethod, $paymentReference, $paymentProof, $request) {
                $preparedItems = [];
                $calculatedTotalPrice = 0;
                $sellerId = null;

                foreach ($items as $item) {
                    $productId = $item['productId'] ?? $item['id'] ?? $item['product'] ?? null;
                    $quantity = (int) ($item['quantity'] ?? 0);

                    if ($quantity <= 0) {
                        throw new \Exception('All item quantities must be greater than zero', 400);
                    }

                    // Row lock for update
                    $product = Product::lockForUpdate()->find($productId);
                    if (!$product) {
                        throw new \Exception("Artisan product not found: {$productId}", 404);
                    }

                    // Check per-size stock
                    $sizes = $this->parseStoredList($product->sizes);
                    if (count($sizes) > 0 && isset($sizes[0]['size'])) {
                        $sizeInfo = null;
                        foreach ($sizes as $s) {
                            if (($s['size'] ?? '') == ($item['size'] ?? '')) {
                                $sizeInfo = $s;
                                break;
                            }
                        }
                        if ($sizeInfo) {
                            if ((int) ($sizeInfo['stock'] ?? 0) < $quantity) {
                                throw new \Exception("Size {$item['size']} of {$product->name} is out of stock", 400);
                            }
                        }
                    }

                    if ($product->stock < $quantity) {
                        throw new \Exception("{$product->name} does not have enough total stock", 400);
                    }

                    if ($sellerId && $sellerId !== $product->sellerId) {
                        throw new \Exception('Orders can only contain products from one seller', 400);
                    }

                    $sellerId = $sellerId ?: $product->sellerId;
                    $unitPrice = (float) $product->price;
                    $itemTotal = $unitPrice * $quantity;
                    $calculatedTotalPrice += $itemTotal;

                    // Payment method validation
                    if ($paymentMethod === 'GCash' && $product->allowGcash === false) {
                        throw new \Exception("The product \"{$product->name}\" does not support GCash payments.", 400);
                    }
                    if ($paymentMethod === 'Maya' && $product->allowMaya === false) {
                        throw new \Exception("The product \"{$product->name}\" does not support Maya payments.", 400);
                    }

                    $preparedItems[] = [
                        'product' => $product,
                        'productId' => $product->id,
                        'quantity' => $quantity,
                        'price' => $unitPrice,
                        'size' => $item['size'] ?? 'M',
                        'variation' => $item['variation'] ?? 'Original',
                    ];
                }

                $deliveryFee = 0;
                $finalTotal = $calculatedTotalPrice + $deliveryFee;

                $shippingAddress['itemSnapshots'] = array_map(function ($item) {
                    return [
                        'name' => $item['product']->name,
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'size' => $item['size'],
                        'variation' => $item['variation']
                    ];
                }, $preparedItems);

                $paymentProofUrl = $paymentProof;
                if ($request->hasFile('paymentProof')) {
                    $file = $request->file('paymentProof');
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/payments'), $fileName);
                    $paymentProofUrl = "/uploads/payments/{$fileName}";
                }

                $newOrder = Order::create([
                    'customerId' => $user->id,
                    'sellerId' => $sellerId,
                    'totalAmount' => $finalTotal,
                    'shippingAddress' => $shippingAddress,
                    'paymentMethod' => $paymentMethod,
                    'paymentReference' => $paymentReference,
                    'paymentProof' => $paymentProofUrl,
                    'status' => 'pending',
                ]);

                foreach ($preparedItems as $item) {
                    $prod = $item['product'];
                    $sizes = $this->parseStoredList($prod->sizes);
                    if (count($sizes) > 0 && isset($sizes[0]['size'])) {
                        foreach ($sizes as $idx => $s) {
                            if (($s['size'] ?? '') == $item['size']) {
                                $sizes[$idx]['stock'] = ((int) ($s['stock'] ?? 0)) - $item['quantity'];
                            }
                        }
                        $prod->sizes = $sizes;
                    }
                    $prod->stock -= $item['quantity'];
                    $prod->save();

                    OrderItem::create([
                        'orderId' => $newOrder->id,
                        'productId' => $item['productId'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'size' => $item['size'],
                        'variation' => $item['variation'],
                    ]);
                }

                return $newOrder;
            });

            // Sockets & Notifications
            $fullOrder = Order::with(['customer:id,name,email', 'seller:id,name,email', 'items.product'])->find($order->id);
            SocketUtility::emitOrderCreated($fullOrder);

            Notification::create([
                'userId' => $order->sellerId,
                'title' => 'New Order Received',
                'message' => "You have received a new order from {$user->name} totaling PHP " . number_format($order->totalAmount, 2),
                'type' => 'order_update',
                'link' => "/seller/orders?id={$order->id}",
                'targetRole' => 'seller'
            ]);
            SocketUtility::emitToUser($order->sellerId, 'new_notification', [
                'title' => 'New Order Received',
                'message' => "You have received a new order from {$user->name} totaling PHP " . number_format($order->totalAmount, 2),
            ]);

            return response()->json($fullOrder, 201);

        } catch (\Exception $e) {
            $code = $e->getCode();
            $statusCode = ($code >= 400 && $code < 600) ? $code : 400;
            return response()->json(['message' => $e->getMessage()], $statusCode);
        }
    }

    public function getOrders(Request $request)
    {
        try {
            $user = Auth::user();
            $status = $request->query('status');
            
            $query = Order::query();

            if ($user->role === 'customer') {
                $query->where('customerId', $user->id);
            } elseif ($user->role === 'seller') {
                $query->where('sellerId', $user->id);
            }

            if ($status) {
                $query->where('status', $status);
            }

            $orders = $query->with(['customer:id,name,email', 'seller:id,name,email', 'items.product', 'reviews'])
                ->orderBy('createdAt', 'DESC')
                ->get();

            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getOrderById(string $id)
    {
        try {
            $user = Auth::user();
            $order = Order::with(['customer:id,name,email', 'seller:id,name,email', 'items.product', 'reviews'])->find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            if ($user->role === 'customer' && $order->customerId !== $user->id) {
                return response()->json(['message' => 'Access denied'], 403);
            }
            if ($user->role === 'seller' && $order->sellerId !== $user->id) {
                return response()->json(['message' => 'Access denied'], 403);
            }

            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function updateOrderStatus(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $status = $request->input('status');
            
            $order = Order::find($id);
            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            if ($user->role === 'seller' && $order->sellerId !== $user->id) {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $order->update(['status' => $status]);
            $fullOrder = Order::with(['customer:id,name,email', 'seller:id,name,email', 'items.product'])->find($order->id);

            SocketUtility::emitOrderUpdated($fullOrder);

            Notification::create([
                'userId' => $order->customerId,
                'title' => 'Order Updated',
                'message' => "Your order status has been updated to {$status}",
                'type' => 'order_update',
                'link' => "/orders?id={$order->id}",
                'targetRole' => 'customer'
            ]);
            SocketUtility::emitToUser($order->customerId, 'new_notification', [
                'title' => 'Order Updated',
                'message' => "Your order status has been updated to {$status}",
            ]);

            return response()->json($fullOrder);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function cancelOrder(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $reason = $request->input('cancellationReason');

            $order = Order::find($id);
            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            if ($user->role === 'customer' && $order->customerId !== $user->id) {
                return response()->json(['message' => 'Access denied'], 403);
            }
            if ($user->role === 'seller' && $order->sellerId !== $user->id) {
                return response()->json(['message' => 'Access denied'], 403);
            }

            DB::transaction(function () use ($order, $reason) {
                // Restock items
                $items = OrderItem::where('orderId', $order->id)->get();
                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->find($item->productId);
                    if ($product) {
                        $sizes = $this->parseStoredList($product->sizes);
                        if (count($sizes) > 0 && isset($sizes[0]['size'])) {
                            foreach ($sizes as $idx => $s) {
                                if (($s['size'] ?? '') == $item->size) {
                                    $sizes[$idx]['stock'] = ((int) ($s['stock'] ?? 0)) + $item->quantity;
                                }
                            }
                            $product->sizes = $sizes;
                        }
                        $product->stock += $item->quantity;
                        $product->save();
                    }
                }
                $order->update([
                    'status' => 'cancelled',
                    'cancellationReason' => $reason
                ]);
            });

            $fullOrder = Order::with(['customer:id,name,email', 'seller:id,name,email', 'items.product'])->find($order->id);
            SocketUtility::emitOrderUpdated($fullOrder);

            // Notify counterpart
            $targetUserId = ($user->role === 'customer') ? $order->sellerId : $order->customerId;
            $targetRole = ($user->role === 'customer') ? 'seller' : 'customer';

            Notification::create([
                'userId' => $targetUserId,
                'title' => 'Order Cancelled',
                'message' => "Order #{$order->id} was cancelled by the counterpart.",
                'type' => 'order_update',
                'link' => $targetRole === 'seller' ? "/seller/orders?id={$order->id}" : "/orders?id={$order->id}",
                'targetRole' => $targetRole
            ]);
            SocketUtility::emitToUser($targetUserId, 'new_notification', [
                'title' => 'Order Cancelled',
                'message' => "Order #{$order->id} was cancelled by the counterpart.",
            ]);

            return response()->json($fullOrder);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function exportOrdersCsv()
    {
        try {
            $user = Auth::user();
            $query = Order::query();

            if ($user->role === 'seller') {
                $query->where('sellerId', $user->id);
            }

            $orders = $query->with(['customer', 'items.product'])->get();

            $csvData = "Order ID,Customer,Total Amount,Status,Date\n";
            foreach ($orders as $order) {
                $csvData .= "{$order->id},\"{$order->customer->name}\",{$order->totalAmount},{$order->status},\"{$order->createdAt}\"\n";
            }

            return response($csvData, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="orders.csv"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function approveCancellation(string $id)
    {
        try {
            $sellerId = Auth::id();
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }
            if ($order->sellerId !== $sellerId) {
                return response()->json(['message' => 'Only the seller can approve cancellation'], 403);
            }
            if ($order->status !== 'Cancellation Pending') {
                return response()->json(['message' => 'This order does not have a pending cancellation request'], 400);
            }

            $previousStatus = $order->status;
            DB::transaction(function () use ($order) {
                $items = OrderItem::where('orderId', $order->id)->get();
                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->find($item->productId);
                    if ($product) {
                        $sizes = $this->parseStoredList($product->sizes);
                        if (count($sizes) > 0 && isset($sizes[0]['size'])) {
                            foreach ($sizes as $idx => $s) {
                                if (($s['size'] ?? '') == $item->size) {
                                    $sizes[$idx]['stock'] = ((int) ($s['stock'] ?? 0)) + $item->quantity;
                                }
                            }
                            $product->sizes = $sizes;
                        }
                        $product->stock += $item->quantity;
                        $product->save();
                    }
                }
                $order->update(['status' => 'Cancelled']);
            });

            $fullOrder = Order::with(['customer:id,name,email', 'seller:id,name,email', 'items.product'])->find($order->id);
            SocketUtility::emitOrderUpdated($fullOrder);

            Notification::create([
                'userId' => $order->customerId,
                'title' => 'Order Cancellation Approved',
                'message' => 'Your order cancellation request has been approved.',
                'type' => 'order_update',
                'link' => '/orders',
                'targetRole' => 'customer'
            ]);
            SocketUtility::emitToUser($order->customerId, 'new_notification', [
                'title' => 'Order Cancellation Approved',
                'message' => 'Your order cancellation request has been approved.'
            ]);

            return response()->json($fullOrder);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function rejectCancellation(string $id)
    {
        try {
            $sellerId = Auth::id();
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }
            if ($order->sellerId !== $sellerId) {
                return response()->json(['message' => 'Only the seller can reject cancellation'], 403);
            }
            if ($order->status !== 'Cancellation Pending') {
                return response()->json(['message' => 'This order does not have a pending cancellation request'], 400);
            }

            $order->update([
                'status' => 'Processing',
                'cancellationReason' => null
            ]);

            $fullOrder = Order::with(['customer:id,name,email', 'seller:id,name,email', 'items.product'])->find($order->id);
            SocketUtility::emitOrderUpdated($fullOrder);

            Notification::create([
                'userId' => $order->customerId,
                'title' => 'Order Cancellation Rejected',
                'message' => 'Your order cancellation request has been rejected by the artisan.',
                'type' => 'order_update',
                'link' => '/orders',
                'targetRole' => 'customer'
            ]);
            SocketUtility::emitToUser($order->customerId, 'new_notification', [
                'title' => 'Order Cancellation Rejected',
                'message' => 'Your order cancellation request has been rejected by the artisan.'
            ]);

            return response()->json($fullOrder);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function exportSellerReport()
    {
        try {
            $sellerId = Auth::id();

            $orders = Order::where('sellerId', $sellerId)
                ->with(['customer', 'items.product'])
                ->orderBy('createdAt', 'DESC')
                ->get();

            $products = Product::where('sellerId', $sellerId)
                ->orderBy('name', 'ASC')
                ->get();

            $csv = "Type,ID,Title,Details,Amount,Status,Date\n";
            $csv .= "--- ORDERS ---,,,,,\n";

            foreach ($orders as $o) {
                $customerName = $o->customer ? $o->customer->name : 'Unknown';
                $customerContact = $o->customer ? ($o->customer->mobileNumber ?: $o->customer->email) : 'N/A';
                $addr = $o->shippingAddress;
                $location = isset($addr['city']) ? "{$addr['city']}, {$addr['province']}" : 'N/A';

                $csv .= "ORDER,{$o->id},\"Order from {$customerName}\",\"Contact: {$customerContact} \| Location: {$location} \| Pay: {$o->paymentMethod}\"," . number_format($o->totalAmount, 2) . ",{$o->status},\"{$o->createdAt}\"\n";

                foreach ($o->items as $item) {
                    $prodName = $item->product ? $item->product->name : 'Deleted Product';
                    $itemTotal = $item->quantity * (float) $item->price;
                    $csv .= "ITEM,,\"  > {$prodName}\",\"Qty: {$item->quantity} @ ₱" . number_format($item->price, 2) . "\"," . number_format($itemTotal, 2) . ",,\n";
                }
                $csv .= ",,,,,,\n";
            }

            $csv .= "--- INVENTORY ---,,,,,\n";
            foreach ($products as $p) {
                $cat = is_array($p->categories) ? implode(', ', $p->categories) : ($p->categories ?: 'Uncategorized');
                $csv .= "PRODUCT,{$p->id},\"{$p->name}\",\"Category: {$cat} \| Stock: {$p->stock}\"," . number_format($p->price, 2) . ",{$p->status},-\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="seller_report_' . time() . '.csv"',
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error generating report', 'error' => $e->getMessage()], 500);
        }
    }
}
