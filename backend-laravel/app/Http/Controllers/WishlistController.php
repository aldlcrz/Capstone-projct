<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlistItems = Wishlist::where('customerId', Auth::id())
            ->with('product')
            ->orderBy('createdAt', 'desc')
            ->get();

        return view('wishlist.index', compact('wishlistItems'));
    }

    public function toggle(Request $request)
    {
        $productId = $request->input('productId');
        $customerId = Auth::id();

        $wishlistItem = Wishlist::where('customerId', $customerId)
            ->where('productId', $productId)
            ->first();

        if ($wishlistItem) {
            $wishlistItem->delete();
            $status = 'removed';
            $message = 'Removed from wishlist';
        } else {
            Wishlist::create([
                'customerId' => $customerId,
                'productId' => $productId
            ]);
            $status = 'added';
            $message = 'Added to wishlist';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => $status,
                'message' => $message
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function remove($id)
    {
        $wishlistItem = Wishlist::where('id', $id)
            ->where('customerId', Auth::id())
            ->firstOrFail();

        $wishlistItem->delete();

        return redirect()->back()->with('success', 'Item removed from wishlist');
    }
}
