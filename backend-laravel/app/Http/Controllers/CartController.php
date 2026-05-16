<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        return view('cart.index', compact('cart', 'subtotal'));
    }

    public function add(Request $request)
    {
        $productId = $request->input('productId');
        $quantity = $request->input('quantity', 1);
        $size = $request->input('size');
        $variation = $request->input('variation');

        $product = Product::findOrFail($productId);

        $cart = session()->get('cart', []);

        $key = $productId . '_' . ($size ?? '') . '_' . ($variation ?? '');

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => (is_array($product->image) && count($product->image) > 0) ? $product->image[0] : null,
                'quantity' => $quantity,
                'size' => $size,
                'variation' => $variation,
                'sellerId' => $product->sellerId
            ];
        }

        session()->put('cart', $cart);

        return redirect()->back()->with('success', 'Product added to cart!');
    }

    public function update(Request $request)
    {
        $key = $request->input('key');
        $quantity = $request->input('quantity');

        $cart = session()->get('cart', []);

        if (isset($cart[$key])) {
            if ($quantity <= 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['quantity'] = $quantity;
            }
            session()->put('cart', $cart);
        }

        return response()->json(['success' => true]);
    }

    public function remove($key)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put('cart', $cart);
        }

        return redirect()->back()->with('success', 'Item removed from cart.');
    }
}
