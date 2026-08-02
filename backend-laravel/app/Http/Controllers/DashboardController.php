<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\SellerFunnelEvent;
use Illuminate\Http\Request;
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

        if ($startDate || $endDate) {
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
        } elseif ($preset) {
            switch ($preset) {
                case 'today':
                    $from  = Carbon::now()->startOfDay();
                    $to    = Carbon::now()->endOfDay();
                    $label = 'Today (' . $from->format('M d, Y') . ')';
                    break;
                case 'yesterday':
                    $from  = Carbon::yesterday()->startOfDay();
                    $to    = Carbon::yesterday()->endOfDay();
                    $label = 'Yesterday (' . $from->format('M d, Y') . ')';
                    break;
                case 'last_7_days':
                    $from  = Carbon::now()->subDays(6)->startOfDay();
                    $to    = Carbon::now()->endOfDay();
                    $label = 'Last 7 Days';
                    break;
                case 'last_30_days':
                    $from  = Carbon::now()->subDays(29)->startOfDay();
                    $to    = Carbon::now()->endOfDay();
                    $label = 'Last 30 Days';
                    break;
                case 'this_month':
                    $from  = Carbon::now()->startOfMonth();
                    $to    = Carbon::now()->endOfMonth();
                    $label = 'This Month (' . Carbon::now()->format('M Y') . ')';
                    break;
                case 'last_month':
                    $from  = Carbon::now()->subMonth()->startOfMonth();
                    $to    = Carbon::now()->subMonth()->endOfMonth();
                    $label = 'Last Month (' . Carbon::now()->subMonth()->format('M Y') . ')';
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
            'start_date' => $startDate ?? ($from ? $from->format('Y-m-d') : ''),
            'end_date'   => $endDate ?? ($to ? $to->format('Y-m-d') : ''),
            'from'       => $from,
            'to'         => $to,
            'label'      => $label,
        ];
    }

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

        $topProducts = $topProductsQuery->select(
            'products.id',
            'products.name',
            DB::raw('SUM(order_items.quantity) as units_sold'),
            DB::raw('SUM(order_items.quantity * order_items.price) as revenue')
        )
        ->groupBy('products.id', 'products.name')
        ->orderByDesc('units_sold')
        ->limit(5)
        ->get();

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
            // All Time mode: Anchor on latest active order date if recent 7 days have no orders
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
        $orders = Order::where('sellerId', $request->user()->id)
            ->with(['customer:id,name,email,mobileNumber', 'items.product'])
            ->orderBy('createdAt', 'desc')
            ->get();
        return view('seller.orders.index', compact('orders'));
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
            $path = $request->file('profilePhoto')->store('profiles', 'public');
            if ($user->profilePhoto && !str_starts_with($user->profilePhoto, 'http') && !str_starts_with($user->profilePhoto, '/')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profilePhoto);
            }
            $user->profilePhoto = $path;
        }

        if ($request->hasFile('gcashQrCode')) {
            $path = $request->file('gcashQrCode')->store('qrcodes', 'public');
            if ($user->gcashQrCode && !str_starts_with($user->gcashQrCode, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->gcashQrCode);
            }
            $user->gcashQrCode = $path;
        }

        if ($request->hasFile('mayaQrCode')) {
            $path = $request->file('mayaQrCode')->store('qrcodes', 'public');
            if ($user->mayaQrCode && !str_starts_with($user->mayaQrCode, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->mayaQrCode);
            }
            $user->mayaQrCode = $path;
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
}
