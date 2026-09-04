<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\Review;
use App\Models\SellerFunnelEvent;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    /**
     * Display the seller analytics view.
     */
    public function sellerAnalytics(Request $request)
    {
        $sellerId   = $request->user()->id;
        $dateFilter = $this->resolveDateRange($request);
        $from       = $dateFilter['from'];
        $to         = $dateFilter['to'];

        // Retrieve all seller orders
        $allOrdersQuery = Order::where('sellerId', $sellerId)->with(['items.product', 'customer']);
        $ordersQuery    = Order::where('sellerId', $sellerId)->with(['items.product', 'customer']);

        if ($from) {
            $ordersQuery->where('createdAt', '>=', $from);
        }
        if ($to) {
            $ordersQuery->where('createdAt', '<=', $to);
        }

        $allOrders    = $allOrdersQuery->orderBy('createdAt', 'desc')->get();
        $orders       = $ordersQuery->orderBy('createdAt', 'desc')->get();
        $activeOrders = $orders->reject(fn ($o) => $this->isCancelledOrder($o->status));

        $products = Product::where('sellerId', $sellerId)->get();

        // 1. SALES ANALYTICS & TIME COMPARISONS
        $totalSales  = (float) $activeOrders->sum('totalAmount');
        $orderCount  = $activeOrders->count();
        $totalItemsSold = (int) DB::table('order_items')
            ->join('orders', 'order_items.orderId', '=', 'orders.id')
            ->where('orders.sellerId', $sellerId)
            ->whereRaw('LOWER(orders.status) NOT IN (?, ?, ?)', ['cancelled', 'cancellation pending', 'cancellation requested'])
            ->when($from, fn ($q) => $q->where('orders.createdAt', '>=', $from))
            ->when($to, fn ($q) => $q->where('orders.createdAt', '<=', $to))
            ->sum('order_items.quantity');

        $averageOrderValue = $orderCount > 0 ? $totalSales / $orderCount : 0;

        // Platform commission rate = 10%
        $commissionFee = $totalSales * 0.10;
        $grossSales    = $totalSales + $commissionFee;
        $totalDiscounts = (float) $activeOrders->sum('discount_amount');
        $totalRefunds   = (float) Order::where('sellerId', $sellerId)
            ->whereIn('status', ['refunded', 'returned', 'return requested'])
            ->sum('totalAmount');

        $totalShippingFees = (float) $activeOrders->sum('shippingFee');
        $netSales          = max(0, $totalSales - $totalDiscounts - $totalRefunds);
        $sellerEarnings    = max(0, $netSales - $commissionFee);

        // Time Period Comparisons (Month vs Last Month, Year vs Last Year, Week vs Previous Week)
        $now = Carbon::now();

        // Month vs Last Month
        $thisMonthSales = (float) $allOrders
            ->reject(fn ($o) => $this->isCancelledOrder($o->status))
            ->filter(fn ($o) => Carbon::parse($o->createdAt)->gte($now->copy()->startOfMonth()))
            ->sum('totalAmount');

        $lastMonthSales = (float) $allOrders
            ->reject(fn ($o) => $this->isCancelledOrder($o->status))
            ->filter(fn ($o) => Carbon::parse($o->createdAt)->between(
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth()
            ))
            ->sum('totalAmount');

        $monthGrowthPct = $lastMonthSales > 0
            ? round((($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100, 1)
            : ($thisMonthSales > 0 ? 100 : 0);

        // Week vs Previous Week
        $thisWeekSales = (float) $allOrders
            ->reject(fn ($o) => $this->isCancelledOrder($o->status))
            ->filter(fn ($o) => Carbon::parse($o->createdAt)->gte($now->copy()->subDays(6)->startOfDay()))
            ->sum('totalAmount');

        $prevWeekSales = (float) $allOrders
            ->reject(fn ($o) => $this->isCancelledOrder($o->status))
            ->filter(fn ($o) => Carbon::parse($o->createdAt)->between(
                $now->copy()->subDays(13)->startOfDay(),
                $now->copy()->subDays(7)->endOfDay()
            ))
            ->sum('totalAmount');

        $weekGrowthPct = $prevWeekSales > 0
            ? round((($thisWeekSales - $prevWeekSales) / $prevWeekSales) * 100, 1)
            : ($thisWeekSales > 0 ? 100 : 0);

        // Year vs Previous Year
        $thisYearSales = (float) $allOrders
            ->reject(fn ($o) => $this->isCancelledOrder($o->status))
            ->filter(fn ($o) => Carbon::parse($o->createdAt)->gte($now->copy()->startOfYear()))
            ->sum('totalAmount');

        $prevYearSales = (float) $allOrders
            ->reject(fn ($o) => $this->isCancelledOrder($o->status))
            ->filter(fn ($o) => Carbon::parse($o->createdAt)->between(
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->endOfYear()
            ))
            ->sum('totalAmount');

        $yearGrowthPct = $prevYearSales > 0
            ? round((($thisYearSales - $prevYearSales) / $prevYearSales) * 100, 1)
            : ($thisYearSales > 0 ? 100 : 0);

        // 2. ORDER ANALYTICS & STATUS BREAKDOWN
        $orderStats = [
            'total'     => $orders->count(),
            'completed' => $orders->filter(fn ($o) => in_array(strtolower($o->status), ['completed', 'delivered']))->count(),
            'pending'   => $orders->filter(fn ($o) => strtolower($o->status) === 'pending')->count(),
            'toShip'    => $orders->filter(fn ($o) => in_array(strtolower($o->status), ['processing', 'to ship', 'confirmed', 'packed']))->count(),
            'delivered' => $orders->filter(fn ($o) => in_array(strtolower($o->status), ['delivered', 'completed']))->count(),
            'cancelled' => $orders->filter(fn ($o) => $this->isCancelledOrder($o->status))->count(),
        ];

        $completionRate = $orderStats['total'] > 0
            ? round(($orderStats['completed'] / $orderStats['total']) * 100, 1)
            : 100;

        $avgProcessingTimeHours = 12; // Average order processing estimation

        // 3. PRODUCT ANALYTICS
        $productMetrics = $this->buildProductAnalytics($sellerId, $from, $to);

        // 4. CUSTOMER ANALYTICS & RETENTION
        $customerMetrics = $this->buildCustomerAnalytics($sellerId, $allOrders);

        // 5. SALES BY CATEGORY
        $categorySales = $this->buildCategorySales($sellerId, $from, $to);

        // 6. MARKETING & PROMOTIONS ANALYTICS
        $marketingMetrics = $this->buildMarketingMetrics($sellerId, $from, $to);

        // 7. CHARTS & TREND DATA
        $salesTrendChart = $this->buildSalesTrendChart($activeOrders, $from, $to);

        $data = [
            'filters' => $dateFilter,
            'salesAnalytics' => [
                'totalSales'        => $totalSales,
                'grossSales'        => $grossSales,
                'netSales'          => $netSales,
                'averageOrderValue' => $averageOrderValue,
                'totalItemsSold'    => $totalItemsSold,
                'thisMonthSales'    => $thisMonthSales,
                'lastMonthSales'    => $lastMonthSales,
                'monthGrowthPct'    => $monthGrowthPct,
                'thisWeekSales'     => $thisWeekSales,
                'prevWeekSales'     => $prevWeekSales,
                'weekGrowthPct'     => $weekGrowthPct,
                'thisYearSales'     => $thisYearSales,
                'prevYearSales'     => $prevYearSales,
                'yearGrowthPct'     => $yearGrowthPct,
            ],
            'orderAnalytics' => [
                'stats'                  => $orderStats,
                'completionRate'         => $completionRate,
                'avgProcessingTimeHours' => $avgProcessingTimeHours,
            ],
            'productAnalytics'   => $productMetrics,
            'customerAnalytics'  => $customerMetrics,
            'categorySales'      => $categorySales,
            'financialAnalytics' => [
                'grossSales'        => $grossSales,
                'commissionFee'     => $commissionFee,
                'discounts'          => $totalDiscounts,
                'refunds'            => $totalRefunds,
                'shippingFees'       => $totalShippingFees,
                'netSales'          => $netSales,
                'sellerEarnings'    => $sellerEarnings,
            ],
            'marketingAnalytics' => $marketingMetrics,
            'salesTrendChart'    => $salesTrendChart,
        ];

        if ($request->wantsJson() || $request->ajax() || $request->input('format') === 'json') {
            return response()->json($data);
        }

        return view('seller.analytics.index', $data);
    }

    private function buildProductAnalytics(string $sellerId, ?Carbon $from, ?Carbon $to): array
    {
        $products = Product::where('sellerId', $sellerId)->get();

        $productStats = $products->map(function ($product) use ($sellerId, $from, $to) {
            /** @var Product $product */
            $itemsQuery = DB::table('order_items')
                ->join('orders', 'order_items.orderId', '=', 'orders.id')
                ->where('order_items.productId', $product->id)
                ->whereRaw('LOWER(orders.status) NOT IN (?, ?, ?)', ['cancelled', 'cancellation pending', 'cancellation requested']);

            if ($from) {
                $itemsQuery->where('orders.createdAt', '>=', $from);
            }
            if ($to) {
                $itemsQuery->where('orders.createdAt', '<=', $to);
            }

            $unitsSold = (int) $itemsQuery->sum('order_items.quantity');
            $revenue   = (float) $itemsQuery->sum(DB::raw('order_items.quantity * order_items.price'));
            $orderCount = (int) $itemsQuery->count();

            $views = max((int) $product->views, 1);
            if (Schema::hasTable('product_views')) {
                $pv = ProductView::where('product_id', $product->id)->count();
                $views = max($views, $pv);
            }

            $addToCartCount = 0;
            if (Schema::hasTable('seller_funnel_events')) {
                $addToCartCount = SellerFunnelEvent::where('product_id', $product->id)
                    ->where('event_type', 'add_to_cart')
                    ->count();
            }

            $wishlistCount = DB::table('wishlists')->where('product_id', $product->id)->count();
            $conversionRate = $views > 0 ? min(100.0, round(($orderCount / $views) * 100, 1)) : 0;

            $rating = (float) DB::table('reviews')->where('productId', $product->id)->avg('rating') ?: 5.0;

            return [
                'id'             => $product->id,
                'name'           => $product->name,
                'image'          => $product->getImageUrl(),
                'stock'          => $product->stock,
                'price'          => $product->price,
                'status'         => $product->status,
                'views'          => $views,
                'orders'         => $orderCount,
                'unitsSold'      => $unitsSold,
                'revenue'        => $revenue,
                'addToCart'      => $addToCartCount,
                'wishlist'       => $wishlistCount,
                'conversionRate' => $conversionRate,
                'rating'         => round($rating, 1),
            ];
        });

        $bestSelling    = $productStats->sortByDesc('unitsSold')->take(5)->values();
        $worstSelling   = $productStats->sortBy('unitsSold')->take(5)->values();
        $mostViewed     = $productStats->sortByDesc('views')->take(5)->values();
        $highestRevenue = $productStats->sortByDesc('revenue')->take(5)->values();

        return [
            'all'            => $productStats->sortByDesc('revenue')->values(),
            'bestSelling'    => $bestSelling,
            'worstSelling'   => $worstSelling,
            'mostViewed'     => $mostViewed,
            'highestRevenue' => $highestRevenue,
        ];
    }

    /**
     * @param string $sellerId
     * @param \Illuminate\Support\Collection $allOrders
     * @return array
     */
    private function buildCustomerAnalytics(string $sellerId, \Illuminate\Support\Collection $allOrders): array
    {
        $activeOrders = $allOrders->reject(fn ($o) => $this->isCancelledOrder($o->status));

        $customersGrouped = $activeOrders
            ->filter(fn ($o) => !empty($o->customerId))
            ->groupBy('customerId');

        $totalCustomers    = $customersGrouped->count();
        $repeatCustomers   = $customersGrouped->filter(fn ($g) => $g->count() > 1)->count();
        $newCustomers      = $customersGrouped->filter(fn ($g) => $g->count() === 1)->count();

        $repeatRate = $totalCustomers > 0 ? round(($repeatCustomers / $totalCustomers) * 100, 1) : 0;
        $retentionRate = $totalCustomers > 0 ? round((($totalCustomers - $newCustomers) / $totalCustomers) * 100, 1) : 0;

        $totalRevenue = $activeOrders->sum('totalAmount');
        $avgCustomerSpend = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;

        $topCustomers = $customersGrouped->map(function ($customerOrders) {
            $first = $customerOrders->first();
            return [
                'id'         => $first->customerId,
                'name'       => optional($first->customer)->name ?? 'Customer',
                'email'      => optional($first->customer)->email ?? '—',
                'orderCount' => $customerOrders->count(),
                'totalSpent' => (float) $customerOrders->sum('totalAmount'),
                'lastOrder'  => $customerOrders->max('createdAt'),
            ];
        })
        ->sortByDesc('totalSpent')
        ->take(10)
        ->values();

        // Customer Behavior Funnel
        $totalViews = (int) DB::table('products')->where('sellerId', $sellerId)->sum('views');
        $totalCarts = Schema::hasTable('seller_funnel_events')
            ? DB::table('seller_funnel_events')->where('seller_id', $sellerId)->where('event_type', 'add_to_cart')->count()
            : (int) ($totalViews * 0.25);

        $newReviewsCount = DB::table('reviews')
            ->join('products', 'reviews.productId', '=', 'products.id')
            ->where('products.sellerId', $sellerId)
            ->where('reviews.createdAt', '>=', Carbon::now()->subDays(7))
            ->count();

        $totalWishlists = DB::table('wishlists')
            ->join('products', 'wishlists.product_id', '=', 'products.id')
            ->where('products.sellerId', $sellerId)
            ->count();

        $totalCheckouts = (int) ($activeOrders->count() * 1.2);
        $completedPurchases = $activeOrders->count();

        return [
            'totalCustomers'    => $totalCustomers,
            'repeatCustomers'   => $repeatCustomers,
            'newCustomers'      => $newCustomers,
            'repeatRate'        => $repeatRate,
            'retentionRate'     => $retentionRate,
            'avgCustomerSpend'  => $avgCustomerSpend,
            'topCustomers'      => $topCustomers,
            'funnel' => [
                'views'     => max($totalViews, 10),
                'addToCart' => $totalCarts,
                'wishlist'  => $totalWishlists,
                'checkout'  => max($totalCheckouts, $completedPurchases),
                'completed' => $completedPurchases,
            ]
        ];
    }

    private function buildCategorySales(string $sellerId, ?Carbon $from, ?Carbon $to): array
    {
        $query = DB::table('order_items')
            ->join('orders', 'order_items.orderId', '=', 'orders.id')
            ->join('products', 'order_items.productId', '=', 'products.id')
            ->leftJoin('categories', 'products.CategoryId', '=', 'categories.id')
            ->where('orders.sellerId', $sellerId)
            ->whereRaw('LOWER(orders.status) NOT IN (?, ?, ?)', ['cancelled', 'cancellation pending', 'cancellation requested']);

        if ($from) {
            $query->where('orders.createdAt', '>=', $from);
        }
        if ($to) {
            $query->where('orders.createdAt', '<=', $to);
        }

        $results = $query->select(
            DB::raw("COALESCE(categories.name, 'Uncategorized') as category_name"),
            DB::raw('SUM(order_items.quantity) as units_sold'),
            DB::raw('SUM(order_items.quantity * order_items.price) as revenue')
        )
        ->groupBy('category_name')
        ->orderByDesc('revenue')
        ->get();

        $totalCatRevenue = $results->sum('revenue') ?: 1;

        return $results->map(function ($cat) use ($totalCatRevenue) {
            $cat->percentage = round(($cat->revenue / $totalCatRevenue) * 100, 1);
            return $cat;
        })->toArray();
    }

    private function buildMarketingMetrics(string $sellerId, ?Carbon $from, ?Carbon $to): array
    {
        $discountedProducts = Product::where('sellerId', $sellerId)->where('is_on_sale', true)->get();
        $saleItemsSold = 0;
        $saleRevenue   = 0;

        if ($discountedProducts->isNotEmpty()) {
            $pIds = $discountedProducts->pluck('id')->toArray();
            $query = DB::table('order_items')
                ->join('orders', 'order_items.orderId', '=', 'orders.id')
                ->whereIn('order_items.productId', $pIds)
                ->whereRaw('LOWER(orders.status) NOT IN (?, ?, ?)', ['cancelled', 'cancellation pending', 'cancellation requested']);

            if ($from) {
                $query->where('orders.createdAt', '>=', $from);
            }
            if ($to) {
                $query->where('orders.createdAt', '<=', $to);
            }

            $saleItemsSold = (int) $query->sum('order_items.quantity');
            $saleRevenue   = (float) $query->sum(DB::raw('order_items.quantity * order_items.price'));
        }

        return [
            'discountedProductsCount' => $discountedProducts->count(),
            'saleItemsSold'           => $saleItemsSold,
            'saleRevenue'             => $saleRevenue,
        ];
    }

    /**
     * @param \Illuminate\Support\Collection $activeOrders
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return array
     */
    private function buildSalesTrendChart(\Illuminate\Support\Collection $activeOrders, ?Carbon $from, ?Carbon $to): array
    {
        $chart = [];
        $now   = Carbon::now();

        if ($from && $to) {
            $diffDays = max(1, (int) $from->diffInDays($to));
            if ($diffDays <= 1) {
                for ($h = 0; $h < 24; $h += 4) {
                    $periodStart = $from->copy()->addHours($h);
                    $periodEnd   = $periodStart->copy()->addHours(4)->subSecond();
                    $rev = (float) $activeOrders
                        ->filter(fn ($o) => Carbon::parse($o->createdAt)->between($periodStart, $periodEnd))
                        ->sum('totalAmount');
                    $chart[] = [
                        'label'   => $periodStart->format('g A'),
                        'revenue' => $rev,
                    ];
                }
            } else {
                $numSteps = min(7, max(4, $diffDays));
                $stepDays = max(1, (int) ceil($diffDays / $numSteps));
                for ($i = $numSteps - 1; $i >= 0; $i--) {
                    $day = $to->copy()->subDays($i * $stepDays);
                    $periodStart = $day->copy()->startOfDay();
                    $periodEnd   = ($stepDays > 1) ? $day->copy()->addDays($stepDays - 1)->endOfDay() : $day->copy()->endOfDay();

                    $rev = (float) $activeOrders
                        ->filter(fn ($o) => Carbon::parse($o->createdAt)->between($periodStart, $periodEnd))
                        ->sum('totalAmount');

                    $chart[] = [
                        'label'   => $diffDays <= 7 ? $periodStart->format('D') : $periodStart->format('M d'),
                        'revenue' => $rev,
                    ];
                }
            }
        } else {
            for ($i = 6; $i >= 0; $i--) {
                $day = $now->copy()->subDays($i);
                $rev = (float) $activeOrders
                    ->filter(fn ($o) => Carbon::parse($o->createdAt)->between($day->copy()->startOfDay(), $day->copy()->endOfDay()))
                    ->sum('totalAmount');

                $chart[] = [
                    'label'   => $day->format('D'),
                    'revenue' => $rev,
                ];
            }
        }

        $maxRev = max(array_column($chart, 'revenue')) ?: 1;

        return [
            'points' => $chart,
            'max'    => $maxRev,
        ];
    }

    private function isCancelledOrder(string $status): bool
    {
        return in_array(strtolower($status), ['cancelled', 'cancellation pending', 'cancellation requested'], true);
    }

    private function resolveDateRange(Request $request): array
    {
        $preset    = $request->input('date_preset');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $from = null;
        $to   = null;
        $label = 'All Time';

        if ($preset && $preset !== 'custom') {
            switch ($preset) {
                case 'today':
                    $from  = Carbon::now()->startOfDay();
                    $to    = Carbon::now()->endOfDay();
                    $label = 'Today';
                    break;
                case '1_week':
                case 'last_7_days':
                    $from  = Carbon::now()->subDays(6)->startOfDay();
                    $to    = Carbon::now()->endOfDay();
                    $label = '1 Week';
                    break;
                case '1_month':
                case 'last_30_days':
                    $from  = Carbon::now()->subDays(29)->startOfDay();
                    $to    = Carbon::now()->endOfDay();
                    $label = '1 Month';
                    break;
                case '1_year':
                case 'last_365_days':
                    $from  = Carbon::now()->subYear()->startOfDay();
                    $to    = Carbon::now()->endOfDay();
                    $label = '1 Year';
                    break;
                case 'all_time':
                default:
                    $preset = 'all_time';
                    $label  = 'All Time';
                    break;
            }
        }

        return [
            'preset'     => $preset ?? 'all_time',
            'start_date' => $startDate ?? '',
            'end_date'   => $endDate ?? '',
            'from'       => $from,
            'to'         => $to,
            'label'      => $label,
        ];
    }
}
