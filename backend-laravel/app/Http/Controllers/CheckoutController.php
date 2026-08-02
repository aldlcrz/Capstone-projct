<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Support\VariationFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->query('mode', 'cart');
        $cart = [];

        // Handle direct buy from product page
        if ($request->has('productId')) {
            $product = Product::findOrFail($request->productId);
            // Safe image resolution: JSON decode if necessary
            $images = is_string($product->image) ? json_decode((string) $product->image, true) : (is_array($product->image) ? $product->image : [$product->image]);
            $image = (is_array($images) && count($images) > 0) ? $images[0] : null;

            $directItem = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->sale_price,
                'image' => $image,
                'quantity' => $request->input('quantity', 1),
                'size' => $request->input('size'),
                'variation' => VariationFormatter::label($request->input('variation'), $product->image)
                    ?? $request->input('variation'),
                'sellerId' => $product->sellerId,
                'shippingFee' => $product->shippingFee ?? 0,
                'original_price' => $product->price,
                'discount_percentage' => $product->discount_percentage,
                'is_on_sale' => $product->is_on_sale && ($product->discount_percentage > 0),
                'category_name' => $product->category->name ?? 'Traditional',
            ];
            session()->put('buy_now_item', $directItem);
            $mode = 'buy_now';
        }

        if ($mode === 'buy_now') {
            $buyNowItem = session()->get('buy_now_item');
            if (!$buyNowItem) return redirect('/cart');
            $cart = [$buyNowItem];
        } elseif ($mode === 'selected') {
            $cart = session()->get('checkout_cart', []);
            if (empty($cart)) return redirect('/cart');
        } else {
            $cart = session()->get('cart', []);
            if (empty($cart)) return redirect('/cart');
        }

        $addresses = Auth::user()->addresses ?? [];
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $seller = null;
        $resolvedPayment = null;

        if (!empty($cart)) {
            $firstItem = reset($cart);
            $sellerId  = $firstItem['sellerId'] ?? null;

            if ($sellerId) {
                $seller = User::find($sellerId);
            }

            // Check if the first cart item's product has its own payment overrides
            $productId = $firstItem['id'] ?? null;
            if ($productId) {
                $cartProduct = Product::find($productId);
                if ($cartProduct) {
                    // Build a resolved payment object that merges product overrides onto seller defaults
                    $resolvedPayment = (object) [
                        'isGcashAvailable' => $cartProduct->is_gcash_available ?? ($seller->isGcashAvailable ?? true),
                        'gcashNumber'      => $seller->gcashNumber ?? null,
                        'gcashQrCode'      => $seller->gcashQrCode ?? null,
                        'isMayaAvailable'  => $cartProduct->is_maya_available  ?? ($seller->isMayaAvailable ?? false),
                        'mayaNumber'       => $seller->mayaNumber ?? null,
                        'mayaQrCode'       => $seller->mayaQrCode ?? null,
                        'shopName'         => $seller->shopName ?? ($seller->name ?? 'Artisan'),
                    ];
                }
            }
        }

        // If no product-level override, fall back to seller profile entirely
        $paymentSource = $resolvedPayment ?? $seller;

        return view('checkout.index', compact('cart', 'addresses', 'subtotal', 'mode', 'seller', 'paymentSource'));
    }

    public function fromSelected(Request $request)
    {
        $request->validate([
            'selected_keys' => 'required|array|min:1',
            'selected_keys.*' => 'required|string',
        ]);

        $cart = session()->get('cart', []);
        $selectedCart = [];

        foreach ($request->input('selected_keys', []) as $key) {
            if (isset($cart[$key])) {
                $selectedCart[$key] = $cart[$key];
            }
        }

        if (empty($selectedCart)) {
            return redirect()->route('cart.index')->with('error', 'No valid items selected for checkout.');
        }

        session()->put('checkout_cart', $selectedCart);
        session()->put('checkout_selected_keys', array_keys($selectedCart));

        return redirect()->route('checkout.index', ['mode' => 'selected']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'paymentMethod' => 'required',
            'paymentReference' => 'required',
            'paymentScreenshot' => 'required|image',
            'shippingAddress' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $mode = $request->input('mode', 'cart');
            if ($mode === 'buy_now') {
                $cart = [session()->get('buy_now_item')];
            } elseif ($mode === 'selected') {
                $cart = session()->get('checkout_cart', []);
            } else {
                $cart = session()->get('cart', []);
            }
            
            if (empty($cart)) throw new \Exception('Cart is empty');

            $addressData = json_decode($request->input('shippingAddress'), true);
            
            // In a real app, we might group by sellerId. 
            // For now, let's follow the existing logic which seems to create one order per checkout or group by seller.
            // The frontend send "items" as a whole. Let's group by sellerId to create multiple orders if needed.
            
            $itemsBySeller = [];
            foreach ($cart as $item) {
                $itemsBySeller[$item['sellerId']][] = $item;
            }

            $orders = [];
            foreach ($itemsBySeller as $sellerId => $items) {
                $sellerUser = User::find($sellerId);
                if ($sellerUser && $sellerUser->status === 'frozen') {
                    $shopName = $sellerUser->shopName ?: $sellerUser->name;
                    throw new \Exception("The shop '{$shopName}' is currently frozen and cannot process orders at this time.");
                }

                $orderId = (string) Str::uuid();
                $totalAmount = 0;
                foreach ($items as $item) {
                    $totalAmount += $item['price'] * $item['quantity'];
                }

                // Add fees: maximum shipping fee among all items in this seller's group
                $shippingFee = 0;
                foreach ($items as $item) {
                    $itemShipping = (float) ($item['shippingFee'] ?? 0);
                    if ($itemShipping > $shippingFee) {
                        $shippingFee = $itemShipping;
                    }
                }
                $totalAmount += $shippingFee;

                $order = Order::create([
                    'id' => $orderId,
                    'customerId' => Auth::id(),
                    'sellerId' => $sellerId,
                    'totalAmount' => $totalAmount,
                    'status' => 'Pending',
                    'paymentMethod' => $request->paymentMethod,
                    'paymentReference' => $request->paymentReference,
                    'paymentStatus' => 'Paid',
                    'shippingAddress' => $addressData,
                    'createdAt' => now(),
                    'updatedAt' => now(),
                ]);

                if ($request->hasFile('paymentScreenshot')) {
                    $path = $request->file('paymentScreenshot')->store('payments', 'public');
                    $order->paymentProof = $path;
                    $order->save();
                }

                foreach ($items as $item) {
                    $product = Product::find($item['id']);

                    OrderItem::create([
                        'id' => (string) Str::uuid(),
                        'orderId' => $orderId,
                        'productId' => $item['id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'size' => $item['size'],
                        'variation' => VariationFormatter::label($item['variation'] ?? null, $product?->image)
                            ?? ($item['variation'] ?? 'Original'),
                    ]);

                    // Update stock
                    if ($product) {
                        $product->stock -= $item['quantity'];
                        
                        // Deduct from size stock if set
                        if (!empty($product->size_stocks) && isset($item['size'])) {
                            $sizeStocks = $product->size_stocks;
                            if (isset($sizeStocks[$item['size']])) {
                                $sizeStocks[$item['size']] = max(0, $sizeStocks[$item['size']] - $item['quantity']);
                                $product->size_stocks = $sizeStocks;
                            }
                        }
                        
                        $product->save();

                        // Low/Out of Stock Warnings
                        $freshStock = $product->fresh()->stock;
                        if ($freshStock <= 0) {
                            \App\Models\Notification::send($sellerId, '⚠️ Out of Stock', "\"{$product->name}\" is now out of stock.", 'system', '/seller/products', 'seller');
                        } elseif ($freshStock <= 5) {
                            \App\Models\Notification::send($sellerId, 'Low Stock Alert', "\"{$product->name}\" has only {$freshStock} items left.", 'system', '/seller/products', 'seller');
                        }
                    }
                }
                
                // Send notifications for order placement
                \App\Models\Notification::send(Auth::id(), 'Order Placed', 'Your order has been placed successfully and is awaiting confirmation.', 'order', '/orders/' . $orderId, 'customer');
                \App\Models\Notification::send($sellerId, 'New order received', 'A customer has placed a new order in your shop.', 'order', '/seller/orders', 'seller');
                
                $orders[] = $order;
            }

            if ($mode === 'cart') {
                session()->forget('cart');
                if (Auth::check()) {
                    User::find(Auth::id())->update(['cart' => json_encode([])]);
                }
            } elseif ($mode === 'selected') {
                $mainCart = session()->get('cart', []);
                foreach (session()->get('checkout_selected_keys', []) as $key) {
                    unset($mainCart[$key]);
                }
                session()->put('cart', $mainCart);
                session()->forget(['checkout_cart', 'checkout_selected_keys']);
                if (Auth::check()) {
                    User::find(Auth::id())->update(['cart' => json_encode($mainCart)]);
                }
            } else {
                session()->forget('buy_now_item');
            }

            DB::commit();

            return redirect()->route('orders')->with('success', 'Order placed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to place order: ' . $e->getMessage());
        }
    }
}
