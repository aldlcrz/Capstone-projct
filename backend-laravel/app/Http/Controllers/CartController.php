<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Support\VariationFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        $updated = false;

        foreach ($cart as $key => &$item) {
            $item['key'] = (string) $key;
            $product = Product::with('seller')->find($item['id']);
            if (!$product) {
                unset($cart[$key]);
                $updated = true;
                continue;
            }

            // Sync current price in case it changed
            if ($item['price'] != $product->sale_price) {
                $item['price'] = $product->sale_price;
                $updated = true;
            }

            // Sync current stock limits
            $size = $item['size'] ?? null;
            $availableStock = $product->stock;
            if ($size && !empty($product->size_stocks) && isset($product->size_stocks[$size])) {
                $availableStock = (int) $product->size_stocks[$size];
            }

            if ($item['quantity'] > $availableStock) {
                if ($availableStock <= 0) {
                    unset($cart[$key]);
                } else {
                    $item['quantity'] = $availableStock;
                }
                $updated = true;
            }

            // Dynamic detail injection for UI rendering
            $seller = $product->seller;
            $item['name'] = $product->name;
            $item['image'] = $product->getImageUrl();
            $item['original_price'] = $product->price;
            $item['discount_percentage'] = $product->discount_percentage;
            $item['is_on_sale'] = $product->is_on_sale && ($product->discount_percentage > 0);
            $item['category_name'] = $product->category->name ?? 'Traditional';
            $item['sellerId'] = $product->sellerId ?? ($seller->id ?? 'unknown');
            $item['shop_name'] = $seller ? ($seller->shopName ?: $seller->name ?: 'Lumban Heritage Shop') : 'Lumban Heritage Shop';
        }

        if ($updated) {
            session()->put('cart', $cart);
            $user = Auth::user();
            if ($user instanceof User) {
                $user->update(['cart' => json_encode($cart)]);
            }
        }

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        return view('cart.index', compact('cart', 'subtotal'));
    }

    public function add(Request $request)
    {
        // Only logged-in customers can add to cart
        if (!Auth::check()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'unauthorized',
                    'redirect' => route('login'),
                    'message' => 'Please log in to add items to your cart.'
                ], 401);
            }
            return redirect()->route('login')
                ->with('info', 'Please log in to add items to your cart.');
        }

        $productId = $request->input('productId');
        $quantity = (int) $request->input('quantity', 1);
        $size = $request->input('size');
        $product = Product::with('seller')->findOrFail($productId);

        // Check if seller is frozen
        if ($product->seller && $product->seller->status === 'frozen') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This shop is currently frozen due to pending requirements and cannot accept new orders at this time.'
                ], 403);
            }
            return redirect()->back()->with('error', 'This shop is currently frozen due to pending requirements and cannot accept new orders at this time.');
        }

        $variation = VariationFormatter::label($request->input('variation'), $product->image)
            ?? $request->input('variation');

        // Get available stock for selected size or overall product
        $availableStock = $product->stock;
        if ($size && !empty($product->size_stocks) && isset($product->size_stocks[$size])) {
            $availableStock = (int) $product->size_stocks[$size];
        }

        $cart = session()->get('cart', []);

        $key = $productId . '_' . ($size ?? '') . '_' . ($variation ?? '');

        // Safe image resolution
        $image = $product->getImageUrl();

        if (isset($cart[$key])) {
            $newQuantity = $cart[$key]['quantity'] + $quantity;
            $updatedItem = $cart[$key];
            $updatedItem['key'] = $key;
            $updatedItem['quantity'] = min($newQuantity, $availableStock);
            $updatedItem['image'] = $image;
            $updatedItem['name'] = $product->name;
            unset($cart[$key]);
            $cart = [$key => $updatedItem] + $cart;
        } else {
            $newItem = [
                'key' => $key,
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->sale_price,
                'image' => $image,
                'quantity' => min($quantity, $availableStock),
                'size' => $size,
                'variation' => $variation,
                'sellerId' => $product->sellerId,
                'shippingFee' => $product->shippingFee ?? 0,
                'original_price' => $product->price,
                'discount_percentage' => $product->discount_percentage,
                'is_on_sale' => $product->is_on_sale && ($product->discount_percentage > 0),
                'category_name' => $product->category->name ?? 'Traditional',
                'shop_name' => $product->seller ? ($product->seller->shopName ?: $product->seller->name ?: 'Lumban Heritage Shop') : 'Lumban Heritage Shop',
            ];
            $cart = [$key => $newItem] + $cart;
        }

        session()->put('cart', $cart);
        $user = Auth::user();
        if ($user instanceof User) {
            $user->update(['cart' => json_encode($cart)]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product added to cart!',
                'cart_count' => count($cart),
                'cart' => $cart
            ]);
        }

        return redirect()->back()->with('success', 'Product added to cart!');
    }

    public function update(Request $request)
    {
        $key = $request->input('key');
        $quantity = (int) $request->input('quantity');

        $cart = session()->get('cart', []);

        if (isset($cart[$key])) {
            if ($quantity <= 0) {
                unset($cart[$key]);
            } else {
                // Validate stock limit
                $productId = $cart[$key]['id'];
                $product = Product::find($productId);
                if ($product) {
                    $size = $cart[$key]['size'] ?? null;
                    $availableStock = $product->stock;
                    if ($size && !empty($product->size_stocks) && isset($product->size_stocks[$size])) {
                        $availableStock = (int) $product->size_stocks[$size];
                    }
                    $quantity = min($quantity, $availableStock);
                }

                $cart[$key]['quantity'] = $quantity;
            }
            session()->put('cart', $cart);
            $user = Auth::user();
            if ($user instanceof User) {
                $user->update(['cart' => json_encode($cart)]);
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart_count' => count($cart),
                'cart' => $cart
            ]);
        }

        return redirect()->back();
    }

    public function remove(string $key)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put('cart', $cart);
            $user = Auth::user();
            if ($user instanceof User) {
                $user->update(['cart' => json_encode($cart)]);
            }
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart.',
                'cart_count' => count($cart),
                'cart' => $cart
            ]);
        }

        return redirect()->back()->with('success', 'Item removed from cart.');
    }

    public function removeSelected(Request $request)
    {
        $keys = $request->input('keys', []);
        $cart = session()->get('cart', []);

        if (is_array($keys)) {
            foreach ($keys as $key) {
                unset($cart[$key]);
            }
        }

        session()->put('cart', $cart);
        $user = Auth::user();
        if ($user instanceof User) {
            $user->update(['cart' => json_encode($cart)]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Selected items removed from cart.',
                'cart_count' => count($cart),
                'cart' => $cart
            ]);
        }

        return redirect()->back()->with('success', 'Selected items removed from cart.');
    }

    public function clear(Request $request)
    {
        session()->put('cart', []);
        $user = Auth::user();
        if ($user instanceof User) {
            $user->update(['cart' => json_encode([])]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully.',
                'cart_count' => 0,
                'cart' => []
            ]);
        }

        return redirect()->back()->with('success', 'Cart cleared successfully.');
    }
}
