<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\SellerFunnelEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function sellerDashboard(Request $request)
    {
        return view('seller.dashboard', $this->buildSellerDashboardData($request->user()->id, $request));
    }

    public function getSellerDashboardSummary(Request $request)
    {
        try {
            return response()->json($this->buildSellerDashboardData($request->user()->id, $request));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error aggregating dashboard data', 'error' => $e->getMessage()], 500);
        }
    }

    public function exportSellerReport(Request $request): StreamedResponse
    {
        $sellerId   = $request->user()->id;
        $dateFilter = $this->resolveDateRange($request);
        $from       = $dateFilter['from'];
        $to         = $dateFilter['to'];

        $ordersQuery = Order::where('sellerId', $sellerId)
            ->with(['customer:id,name,email', 'items.product:id,name']);

        if ($from) {
            $ordersQuery->where('createdAt', '>=', $from);
        }
        if ($to) {
            $ordersQuery->where('createdAt', '<=', $to);
        }

        $orders = $ordersQuery->orderBy('createdAt', 'desc')->get();

        $filename = 'lumbarong-orders-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Order ID', 'Date', 'Customer', 'Email', 'Status', 'Items', 'Total (PHP)', 'Payment Method', 'Reference']);

            foreach ($orders as $order) {
                $items = $order->items->map(function ($item) {
                    $name = $item->product->name ?? 'Deleted Product';
                    return $name . ' x' . $item->quantity;
                })->implode('; ');

                fputcsv($handle, [
                    $order->id,
                    $order->createdAt?->format('Y-m-d H:i'),
                    $order->customer->name ?? 'N/A',
                    $order->customer->email ?? 'N/A',
                    $order->status,
                    $items,
                    $order->totalAmount,
                    $order->paymentMethod ?? 'N/A',
                    $order->paymentReference ?? 'N/A',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
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
                case 'this_month':
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
            $startDate = '';
            $endDate   = '';
        } elseif ($startDate || $endDate || $preset === 'custom') {
            $preset = 'custom';
            if ($startDate) {
                $from = Carbon::parse($startDate)->startOfDay();
            }
            if ($endDate) {
                $to = Carbon::parse($endDate)->endOfDay();
            }
            if ($from && $to) {
                $label = $from->format('M d, Y') . ' - ' . $to->format('M d, Y');
            } elseif ($from) {
                $label = 'From ' . $from->format('M d, Y');
            } elseif ($to) {
                $label = 'Until ' . $to->format('M d, Y');
            }
        } else {
            $preset = 'all_time';
            $label  = 'All Time';
        }

        return [
            'preset'     => $preset ?? 'all_time',
            'start_date' => $startDate ?? ($from ? $from->format('Y-m-d') : ''),
            'end_date'   => $endDate ?? ($to ? $to->format('Y-m-d') : ''),
            'from'       => $from,
            'to'         => $to,
            'label'      => $label,
        ];
    }

    /**
     * Build seller dashboard dataset.
     *
     * @param string $sellerId
     * @param Request|null $request
     * @return array<string, mixed>
     */
    private function buildSellerDashboardData(string $sellerId, ?Request $request = null): array
    {
        $request    = $request ?? request();
        $dateFilter = $this->resolveDateRange($request);
        $from       = $dateFilter['from'];
        $to         = $dateFilter['to'];

        // Retrieve all seller orders (unfiltered for historical timeline stats)
        $allOrders = Order::where('sellerId', $sellerId)
            ->with(['customer:id,name,email', 'items'])
            ->orderBy('createdAt', 'desc')
            ->get();

        $allActiveOrders = $allOrders->reject(fn ($order) => $this->isCancelledOrder($order->status));

        // Filtered orders for date range
        $ordersQuery = Order::where('sellerId', $sellerId)->with(['customer:id,name,email', 'items']);
        if ($from) {
            $ordersQuery->where('createdAt', '>=', $from);
        }
        if ($to) {
            $ordersQuery->where('createdAt', '<=', $to);
        }
        $orders = $ordersQuery->orderBy('createdAt', 'desc')->get();
        $activeOrders = $orders->reject(fn ($order) => $this->isCancelledOrder($order->status));

        $products = Product::where('sellerId', $sellerId)
            ->select('id', 'name', 'price', 'stock', 'status', 'views')
            ->get();

        // 1. SALES SUMMARY CALCULATIONS
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $weeklyStart = $now->copy()->subDays(6)->startOfDay();
        $monthlyStart = $now->copy()->subDays(29)->startOfDay();

        $todaySales = (float) $allActiveOrders
            ->filter(fn ($o) => Carbon::parse($o->createdAt)->gte($todayStart))
            ->sum('totalAmount');

        $weeklySales = (float) $allActiveOrders
            ->filter(fn ($o) => Carbon::parse($o->createdAt)->gte($weeklyStart))
            ->sum('totalAmount');

        $monthlySales = (float) $allActiveOrders
            ->filter(fn ($o) => Carbon::parse($o->createdAt)->gte($monthlyStart))
            ->sum('totalAmount');

        $totalRevenue = (float) $activeOrders->sum('totalAmount');
        $orderCount = $activeOrders->count();

        // Order Pipeline buckets
        $statusDistribution = [
            'pending' => 0,
            'processing' => 0,
            'shipped' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
        foreach ($orders as $order) {
            $bucket = $this->resolveOrderStatusBucket($order->status);
            $statusDistribution[$bucket]++;
        }

        $pendingOrdersCount = $statusDistribution['pending'];
        $readyToShipCount = $statusDistribution['processing'];
        $completedOrdersCount = $statusDistribution['completed'];

        // Custom Orders count (items with custom size or bespoke notes)
        $customOrdersCount = $orders->filter(function ($order) {
            if ($order->items) {
                foreach ($order->items as $item) {
                    $size = strtolower($item->size ?? '');
                    $notes = strtolower($item->notes ?? '');
                    if ($size === 'custom' || str_contains($notes, 'custom') || str_contains($notes, 'embroidery')) {
                        return true;
                    }
                }
            }
            return false;
        })->count();

        // 2. STORE PERFORMANCE METRICS
        $storeRating = (float) DB::table('reviews')
            ->join('products', 'reviews.productId', '=', 'products.id')
            ->where('products.sellerId', $sellerId)
            ->avg('reviews.rating') ?: 5.0;

        $totalFollowers = DB::table('wishlists')
            ->join('products', 'wishlists.product_id', '=', 'products.id')
            ->where('products.sellerId', $sellerId)
            ->distinct('wishlists.user_id')
            ->count('wishlists.user_id');

        $productViews = max((int) $products->sum('views'), 1);
        if (Schema::hasTable('product_views')) {
            $pvCount = ProductView::where('seller_id', $sellerId)->count();
            $productViews = max($productViews, $pvCount);
        }

        $conversionRate = $productViews > 0
            ? number_format(($orderCount / $productViews) * 100, 1)
            : '0.0';

        // Customer List & Repeat Customers
        $customerList = $this->compileSellerCustomerList($allActiveOrders);
        $repeatCustomersCount = $customerList->filter(fn ($c) => (int) data_get($c, 'orderCount') > 1)->count();

        // 3. QUICK ALERTS
        $newOrdersAlertCount = $allActiveOrders->filter(fn ($o) => Carbon::parse($o->createdAt)->gte($now->copy()->subHours(24)))->count();

        $lowStockProducts = $products->filter(fn ($p) => (int) $p->stock > 0 && (int) $p->stock <= 5)->values();
        $outOfStockProducts = $products->filter(fn ($p) => (int) $p->stock === 0)->values();

        $newReviewsCount = DB::table('reviews')
            ->join('products', 'reviews.productId', '=', 'products.id')
            ->where('products.sellerId', $sellerId)
            ->where('reviews.createdAt', '>=', $now->copy()->subDays(7))
            ->count();

        $unreadMessagesCount = Schema::hasTable('messages')
            ? DB::table('messages')->where('receiverId', $sellerId)->where('read', false)->count()
            : 0;

        $topProducts = $this->fetchSellerTopProducts($sellerId, $from, $to);
        $revenueChart = $this->buildSellerRevenueChart($activeOrders, $from, $to);
        $maxChartRevenue = max(array_column($revenueChart, 'revenue')) ?: 1;

        $inventoryHealth = [
            'total' => $products->count(),
            'lowStock' => $lowStockProducts->count(),
            'outOfStock' => $outOfStockProducts->count(),
            'healthy' => max(0, $products->count() - $lowStockProducts->count() - $outOfStockProducts->count()),
        ];

        return [
            'filters' => $dateFilter,
            'salesSummary' => [
                'todaySales' => $todaySales,
                'weeklySales' => $weeklySales,
                'monthlySales' => $monthlySales,
                'totalRevenue' => $totalRevenue,
                'totalOrders' => $orderCount,
                'pendingOrders' => $pendingOrdersCount,
                'customOrders' => $customOrdersCount,
                'readyToShip' => $readyToShipCount,
                'completedOrders' => $completedOrdersCount,
            ],
            'storePerformance' => [
                'rating' => round($storeRating, 1),
                'followers' => $totalFollowers,
                'productViews' => $productViews,
                'conversionRate' => "{$conversionRate}%",
                'repeatCustomers' => $repeatCustomersCount,
                'totalCustomers' => $customerList->count(),
            ],
            'quickAlerts' => [
                'newOrders' => $newOrdersAlertCount,
                'lowStock' => $lowStockProducts->count(),
                'newReviews' => $newReviewsCount,
                'messages' => $unreadMessagesCount,
            ],
            'summary' => [
                'revenue' => $totalRevenue,
                'orders' => $orderCount,
                'customers' => $customerList->count(),
                'conversionRate' => "{$conversionRate}%",
                'thisMonthRevenue' => $monthlySales,
                'revenueChange' => 0,
                'averageOrderValue' => $orderCount > 0 ? $totalRevenue / $orderCount : 0,
                'productViews' => $productViews,
                'pendingOrders' => $pendingOrdersCount,
                'approvedProducts' => $products->where('status', 'approved')->count(),
            ],
            'inventoryHealth' => $inventoryHealth,
            'statusDistribution' => $statusDistribution,
            'recentActivity' => $orders->take(6)->map(fn ($order) => [
                'id' => $order->id,
                'status' => $order->status,
                'amount' => $order->totalAmount,
                'date' => $order->createdAt,
            ])->values(),
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts->take(5)->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
            ])->values(),
            'revenueChart' => $revenueChart,
            'maxChartRevenue' => $maxChartRevenue,
            'customerList' => $customerList,
        ];
    }

    /**
     * Compute customer list for seller analytics.
     *
     * @param Collection $allActiveOrders
     * @return Collection
     */
    private function compileSellerCustomerList($allActiveOrders): Collection
    {
        return $allActiveOrders
            ->filter(fn ($o) => !empty($o->customerId))
            ->groupBy('customerId')
            ->map(function ($customerOrders) {
                $first = $customerOrders->first();
                return [
                    'id'          => $first->customerId,
                    'name'        => optional($first->customer)->name ?? 'Customer',
                    'email'       => optional($first->customer)->email ?? '—',
                    'orderCount'  => $customerOrders->count(),
                    'ordersCount' => $customerOrders->count(),
                    'totalSpent'  => $customerOrders->sum('totalAmount'),
                    'lastOrder'   => $customerOrders->max('createdAt'),
                ];
            })
            ->sortByDesc('totalSpent')
            ->values();
    }

    /**
     * Fetch top products for seller dashboard.
     *
     * @param string $sellerId
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return Collection
     */
    private function fetchSellerTopProducts(string $sellerId, ?Carbon $from, ?Carbon $to): Collection
    {
        $topProductsQuery = DB::table('order_items')
            ->join('orders', 'order_items.orderId', '=', 'orders.id')
            ->join('products', 'order_items.productId', '=', 'products.id')
            ->where('orders.sellerId', $sellerId)
            ->whereRaw('LOWER(orders.status) NOT IN (?, ?, ?)', ['cancelled', 'cancellation pending', 'cancellation requested']);

        if ($from) {
            $topProductsQuery->where('orders.createdAt', '>=', $from);
        }
        if ($to) {
            $topProductsQuery->where('orders.createdAt', '<=', $to);
        }

        $top = $topProductsQuery->select(
            'products.id',
            'products.name',
            'products.stock',
            'products.price',
            'products.status',
            DB::raw('SUM(order_items.quantity) as units_sold'),
            DB::raw('SUM(order_items.quantity * order_items.price) as revenue')
        )
        ->groupBy('products.id', 'products.name', 'products.stock', 'products.price', 'products.status')
        ->orderByDesc('units_sold')
        ->limit(5)
        ->get();

        return $top->map(function ($item) {
            $productModel = Product::find($item->id);
            $item->image = $productModel ? $productModel->getImageUrl() : asset('images/placeholder.jpg');
            return $item;
        });
    }

    /**
     * Build revenue chart dataset for seller dashboard.
     *
     * @param Collection $activeOrders
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return array<int, array<string, mixed>>
     */
    private function buildSellerRevenueChart(Collection $activeOrders, ?Carbon $from, ?Carbon $to): array
    {
        $revenueChart = [];
        if ($from && $to) {
            $diffDays = max(1, (int) $from->diffInDays($to));
            $stepDays = max(1, (int) ceil($diffDays / 6));
            for ($i = 6; $i >= 0; $i--) {
                $day = $to->copy()->subDays($i * $stepDays);
                $periodStart = $day->copy()->startOfDay();
                $periodEnd   = ($stepDays > 1) ? $day->copy()->addDays($stepDays - 1)->endOfDay() : $day->copy()->endOfDay();

                $dayRevenue = (float) $activeOrders
                    ->filter(fn ($order) => Carbon::parse($order->createdAt)->between($periodStart, $periodEnd))
                    ->sum('totalAmount');

                $revenueChart[] = [
                    'label' => $diffDays <= 7 ? $periodStart->format('D') : $periodStart->format('M d'),
                    'date' => $periodStart->format('M d'),
                    'revenue' => $dayRevenue,
                ];
            }
        } else {
            $recentCount = $activeOrders->filter(fn ($order) => Carbon::parse($order->createdAt)->gte(Carbon::now()->subDays(6)->startOfDay()))->count();
            $anchorEnd = ($recentCount === 0 && $activeOrders->isNotEmpty())
                ? Carbon::parse($activeOrders->first()->createdAt ?? Carbon::now())
                : Carbon::now();

            for ($i = 6; $i >= 0; $i--) {
                $day = $anchorEnd->copy()->subDays($i);
                $dayRevenue = (float) $activeOrders
                    ->filter(fn ($order) => Carbon::parse($order->createdAt)->between(
                        $day->copy()->startOfDay(),
                        $day->copy()->endOfDay()
                    ))
                    ->sum('totalAmount');

                $revenueChart[] = [
                    'label' => $day->format('D'),
                    'date' => $day->format('M d'),
                    'revenue' => $dayRevenue,
                ];
            }
        }

        return $revenueChart;
    }

    private function isCancelledOrder(string $status): bool
    {
        return in_array(strtolower($status), ['cancelled', 'cancellation pending', 'cancellation requested'], true);
    }

    private function resolveOrderStatusBucket(string $status): string
    {
        $status = strtolower($status);

        if ($status === 'pending') {
            return 'pending';
        }

        if (in_array($status, ['processing', 'to ship', 'confirmed', 'packed'], true)) {
            return 'processing';
        }

        if (in_array($status, ['shipped', 'to receive'], true)) {
            return 'shipped';
        }

        if (in_array($status, ['delivered', 'completed'], true)) {
            return 'completed';
        }

        if (in_array($status, ['cancelled', 'cancellation pending', 'cancellation requested'], true)) {
            return 'cancelled';
        }

        return 'processing';
    }

    private function buildSellerPrescriptions(
        int $pendingOrders,
        array $inventoryHealth,
        mixed $lowStockProducts,
        mixed $outOfStockProducts,
        int $pendingApprovalProducts,
        int $totalProducts,
        float $conversionRate,
        int $addToCartEvents,
        int $orderCount
    ): array {
        $prescriptions = [];

        if ($pendingOrders > 0) {
            $prescriptions[] = [
                'priority' => 'urgent',
                'title' => 'Process Pending Orders',
                'message' => "You have {$pendingOrders} order(s) that need attention. Fast fulfillment improves buyer trust.",
                'action' => 'View Orders',
                'url' => route('seller.orders'),
            ];
        }

        if ($inventoryHealth['outOfStock'] > 0) {
            $names = $outOfStockProducts->take(2)->pluck('name')->implode(', ');
            $prescriptions[] = [
                'priority' => 'urgent',
                'title' => 'Out of Stock Items',
                'message' => "{$inventoryHealth['outOfStock']} product(s) are sold out" . ($names ? " ({$names})" : '') . '. Restock to avoid missed sales.',
                'action' => 'Manage Inventory',
                'url' => route('seller.products.index'),
            ];
        }

        if ($inventoryHealth['lowStock'] > 0) {
            $prescriptions[] = [
                'priority' => 'warning',
                'title' => 'Low Stock Warning',
                'message' => "{$inventoryHealth['lowStock']} product(s) have 5 or fewer pieces left. Plan a restock soon.",
                'action' => 'Review Stock',
                'url' => route('seller.products.index'),
            ];
        }

        if ($pendingApprovalProducts > 0) {
            $prescriptions[] = [
                'priority' => 'warning',
                'title' => 'Products Awaiting Approval',
                'message' => "{$pendingApprovalProducts} listing(s) are pending admin review and are not visible to buyers yet.",
                'action' => 'View Listings',
                'url' => route('seller.products.index'),
            ];
        }

        if ($totalProducts === 0) {
            $prescriptions[] = [
                'priority' => 'warning',
                'title' => 'Start Your Catalogue',
                'message' => 'You have no products listed yet. Add your first Barong piece to start receiving orders.',
                'action' => 'Add Product',
                'url' => route('seller.products.create'),
            ];
        }

        if ($addToCartEvents > 0 && $orderCount === 0) {
            $prescriptions[] = [
                'priority' => 'warning',
                'title' => 'Cart Interest, No Orders Yet',
                'message' => "Buyers added items to cart {$addToCartEvents} time(s) this month. Competitive pricing and clear photos can help close sales.",
                'action' => 'Improve Listings',
                'url' => route('seller.products.index'),
            ];
        }

        if ($conversionRate > 0 && $conversionRate < 2 && $orderCount > 0) {
            $prescriptions[] = [
                'priority' => 'info',
                'title' => 'Boost Conversion',
                'message' => 'Your view-to-order rate is below 2%. Try Lumban Special discounts or update product descriptions.',
                'action' => 'Edit Products',
                'url' => route('seller.products.index'),
            ];
        }

        if (empty($prescriptions)) {
            $prescriptions[] = [
                'priority' => 'info',
                'title' => 'Strong Performance',
                'message' => 'Your shop metrics look healthy. Keep your catalogue updated and respond quickly to buyer messages.',
                'action' => 'View Messages',
                'url' => route('seller.messages'),
            ];
        }

        return array_slice($prescriptions, 0, 4);
    }

    public function sellerOrders(Request $request)
    {
        $sellerId = $request->user()->id;
        $query = Order::where('sellerId', $sellerId)
            ->with(['customer:id,name,email,mobileNumber', 'items.product', 'reviews.customer:id,name,profilePhoto']);

        $status = strtolower($request->input('status', 'all'));
        if ($status && $status !== 'all') {
            if ($status === 'processing') {
                $query->whereIn(DB::raw('LOWER(status)'), ['processing', 'to ship', 'confirmed', 'packed']);
            } elseif ($status === 'shipped') {
                $query->whereIn(DB::raw('LOWER(status)'), ['shipped', 'to receive']);
            } elseif ($status === 'delivered') {
                $query->whereIn(DB::raw('LOWER(status)'), ['delivered']);
            } elseif ($status === 'completed') {
                $query->whereIn(DB::raw('LOWER(status)'), ['completed']);
            } elseif ($status === 'cancelled') {
                $query->whereIn(DB::raw('LOWER(status)'), ['cancelled', 'cancellation pending', 'cancellation requested']);
            } elseif ($status === 'pending') {
                $query->whereIn(DB::raw('LOWER(status)'), ['pending']);
            } else {
                $query->where(DB::raw('LOWER(status)'), $status);
            }
        }

        if ($request->filled('search')) {
            $s = strtolower($request->search);
            $query->where(function($q) use ($s) {
                $q->where(DB::raw('LOWER(id)'), 'like', "%{$s}%")
                  ->orWhereHas('customer', function($cq) use ($s) {
                      $cq->where(DB::raw('LOWER(name)'), 'like', "%{$s}%")
                         ->orWhere(DB::raw('LOWER(email)'), 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('start_date')) {
            $query->where('createdAt', '>=', Carbon::parse($request->start_date)->startOfDay());
        }

        if ($request->filled('end_date')) {
            $query->where('createdAt', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        $orders = $query->orderBy('createdAt', 'desc')->get();

        $allOrders = Order::where('sellerId', $sellerId)->get();
        $counts = [
            'all'        => $allOrders->count(),
            'pending'    => $allOrders->filter(fn($o) => strtolower($o->status) === 'pending')->count(),
            'to ship'    => $allOrders->filter(fn($o) => in_array(strtolower($o->status), ['to ship', 'to_ship', 'processing', 'ready to ship', 'ready_to_ship']))->count(),
            'shipped'    => $allOrders->filter(fn($o) => strtolower($o->status) === 'shipped')->count(),
            'in transit' => $allOrders->filter(fn($o) => in_array(strtolower($o->status), ['in transit', 'in_transit']))->count(),
            'delivered'  => $allOrders->filter(fn($o) => strtolower($o->status) === 'delivered')->count(),
            'completed'  => $allOrders->filter(fn($o) => strtolower($o->status) === 'completed')->count(),
            'cancelled'  => $allOrders->filter(fn($o) => in_array(strtolower($o->status), ['cancelled', 'cancellation pending', 'cancellation requested']))->count(),
        ];

        return view('seller.orders.index', compact('orders', 'counts', 'status'));
    }

    public function sellerProfile(Request $request)
    {
        $user = $request->user();
        return view('seller.profile.index', compact('user'));
    }

    public function sellerPolicies(Request $request)
    {
        $user = $request->user();
        return view('seller.policies.index', compact('user'));
    }

    public function updateSellerPolicies(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'cancellation_policy' => 'nullable|string|max:2000',
            'refund_policy'       => 'nullable|string|max:2000',
        ]);

        $user->cancellation_policy = $request->cancellation_policy;
        $user->refund_policy       = $request->refund_policy;
        $user->save();

        return redirect()->route('seller.policies.index')->with('success', 'Shop Cancellation and Refund policies updated successfully!');
    }

    public function updateSellerProfile(Request $request)
    {
        $user = $request->user();

        if (!$request->filled('name') && $user->name) {
            $request->merge(['name' => $user->name]);
        }

        $request->validate([
            'name'                 => 'required|string|max:255',
            'mobileNumber'         => 'nullable|string|max:20',
            'shopName'             => 'nullable|string|max:100',
            'shopDescription'      => 'nullable|string|max:500',
            'cancellation_policy'  => 'nullable|string|max:2000',
            'refund_policy'        => 'nullable|string|max:2000',
            'gcashNumber'          => 'nullable|string|max:20',
            'gcashQrCode'          => 'nullable|image|max:5120',
            'mayaNumber'           => 'nullable|string|max:20',
            'mayaQrCode'           => 'nullable|image|max:5120',
            'profilePhoto'         => 'nullable|image|max:5120',
            'businessPermit'       => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'birDocument'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'residencyCertificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        // Validate GCash: both number and QR required if configured
        if ($request->filled('gcashNumber') || $request->hasFile('gcashQrCode')) {
            $hasGcashNumber = $request->filled('gcashNumber');
            $hasGcashQr = $request->hasFile('gcashQrCode') || !empty($user->gcashQrCode);
            if (!$hasGcashNumber || !$hasGcashQr) {
                return redirect()->back()->withInput()->with('error', 'GCash requires both a mobile number and a QR code image.');
            }
        }

        // Validate Maya: both number and QR required if configured
        if ($request->filled('mayaNumber') || $request->hasFile('mayaQrCode')) {
            $hasMayaNumber = $request->filled('mayaNumber');
            $hasMayaQr = $request->hasFile('mayaQrCode') || !empty($user->mayaQrCode);
            if (!$hasMayaNumber || !$hasMayaQr) {
                return redirect()->back()->withInput()->with('error', 'Maya requires both an account number and a QR code image.');
            }
        }

        $user->name                = $request->name;
        $user->mobileNumber        = $request->mobileNumber;
        $user->shopName            = $request->shopName ?? $request->name;
        $user->shopDescription     = $request->shopDescription;
        $user->cancellation_policy = $request->cancellation_policy;
        $user->refund_policy       = $request->refund_policy;
        $user->gcashNumber         = $request->gcashNumber;
        $user->mayaNumber          = $request->mayaNumber;
        $user->isGcashAvailable    = $request->has('isGcashAvailable');
        $user->isMayaAvailable     = $request->has('isMayaAvailable');

        if ($request->hasFile('profilePhoto')) {
            $file = $request->file('profilePhoto');
            $filename = time() . '_seller_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/avatars'), $filename);
            $user->profilePhoto = '/uploads/avatars/' . $filename;
        }

        if ($request->hasFile('gcashQrCode')) {
            $file = $request->file('gcashQrCode');
            $filename = time() . '_gcash_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/qrcodes'), $filename);
            $user->gcashQrCode = '/uploads/qrcodes/' . $filename;
        }

        if ($request->hasFile('mayaQrCode')) {
            $file = $request->file('mayaQrCode');
            $filename = time() . '_maya_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/qrcodes'), $filename);
            $user->mayaQrCode = '/uploads/qrcodes/' . $filename;
        }

        foreach (['businessPermit', 'birDocument', 'residencyCertificate'] as $docField) {
            if ($request->hasFile($docField)) {
                $file = $request->file($docField);
                $filename = time() . '_' . $docField . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/requirements'), $filename);
                $user->{$docField} = '/uploads/requirements/' . $filename;
            }
        }

        $user->save();
        return redirect()->route('seller.profile')->with('success', 'Profile updated successfully.');
    }

    public function notifications()
    {
        $notifications = \App\Models\Notification::where('userId', \Illuminate\Support\Facades\Auth::id())
            ->where('targetRole', 'seller')
            ->orderBy('createdAt', 'desc')
            ->paginate(15);

        // Mark all as read when visiting
        \App\Models\Notification::where('userId', \Illuminate\Support\Facades\Auth::id())
            ->where('targetRole', 'seller')
            ->where('isRead', false)
            ->update(['isRead' => true]);

        return view('seller.notifications', compact('notifications'));
    }

    public function readAllNotifications()
    {
        \App\Models\Notification::where('userId', \Illuminate\Support\Facades\Auth::id())
            ->where('targetRole', 'seller')
            ->where('isRead', false)
            ->update(['isRead' => true]);

        return redirect()->back()->with('success', 'All seller notifications marked as read.');
    }

    public function sellerCommission(Request $request)
    {
        $seller = $request->user();
        $rate = (float) (\App\Models\SystemSetting::where('key', 'commission_rate')->value('value') ?? 5);
        $period = Carbon::now()->format('Y-m');
        [$year, $month] = explode('-', $period);

        $totalSales = (float) Order::whereNotIn('status', ['Cancelled', 'cancellation pending', 'cancellation requested'])
            ->where('sellerId', $seller->id)
            ->whereYear('createdAt', $year)
            ->whereMonth('createdAt', $month)
            ->sum('totalAmount');

        $commissionDue = round($totalSales * ($rate / 100), 2);

        $paymentSettings = [
            'gcash_number' => \App\Models\SystemSetting::where('key', 'superadmin_gcash_number')->value('value') ?? '',
            'gcash_qr'     => \App\Models\SystemSetting::where('key', 'superadmin_gcash_qr')->value('value') ?? '',
            'maya_number'  => \App\Models\SystemSetting::where('key', 'superadmin_maya_number')->value('value') ?? '',
            'maya_qr'      => \App\Models\SystemSetting::where('key', 'superadmin_maya_qr')->value('value') ?? '',
        ];

        $pastRecords = \App\Models\CommissionRecord::where('sellerId', $seller->id)
            ->orderByDesc('created_at')
            ->get();

        $currentRecord = $pastRecords->firstWhere('period', $period);

        return view('seller.commission.index', compact(
            'seller', 'rate', 'period', 'totalSales', 'commissionDue',
            'paymentSettings', 'pastRecords', 'currentRecord'
        ));
    }

    public function submitCommissionPayment(Request $request)
    {
        $request->validate([
            'period'           => 'required|string',
            'paymentMethod'    => 'required|string|in:GCash,Maya,Bank Transfer',
            'referenceNumber'  => 'required|string|max:100',
            'paymentProof'     => 'required|image|max:3072',
            'notes'            => 'nullable|string|max:500',
        ]);

        $seller = $request->user();
        $rate = (float) (\App\Models\SystemSetting::where('key', 'commission_rate')->value('value') ?? 5);
        [$year, $month] = explode('-', $request->period);

        $totalSales = (float) Order::whereNotIn('status', ['Cancelled', 'cancellation pending', 'cancellation requested'])
            ->where('sellerId', $seller->id)
            ->whereYear('createdAt', $year)
            ->whereMonth('createdAt', $month)
            ->sum('totalAmount');

        $commissionAmount = round($totalSales * ($rate / 100), 2);

        $proofPath = '';
        if ($request->hasFile('paymentProof')) {
            $file = $request->file('paymentProof');
            $filename = time() . '_commission_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/payments'), $filename);
            $proofPath = '/uploads/payments/' . $filename;
        }

        \App\Models\CommissionRecord::updateOrCreate(
            ['sellerId' => $seller->id, 'period' => $request->period],
            [
                'totalSales'       => $totalSales,
                'commissionRate'   => $rate,
                'commissionAmount' => $commissionAmount,
                'status'           => 'verification_pending',
                'paymentMethod'    => $request->paymentMethod,
                'referenceNumber'  => $request->referenceNumber,
                'paymentProof'     => $proofPath,
                'notes'            => $request->notes,
            ]
        );

        return redirect()->back()->with('success', 'Commission payment proof submitted! Awaiting Super Admin verification.');
    }

    public function sellerCustomers(Request $request)
    {
        $sellerId = $request->user()->id;
        $orders = Order::where('sellerId', $sellerId)
            ->with(['customer', 'items.product'])
            ->orderBy('createdAt', 'desc')
            ->get();

        $customerList = $orders->groupBy(function ($order) {
            if (!empty($order->customerId)) {
                return (string) $order->customerId;
            }
            if ($order->customer && !empty($order->customer->email)) {
                return strtolower(trim($order->customer->email));
            }
            if (!empty($order->customer_email)) {
                return strtolower(trim($order->customer_email));
            }
            return 'guest_' . $order->id;
        })->map(function ($customerOrders) {
            $firstOrder = $customerOrders->first();
            $customer = $firstOrder->customer ?? null;
            return [
                'id'            => $customer->id ?? null,
                'name'          => $customer->name ?? $firstOrder->customer_name ?? 'Guest Customer',
                'email'         => $customer->email ?? $firstOrder->customer_email ?? 'N/A',
                'phone'         => $customer->mobileNumber ?? $customer->phone ?? $firstOrder->customer_phone ?? 'N/A',
                'avatar'        => $customer->profilePhoto ?? null,
                'ordersCount'   => $customerOrders->count(),
                'totalSpent'    => (float) $customerOrders->sum('totalAmount'),
                'lastOrderDate' => $customerOrders->max('createdAt'),
                'history'        => $customerOrders->map(function ($ord) {
                    return [
                        'id'            => $ord->id,
                        'orderNumber'   => $ord->orderNumber ?? '#' . $ord->id,
                        'status'        => $ord->status ?? 'pending',
                        'totalAmount'   => (float) $ord->totalAmount,
                        'paymentMethod' => $ord->paymentMethod ?? $ord->payment_method ?? 'COD',
                        'date'          => $ord->createdAt ? Carbon::parse($ord->createdAt)->format('M d, Y • g:i A') : 'N/A',
                        'items'         => $ord->items ? $ord->items->map(function ($it) {
                            return [
                                'name'     => $it->product->name ?? 'Artisan Item',
                                'quantity' => $it->quantity,
                                'price'    => (float) $it->price,
                            ];
                        })->values() : [],
                    ];
                })->values(),
            ];
        })->values();

        return view('seller.customers.index', compact('customerList'));
    }
}
