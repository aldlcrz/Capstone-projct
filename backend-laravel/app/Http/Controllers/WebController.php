<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WebController extends Controller
{
    /**
     * Display the home page/shop.
     */
    public function index(Request $request)
    {
        if (Auth::check()) {
            if (Auth::user()->role === 'superadmin') {
                return redirect()->route('superadmin.dashboard');
            }
            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
        }

        $query = Product::where('status', 'approved')
            ->select('products.*')
            ->selectSub(function($q) {
                $q->select('isPremium')->from('users')->whereColumn('users.id', 'products.sellerId');
            }, 'seller_is_premium')
            ->with(['seller'])
            ->withAvg('reviews as avgRating', 'rating')
            ->withCount('reviews as reviewCount')
            ->orderBy('seller_is_premium', 'desc');

        if ($request->filled('category')) {
            $catVal = $request->category;
            $demographics = ['men', 'male', 'women', 'female', 'kids'];

            if (in_array(strtolower($catVal), $demographics)) {
                $normalised = match(strtolower($catVal)) {
                    'male', 'men'     => 'Men',
                    'female', 'women' => 'Women',
                    'kids'            => 'Kids',
                    default           => $catVal,
                };
                $query->where('target_group', $normalised);
            } else {
                $query->where(function($q) use ($catVal) {
                    $q->where('CategoryId', $catVal)
                      ->orWhereHas('category', function($cq) use ($catVal) {
                          $cq->where('name', $catVal)->orWhere('id', $catVal);
                      });
                });
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhereHas('seller', function($sq) use ($search) {
                      $sq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('shopName', 'like', '%' . $search . '%');
                  });
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
        $banners = Banner::where('is_active', true)
            ->orderBy('order_index', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('welcome', compact('products', 'categories', 'banners'));
    }

    /**
     * Display product details.
     */
    public function productDetails(string $id)
    {
        $product = Product::with(['reviews.customer', 'category', 'seller'])
            ->withAvg('reviews as avgRating', 'rating')
            ->withCount('reviews as reviewCount')
            ->findOrFail($id);

        $soldCount = DB::table('order_items')
            ->join('orders', 'order_items.orderId', '=', 'orders.id')
            ->where('order_items.productId', $id)
            ->whereIn('orders.status', ['Delivered', 'Completed'])
            ->sum('order_items.quantity') ?: 0;

        $recommended = $this->getRecommendedProducts($product);

        return view('products.show', compact('product', 'soldCount', 'recommended'));
    }

    /**
     * Display a seller's public shop.
     */
    public function sellerShop(string $id)
    {
        $seller = \App\Models\User::where('id', $id)->where('role', 'seller')->firstOrFail();

        return view('shops.show', ['id' => $seller->id]);
    }

    /**
     * Get products to recommend on the product detail page.
     */
    private function getRecommendedProducts(Product $product, int $limit = 4)
    {
        $baseQuery = fn () => Product::where('status', 'approved')
            ->where('id', '!=', $product->id)
            ->where('stock', '>', 0)
            ->withAvg('reviews as avgRating', 'rating')
            ->withCount('reviews as reviewCount');

        $recommended = collect();

        if ($product->CategoryId) {
            $recommended = $baseQuery()
                ->where('CategoryId', $product->CategoryId)
                ->orderByDesc('views')
                ->limit($limit)
                ->get();
        }

        if ($recommended->count() < $limit && $product->target_group) {
            $excludeIds = $recommended->pluck('id')->push($product->id);
            $more = $baseQuery()
                ->whereNotIn('id', $excludeIds)
                ->where('target_group', $product->target_group)
                ->orderByDesc('views')
                ->limit($limit - $recommended->count())
                ->get();
            $recommended = $recommended->concat($more);
        }

        if ($recommended->count() < $limit) {
            $excludeIds = $recommended->pluck('id')->push($product->id);
            $more = $baseQuery()
                ->whereNotIn('id', $excludeIds)
                ->inRandomOrder()
                ->limit($limit - $recommended->count())
                ->get();
            $recommended = $recommended->concat($more);
        }

        return $recommended;
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
    public function orders(Request $request)
    {
        $query = Order::where('customerId', Auth::id())
            ->with(['items.product'])
            ->orderBy('createdAt', 'desc');

        // Filter by status tab
        $tab = strtolower($request->input('tab', 'all'));
        $statusMap = [
            'pending'    => 'pending',
            'to ship'    => 'to ship',
            'to receive' => 'to receive',
            'completed'  => 'completed',
        ];
        if ($tab !== 'all' && isset($statusMap[$tab])) {
            $query->where('status', $statusMap[$tab]);
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('items.product', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->get();
        return view('orders.index', compact('orders'));
    }

    /**
     * Display a single order detail.
     */
    public function orderDetail(string $id)
    {
        $order = Order::where('id', $id)
            ->where('customerId', Auth::id())
            ->with(['items.product', 'seller', 'reviews'])
            ->firstOrFail();

        $recommended = $this->getRecommendedProductsForOrder($order);

        return view('orders.show', compact('order', 'recommended'));
    }

    /**
     * Get products to recommend after viewing an order.
     */
    private function getRecommendedProductsForOrder(Order $order, int $limit = 4)
    {
        $orderedProductIds = $order->items->pluck('productId')->filter()->unique();
        $categoryIds = $order->items
            ->map(fn ($item) => $item->product?->CategoryId)
            ->filter()
            ->unique();

        $baseQuery = fn () => Product::where('status', 'approved')
            ->where('stock', '>', 0)
            ->whereNotIn('id', $orderedProductIds)
            ->withAvg('reviews as avgRating', 'rating')
            ->withCount('reviews as reviewCount');

        $recommended = collect();

        if ($categoryIds->isNotEmpty()) {
            $recommended = $baseQuery()
                ->whereIn('CategoryId', $categoryIds)
                ->orderByDesc('views')
                ->limit($limit)
                ->get();
        }

        if ($recommended->count() < $limit && $order->sellerId) {
            $excludeIds = $recommended->pluck('id')->concat($orderedProductIds);
            $more = $baseQuery()
                ->whereNotIn('id', $excludeIds)
                ->where('sellerId', $order->sellerId)
                ->orderByDesc('views')
                ->limit($limit - $recommended->count())
                ->get();
            $recommended = $recommended->concat($more);
        }

        if ($recommended->count() < $limit) {
            $excludeIds = $recommended->pluck('id')->concat($orderedProductIds);
            $more = $baseQuery()
                ->whereNotIn('id', $excludeIds)
                ->inRandomOrder()
                ->limit($limit - $recommended->count())
                ->get();
            $recommended = $recommended->concat($more);
        }

        return $recommended;
    }

    /**
     * Display notifications.
     */
    public function notifications()
    {
        $notifications = \App\Models\Notification::where('userId', Auth::id())
            ->where('targetRole', 'customer')
            ->orderBy('createdAt', 'desc')
            ->paginate(15);
        
        // Mark all as read when visiting
        \App\Models\Notification::where('userId', Auth::id())
            ->where('targetRole', 'customer')
            ->where('isRead', false)
            ->update(['isRead' => true]);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark all customer notifications as read.
     */
    public function readAllNotifications()
    {
        \App\Models\Notification::where('userId', Auth::id())
            ->where('targetRole', 'customer')
            ->where('isRead', false)
            ->update(['isRead' => true]);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}
