<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebController extends Controller
{
    /**
     * Display the home page/shop.
     */
    public function index(Request $request)
    {
        $query = Product::where('status', 'approved');

        if ($request->has('category')) {
            $categoryName = $request->category;
            $query->where('categories', 'like', '%' . $categoryName . '%');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('artisan', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('sort')) {
            if ($request->sort === 'trending') {
                $query->orderBy('views', 'desc');
            } else {
                $query->orderBy('createdAt', 'desc');
            }
        } else {
            $query->orderBy('createdAt', 'desc');
        }

        $products = $query->paginate(15);
        $categories = Category::all();

        return view('welcome', compact('products', 'categories'));
    }

    /**
     * Display product details.
     */
    public function productDetails($id)
    {
        $product = Product::findOrFail($id);
        $isWishlisted = false;
        
        if (Auth::check()) {
            $isWishlisted = \App\Models\Wishlist::where('customerId', Auth::id())
                ->where('productId', $id)
                ->exists();
        }

        return view('products.show', compact('product', 'isWishlisted'));
    }

    /**
     * Display user profile.
     */
    public function profile()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    /**
     * Display user orders.
     */
    public function orders()
    {
        $orders = Order::where('customerId', Auth::id())
            ->with(['items.product'])
            ->orderBy('createdAt', 'desc')
            ->get();
        return view('orders.index', compact('orders'));
    }

    /**
     * Display a single order detail.
     */
    public function orderDetail($id)
    {
        $order = Order::where('id', $id)
            ->where('customerId', Auth::id())
            ->with(['items.product', 'seller'])
            ->firstOrFail();
        return view('orders.show', compact('order'));
    }

    /**
     * Display notifications.
     */
    public function notifications()
    {
        $notifications = \App\Models\Notification::where('userId', Auth::id())
            ->orderBy('createdAt', 'desc')
            ->paginate(15);
        
        // Mark all as read when visiting
        \App\Models\Notification::where('userId', Auth::id())
            ->where('isRead', false)
            ->update(['isRead' => true]);

        return view('notifications.index', compact('notifications'));
    }
}
