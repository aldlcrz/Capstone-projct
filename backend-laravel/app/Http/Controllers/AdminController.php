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
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $range = $request->query('range', 'week');
        $statsResponse = $this->getGlobalStats($request);
        $stats = json_decode($statsResponse->getContent(), true);

        $recentActivity = Notification::where('targetRole', 'admin')
            ->orderBy('createdAt', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentActivity', 'range'));
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
        $filename = "admin_system_report_" . time() . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['Type', 'ID', 'Title', 'Details', 'Amount', 'Status', 'Date']);

        // 1. Overview
        $totalRevenue = Order::whereNotIn('status', ['Cancelled'])->sum('totalAmount');
        fputcsv($handle, ['METRIC', '-', 'Total Platform Revenue', 'Aggregated sales', number_format($totalRevenue, 2), 'Live', date('Y-m-d')]);
        fputcsv($handle, ['', '', '', '', '', '', '']);

        // 2. Orders
        fputcsv($handle, ['--- TRANSACTION LOG ---', '', '', '', '', '', '']);
        $orders = Order::with(['customer', 'seller'])->orderBy('createdAt', 'desc')->get();
        foreach ($orders as $o) {
            fputcsv($handle, [
                'ORDER',
                $o->id,
                ($o->customer->name ?? 'Unknown') . " bought from " . ($o->seller->name ?? 'Unknown'),
                "Pay: {$o->paymentMethod} | Status: {$o->status}",
                number_format($o->totalAmount, 2),
                $o->status,
                $o->createdAt
            ]);
        }

        fclose($handle);
        exit;
    }

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
        $status = $request->status === 'all' || !$request->status ? null : $request->status;
        $query  = Product::with('seller:id,name,email')->orderBy('createdAt', 'desc');
        if ($status) $query->where('status', $status);
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
}
