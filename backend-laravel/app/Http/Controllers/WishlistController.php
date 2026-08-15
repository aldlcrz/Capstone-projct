<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Display customer wishlist.
     */
    public function index()
    {
        $userId = Auth::id();
        $wishlists = Wishlist::with(['product.seller', 'product.category'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $products = $wishlists->pluck('product')->filter();

        return view('wishlist.index', compact('products'));
    }

    /**
     * Toggle a product in customer's wishlist.
     */
    public function toggle(Request $request)
    {
        if (!Auth::check()) {
            $intent = [
                'action'      => 'wishlist',
                'productId'   => $request->input('product_id'),
                'redirectUrl' => $request->headers->get('referer') ?: route('wishlist.index'),
            ];
            session(['pending_intent' => $intent]);
            session()->put('url.intended', $intent['redirectUrl']);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success'       => false,
                    'error'         => 'unauthorized',
                    'redirect'      => route('login'),
                    'pendingIntent' => $intent,
                    'message'       => 'Please log in or register to save items to your wishlist.'
                ], 401);
            }
            return redirect()->route('login')->with('info', 'Please log in or register to save items to your wishlist.');
        }

        $request->validate([
            'product_id' => 'required|string|exists:products,id',
        ]);

        $userId = Auth::id();
        $productId = $request->product_id;

        $existing = Wishlist::where('user_id', $userId)->where('product_id', $productId)->first();

        if ($existing) {
            $existing->delete();
            $status = 'removed';
            $message = 'Item removed from your wishlist.';
        } else {
            Wishlist::create([
                'user_id' => $userId,
                'product_id' => $productId,
            ]);
            $status = 'added';
            $message = 'Item added to your wishlist!';
        }

        $count = Wishlist::where('user_id', $userId)->count();

        return response()->json([
            'success' => true,
            'status'  => $status,
            'message' => $message,
            'count'   => $count,
        ]);
    }
}
