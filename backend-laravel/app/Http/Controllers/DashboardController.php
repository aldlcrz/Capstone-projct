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

        $ordersQuery = Order::where('sellerId', $sellerId);
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

        $totalRevenue = (float) $activeOrders->sum('totalAmount');
        $orderCount = $activeOrders->count();
        $uniqueCustomers = $activeOrders->pluck('customerId')->unique()->filter()->count();
        $averageOrderValue = $orderCount > 0 ? $totalRevenue / $orderCount : 0;

        $thisMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $thisMonthRevenue = (float) $activeOrders
            ->filter(fn ($order) => Carbon::parse($order->createdAt)->gte($thisMonthStart))
            ->sum('totalAmount');

        $lastMonthRevenue = (float) Order::where('sellerId', $sellerId)
            ->whereBetween('createdAt', [$lastMonthStart, $lastMonthEnd])
            ->get()
            ->reject(fn ($order) => $this->isCancelledOrder($order->status))
            ->sum('totalAmount');

        $revenueChange = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : ($thisMonthRevenue > 0 ? 100 : 0);

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

        $pendingOrders = $statusDistribution['pending'] + $statusDistribution['processing'];

        $lowStockProducts = $products->filter(fn ($product) => $product->stock > 0 && $product->stock <= 5)->values();
        $outOfStockProducts = $products->filter(fn ($product) => (int) $product->stock === 0)->values();
        $pendingApprovalProducts = $products->where('status', 'pending')->count();

        $inventoryHealth = [
            'total' => $products->count(),
            'lowStock' => $lowStockProducts->count(),
            'outOfStock' => $outOfStockProducts->count(),
            'healthy' => max(0, $products->count() - $lowStockProducts->count() - $outOfStockProducts->count()),
        ];

        $productViews = 0;
        $addToCartEvents = 0;

        if (Schema::hasTable('product_views')) {
            $productViews = ProductView::where('seller_id', $sellerId)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();
        }

        if (Schema::hasTable('seller_funnel_events')) {
            $addToCartEvents = SellerFunnelEvent::where('seller_id', $sellerId)
                ->where('event_type', 'add_to_cart')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();
        }

        $shopViews = max($productViews, (int) $products->sum('views'));
        $conversionRate = $shopViews > 0
            ? number_format(($orderCount / $shopViews) * 100, 1)
            : '0.0';

        $topProducts = $this->fetchSellerTopProducts($sellerId, $from, $to);
        $revenueChart = $this->buildSellerRevenueChart($activeOrders, $from, $to);

        $maxChartRevenue = max(array_column($revenueChart, 'revenue')) ?: 1;

        $prescriptions = $this->buildSellerPrescriptions(
            $pendingOrders,
            $inventoryHealth,
            $lowStockProducts,
            $outOfStockProducts,
            $pendingApprovalProducts,
            $products->count(),
            (float) $conversionRate,
            $addToCartEvents,
            $orderCount
        );

        return [
            'filters' => $dateFilter,
            'summary' => [
                'revenue' => $totalRevenue,
                'orders' => $orderCount,
                'customers' => $uniqueCustomers,
                'conversionRate' => "{$conversionRate}%",
                'thisMonthRevenue' => $thisMonthRevenue,
                'revenueChange' => $revenueChange,
                'averageOrderValue' => $averageOrderValue,
                'productViews' => $productViews,
                'pendingOrders' => $pendingOrders,
                'addToCartEvents' => $addToCartEvents,
                'approvedProducts' => $products->where('status', 'approved')->count(),
            ],
            'inventoryHealth' => $inventoryHealth,
            'statusDistribution' => $statusDistribution,
            'prescriptions' => $prescriptions,
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
        ];
    }

    /**
     * Fetch top products for seller dashboard.
     *
     * @param string $sellerId
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return \Illuminate\Support\Collection
     */
    private function fetchSellerTopProducts(string $sellerId, ?Carbon $from, ?Carbon $to): \Illuminate\Support\Collection
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

        return $topProductsQuery->select(
            'products.id',
            'products.name',
            DB::raw('SUM(order_items.quantity) as units_sold'),
            DB::raw('SUM(order_items.quantity * order_items.price) as revenue')
        )
        ->groupBy('products.id', 'products.name')
        ->orderByDesc('units_sold')
        ->limit(5)
        ->get();
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
            ->with(['customer:id,name,email,mobileNumber', 'items.product']);

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
            'processing' => $allOrders->filter(fn($o) => in_array(strtolower($o->status), ['processing', 'to ship', 'confirmed', 'packed']))->count(),
            'shipped'    => $allOrders->filter(fn($o) => in_array(strtolower($o->status), ['shipped', 'to receive']))->count(),
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

    public function updateSellerProfile(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'name'            => 'required|string|max:255',
            'mobileNumber'    => 'nullable|string|max:20',
            'shopName'        => 'nullable|string|max:100',
            'shopDescription' => 'nullable|string|max:500',
            'gcashNumber'     => 'nullable|string|max:20',
            'gcashQrCode'     => 'nullable|image|max:2048',
            'mayaNumber'      => 'nullable|string|max:20',
            'mayaQrCode'      => 'nullable|image|max:2048',
            'profilePhoto'    => 'nullable|image|max:2048',
        ]);
        $user->name            = $request->name;
        $user->mobileNumber    = $request->mobileNumber;
        $user->shopName        = $request->shopName ?? $request->name;
        $user->shopDescription = $request->shopDescription;
        $user->gcashNumber      = $request->gcashNumber;
        $user->mayaNumber       = $request->mayaNumber;
        $user->isGcashAvailable = $request->has('isGcashAvailable');
        $user->isMayaAvailable  = $request->has('isMayaAvailable');

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
}
