<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\Product;
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
            $directItem = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => (is_array($product->image) && count($product->image) > 0) ? $product->image[0] : null,
                'quantity' => $request->input('quantity', 1),
                'size' => $request->input('size'),
                'variation' => $request->input('variation'),
                'sellerId' => $product->sellerId
            ];
            session()->put('buy_now_item', $directItem);
            $mode = 'buy_now';
        }

        if ($mode === 'buy_now') {
            $buyNowItem = session()->get('buy_now_item');
            if (!$buyNowItem) return redirect('/cart');
            $cart = [$buyNowItem];
        } else {
            $cart = session()->get('cart', []);
            if (empty($cart)) return redirect('/cart');
        }

        $addresses = Auth::user()->addresses ?? [];
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        return view('checkout.index', compact('cart', 'addresses', 'subtotal', 'mode'));
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
            $cart = ($mode === 'buy_now') ? [session()->get('buy_now_item')] : session()->get('cart', []);
            
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
                $orderId = (string) Str::uuid();
                $totalAmount = 0;
                foreach ($items as $item) {
                    $totalAmount += $item['price'] * $item['quantity'];
                }

                // Add fees (simplified)
                $totalAmount += 15; // delivery
                $totalAmount -= 15; // promo

                $order = Order::create([
                    'id' => $orderId,
                    'customerId' => Auth::id(),
                    'sellerId' => $sellerId,
                    'totalAmount' => $totalAmount,
                    'status' => 'Pending',
                    'paymentMethod' => $request->paymentMethod,
                    'paymentReference' => $request->paymentReference,
                    'shippingAddress' => json_encode($addressData),
                    'createdAt' => now(),
                    'updatedAt' => now(),
                ]);

                if ($request->hasFile('paymentScreenshot')) {
                    $path = $request->file('paymentScreenshot')->store('payments', 'public');
                    $order->paymentScreenshot = $path;
                    $order->save();
                }

                foreach ($items as $item) {
                    OrderItem::create([
                        'id' => (string) Str::uuid(),
                        'orderId' => $orderId,
                        'productId' => $item['id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'size' => $item['size'],
                        'variation' => $item['variation'],
                    ]);

                    // Update stock
                    $product = Product::find($item['id']);
                    if ($product) {
                        $product->stock -= $item['quantity'];
                        $product->save();
                    }
                }
                $orders[] = $order;
            }

            if ($mode === 'cart') {
                session()->forget('cart');
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
