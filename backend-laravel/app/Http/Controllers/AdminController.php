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
        $range = $request->query('range', 'month');
        $stats = json_decode($this->getGlobalStats($request)->getContent(), true);

        $recentActivity = Notification::where('targetRole', 'admin')
            ->orderBy('createdAt', 'desc')
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'range'          => $range,
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
    private function getTopSellers(): \Illuminate\Database\Eloquent\Collection
    {
        return Order::select(
                'sellerId',
                DB::raw('SUM(totalAmount) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->whereNotIn('status', ['Cancelled'])
            ->groupBy('sellerId')
            ->orderByDesc('revenue')
            ->limit(5)
            ->with('seller:id,name,shopName')
            ->get();
    }

    /** Top 5 products ranked by units sold. */
    private function getTopProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return OrderItem::select(
                'productId',
                DB::raw('SUM(quantity) as units'),
                DB::raw('SUM(price * quantity) as revenue')
            )
            ->join('orders', 'order_items.orderId', '=', 'orders.id')
            ->whereNotIn('orders.status', ['Cancelled'])
            ->groupBy('productId')
            ->orderByDesc('units')
            ->limit(5)
            ->with('product:id,name')
            ->get();
    }

    /** Pending item counts across all admin action queues. */
    private function getPendingActionCounts(): array
    {
        return [
            'products'      => Product::where('status', 'pending')->count(),
            'sellers'       => User::where('role', 'seller')->where('isVerified', false)->where('status', 'active')->count(),
            'subscriptions' => \App\Models\SellerSubscription::where('status', 'pending')->count(),
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
            $range = $request->query('range');
            $query = Order::query();

            if ($range && $range !== 'all') {
                $start = $this->getRangeBounds($range);
                $query->where('createdAt', '>=', $start);
            }

            $totalSalesValue = $query->whereNotIn('status', ['Cancelled'])->sum('totalAmount') ?: 0;
            $totalOrdersCount = $query->count();
            
            $totalCustomersCount = User::where('role', 'customer')
                ->where('status', '!=', 'blocked')
                ->count();
            
            $totalProductsCount = Product::count();

            // Calculate capital based on order items
            $totalCapital = DB::table('order_items')
                ->join('orders', 'order_items.orderId', '=', 'orders.id')
                ->join('products', 'order_items.productId', '=', 'products.id')
                ->whereNotIn('orders.status', ['Cancelled'])
                ->when($range && $range !== 'all', function ($q) use ($range) {
                    return $q->where('orders.createdAt', '>=', $this->getRangeBounds($range));
                })
                ->selectRaw('SUM(order_items.quantity * products.costPerPiece) as total_capital')
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

    private function getRangeBounds(string $range)
    {
        switch ($range) {
            case 'today': return Carbon::today();
            case 'week': return Carbon::now()->startOfWeek();
            case 'month': return Carbon::now()->startOfMonth();
            case 'year': return Carbon::now()->startOfYear();
            default: return Carbon::now()->subDays(30);
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
            ->where('status', 'active')
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
        fputcsv($out, ['Pending Sellers',      User::where('role', 'seller')->where('isVerified', false)->where('status', 'active')->count()]);
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
        $query = User::query();
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }
        
        // Default to customer role if no role is specified and not searching all
        $role = $request->input('role', 'customer');
        if ($role && $role !== 'all') {
            $query->where('role', $role);
        }
        
        if ($request->status) $query->where('status', $request->status);
        $users = $query->orderBy('createdAt', 'desc')->paginate(20);
        return view('admin.users', compact('users'));
    }

    public function banUser(string $id)
    {
        $user = User::findOrFail($id);
        $user->status = 'blocked';
        $user->save();
        return redirect()->back()->with('success', 'User banned.');
    }

    public function unbanUser(string $id)
    {
        $user = User::findOrFail($id);
        $user->status = 'active';
        $user->save();
        return redirect()->back()->with('success', 'User restored.');
    }

    public function deleteUser(string $id)
    {
        User::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'User deleted.');
    }

    public function sellers(Request $request)
    {
        $sellers = User::where('role', 'seller')
            ->withCount(['products', 'orders'])
            ->orderBy('createdAt', 'desc')
            ->paginate(20);
        $pendingSellers = User::where('role', 'seller')->where('isVerified', false)->where('status', 'active')->get();
        $counts = [
            'verified'  => User::where('role', 'seller')->where('isVerified', true)->count(),
            'pending'   => User::where('role', 'seller')->where('isVerified', false)->where('status', 'active')->count(),
            'suspended' => User::where('role', 'seller')->where('status', 'blocked')->count(),
        ];
        return view('admin.sellers', compact('sellers', 'pendingSellers', 'counts'));
    }

    public function verifySellerWeb(string $id)
    {
        $user = User::findOrFail($id);
        $user->isVerified = true;
        $user->status     = 'active';
        $user->save();
        $this->sendNotification($user->id, 'Seller Verified', 'Your artisan workshop is now verified!', 'system', '/seller/dashboard', 'seller');
        return redirect()->back()->with('success', 'Seller verified.');
    }

    public function suspendSeller(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $user->status          = 'blocked';
        $user->violationReason = $request->reason ?? 'Suspended by admin.';
        $user->save();
        return redirect()->back()->with('success', 'Seller suspended.');
    }

    public function products(Request $request)
    {
        $status = $request->input('status', 'pending');
        $query  = Product::with('seller:id,name,email')->orderBy('createdAt', 'desc');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        $products = $query->paginate(12);
        $counts = [
            'pending'  => Product::where('status', 'pending')->count(),
            'approved' => Product::where('status', 'approved')->count(),
            'rejected' => Product::where('status', 'rejected')->count(),
        ];
        return view('admin.products', compact('products', 'counts'));
    }

    public function approveProductWeb(string $id)
    {
        $product = Product::findOrFail($id);
        $product->status          = 'approved';
        $product->rejectionReason = null;
        $product->save();
        $this->sendNotification($product->sellerId, 'Product Approved', "Your product \"{$product->name}\" is now live!", 'product_approved', '/seller/products', 'seller');
        return redirect()->back()->with('success', 'Product approved.');
    }

    public function rejectProductWeb(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        $product->status          = 'rejected';
        $product->rejectionReason = $request->reason ?? 'Rejected by admin.';
        $product->save();
        $this->sendNotification($product->sellerId, 'Product Rejected', "Your product \"{$product->name}\" was rejected. Reason: {$product->rejectionReason}", 'product_rejected', '/seller/products', 'seller');
        return redirect()->back()->with('success', 'Product rejected.');
    }

    public function reports(Request $request)
    {
        $status = $request->status ?? 'Pending';
        $reports = \App\Models\Report::with(['reporter', 'reported'])
            ->when($status !== 'all', function($q) use ($status) {
                return $q->where('status', $status);
            })
            ->orderBy('createdAt', 'desc')
            ->paginate(15);
            
        $counts = [
            'pending' => \App\Models\Report::where('status', 'Pending')->count(),
            'resolved' => \App\Models\Report::where('status', 'Resolved')->count(),
        ];
            
        return view('admin.reports', compact('reports', 'counts', 'status'));
    }

    public function resolveReport(Request $request, string $id)
    {
        $report = \App\Models\Report::findOrFail($id);
        $report->status = 'Resolved';
        $report->adminNotes = $request->notes;
        $report->actionTaken = $request->action;
        $report->save();
        
        return redirect()->back()->with('success', 'Report marked as resolved.');
    }

    public function deleteReport(string $id)
    {
        \App\Models\Report::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Report deleted.');
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
