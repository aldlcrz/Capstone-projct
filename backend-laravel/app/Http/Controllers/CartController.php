<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Support\CartHelper;
use App\Support\VariationFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            return redirect()->route(Auth::user()->role === 'superadmin' ? 'superadmin.dashboard' : 'admin.dashboard')
                ->with('info', 'Administrators do not have a customer shopping cart.');
        }

        $rawCart = session()->get('cart', []);
        $cart = CartHelper::consolidateCart($rawCart);

        // Check if consolidation or product state change caused cart to update
        if ($cart !== $rawCart) {
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
        // Save intent for guest customers before redirecting to login
        if (!Auth::check()) {
            $intentAction = $request->input('action') === 'buy_now' ? 'buy_now' : 'add_to_cart';
            $redirectTarget = $intentAction === 'buy_now' ? route('checkout.index') : ($request->headers->get('referer') ?: route('cart.index'));

            $intent = [
                'action'      => $intentAction,
                'productId'   => $request->input('productId'),
                'quantity'    => (int) $request->input('quantity', 1),
                'size'        => $request->input('size'),
                'variation'   => $request->input('variation'),
                'redirectUrl' => $redirectTarget,
            ];

            session(['pending_intent' => $intent]);
            session()->put('url.intended', $redirectTarget);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success'       => false,
                    'error'         => 'unauthorized',
                    'redirect'      => route('login'),
                    'pendingIntent' => $intent,
                    'message'       => 'Please log in or register to complete adding this item to your cart.'
                ], 401);
            }
            return redirect()->route('login')
                ->with('info', 'Please log in or register to complete adding this item to your cart.');
        }

        $productId = (string) $request->input('productId');
        $quantity = max(1, (int) $request->input('quantity', 1));
        $product = Product::with('seller')->findOrFail($productId);

        // Guard: Administrators cannot purchase or add items to cart
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Administrators cannot add products to cart or make purchases.'
                ], 403);
            }
            return redirect()->back()->with('error', 'Administrators cannot add products to cart or make purchases.');
        }

        // Guard: Sellers cannot purchase their own products
        if (Auth::check() && Auth::user()->role === 'seller' && Auth::id() === $product->sellerId) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sellers cannot purchase their own products.'
                ], 403);
            }
            return redirect()->back()->with('error', 'Sellers cannot purchase their own products.');
        }

        // Check if seller is suspended or frozen
        if ($product->seller) {
            if (in_array($product->seller->status, ['blocked', 'suspended'])) {
                $msg = 'This shop is currently suspended due to policy review and cannot accept orders at this time.';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 403);
                }
                return redirect()->back()->with('error', $msg);
            }
            if ($product->seller->status === 'frozen') {
                $msg = 'This shop is temporarily unavailable due to administrative billing maintenance and cannot accept new orders at this time.';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 403);
                }
                return redirect()->back()->with('error', $msg);
            }
        }

        $size = CartHelper::normalizeSize($request->input('size'));
        $variation = CartHelper::normalizeVariation($request->input('variation'), $product);

        // Get available stock for selected size or overall product
        $availableStock = (int) $product->stock;
        if ($size && !empty($product->size_stocks) && isset($product->size_stocks[$size])) {
            $availableStock = (int) $product->size_stocks[$size];
        }

        if ($availableStock <= 0) {
            $errMsg = $size ? "Size {$size} is currently out of stock." : "This product is currently out of stock.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            return redirect()->back()->with('error', $errMsg);
        }

        // Consolidate current cart first to ensure existing items have canonical keys
        $cart = CartHelper::consolidateCart(session()->get('cart', []));
        $key = CartHelper::getCanonicalKey($product->id, $size, $variation, $product);

        // Safe image resolution for selected variation
        $image = VariationFormatter::getImageForVariation($variation, $product) ?: $product->getImageUrl();
        $seller = $product->seller;

        if (isset($cart[$key])) {
            // Merge quantity onto existing item
            $newQuantity = $cart[$key]['quantity'] + $quantity;
            $updatedItem = $cart[$key];
            $updatedItem['key'] = $key;
            $updatedItem['quantity'] = min($newQuantity, $availableStock);
            $updatedItem['price'] = (float) $product->sale_price;
            $updatedItem['image'] = $image;
            $updatedItem['name'] = $product->name;
            $updatedItem['size'] = $size;
            $updatedItem['variation'] = $variation;

            // Re-insert at top of cart preserving order
            unset($cart[$key]);
            $cart = [$key => $updatedItem] + $cart;
        } else {
            $newItem = [
                'key'                 => $key,
                'id'                  => $product->id,
                'name'                => $product->name,
                'price'               => (float) $product->sale_price,
                'image'               => $image,
                'quantity'            => min($quantity, $availableStock),
                'size'                => $size,
                'variation'           => $variation,
                'sellerId'            => $product->sellerId,
                'shippingFee'         => (float) ($product->shippingFee ?? 0),
                'original_price'      => (float) $product->price,
                'discount_percentage' => $product->isSaleActive() ? (float) $product->discount_percentage : 0,
                'is_on_sale'          => $product->isSaleActive(),
                'category_name'       => $product->category->name ?? 'Traditional',
                'shop_name'           => $seller ? ($seller->shopName ?: $seller->name ?: 'Lumban Heritage Shop') : 'Lumban Heritage Shop',
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
                'success'    => true,
                'message'    => 'Product added to cart!',
                'cart_count' => count($cart),
                'cart'       => $cart
            ]);
        }

        return redirect()->back()->with('success', 'Product added to cart!');
    }

    public function update(Request $request)
    {
        $key = (string) $request->input('key');
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
                    $availableStock = (int) $product->stock;
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
                'success'    => true,
                'cart_count' => count($cart),
                'cart'       => $cart
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
