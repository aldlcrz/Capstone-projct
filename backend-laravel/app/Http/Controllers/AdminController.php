<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SystemSetting;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard(Request $request): \Illuminate\View\View
    {
        $preset = $request->query('date_preset', $request->query('range', 'all_time'));
        $stats = json_decode($this->getGlobalStats($request)->getContent(), true);

        $recentActivity = Notification::where('targetRole', 'admin')
            ->orderBy('createdAt', 'desc')
            ->limit(8)
            ->get();

        $filters = [
            'preset' => $preset,
            'start_date' => $request->query('start_date', ''),
            'end_date' => $request->query('end_date', ''),
        ];

        return view('admin.dashboard', [
            'range'          => $preset,
            'filters'        => $filters,
            'stats'          => $stats,
            'recentActivity' => $recentActivity,
            'revenueTrend'   => $this->getRevenueTrend(),
            'orderStatuses'  => $this->getOrderStatusBreakdown(),
            'topSellers'     => $this->getTopSellers(),
            'topProducts'    => $this->getTopProducts(),
            'pendingActions' => $this->getPendingActionCounts(),
            'userTrend'      => $this->getUserRegistrationTrend(),
            'aov'            => (float) (Order::whereNotIn('status', ['Cancelled'])->avg('totalAmount') ?? 0),
            'userCounts'     => $this->getUserCounts(),
        ]);
    }

    /** Revenue (PHP) for each of the last 7 days. */
    private function getRevenueTrend(): \Illuminate\Support\Collection
    {
        return collect(range(6, 0))->map(function (int $daysAgo): array {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date'    => $date->format('M d'),
                'revenue' => (float) round(
                    Order::whereNotIn('status', ['Cancelled'])
                         ->whereDate('createdAt', $date)
                         ->sum('totalAmount'),
                    2
                ),
            ];
        });
    }

    /** Order count keyed by status string. */
    private function getOrderStatusBreakdown(): \Illuminate\Support\Collection
    {
        return Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
    }

    /** Top 5 sellers ranked by gross revenue. */
    private function getTopSellers()
    {
        return DB::table('orders')
            ->join('users', 'orders.sellerId', '=', 'users.id')
            ->select(
                'users.id as sellerId',
                'users.name',
                'users.shopName',
                DB::raw('SUM(orders.totalAmount) as revenue'),
                DB::raw('COUNT(orders.id) as orders')
            )
            ->whereNotIn('orders.status', ['Cancelled'])
            ->groupBy('users.id', 'users.name', 'users.shopName')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();
    }

    /** Top 5 products ranked by units sold. */
    private function getTopProducts()
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.orderId', '=', 'orders.id')
            ->join('products', 'order_items.productId', '=', 'products.id')
            ->select(
                'products.id as productId',
                'products.name',
                DB::raw('SUM(order_items.quantity) as units'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->whereNotIn('orders.status', ['Cancelled'])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units')
            ->limit(5)
            ->get();
    }

    /** Pending item counts across all admin action queues. */
    private function getPendingActionCounts(): array
    {
        return [
            'products'      => Product::where('status', 'pending')->count(),
            'sellers'       => User::where('role', 'seller')->where('isVerified', false)->where('status', '!=', 'blocked')->count(),
            'banners'       => \App\Models\Banner::whereNotNull('userId')->where('status', 'pending')->count(),
            'reports'       => \App\Models\Report::where('status', 'Pending')->count(),
        ];
    }

    /** New user registrations for each of the last 7 days. */
    private function getUserRegistrationTrend(): \Illuminate\Support\Collection
    {
        return collect(range(6, 0))->map(function (int $daysAgo): array {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date'  => $date->format('M d'),
                'count' => User::whereDate('createdAt', $date)->count(),
            ];
        });
    }

    /** Total platform user counts by role. */
    private function getUserCounts(): array
    {
        return [
            'customers' => User::where('role', 'customer')->count(),
            'sellers'   => User::where('role', 'seller')->where('isVerified', true)->count(),
            'admins'    => User::where('role', 'admin')->count(),
        ];
    }

    /**
     * Helper to send notifications.
     */
    private function sendNotification(string $userId, string $title, string $message, string $type = 'system', ?string $link = null, string $role = 'customer')
    {
        try {
            Notification::create([
                'userId' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'link' => $link,
                'targetRole' => $role,
                'isRead' => false
            ]);
        } catch (\Exception $e) {
            Log::error('Notification error: ' . $e->getMessage());
        }
    }

    public function getGlobalStats(Request $request)
    {
        try {
            $preset = $request->query('date_preset', $request->query('range', 'all_time'));
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            $query = Order::query();
            $bounds = null;

            if ($preset && !in_array($preset, ['all', 'all_time'])) {
                $bounds = $this->getRangeBounds($preset, $startDate, $endDate);
                if (is_array($bounds)) {
                    $query->whereBetween('createdAt', $bounds);
                }
            }

            $totalSalesValue = $query->whereNotIn('status', ['Cancelled'])->sum('totalAmount') ?: 0;
            $totalOrdersCount = $query->count();
            
            $totalCustomersCount = User::where('role', 'customer')
                ->where('status', '!=', 'blocked')
                ->count();
            
            $totalProductsCount = Product::count();

            // Calculate capital based on order items
            $capitalQuery = DB::table('order_items')
                ->join('orders', 'order_items.orderId', '=', 'orders.id')
                ->join('products', 'order_items.productId', '=', 'products.id')
                ->whereNotIn('orders.status', ['Cancelled']);

            if (is_array($bounds)) {
                $capitalQuery->whereBetween('orders.createdAt', $bounds);
            }

            $totalCapital = $capitalQuery->selectRaw('SUM(order_items.quantity * products.costPerPiece) as total_capital')
                ->value('total_capital') ?: 0;

            $totalProfit = $totalSalesValue - $totalCapital;

            return response()->json([
                'totalSales' => '₱' . number_format($totalSalesValue),
                'totalCapital' => '₱' . number_format($totalCapital),
                'totalRevenue' => '₱' . number_format($totalSalesValue),
                'totalProfit' => '₱' . number_format($totalProfit),
                'totalOrders' => number_format($totalOrdersCount),
                'activeCustomers' => number_format($totalCustomersCount),
                'liveProducts' => number_format($totalProductsCount)
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function getRangeBounds(string $range, ?string $startDate = null, ?string $endDate = null)
    {
        switch ($range) {
            case 'today': return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
            case 'yesterday': return [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
            case 'last_7_days': return [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()];
            case 'last_30_days': return [Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay()];
            case 'this_month': return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            case 'last_month': return [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];
            case 'week': return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            case 'month': return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            case 'year': return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            case 'custom':
                $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
                $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();
                return [$start, $end];
            default: return null;
        }
    }

    public function getAllUsers(Request $request)
    {
        $role = $request->query('role');
        $query = User::query()->where('status', '!=', 'blocked');
        
        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('createdAt', 'desc')->get();
        return response()->json($users);
    }

    public function getPendingSellers()
    {
        $sellers = User::where('role', 'seller')
            ->where('isVerified', false)
            ->where('status', '!=', 'blocked')
            ->get();
        return response()->json($sellers);
    }

    public function getSellers()
    {
        $sellers = User::where('role', 'seller')
            ->where('isVerified', true)
            ->where('status', '!=', 'blocked')
            ->orderBy('createdAt', 'desc')
            ->get();
        return response()->json($sellers);
    }

    public function getCustomers()
    {
        $customers = User::where('role', 'customer')
            ->where('status', '!=', 'blocked')
            ->orderBy('createdAt', 'desc')
            ->get();
        return response()->json($customers);
    }

    public function verifySeller(string $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Seller not found'], 404);

        $user->isVerified = true;
        $user->status     = 'active';
        $user->save();

        $this->sendNotification($user->id, 'Seller verification approved', 'Your artisan workshop is now verified and can access seller tools.', 'system', '/seller/dashboard', 'seller');

        return response()->json(['message' => 'Seller verified successfully', 'user' => $user]);
    }

    public function rejectSeller(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Seller not found'], 404);

        $reason = $request->input('reason');
        if (!$reason) return response()->json(['message' => 'Reason is required'], 400);

        $user->status = 'rejected';
        $user->rejectionReason = $reason;
        $user->isVerified = false;
        $user->save();

        $this->sendNotification($user->id, 'Seller Application Rejected', "Your artisan application was not approved. Reason: {$reason}", 'system', '/seller/profile', 'seller');

        return response()->json(['message' => 'Seller application rejected']);
    }

    public function getPendingProducts()
    {
        $products = Product::where('status', 'pending')
            ->with('seller:id,name,email')
            ->orderBy('createdAt', 'asc')
            ->get();
        return response()->json($products);
    }

    public function approveProduct(string $id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['message' => 'Product not found'], 404);

        $product->status = 'approved';
        $product->rejectionReason = null;
        $product->save();

        $this->sendNotification($product->sellerId, 'Product Approved', "Your product \"{$product->name}\" has been approved and is now live!", 'product_approved', "/seller/products/{$product->id}", 'seller');
        
        try {
            \App\Services\WishlistService::handleProductRestocked($product);
        } catch (\Throwable $we) {
            Log::warning('Wishlist restock handling error on approval API: ' . $we->getMessage());
        }

        return response()->json(['message' => 'Product approved successfully', 'product' => $product]);
    }

    public function rejectProduct(Request $request, string $id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['message' => 'Product not found'], 404);

        $reason = $request->input('reason');
        if (!$reason) return response()->json(['message' => 'Reason is required'], 400);

        $product->status = 'rejected';
        $product->rejectionReason = $reason;
        $product->save();

        $this->sendNotification($product->sellerId, 'Product Rejected', "Your product \"{$product->name}\" was rejected. Reason: {$reason}", 'product_rejected', "/seller/products/{$product->id}", 'seller');

        return response()->json(['message' => 'Product rejected successfully', 'product' => $product]);
    }

    public function blockUser(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $reason = $request->input('reason', 'Account terminated by administrator.');
        $user->status = 'blocked';
        $user->violationReason = $reason;
        $user->save();

        // Invalidate active web and API sessions immediately
        try {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
        } catch (\Throwable $e) {}

        return response()->json(['message' => 'User blocked successfully']);
    }

    public function freezeUser(Request $request, string $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $reason = $request->input('reason');
        if (!$reason) return response()->json(['message' => 'Reason is required'], 400);

        $user->status = 'frozen';
        $user->violationReason = $reason;
        $user->save();

        return response()->json(['message' => 'User frozen successfully']);
    }

    public function unfreezeUser(string $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $user->status = 'active';
        $user->violationReason = null;
        $user->save();

        return response()->json(['message' => 'User unfrozen successfully']);
    }

    public function getSettings()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');
        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        $updates = $request->all();
        foreach ($updates as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return response()->json(['message' => 'Settings updated successfully']);
    }

    public function exportGlobalReport()
    {
        $filename = 'lumbarong_admin_report_' . now()->format('Ymd_His') . '.csv';

        // Build CSV in memory to avoid output-buffering conflicts with Laravel middleware
        ob_start();
        $out = fopen('php://output', 'w');

        // UTF-8 BOM — ensures Excel opens with correct encoding
        fputs($out, "\xEF\xBB\xBF");

        $section = function (string $title) use ($out) {
            fputcsv($out, []);
            fputcsv($out, ['=== ' . strtoupper($title) . ' ===']);
        };

        // ── SECTION 1 — Platform Summary ────────────────────────────────────
        $section('Platform Summary');
        fputcsv($out, ['Generated At', now()->format('Y-m-d H:i:s')]);
        fputcsv($out, []);

        $totalRevenue = Order::whereNotIn('status', ['Cancelled'])->sum('totalAmount') ?: 0;
        $totalCapital = DB::table('order_items')
            ->join('orders',   'order_items.orderId',   '=', 'orders.id')
            ->join('products', 'order_items.productId', '=', 'products.id')
            ->whereNotIn('orders.status', ['Cancelled'])
            ->selectRaw('SUM(order_items.quantity * products.costPerPiece) as cap')
            ->value('cap') ?: 0;
        $totalProfit  = $totalRevenue - $totalCapital;
        $totalOrders  = Order::count();
        $aov          = Order::whereNotIn('status', ['Cancelled'])->avg('totalAmount') ?: 0;

        fputcsv($out, ['Metric', 'Value']);
        fputcsv($out, ['Total Gross Revenue',  'PHP ' . number_format($totalRevenue, 2)]);
        fputcsv($out, ['Total Capital (COGS)', 'PHP ' . number_format($totalCapital, 2)]);
        fputcsv($out, ['Net Profit',           'PHP ' . number_format($totalProfit,  2)]);
        fputcsv($out, ['Total Orders',         $totalOrders]);
        fputcsv($out, ['Average Order Value',  'PHP ' . number_format($aov, 2)]);
        fputcsv($out, ['Total Customers',      User::where('role', 'customer')->count()]);
        fputcsv($out, ['Verified Sellers',     User::where('role', 'seller')->where('isVerified', true)->count()]);
        fputcsv($out, ['Pending Sellers',      User::where('role', 'seller')->where('isVerified', false)->where('status', '!=', 'blocked')->count()]);
        fputcsv($out, ['Total Products',       Product::count()]);
        fputcsv($out, ['Approved Products',    Product::where('status', 'approved')->count()]);
        fputcsv($out, ['Pending Products',     Product::where('status', 'pending')->count()]);

        // ── SECTION 2 — Order Status Breakdown ──────────────────────────────
        $section('Order Status Breakdown');
        fputcsv($out, ['Status', 'Count', 'Total Amount (PHP)']);
        Order::select('status', DB::raw('count(*) as cnt'), DB::raw('SUM(totalAmount) as amt'))
            ->groupBy('status')->orderBy('cnt', 'desc')->get()
            ->each(fn($r) => fputcsv($out, [$r->status, $r->cnt, number_format($r->amt, 2)]));

        // ── SECTION 3 — Full Order Log ───────────────────────────────────────
        $section('Full Order Log');
        fputcsv($out, ['Order ID','Date','Customer Name','Customer Email','Seller Name','Shop Name','Status','Payment Method','Items (name x qty)','Total Amount (PHP)']);
        Order::with(['customer:id,name,email','seller:id,name,shopName','items.product:id,name'])
            ->orderBy('createdAt', 'desc')->get()
            ->each(function ($o) use ($out) {
                $items = $o->items->map(fn($i) => ($i->product->name ?? 'Unknown') . ' x' . $i->quantity)->implode('; ');
                fputcsv($out, [
                    $o->id,
                    $o->createdAt?->format('Y-m-d H:i'),
                    $o->customer->name  ?? 'N/A',
                    $o->customer->email ?? 'N/A',
                    $o->seller->name    ?? 'N/A',
                    $o->seller->shopName ?? 'N/A',
                    $o->status,
                    $o->paymentMethod ?? 'N/A',
                    $items,
                    number_format($o->totalAmount, 2),
                ]);
            });

        // ── SECTION 4 — Revenue by Seller ───────────────────────────────────
        $section('Revenue by Seller');
        fputcsv($out, ['Seller Name','Shop Name','Orders','Gross Revenue (PHP)','Capital (PHP)','Est. Profit (PHP)']);
        Order::select('sellerId', DB::raw('COUNT(*) as orders'), DB::raw('SUM(totalAmount) as revenue'))
            ->whereNotIn('status', ['Cancelled'])->groupBy('sellerId')->orderByDesc('revenue')
            ->with('seller:id,name,shopName')->get()
            ->each(function ($row) use ($out) {
                $cap = DB::table('order_items')
                    ->join('orders',   'order_items.orderId',   '=', 'orders.id')
                    ->join('products', 'order_items.productId', '=', 'products.id')
                    ->where('orders.sellerId', $row->sellerId)
                    ->whereNotIn('orders.status', ['Cancelled'])
                    ->selectRaw('SUM(order_items.quantity * products.costPerPiece) as cap')
                    ->value('cap') ?: 0;
                fputcsv($out, [
                    $row->seller->name     ?? 'Unknown',
                    $row->seller->shopName ?? 'N/A',
                    $row->orders,
                    number_format($row->revenue, 2),
                    number_format($cap, 2),
                    number_format($row->revenue - $cap, 2),
                ]);
            });

        // ── SECTION 5 — Top Products ─────────────────────────────────────────
        $section('Top Products by Units Sold');
        fputcsv($out, ['Product Name','Units Sold','Gross Revenue (PHP)','Cost per Unit (PHP)','Est. COGS (PHP)','Est. Profit (PHP)']);
        OrderItem::select('productId', DB::raw('SUM(quantity) as units'), DB::raw('SUM(order_items.price * order_items.quantity) as revenue'))
            ->join('orders', 'order_items.orderId', '=', 'orders.id')
            ->whereNotIn('orders.status', ['Cancelled'])->groupBy('productId')->orderByDesc('units')
            ->with('product:id,name,costPerPiece')->get()
            ->each(function ($row) use ($out) {
                $cost = $row->product->costPerPiece ?? 0;
                $cogs = $row->units * $cost;
                fputcsv($out, [
                    $row->product->name ?? 'Unknown',
                    $row->units,
                    number_format($row->revenue, 2),
                    number_format($cost, 2),
                    number_format($cogs, 2),
                    number_format($row->revenue - $cogs, 2),
                ]);
            });

        // ── SECTION 6 — Customer Registry ───────────────────────────────────
        $section('Customer Registry');
        fputcsv($out, ['Name','Email','Status','Total Orders','Total Spent (PHP)','Joined']);
        User::where('role', 'customer')
            ->select([
                'id', 'name', 'email', 'status', 'createdAt',
                DB::raw('(SELECT COUNT(*) FROM orders WHERE orders.customerId = users.id) as order_count'),
                DB::raw('(SELECT COALESCE(SUM(totalAmount),0) FROM orders WHERE orders.customerId = users.id) as total_spent'),
            ])
            ->orderByDesc('total_spent')
            ->get()
            ->each(fn($u) => fputcsv($out, [
                $u->name, $u->email, $u->status ?? 'active',
                $u->order_count, number_format($u->total_spent, 2),
                optional($u->createdAt)->format('Y-m-d'),
            ]));

        // ── SECTION 7 — Seller Registry ─────────────────────────────────────
        $section('Seller Registry');
        fputcsv($out, ['Name','Shop Name','Email','Verified','Status','Products Listed','Joined']);
        User::where('role', 'seller')->withCount('products')->orderByDesc('createdAt')->get()
            ->each(fn($u) => fputcsv($out, [
                $u->name, $u->shopName ?? 'N/A', $u->email,
                $u->isVerified ? 'Yes' : 'No', $u->status ?? 'active',
                $u->products_count, $u->createdAt?->format('Y-m-d'),
            ]));

        // ── SECTION 8 — Premium Subscriptions ───────────────────────────────
        $section('Premium Subscriptions');
        fputcsv($out, ['Seller Name','Shop Name','Plan','Status','Amount (PHP)','Starts','Expires','Requested']);
        \App\Models\SellerSubscription::with('user:id,name,shopName')->orderByDesc('createdAt')->get()
            ->each(fn($s) => fputcsv($out, [
                $s->user->name     ?? 'N/A',
                $s->user->shopName ?? 'N/A',
                $s->planName       ?? 'N/A',
                $s->status,
                number_format($s->amount ?? 0, 2),
                $s->startsAt?->format('Y-m-d')  ?? 'N/A',
                $s->endsAt?->format('Y-m-d')    ?? 'N/A',
                $s->createdAt?->format('Y-m-d'),
            ]));

        // ── SECTION 9 — Platform Reports ────────────────────────────────────
        $section('Platform Reports');
        fputcsv($out, ['ID','Reporter','Reported User','Reason','Status','Action Taken','Date']);
        \App\Models\Report::with(['reporter:id,name','reported:id,name'])->orderByDesc('createdAt')->get()
            ->each(fn($r) => fputcsv($out, [
                $r->id,
                $r->reporter->name ?? 'N/A',
                $r->reported->name ?? 'N/A',
                $r->reason         ?? 'N/A',
                $r->status,
                $r->actionTaken    ?? 'None',
                $r->createdAt?->format('Y-m-d'),
            ]));

        fclose($out);
        $csv = ob_get_clean();

        return response()->make($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ]);
    }


    // ─── Blade-facing methods ────────────────────────────────────────────────




    // ─── Blade-facing methods ────────────────────────────────────────────────

    public function users(Request $request)
    {
        // User Management is strictly for Customer accounts (Sellers are managed under Seller Management)
        $query = User::where('role', 'customer');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%')
                  ->orWhere('username', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('createdAt', 'desc')->paginate(20);
        $counts = [
            'all'     => User::where('role', 'customer')->count(),
            'active'  => User::where('role', 'customer')->where('status', 'active')->count(),
            'blocked' => User::where('role', 'customer')->where('status', 'blocked')->count(),
            'frozen'  => User::where('role', 'customer')->where('status', 'frozen')->count(),
        ];
        return view('admin.users', compact('users', 'counts'));
    }

    public function banUser(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);
            $reason = $request->input('reason', 'Violation of community guidelines');

            $user->status = 'blocked';
            $user->violationReason = $reason;
            $user->save();

            // In-app notification
            try {
                $this->sendNotification(
                    $user->id,
                    'Account Suspended',
                    "Your account has been suspended by an administrator. Reason: {$reason}",
                    'system',
                    null,
                    'customer'
                );
            } catch (\Throwable $e) {
                Log::warning('Notification error on banUser: ' . $e->getMessage());
            }

            // Gmail / Email notification
            if ($user->email) {
                try {
                    $mailable = new \App\Mail\CustomerBannedMail($user->name, $reason);
                    \App\Services\EmailNotificationService::sendNotification(
                        $user->email,
                        $mailable,
                        'customer_banned',
                        $user->id,
                        'User',
                        $user->id
                    );
                } catch (\Throwable $me) {
                    Log::warning('Email sending failed on banUser: ' . $me->getMessage());
                }
            }

            // Invalidate active web and API sessions immediately
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            } catch (\Throwable $e) {
                Log::warning('Session deletion error on banUser: ' . $e->getMessage());
            }

            return redirect()->route('admin.users')->with('success', 'Customer account banned and email notification sent.');
        } catch (\Throwable $e) {
            Log::error('banUser fatal error: ' . $e->getMessage());
            return redirect()->route('admin.users')->with('error', 'Error banning account: ' . $e->getMessage());
        }
    }

    public function unbanUser(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->status = 'active';
            $user->violationReason = null;
            $user->save();

            // Gmail / Email notification
            if ($user->email) {
                try {
                    $mailable = new \App\Mail\CustomerRestoredMail($user->name);
                    \App\Services\EmailNotificationService::sendNotification(
                        $user->email,
                        $mailable,
                        'customer_restored',
                        $user->id,
                        'User',
                        $user->id
                    );
                } catch (\Throwable $me) {
                    Log::warning('Email sending failed on unbanUser: ' . $me->getMessage());
                }
            }

            return redirect()->route('admin.users')->with('success', 'Customer account restored and notification sent.');
        } catch (\Throwable $e) {
            Log::error('unbanUser fatal error: ' . $e->getMessage());
            return redirect()->route('admin.users')->with('error', 'Error restoring account: ' . $e->getMessage());
        }
    }

    public function deleteUser(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);
            $reason = $request->input('reason', 'Administrative deletion');
            $customerName = $user->name;
            $customerEmail = $user->email;
            $customerId = $user->id;

            // Gmail / Email notification before deletion
            if ($customerEmail) {
                try {
                    $mailable = new \App\Mail\CustomerDeletedMail($customerName, $reason);
                    \App\Services\EmailNotificationService::sendNotification(
                        $customerEmail,
                        $mailable,
                        'customer_deleted',
                        $customerId,
                        'User',
                        $customerId
                    );
                } catch (\Throwable $me) {
                    Log::warning('Email sending failed on deleteUser: ' . $me->getMessage());
                }
            }

            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $customerId)->delete();
                }
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            } catch (\Throwable $e) {
                Log::warning('Session deletion error on deleteUser: ' . $e->getMessage());
            }

            // Archive customer before permanent deletion
            try {
                \App\Models\ArchivedRecord::archive('customer', $user, $reason);
            } catch (\Throwable $ae) {
                Log::warning('Archive error on deleteUser: ' . $ae->getMessage());
            }

            Log::info("Customer [{$customerId} - {$customerName} ({$customerEmail})] permanently deleted by admin. Reason: {$reason}");

            $user->delete();
            return redirect()->route('admin.users')->with('success', "Customer {$customerName} permanently deleted, archived, and notification sent.");
        } catch (\Throwable $e) {
            Log::error('deleteUser fatal error: ' . $e->getMessage());
            return redirect()->route('admin.users')->with('error', 'Error deleting user: ' . $e->getMessage());
        }
    }

    public function sellers(Request $request)
    {
        $query = User::where('role', 'seller')
            ->withCount(['products', 'orders']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%')
                  ->orWhere('shopName', 'like', '%'.$request->search.'%');
            });
        }

        $filter = $request->filter;

        if ($filter === 'pending') {
            $query->where('isVerified', false)->where('status', '!=', 'blocked');
        } elseif ($filter === 'suspended') {
            $query->where('status', 'blocked');
        } elseif ($filter === 'all') {
            // all sellers
        } else {
            // Default view (Approved Sellers): strictly only verified/approved sellers
            $query->where('isVerified', true)->where('status', '!=', 'blocked');
        }

        $sellers = $query->orderBy('createdAt', 'desc')->paginate(20);
        $pendingSellers = User::where('role', 'seller')->where('isVerified', false)->where('status', '!=', 'blocked')->get();
        $counts = [
            'all'       => User::where('role', 'seller')->count(),
            'verified'  => User::where('role', 'seller')->where('isVerified', true)->where('status', '!=', 'blocked')->count(),
            'pending'   => User::where('role', 'seller')->where('isVerified', false)->where('status', '!=', 'blocked')->count(),
            'suspended' => User::where('role', 'seller')->where('status', 'blocked')->count(),
        ];
        return view('admin.sellers', compact('sellers', 'pendingSellers', 'counts'));
    }

    public function verifySellerWeb(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->isVerified = true;
            $user->status     = 'active';
            $user->save();
            
            $this->sendNotification($user->id, 'Seller Verified', 'Your artisan workshop is now verified!', 'system', '/seller/dashboard', 'seller');

            // Send Gmail/Email Notification to Seller
            if ($user->email) {
                try {
                    $mailable = new \App\Mail\SellerApprovedMail($user->name, $user->shopName);
                    \App\Services\EmailNotificationService::sendNotification(
                        $user->email,
                        $mailable,
                        'seller_approved',
                        $user->id,
                        'User',
                        $user->id
                    );
                } catch (\Throwable $me) {
                    Log::warning('Email sending failed for seller approval: ' . $me->getMessage());
                }
            }

            return redirect()->route('admin.sellers')->with('success', 'Seller verified and email notification sent.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.sellers')->with('error', 'Error verifying seller: ' . $e->getMessage());
        }
    }

    public function unverifySellerWeb(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->isVerified = false;
            $user->status     = 'active';
            $user->save();
            $this->sendNotification($user->id, 'Verification Revoked', 'Your artisan workshop verification has been revoked by an administrator.', 'system', '/profile', 'seller');
            return redirect()->route('admin.sellers')->with('success', 'Seller verification revoked. Account moved back to Pending.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.sellers')->with('error', 'Error revoking verification: ' . $e->getMessage());
        }
    }

    public function suspendSeller(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);
            $reason = $request->input('reason', 'Violation of platform seller policies');
            $user->status          = 'blocked';
            $user->violationReason = $reason;
            $user->save();

            // In-app notification to seller
            try {
                $this->sendNotification(
                    $user->id,
                    'Account Suspended',
                    "Your artisan workshop has been suspended. Reason: {$reason}",
                    'system',
                    null,
                    'seller'
                );
            } catch (\Throwable $e) {
                Log::warning('Notification error on suspendSeller: ' . $e->getMessage());
            }

            // Send Gmail/Email Notification to Seller
            if ($user->email) {
                try {
                    $mailable = new \App\Mail\SellerSuspendedMail($user->name, $user->shopName, $reason);
                    \App\Services\EmailNotificationService::sendNotification(
                        $user->email,
                        $mailable,
                        'seller_suspended',
                        $user->id,
                        'User',
                        $user->id
                    );
                } catch (\Throwable $me) {
                    Log::warning('Email sending failed for seller suspension: ' . $me->getMessage());
                }
            }

            // Invalidate active web and API sessions immediately
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            } catch (\Throwable $e) {
                Log::warning('Session deletion error on suspendSeller: ' . $e->getMessage());
            }

            return redirect()->route('admin.sellers')->with('success', 'Seller account suspended successfully and notification sent.');
        } catch (\Throwable $e) {
            Log::error('suspendSeller fatal error: ' . $e->getMessage());
            return redirect()->route('admin.sellers')->with('error', 'Error suspending seller: ' . $e->getMessage());
        }
    }

    public function unsuspendSeller(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->status          = 'active';
            $user->violationReason = null;
            $user->save();

            try {
                $this->sendNotification(
                    $user->id,
                    'Account Restored',
                    'Your artisan workshop account has been restored to active status.',
                    'system',
                    '/seller/dashboard',
                    'seller'
                );
            } catch (\Throwable $e) {
                Log::warning('Notification error on unsuspendSeller: ' . $e->getMessage());
            }

            // Send Gmail/Email Notification to Seller
            if ($user->email) {
                try {
                    $mailable = new \App\Mail\SellerRestoredMail($user->name, $user->shopName);
                    \App\Services\EmailNotificationService::sendNotification(
                        $user->email,
                        $mailable,
                        'seller_restored',
                        $user->id,
                        'User',
                        $user->id
                    );
                } catch (\Throwable $me) {
                    Log::warning('Email sending failed for seller restore: ' . $me->getMessage());
                }
            }

            return redirect()->route('admin.sellers')->with('success', 'Seller account restored and notification sent.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.sellers')->with('error', 'Error restoring seller: ' . $e->getMessage());
        }
    }

    public function deleteSeller(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);
            $reason = $request->input('reason', 'Administrative deletion');
            $sellerName = $user->name;
            $sellerEmail = $user->email;
            $shopName = $user->shopName;
            $sellerId = $user->id;

            // Send Gmail/Email Notification to Seller before deletion
            if ($sellerEmail) {
                try {
                    $mailable = new \App\Mail\SellerDeletedMail($sellerName, $shopName, $reason);
                    \App\Services\EmailNotificationService::sendNotification(
                        $sellerEmail,
                        $mailable,
                        'seller_deleted',
                        $sellerId,
                        'User',
                        $sellerId
                    );
                } catch (\Throwable $me) {
                    Log::warning('Email sending failed for seller deletion: ' . $me->getMessage());
                }
            }

            // Invalidate active web and API sessions immediately
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $sellerId)->delete();
                }
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            } catch (\Throwable $e) {
                Log::warning('Session deletion error on deleteSeller: ' . $e->getMessage());
            }

            // Archive seller before permanent deletion
            try {
                \App\Models\ArchivedRecord::archive('seller', $user, $reason);
            } catch (\Throwable $ae) {
                Log::warning('Archive error on deleteSeller: ' . $ae->getMessage());
            }

            Log::info("Seller [{$sellerId} - {$sellerName} ({$sellerEmail})] permanently deleted by admin. Reason: {$reason}");

            $user->delete();
            return redirect()->route('admin.sellers')->with('success', "Seller {$sellerName} permanently deleted, archived, and notification sent.");
        } catch (\Throwable $e) {
            Log::error('deleteSeller fatal error: ' . $e->getMessage());
            return redirect()->route('admin.sellers')->with('error', 'Error deleting seller: ' . $e->getMessage());
        }
    }

    public function products(Request $request)
    {
        $status = $request->input('status', 'pending');
        $search = trim($request->input('search', ''));
        $query  = Product::with(['seller:id,name,email,shopName', 'category:id,name'])->orderBy('createdAt', 'desc');
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('seller', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('shopName', 'like', "%{$search}%");
                  });
            });
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $products = $query->paginate(12);
        $counts = [
            'pending'  => Product::where('status', 'pending')->count(),
            'approved' => Product::where('status', 'approved')->count(),
            'rejected' => Product::where('status', 'rejected')->count(),
            'all'      => Product::count(),
        ];
        return view('admin.products', compact('products', 'counts', 'status', 'search'));
    }

    public function approveProductWeb(string $id)
    {
        $product = Product::with('seller')->findOrFail($id);
        $product->status          = 'approved';
        $product->rejectionReason = null;
        $product->save();

        $this->sendNotification($product->sellerId, 'Product Approved', "Your product \"{$product->name}\" is now live!", 'product_approved', '/seller/products', 'seller');

        // Email Seller
        if ($product->seller && $product->seller->email) {
            $mailable = new \App\Mail\ProductApprovedMail($product->seller->name, $product->name, $product->id);
            \App\Services\EmailNotificationService::sendNotification($product->seller->email, $mailable, 'product_approved', $product->sellerId, 'Product', $product->id);
        }

        // Email Active Customers about New Available Product or Discounted Product
        $activeCustomers = User::where('role', 'customer')->where('status', 'active')->get();
        $shopName = $product->seller->shopName ?? $product->seller->name ?? 'Artisan';

        foreach ($activeCustomers as $customer) {
            if ($customer->email) {
                if ($product->is_on_sale && $product->discount_percentage > 0) {
                    $salePrice = round((float) $product->price * (1 - ($product->discount_percentage / 100)), 2);
                    $dMail = new \App\Mail\ProductDiscountMail(
                        $customer->name,
                        $product->name,
                        $shopName,
                        (float) $product->price,
                        $salePrice,
                        (float) $product->discount_percentage,
                        $product->id
                    );
                    \App\Services\EmailNotificationService::sendNotification($customer->email, $dMail, 'product_discount_alert', $customer->id, 'Product', $product->id);
                } else {
                    $cMail = new \App\Mail\NewProductAvailableMail($customer->name, $product->name, $shopName, (float) $product->price, $product->id);
                    \App\Services\EmailNotificationService::sendNotification($customer->email, $cMail, 'new_product_alert', $customer->id, 'Product', $product->id);
                }
            }
        }

        // Auto-add restocked wishlisted items to customer cart & send email notification
        try {
            \App\Services\WishlistService::handleProductRestocked($product);
        } catch (\Throwable $we) {
            Log::warning('Wishlist restock handling error on approval: ' . $we->getMessage());
        }

        return redirect()->back()->with('success', 'Product approved and notifications sent.');
    }

    public function rejectProductWeb(Request $request, string $id)
    {
        $product = Product::with('seller')->findOrFail($id);
        $product->status          = 'rejected';
        $product->rejectionReason = $request->input('reason', 'Rejected by admin.');
        $product->save();

        $this->sendNotification($product->sellerId, 'Product Rejected', "Your product \"{$product->name}\" was rejected. Reason: {$product->rejectionReason}", 'product_rejected', '/seller/products', 'seller');

        // Email Seller with Specific Rejection Reason
        if ($product->seller && $product->seller->email) {
            $mailable = new \App\Mail\ProductRejectedMail($product->seller->name, $product->name, $product->id, $product->rejectionReason);
            \App\Services\EmailNotificationService::sendNotification($product->seller->email, $mailable, 'product_rejected', $product->sellerId, 'Product', $product->id);
        }

        return redirect()->back()->with('success', 'Product rejected and email notification sent to seller.');
    }

    public function deleteProductWeb(Request $request, string $id)
    {
        try {
            $product = Product::with('seller')->findOrFail($id);
            $reason = $request->input('reason', 'Administrative deletion');
            $productName = $product->name;
            $seller = $product->seller;

            // In-app notification to seller
            try {
                if ($product->sellerId) {
                    $this->sendNotification(
                        $product->sellerId,
                        'Product Listing Removed',
                        "Your product \"{$productName}\" was permanently removed by an administrator. Reason: {$reason}",
                        'product_deleted',
                        '/seller/products',
                        'seller'
                    );
                }
            } catch (\Throwable $ne) {
                Log::warning('Notification error on deleteProductWeb: ' . $ne->getMessage());
            }

            // Gmail/Email to Seller
            if ($seller && $seller->email) {
                try {
                    $mailable = new \App\Mail\ProductDeletedMail($seller->name, $productName, $reason);
                    \App\Services\EmailNotificationService::sendNotification(
                        $seller->email,
                        $mailable,
                        'product_deleted',
                        $seller->id,
                        'Product',
                        $product->id
                    );
                } catch (\Throwable $me) {
                    Log::warning('Email sending failed on deleteProductWeb: ' . $me->getMessage());
                }
            }

            // Archive record before permanent deletion
            try {
                \App\Models\ArchivedRecord::archive('product', $product, $reason);
            } catch (\Throwable $ae) {
                Log::warning('Archive error on deleteProductWeb: ' . $ae->getMessage());
            }

            Log::info("Product [{$product->id} - {$productName}] deleted by admin. Reason: {$reason}");
            $product->delete();

            return redirect()->back()->with('success', "Product '{$productName}' deleted, archived, and notification sent.");
        } catch (\Throwable $e) {
            Log::error('deleteProductWeb fatal error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error deleting product: ' . $e->getMessage());
        }
    }

    public function archives(Request $request)
    {
        $type   = $request->input('type', 'all');
        $search = trim($request->input('search', ''));

        $query = \App\Models\ArchivedRecord::orderBy('created_at', 'desc');

        if ($type && $type !== 'all') {
            $query->where('item_type', $type);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('identifier', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('archived_by', 'like', "%{$search}%");
            });
        }

        $archives = $query->paginate(15);

        $counts = [
            'all'      => \App\Models\ArchivedRecord::count(),
            'product'  => \App\Models\ArchivedRecord::where('item_type', 'product')->count(),
            'category' => \App\Models\ArchivedRecord::where('item_type', 'category')->count(),
            'customer' => \App\Models\ArchivedRecord::where('item_type', 'customer')->count(),
            'seller'   => \App\Models\ArchivedRecord::where('item_type', 'seller')->count(),
        ];

        return view('admin.archives.index', compact('archives', 'counts', 'type', 'search'));
    }

    public function restoreArchive(string $id)
    {
        try {
            $record = \App\Models\ArchivedRecord::findOrFail($id);
            $type = $record->item_type;
            $meta = $record->metadata ?? [];

            if ($type === 'product') {
                if (!empty($record->item_id) && Product::find($record->item_id)) {
                    return redirect()->back()->with('error', 'A product with this ID is already active in the catalog.');
                }
                Product::create([
                    'id'                  => $record->item_id ?: (string) \Illuminate\Support\Str::uuid(),
                    'name'                => $meta['name'] ?? $record->name,
                    'description'         => $meta['description'] ?? null,
                    'price'               => $meta['price'] ?? 0,
                    'costPerPiece'        => $meta['costPerPiece'] ?? 0,
                    'stock'               => $meta['stock'] ?? 0,
                    'sizes'               => $meta['sizes'] ?? null,
                    'categories'          => $meta['categories'] ?? null,
                    'image'               => $meta['image'] ?? null,
                    'sellerId'            => $meta['sellerId'] ?? null,
                    'status'              => 'pending',
                    'sku'                 => $meta['sku'] ?? null,
                    'fabric_type'         => $meta['fabric_type'] ?? null,
                    'collar_type'         => $meta['collar_type'] ?? null,
                    'artisan_region'      => $meta['artisan_region'] ?? null,
                    'CategoryId'          => $meta['CategoryId'] ?? null,
                    'target_group'        => $meta['target_group'] ?? null,
                    'size_stocks'         => $meta['size_stocks'] ?? null,
                    'is_on_sale'          => $meta['is_on_sale'] ?? false,
                    'discount_percentage' => $meta['discount_percentage'] ?? 0,
                ]);
            } elseif ($type === 'category') {
                $catName = $meta['name'] ?? $record->name;
                if (\App\Models\Category::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($catName))])->exists()) {
                    return redirect()->back()->with('error', 'A category with this name already exists.');
                }
                \App\Models\Category::create([
                    'id'           => $record->item_id ?: (string) \Illuminate\Support\Str::uuid(),
                    'name'         => $catName,
                    'description'  => $meta['description'] ?? null,
                    'target_group' => $meta['target_group'] ?? [],
                    'image'        => $meta['image'] ?? '/uploads/categories/pina_formal.png',
                ]);
            } elseif ($type === 'customer' || $type === 'seller') {
                $email = $meta['email'] ?? $record->identifier;
                if ($email && User::where('email', $email)->exists()) {
                    return redirect()->back()->with('error', "A user with email {$email} already exists in the system.");
                }
                User::create([
                    'id'           => $record->item_id ?: (string) \Illuminate\Support\Str::uuid(),
                    'name'         => $meta['name'] ?? $record->name,
                    'email'        => $email,
                    'password'     => $meta['password'] ?? bcrypt(\Illuminate\Support\Str::random(16)),
                    'role'         => $type === 'seller' ? 'seller' : 'customer',
                    'status'       => 'active',
                    'shopName'     => $meta['shopName'] ?? null,
                    'mobileNumber' => $meta['mobileNumber'] ?? null,
                    'isVerified'   => $meta['isVerified'] ?? ($type === 'seller' ? false : true),
                    'profilePhoto' => $meta['profilePhoto'] ?? null,
                ]);
            }

            $record->delete();
            return redirect()->back()->with('success', "Archived {$type} '{$record->name}' restored successfully.");
        } catch (\Throwable $e) {
            Log::error('restoreArchive error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error restoring record: ' . $e->getMessage());
        }
    }

    public function purgeArchive(string $id)
    {
        try {
            $record = \App\Models\ArchivedRecord::findOrFail($id);
            $name = $record->name;
            $record->delete();
            return redirect()->back()->with('success', "Archived record '{$name}' permanently purged.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error purging record: ' . $e->getMessage());
        }
    }

    public function emailLogs()
    {
        $logs = \App\Models\EmailLog::with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.email-logs', compact('logs'));
    }

    public function reports(Request $request)
    {
        $status   = $request->query('status', 'Pending');
        $type     = $request->query('type', 'all');
        $severity = $request->query('severity', 'all');
        $search   = trim($request->query('search', ''));

        $query = \App\Models\Report::with(['reporter', 'reported', 'product', 'assignedAdmin', 'timelineEvents']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('reportType', $type);
        }

        if ($severity !== 'all') {
            $query->where('severity', $severity);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('reported', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('shopName', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('reporter', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('product', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $reports = $query->orderBy('createdAt', 'desc')->paginate(15);
            
        $counts = [
            'all'          => \App\Models\Report::count(),
            'pending'      => \App\Models\Report::where('status', 'Pending')->count(),
            'under_review' => \App\Models\Report::where('status', 'Under Review')->count(),
            'resolved'     => \App\Models\Report::where('status', 'Resolved')->count(),
            'dismissed'    => \App\Models\Report::where('status', 'Dismissed')->count(),
            'critical'     => \App\Models\Report::where('severity', 'CRITICAL')->whereIn('status', ['Pending', 'Under Review'])->count(),
        ];

        // Seller Risk Pattern Overview (Decision-Support Analytics)
        $topReportedSellers = collect();
        try {
            $topReportedSellers = \App\Models\User::where('role', 'seller')
                ->whereHas('reports', function($q) {
                    $q->where('createdAt', '>=', now()->subDays(30));
                })
                ->withCount([
                    'reports as recent_reports_count' => fn($q) => $q->where('createdAt', '>=', now()->subDays(30)),
                    'reports as confirmed_violations_count' => fn($q) => $q->where('investigationResult', 'Policy Violation Confirmed'),
                    'reports as pending_reports_count' => fn($q) => $q->whereIn('status', ['Pending', 'Under Review']),
                    'reports as dismissed_reports_count' => fn($q) => $q->where('status', 'Dismissed'),
                ])
                ->orderByDesc('recent_reports_count')
                ->limit(5)
                ->get()
                ->map(function($seller) {
                    $score = ($seller->confirmed_violations_count * 3) + ($seller->pending_reports_count * 1.5) + ($seller->recent_reports_count * 0.5);
                    $riskLevel = match(true) {
                        $score >= 8 => 'CRITICAL',
                        $score >= 5 => 'HIGH',
                        $score >= 2 => 'MEDIUM',
                        default     => 'LOW',
                    };
                    $recommendation = match($riskLevel) {
                        'CRITICAL' => 'Immediate investigation required. Review recent transactions and consider temporary restrictions.',
                        'HIGH'     => 'High priority case review. Cross-examine customer evidence and request seller documentation.',
                        'MEDIUM'   => 'Monitor store activity and review pending customer reports.',
                        default    => 'Normal activity. Standard queue review.',
                    };
                    return [
                        'seller'          => $seller,
                        'recent_reports'  => $seller->recent_reports_count,
                        'violations'      => $seller->confirmed_violations_count,
                        'pending'         => $seller->pending_reports_count,
                        'dismissed'       => $seller->dismissed_reports_count,
                        'risk_level'      => $riskLevel,
                        'recommendation'  => $recommendation,
                    ];
                });
        } catch (\Throwable $e) {
            $topReportedSellers = collect();
        }
            
        return view('admin.reports', compact('reports', 'counts', 'status', 'type', 'severity', 'search', 'topReportedSellers'));
    }

    public function resolveReport(Request $request, string $id)
    {
        $report = \App\Models\Report::with(['reported', 'reporter', 'product'])->findOrFail($id);
        
        $admin = Auth::user();
        $status = $request->input('status', 'Resolved');
        $severity = $request->input('severity', $report->severity ?? 'MEDIUM');
        $investigationResult = $request->input('investigationResult', 'No Violation Found');
        $action = $request->input('action', 'None');
        $disciplinaryReason = $request->input('disciplinaryReason', '');
        $adminNotes = $request->input('notes', '');

        $report->status              = $status;
        $report->severity            = $severity;
        $report->investigationResult = $investigationResult;
        $report->actionTaken         = $action;
        $report->disciplinaryReason  = $disciplinaryReason;
        $report->adminNotes          = $adminNotes;
        $report->assignedAdminId     = $admin?->id;
        $report->save();

        // Disciplinary Enforcement on Reported User
        $reportedUser = $report->reported;
        if ($reportedUser && in_array($action, ['Suspend Account', 'Ban Account', 'Temporary Restriction', 'Warning'])) {
            if (in_array($action, ['Suspend Account', 'Temporary Restriction'])) {
                $reportedUser->status = 'suspended';
                $reportedUser->violationReason = $disciplinaryReason ?: "Account suspended following Trust & Safety investigation: {$investigationResult}.";
                $reportedUser->sessionVersion = ($reportedUser->sessionVersion ?? 1) + 1;
                $reportedUser->save();

                Notification::send(
                    (string) $reportedUser->id,
                    '⛔ Account Suspended - Policy Violation',
                    "Your account has been suspended following a Trust & Safety investigation. Reason: " . ($disciplinaryReason ?: $investigationResult),
                    'error',
                    null,
                    $reportedUser->role
                );
            } elseif ($action === 'Ban Account') {
                $reportedUser->status = 'banned';
                $reportedUser->violationReason = $disciplinaryReason ?: "Account permanently banned for severe policy violation: {$investigationResult}.";
                $reportedUser->sessionVersion = ($reportedUser->sessionVersion ?? 1) + 1;
                $reportedUser->save();

                Notification::send(
                    (string) $reportedUser->id,
                    '🚫 Account Banned - Severe Violation',
                    "Your account has been permanently banned from the platform for severe policy violation. Reason: " . ($disciplinaryReason ?: $investigationResult),
                    'error',
                    null,
                    $reportedUser->role
                );
            } elseif ($action === 'Warning') {
                Notification::send(
                    (string) $reportedUser->id,
                    '⚠️ Official Warning - Trust & Safety Notice',
                    "An official warning has been recorded for your shop regarding: \"{$report->reason}\". Details: " . ($disciplinaryReason ?: 'Please review and adhere strictly to LumBarong seller community guidelines.'),
                    'warning',
                    '/seller/reports',
                    'seller'
                );
            }
        }

        // Add Case Timeline Event
        $timelineTitle = match($status) {
            'Resolved'     => 'Case Resolved',
            'Dismissed'    => 'Case Dismissed',
            'Under Review' => 'Investigation in Progress',
            default        => 'Status Updated',
        };

        $timelineDesc = "Investigation Result: {$investigationResult} | Action: {$action}";
        if (!empty($disciplinaryReason)) {
            $timelineDesc .= " | Note: {$disciplinaryReason}";
        }

        $report->addTimelineEvent(
            strtolower(str_replace(' ', '_', $status)),
            $timelineTitle,
            $timelineDesc,
            $admin,
            $admin->role ?? 'admin',
            [
                'investigation_result' => $investigationResult,
                'action_taken'         => $action,
                'severity'             => $severity,
            ]
        );

        // Notify Reporter of Resolution (Without leaking confidential internal admin notes)
        if ($report->reporterId) {
            $reporterMsg = $status === 'Resolved'
                ? "Your report ({$report->getReportCode()}) has been reviewed and resolved by our Trust & Safety team. Thank you for helping keep LumBarong safe."
                : ($status === 'Dismissed'
                    ? "Your report ({$report->getReportCode()}) was reviewed. Based on available evidence, no policy violation could be confirmed at this time."
                    : "Your report ({$report->getReportCode()}) is currently under active investigation by our Trust & Safety team.");

            Notification::send(
                (string) $report->reporterId,
                $status === 'Resolved' ? '✓ Report Resolved' : ($status === 'Dismissed' ? '🛡️ Report Reviewed' : '🔍 Report Under Review'),
                $reporterMsg,
                'info',
                '/profile/reports',
                'customer'
            );
        }

        // Notify Reported Seller if status was resolved/dismissed
        if ($report->reportedId && $reportedUser?->role === 'seller' && in_array($status, ['Resolved', 'Dismissed'])) {
            $sellerNotice = $status === 'Resolved'
                ? "The customer concern ({$report->getReportCode()}) regarding \"{$report->reason}\" has been marked resolved. Determination: {$investigationResult}."
                : "The customer concern ({$report->getReportCode()}) regarding \"{$report->reason}\" has been dismissed with no violation found.";

            Notification::send(
                (string) $report->reportedId,
                '🛡️ Case Closed - Trust & Safety',
                $sellerNotice,
                'info',
                '/seller/reports',
                'seller'
            );
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status'  => 'success',
                'message' => "Report {$report->getReportCode()} successfully updated to {$status}.",
                'report'  => $report,
            ]);
        }

        return redirect()->route('admin.reports')->with('success', "Report {$report->getReportCode()} marked as {$status}.");
    }

    public function deleteReport(string $id)
    {
        \App\Models\Report::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Report permanently deleted.');
    }

    public function notifications()
    {
        $notifications = Notification::where('userId', Auth::id())
            ->where('targetRole', 'admin')
            ->orderBy('createdAt', 'desc')
            ->paginate(15);

        // Mark all as read when visiting
        Notification::where('userId', Auth::id())
            ->where('targetRole', 'admin')
            ->where('isRead', false)
            ->update(['isRead' => true]);

        return view('admin.notifications', compact('notifications'));
    }

    public function readAllNotifications()
    {
        Notification::where('userId', Auth::id())
            ->where('targetRole', 'admin')
            ->where('isRead', false)
            ->update(['isRead' => true]);

        return redirect()->back()->with('success', 'All admin notifications marked as read.');
    }
}
