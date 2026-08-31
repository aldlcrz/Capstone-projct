<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Banner;
use App\Models\User;
use App\Models\Review;
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
            if (Auth::user()->role === 'seller' && !$request->has('browse')) {
                return redirect()->route('seller.dashboard');
            }
        }

        $query = Product::where('status', 'approved')
            ->select('products.*')
            ->selectSub(function($q) {
                $q->select('isPremium')->from('users')->whereColumn('users.id', 'products.sellerId');
            }, 'seller_is_premium')
            ->selectSub(function($q) {
                $q->selectRaw('COALESCE(SUM(order_items.quantity), 0)')
                    ->from('order_items')
                    ->join('orders', 'order_items.orderId', '=', 'orders.id')
                    ->whereColumn('order_items.productId', 'products.id')
                    ->whereIn('orders.status', ['Delivered', 'Completed', 'completed', 'delivered']);
            }, 'sold_count')
            ->with(['seller'])
            ->withAvg('reviews as avgRating', 'rating')
            ->withCount('reviews as reviewCount');

        if ($request->filled('category')) {
            $catVal = $request->category;

            // Save selected category to session saved categories list
            $savedCats = session()->get('saved_categories', []);
            $filteredCats = array_filter($savedCats, function($c) use ($catVal) {
                return strtolower($c) !== strtolower($catVal);
            });
            array_unshift($filteredCats, $catVal);
            session()->put('saved_categories', array_slice(array_values($filteredCats), 0, 8));

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
                          $cq->where('name', 'like', '%' . $catVal . '%')->orWhere('id', $catVal);
                      })
                      ->orWhere('name', 'like', '%' . $catVal . '%')
                      ->orWhere('description', 'like', '%' . $catVal . '%');
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

        if ($request->has('lumban_special') || $request->sort === 'lumban_special') {
            $query->where('is_on_sale', true)->where('discount_percentage', '>', 0);
        }

        if ($request->has('sort')) {
            if (in_array($request->sort, ['trending', 'best_sellers', 'most_sold'])) {
                // Real-time: only products with confirmed sales are shown
                $query->whereExists(function($q) {
                    $q->select(DB::raw(1))
                      ->from('order_items')
                      ->join('orders', 'order_items.orderId', '=', 'orders.id')
                      ->whereColumn('order_items.productId', 'products.id')
                      ->whereIn('orders.status', ['Delivered', 'Completed', 'completed', 'delivered'])
                      ->where('order_items.quantity', '>', 0);
                })
                ->orderBy('sold_count', 'desc')
                ->orderBy('views', 'desc')
                ->orderBy('createdAt', 'desc');
            } elseif ($request->sort === 'newest') {
                $query->orderBy('createdAt', 'desc');
            } elseif ($request->sort === 'price_asc' || $request->sort === 'price_low') {
                $query->orderBy('price', 'asc');
            } elseif ($request->sort === 'price_desc' || $request->sort === 'price_high') {
                $query->orderBy('price', 'desc');
            } else {
                $query->inRandomOrder();
            }
        } else {
            $query->inRandomOrder();
        }

        $products = $query->paginate(100);
        $categories = Category::withCount('products')->get();
        $banners = Banner::live()
            ->orderBy('order_index', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($banners->isEmpty()) {
            $defaultBanner = new Banner([
                'title' => 'Elegant. Timeless. Filipino.',
                'subtitle' => 'NEW COLLECTION',
                'button_text_1' => 'Shop Now',
                'button_url_1' => '/?sort=newest#catalogue-section',
                'image_path' => '/uploads/categories/featured_best_sellers.png',
                'is_active' => true,
            ]);
            $banners = collect([$defaultBanner]);
        }

        $topShops = $this->fetchTopShops();
        $customerStats = $this->fetchCustomerStats();

        return view('welcome', compact('products', 'categories', 'banners', 'customerStats', 'topShops'));
    }

    /**
     * Fetch Top Rated Artisan Shops (Real-time DB data).
     */
    private function fetchTopShops()
    {
        $topShops = collect();

        try {
            $dbSellers = User::where('role', 'seller')
                ->where('status', '!=', 'blocked')
                ->get();

            foreach ($dbSellers as $seller) {
                // Real product count
                $pCount = 0;
                try {
                    $pCount = Product::where('sellerId', $seller->id)
                        ->where('status', 'approved')
                        ->count();
                } catch (\Throwable $pe) {
                    $pCount = 0;
                }

                // Real total sold
                $totalSold = 0;
                try {
                    $totalSold = DB::table('order_items')
                        ->join('orders', 'order_items.orderId', '=', 'orders.id')
                        ->where('orders.sellerId', $seller->id)
                        ->whereIn('orders.status', ['Delivered', 'Completed', 'completed', 'delivered'])
                        ->sum('order_items.quantity');

                    if (!$totalSold) {
                        $totalSold = Order::where('sellerId', $seller->id)
                            ->whereIn('status', ['Delivered', 'Completed', 'completed', 'delivered'])
                            ->count();
                    }
                } catch (\Throwable $oe) {
                    $totalSold = 0;
                }

                // Real rating
                $avgRating = null;
                $reviewCount = 0;
                try {
                    $avgRating = Review::whereHas('product', function($q) use ($seller) {
                        $q->where('sellerId', $seller->id);
                    })->avg('rating');

                    $reviewCount = Review::whereHas('product', function($q) use ($seller) {
                        $q->where('sellerId', $seller->id);
                    })->count();
                } catch (\Throwable $re) {
                    $avgRating = null;
                    $reviewCount = 0;
                }

                $topShops->push((object)[
                    'id' => $seller->id,
                    'name' => $seller->shopName ?: ($seller->name ?: 'Lumban Heritage Shop'),
                    'description' => $seller->shopDescription ?: 'Handcrafted Barong Tagalog & Filipiniana specialists from Lumban, Laguna.',
                    'location' => trim(($seller->shopCity ?? 'Lumban') . ', ' . ($seller->shopProvince ?? 'Laguna'), ', '),
                    'avatar' => $seller->profile_photo_url ?: ($seller->profilePhoto ?: '/uploads/products/default.jpg'),
                    'rating' => $avgRating ? number_format($avgRating, 1) : '0.0',
                    'review_count' => (int)$reviewCount,
                    'total_sold' => (int)$totalSold,
                    'products_count' => (int)$pCount,
                ]);
            }
        } catch (\Throwable $e) {
            // Ignore DB schema exceptions
        }

        if ($topShops->isEmpty()) {
            try {
                $anySellers = User::where('role', 'seller')->get();
                foreach ($anySellers as $s) {
                    $topShops->push((object)[
                        'id' => $s->id,
                        'name' => $s->shopName ?: ($s->name ?: 'Lumban Shop'),
                        'description' => $s->shopDescription ?: 'Handcrafted Barong Tagalog specialists.',
                        'location' => 'Lumban, Laguna',
                        'avatar' => $s->profile_photo_url ?: ($s->profilePhoto ?: '/uploads/products/default.jpg'),
                        'rating' => '0.0',
                        'review_count' => 0,
                        'total_sold' => 0,
                        'products_count' => 0,
                    ]);
                }
            } catch (\Throwable $e2) {}
        }

        return $topShops->sortByDesc('total_sold')->sortByDesc('products_count')->values();
    }

    /**
     * Fetch customer activity stats for current user.
     */
    private function fetchCustomerStats()
    {
        $customerStats = [
            'recent_orders' => 0,
            'in_production' => 0,
            'wishlist' => 0,
            'reward_points' => 0,
        ];

        if (Auth::check()) {
            try {
                $userId = Auth::id();
                $customerStats['recent_orders'] = Order::where('customerId', $userId)->count();
                $customerStats['in_production'] = Order::where('customerId', $userId)
                    ->whereIn('status', ['pending', 'processing', 'in_production', 'to ship', 'to_ship'])
                    ->count();
                $customerStats['wishlist'] = count(session('cart', []));
                $customerStats['reward_points'] = Auth::user()->reward_points ?? (50 * Order::where('customerId', $userId)->where('status', 'completed')->count());
            } catch (\Throwable $se) {
                // Ignore stats errors
            }
        }

        return $customerStats;
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

        if ($product->status !== 'approved') {
            $isOwner = Auth::check() && Auth::id() === $product->sellerId;
            $isAdmin = Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin']);
            if (!$isOwner && !$isAdmin) {
                abort(404, 'This product is currently under review or unavailable.');
            }
        }

        $soldCount = DB::table('order_items')
            ->join('orders', 'order_items.orderId', '=', 'orders.id')
            ->where('order_items.productId', $id)
            ->whereIn('orders.status', ['Delivered', 'Completed', 'completed', 'delivered'])
            ->sum('order_items.quantity') ?: 0;

        $recommended = $this->getRecommendedProducts($product);

        $isWishlisted = false;
        if (Auth::check()) {
            try {
                $isWishlisted = DB::table('wishlists')
                    ->where('user_id', Auth::id())
                    ->where('product_id', $id)
                    ->exists();
            } catch (\Throwable $e) {
                $isWishlisted = false;
            }
        }

        return view('products.show', compact('product', 'soldCount', 'recommended', 'isWishlisted'));
    }

    /**
     * Display a seller's public shop.
     */
    public function sellerShop(string $id)
    {
        $seller = User::where('role', 'seller')
            ->where('status', '!=', 'blocked')
            ->where(function($q) use ($id) {
                $q->where('id', $id)
                  ->orWhere('shopName', $id)
                  ->orWhere('shopName', urldecode($id));
            })
            ->firstOrFail();

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
            ->select('products.*')
            ->selectSub(function($q) {
                $q->selectRaw('COALESCE(SUM(order_items.quantity), 0)')
                    ->from('order_items')
                    ->join('orders', 'order_items.orderId', '=', 'orders.id')
                    ->whereColumn('order_items.productId', 'products.id')
                    ->whereIn('orders.status', ['Delivered', 'Completed', 'completed', 'delivered']);
            }, 'sold_count')
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
            ->with(['items.product', 'seller', 'reviews', 'statusHistories'])
            ->orderBy('createdAt', 'desc');

        // Filter by status tab
        $tab = strtolower($request->input('tab', 'all'));
        $statusGroupMap = [
            'pending'    => ['Pending', 'pending', 'Order Placed', 'order placed', 'order_placed'],
            'to ship'    => ['Processing', 'processing', 'To Ship', 'to ship', 'to_ship', 'Ready to Ship', 'ready_to_ship', 'Shipped', 'shipped'],
            'to receive' => ['In Transit', 'in_transit', 'To Receive', 'to receive', 'Out for Delivery', 'out_for_delivery'],
            'in transit' => ['In Transit', 'in_transit', 'Out for Delivery', 'out_for_delivery'],
            'delivered'  => ['Delivered', 'delivered'],
            'completed'  => ['Completed', 'completed'],
            'cancelled'  => ['Cancelled', 'cancelled'],
        ];

        if ($tab !== 'all' && isset($statusGroupMap[$tab])) {
            $query->whereIn('status', $statusGroupMap[$tab]);
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

        // Calculate counts for each customer tab
        $allCustomerOrders = Order::where('customerId', Auth::id())->select('id', 'status')->get();
        $counts = [
            'ALL'        => $allCustomerOrders->count(),
            'PENDING'    => $allCustomerOrders->filter(fn($o) => in_array(strtolower(trim($o->status ?? '')), ['pending', 'order placed', 'order_placed']))->count(),
            'TO SHIP'    => $allCustomerOrders->filter(fn($o) => in_array(strtolower(trim(str_replace('_', ' ', $o->status ?? ''))), ['processing', 'to ship', 'ready to ship', 'shipped']))->count(),
            'TO RECEIVE' => $allCustomerOrders->filter(fn($o) => in_array(strtolower(trim(str_replace('_', ' ', $o->status ?? ''))), ['in transit', 'to receive', 'out for delivery']))->count(),
            'DELIVERED'  => $allCustomerOrders->filter(fn($o) => in_array(strtolower(trim($o->status ?? '')), ['delivered']))->count(),
            'COMPLETED'  => $allCustomerOrders->filter(fn($o) => in_array(strtolower(trim($o->status ?? '')), ['completed']))->count(),
            'CANCELLED'  => $allCustomerOrders->filter(fn($o) => in_array(strtolower(trim($o->status ?? '')), ['cancelled']))->count(),
        ];

        return view('orders.index', compact('orders', 'counts'));
    }

    /**
     * Display a single order detail.
     */
    public function orderDetail(string $id)
    {
        $order = Order::where('id', $id)
            ->where('customerId', Auth::id())
            ->with(['items.product', 'seller', 'reviews', 'statusHistories'])
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
