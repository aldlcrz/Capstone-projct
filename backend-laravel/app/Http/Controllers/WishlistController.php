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

        return view('wishlist.index', compact('wishlists'));
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
            'size'       => 'nullable|string|max:50',
        ]);

        $userId = Auth::id();
        $productId = $request->product_id;
        $size = $request->input('size');

        $existing = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            // If already wishlisted with the same size (or no size change), toggle off
            if (!$size || $existing->size === $size) {
                $existing->delete();
                $status = 'removed';
                $message = 'Item removed from your wishlist.';
            } else {
                // Update preferred size
                $existing->update(['size' => $size]);
                $status = 'added';
                $message = "Wishlist updated to Size {$size}!";
            }
        } else {
            Wishlist::create([
                'user_id'    => $userId,
                'product_id' => $productId,
                'size'       => $size,
            ]);
            $status = 'added';
            $message = $size ? "Size {$size} added to your wishlist!" : 'Item added to your wishlist!';
        }

        $count = Wishlist::where('user_id', $userId)->count();

        return response()->json([
            'success' => true,
            'status'  => $status,
            'message' => $message,
            'count'   => $count,
            'size'    => $size,
        ]);
    }
}
