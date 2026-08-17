<?php

namespace App\Http\Controllers;

use App\Models\CommissionRecord;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    // ─── Authentication ──────────────────────────────────────────────────────

    public function showLogin()
    {
        if (Auth::check() && Auth::user()->role === 'superadmin') {
            return redirect()->route('superadmin.dashboard');
        }
        return view('superadmin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            if (Auth::user()->role !== 'superadmin') {
                Auth::logout();
                return back()->withErrors(['email' => 'Access denied. Super Admin only.']);
            }
            $request->session()->regenerate();
            return redirect()->route('superadmin.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function dashboard()
    {
        $rate = $this->getCommissionRate();
        $currentPeriod = Carbon::now()->format('Y-m');

        // Platform-wide totals
        $totalSalesAllTime = (float) Order::whereNotIn('status', ['Cancelled'])->sum('totalAmount');
        $totalSalesThisMonth = (float) Order::whereNotIn('status', ['Cancelled'])
            ->whereYear('createdAt', Carbon::now()->year)
            ->whereMonth('createdAt', Carbon::now()->month)
            ->sum('totalAmount');

        $totalCommissionAllTime    = round($totalSalesAllTime * ($rate / 100), 2);
        $totalCommissionThisMonth  = round($totalSalesThisMonth * ($rate / 100), 2);

        $totalCollected = (float) CommissionRecord::where('status', 'paid')->sum('commissionAmount');
        $totalOutstanding = (float) CommissionRecord::where('status', 'unpaid')->sum('commissionAmount');

        $customerCount   = User::where('role', 'buyer')->count();
        $sellerCount     = User::where('role', 'seller')->count();
        $verifiedSellers = User::where('role', 'seller')->where('isVerified', true)->count();
        $frozenCount     = User::where('role', 'seller')->where('status', 'frozen')->count();
        $unpaidCount     = CommissionRecord::where('status', 'unpaid')->count();
        $productCount    = Product::count();
        $orderCount      = Order::count();

        // Top 5 Profit Shops Leaderboard
        $topShops = User::where('role', 'seller')
            ->get()
            ->map(function (User $seller) use ($rate) {
                $sales = (float) Order::whereNotIn('status', ['Cancelled'])
                    ->where('sellerId', $seller->id)
                    ->sum('totalAmount');
                $ordersCount = Order::whereNotIn('status', ['Cancelled'])
                    ->where('sellerId', $seller->id)
                    ->count();
                return [
                    'id'          => $seller->id,
                    'name'        => $seller->name,
                    'shop_name'   => $seller->shopName ?: $seller->name,
                    'sales'       => $sales,
                    'orders'      => $ordersCount,
                    'commission'  => round($sales * ($rate / 100), 2),
                    'status'      => $seller->status ?? 'active',
                    'is_verified' => (bool)$seller->isVerified,
                ];
            })
            ->sortByDesc('sales')
            ->take(5)
            ->values();

        // System Health & Vitals
        $systemHealth = [
            'php_version'     => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment'     => app()->environment(),
            'is_maintenance'  => $this->isInMaintenance(),
            'cache_driver'    => config('cache.default', 'file'),
            'db_size'         => $this->getDbSize(),
            'memory_usage'    => round(memory_get_usage(true) / 1024 / 1024, 1) . ' MB',
            'memory_limit'    => ini_get('memory_limit'),
            'disk_free'       => $this->getDiskFree(),
        ];

        $recentErrorCount = $this->countLogErrors();

        // Recent commission records
        $recentRecords = CommissionRecord::with('seller')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('superadmin.dashboard', compact(
            'rate', 'totalSalesAllTime', 'totalSalesThisMonth',
            'totalCommissionAllTime', 'totalCommissionThisMonth',
            'totalCollected', 'totalOutstanding',
            'customerCount', 'sellerCount', 'verifiedSellers', 'frozenCount', 'unpaidCount',
            'productCount', 'orderCount', 'topShops', 'systemHealth',
            'recentErrorCount', 'recentRecords', 'currentPeriod'
        ));
    }

    // ─── Commission Monitoring ───────────────────────────────────────────────

    public function commissions(Request $request)
    {
        $rate = $this->getCommissionRate();
        $period = $request->input('period', Carbon::now()->format('Y-m'));
        [$year, $month] = explode('-', $period);

        // Build per-seller commission data for the selected period
        $sellers = User::where('role', 'seller')
            ->where('isVerified', true)
            ->orderBy('name')
            ->get()
            ->map(function (User $seller) use ($period, $year, $month, $rate) {
                // Sales for this period
                $totalSales = (float) Order::whereNotIn('status', ['Cancelled'])
                    ->where('sellerId', $seller->id)
                    ->whereYear('createdAt', $year)
                    ->whereMonth('createdAt', $month)
                    ->sum('totalAmount');

                $commissionAmount = round($totalSales * ($rate / 100), 2);

                // Get or build commission record
                $record = CommissionRecord::where('sellerId', $seller->id)
                    ->where('period', $period)
                    ->first();

                return [
                    'seller'           => $seller,
                    'totalSales'       => $totalSales,
                    'commissionRate'   => $rate,
                    'commissionAmount' => $commissionAmount,
                    'status'           => $record?->status ?? 'unpaid',
                    'paidAt'           => $record?->paidAt,
                    'dueDate'          => $record?->dueDate,
                    'notes'            => $record?->notes,
                    'recordId'         => $record?->id,
                    'paymentMethod'    => $record?->paymentMethod,
                    'referenceNumber'  => $record?->referenceNumber,
                    'paymentProof'     => $record?->paymentProof,
                ];
            });

        $frozenSellers = User::where('role', 'seller')->where('status', 'frozen')->get()->map(function (User $fs) {
            $latestRecord = CommissionRecord::where('sellerId', $fs->id)->orderByDesc('id')->first();
            return [
                'seller'          => $fs,
                'paymentMethod'   => $latestRecord?->paymentMethod,
                'referenceNumber' => $latestRecord?->referenceNumber,
                'paymentProof'    => $latestRecord?->paymentProof,
                'reason'          => $fs->violationReason,
            ];
        });

        // Summary for selected period
        $periodTotalSales  = $sellers->sum('totalSales');
        $periodTotalDue    = $sellers->sum('commissionAmount');
        $periodTotalPaid   = $sellers->where('status', 'paid')->sum('commissionAmount');
        $periodUnpaid      = $sellers->where('status', 'unpaid')->count();

        // Available periods (months with orders)
        $periods = Order::selectRaw("DATE_FORMAT(createdAt, '%Y-%m') as period")
            ->groupBy('period')
            ->orderByDesc('period')
            ->pluck('period');

        return view('superadmin.commissions', compact(
            'sellers', 'frozenSellers', 'period', 'periods', 'rate',
            'periodTotalSales', 'periodTotalDue', 'periodTotalPaid', 'periodUnpaid'
        ));
    }

    public function paymentSettings()
    {
        $gcashNumber = SystemSetting::where('key', 'superadmin_gcash_number')->value('value') ?? '';
        $gcashQr     = SystemSetting::where('key', 'superadmin_gcash_qr')->value('value') ?? '';
        $mayaNumber  = SystemSetting::where('key', 'superadmin_maya_number')->value('value') ?? '';
        $mayaQr      = SystemSetting::where('key', 'superadmin_maya_qr')->value('value') ?? '';

        return view('superadmin.payment_settings', compact('gcashNumber', 'gcashQr', 'mayaNumber', 'mayaQr'));
    }

    public function updateCommissionRate(Request $request)
    {
        $request->validate(['rate' => 'required|numeric|min:0|max:100']);
        SystemSetting::updateOrCreate(
            ['key' => 'commission_rate'],
            ['value' => $request->rate]
        );
        return redirect()->back()->with('success', "Commission rate updated to {$request->rate}%.");
    }

    public function updatePaymentSettings(Request $request)
    {
        $request->validate([
            'gcash_number' => 'nullable|string|max:50',
            'gcash_qr'     => 'nullable|image|max:2048',
            'maya_number'  => 'nullable|string|max:50',
            'maya_qr'      => 'nullable|image|max:2048',
        ]);

        if ($request->has('gcash_number')) {
            SystemSetting::updateOrCreate(['key' => 'superadmin_gcash_number'], ['value' => $request->gcash_number ?? '']);
        }

        if ($request->hasFile('gcash_qr')) {
            $file = $request->file('gcash_qr');
            $filename = time() . '_superadmin_gcash_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/qrcodes'), $filename);
            SystemSetting::updateOrCreate(['key' => 'superadmin_gcash_qr'], ['value' => '/uploads/qrcodes/' . $filename]);
        }

        if ($request->has('maya_number')) {
            SystemSetting::updateOrCreate(['key' => 'superadmin_maya_number'], ['value' => $request->maya_number ?? '']);
        }

        if ($request->hasFile('maya_qr')) {
            $file = $request->file('maya_qr');
            $filename = time() . '_superadmin_maya_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/qrcodes'), $filename);
            SystemSetting::updateOrCreate(['key' => 'superadmin_maya_qr'], ['value' => '/uploads/qrcodes/' . $filename]);
        }

        return redirect()->route('superadmin.payment-settings')->with('success', 'Payment details and QR codes updated successfully.');
    }

    public function markPaid(Request $request, string $sellerId)
    {
        $request->validate(['period' => 'required', 'notes' => 'nullable|string|max:500']);
        $seller = User::findOrFail($sellerId);
        $rate = $this->getCommissionRate();
        [$year, $month] = explode('-', $request->period);

        $totalSales = (float) Order::whereNotIn('status', ['Cancelled'])
            ->where('sellerId', $sellerId)
            ->whereYear('createdAt', $year)
            ->whereMonth('createdAt', $month)
            ->sum('totalAmount');

        $commissionAmount = round($totalSales * ($rate / 100), 2);

        CommissionRecord::updateOrCreate(
            ['sellerId' => $sellerId, 'period' => $request->period],
            [
                'totalSales'       => $totalSales,
                'commissionRate'   => $rate,
                'commissionAmount' => $commissionAmount,
                'status'           => 'paid',
                'paidAt'           => now(),
                'notes'            => $request->notes,
            ]
        );

        // If seller was frozen for commission, unfreeze them
        if ($seller->status === 'frozen') {
            $seller->status = 'active';
            $seller->violationReason = null;
            $seller->save();

            $this->sendNotification(
                $seller->id,
                '✅ Account Unfrozen',
                "Your commission for {$request->period} has been received. Your account is now active again.",
                'system',
                '/seller/dashboard',
                'seller'
            );
        }

        return redirect()->back()->with('success', "Commission marked as paid for {$seller->name}.");
    }

    public function freezeShop(Request $request, string $id)
    {
        $request->validate(['reason' => 'nullable|string|max:500', 'period' => 'nullable|string']);
        $seller = User::findOrFail($id);

        $period = $request->period ?: date('Y-m');
        $reason = $request->reason ?: "Commission for period {$period} is overdue and unpaid.";

        $seller->status = 'frozen';
        $seller->violationReason = $reason;
        $seller->save();

        // Mark freeze notification sent
        CommissionRecord::where('sellerId', $id)
            ->where('period', $period)
            ->update(['freezeNotified' => true]);

        $this->sendNotification(
            $seller->id,
            '🔒 Account Frozen — Commission Overdue',
            "Your shop has been frozen due to unpaid commission for {$period}. Please settle your balance to restore access. Reason: {$reason}",
            'system',
            '/seller/subscription',
            'seller'
        );

        return redirect()->back()->with('success', "Shop '{$seller->name}' has been frozen.");
    }

    public function unfreezeShop(string $id)
    {
        $seller = User::findOrFail($id);
        $seller->status = 'active';
        $seller->violationReason = null;
        $seller->save();

        // Automatically mark unpaid commission records for this seller as paid upon unfreezing
        CommissionRecord::where('sellerId', $id)->where('status', 'unpaid')->update([
            'status' => 'paid',
            'paidAt' => now(),
        ]);

        $this->sendNotification(
            $seller->id,
            '✅ Account Restored',
            "Your shop account has been unfrozen by the administrator. You can now log in and manage your shop.",
            'system',
            '/seller/dashboard',
            'seller'
        );

        return redirect()->back()->with('success', "Shop '{$seller->name}' has been unfrozen and overdue commission set to paid.");
    }

    // ─── Scheduled Jobs ──────────────────────────────────────────────────────

    /**
     * Called daily by the scheduler.
     * 1. Sends 1-week warning to sellers whose commission is due in 7 days.
     * 2. Auto-freezes sellers whose commission is past due.
     */
    public function runScheduledTasks(): void
    {
        $this->sendWeeklyWarnings();
        $this->autoFreezeOverdue();
    }

    public function sendWeeklyWarnings(): void
    {
        $rate = $this->getCommissionRate();
        $period = Carbon::now()->format('Y-m');
        $dueDate = Carbon::now()->endOfMonth()->addDays(7); // due = 7th of next month

        $sellers = User::where('role', 'seller')
            ->where('isVerified', true)
            ->where('status', 'active')
            ->get();

        foreach ($sellers as $seller) {
            // Only warn once per period
            $record = CommissionRecord::where('sellerId', $seller->id)
                ->where('period', $period)
                ->first();

            if ($record && ($record->status === 'paid' || $record->warningNotified)) {
                continue;
            }

            $totalSales = (float) Order::whereNotIn('status', ['Cancelled'])
                ->where('sellerId', $seller->id)
                ->whereYear('createdAt', Carbon::now()->year)
                ->whereMonth('createdAt', Carbon::now()->month)
                ->sum('totalAmount');

            if ($totalSales <= 0) continue;

            $commissionAmount = round($totalSales * ($rate / 100), 2);

            CommissionRecord::updateOrCreate(
                ['sellerId' => $seller->id, 'period' => $period],
                [
                    'totalSales'      => $totalSales,
                    'commissionRate'  => $rate,
                    'commissionAmount'=> $commissionAmount,
                    'status'         => 'unpaid',
                    'dueDate'        => $dueDate,
                    'warningNotified'=> true,
                ]
            );

            $this->sendNotification(
                $seller->id,
                '⚠️ Commission Due in 7 Days',
                "Your monthly commission of ₱" . number_format($commissionAmount, 2) . " for {$period} is due on " . $dueDate->format('F d, Y') . ". Please settle to avoid account suspension.",
                'system',
                '/seller/subscription',
                'seller'
            );
        }

        Log::info('[SuperAdmin] Weekly commission warnings sent.');
    }

    public function autoFreezeOverdue(): void
    {
        $now = Carbon::now();
        // Due date = 7th of the following month
        $overduePeriod = $now->copy()->subMonth()->format('Y-m');
        $overdueDate = Carbon::createFromFormat('Y-m', $overduePeriod)->endOfMonth()->addDays(7);

        if ($now->lessThan($overdueDate)) {
            return; // Not yet overdue
        }

        $overdueRecords = CommissionRecord::where('period', $overduePeriod)
            ->where('status', 'unpaid')
            ->where('commissionAmount', '>', 0)
            ->with('seller')
            ->get();

        foreach ($overdueRecords as $record) {
            $seller = $record->seller;
            if (!$seller || $seller->status === 'frozen' || $seller->status === 'blocked') {
                continue;
            }

            $seller->status = 'frozen';
            $seller->violationReason = "Auto-frozen: Commission for {$overduePeriod} is overdue.";
            $seller->save();

            $record->freezeNotified = true;
            $record->save();

            $this->sendNotification(
                $seller->id,
                '🔒 Account Auto-Frozen — Overdue Commission',
                "Your account has been automatically frozen because your commission of ₱" . number_format($record->commissionAmount, 2) . " for {$overduePeriod} was not paid by the due date. Please contact admin to settle.",
                'system',
                '/seller/subscription',
                'seller'
            );

            Log::info("[SuperAdmin] Auto-frozen seller: {$seller->name} ({$seller->id}) for period {$overduePeriod}");
        }
    }

    // ─── Artisan Sellers & Shops Management ───────────────────────────────────

    public function sellers(Request $request)
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');
        $rate   = $this->getCommissionRate();

        $query = User::where('role', 'seller')
            ->withCount('products');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('shopName', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where(function($q) {
                $q->whereNull('status')->orWhere('status', 'active');
            });
        } elseif ($status === 'frozen') {
            $query->where('status', 'frozen');
        } elseif ($status === 'unverified') {
            $query->where('isVerified', false);
        }

        $sellers = $query->orderByDesc('createdAt')
            ->paginate(15)
            ->through(function (User $seller) use ($rate) {
                $sales = (float) Order::whereNotIn('status', ['Cancelled'])
                    ->where('sellerId', $seller->id)
                    ->sum('totalAmount');
                $unpaid = (float) CommissionRecord::where('sellerId', $seller->id)
                    ->where('status', 'unpaid')
                    ->sum('commissionAmount');

                return [
                    'id'             => $seller->id,
                    'name'           => $seller->name,
                    'shop_name'      => $seller->shopName ?: $seller->name,
                    'email'          => $seller->email,
                    'phone'          => $seller->phone ?: '—',
                    'is_verified'    => (bool) $seller->isVerified,
                    'status'         => $seller->status ?? 'active',
                    'products_count' => $seller->products_count,
                    'total_sales'    => $sales,
                    'total_profit'   => round($sales * ($rate / 100), 2),
                    'unpaid_debt'    => $unpaid,
                    'created_at'     => $seller->createdAt,
                ];
            });

        return view('superadmin.sellers', compact('sellers', 'search', 'status', 'rate'));
    }

    public function verifySeller(string $id)
    {
        $seller = User::where('role', 'seller')->findOrFail($id);
        $seller->isVerified = true;
        $seller->save();

        return back()->with('success', "Artisan shop '{$seller->shopName}' is now verified.");
    }

    public function unverifySeller(string $id)
    {
        $seller = User::where('role', 'seller')->findOrFail($id);
        $seller->isVerified = false;
        $seller->save();

        return back()->with('success', "Artisan shop '{$seller->shopName}' verification removed.");
    }

    // ─── Customer Directory Management ────────────────────────────────────────

    public function customers(Request $request)
    {
        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');

        $query = User::where('role', 'buyer');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where(function($q) {
                $q->whereNull('status')->orWhere('status', 'active');
            });
        } elseif ($status === 'banned') {
            $query->where('status', 'banned');
        }

        $customers = $query->orderByDesc('createdAt')
            ->paginate(15)
            ->through(function (User $user) {
                $ordersCount = Order::where('customerId', $user->id)->count();
                $totalSpent  = (float) Order::where('customerId', $user->id)
                    ->whereNotIn('status', ['Cancelled'])
                    ->sum('totalAmount');

                return [
                    'id'           => $user->id,
                    'name'         => $user->name,
                    'email'        => $user->email,
                    'phone'        => $user->phone ?: '—',
                    'status'       => $user->status ?? 'active',
                    'orders_count' => $ordersCount,
                    'total_spent'  => $totalSpent,
                    'created_at'   => $user->createdAt,
                ];
            });

        return view('superadmin.customers', compact('customers', 'search', 'status'));
    }

    public function banCustomer(string $id)
    {
        $customer = User::where('role', 'buyer')->findOrFail($id);
        $customer->status = 'banned';
        $customer->save();

        return back()->with('success', "Customer account '{$customer->name}' has been banned.");
    }

    public function unbanCustomer(string $id)
    {
        $customer = User::where('role', 'buyer')->findOrFail($id);
        $customer->status = 'active';
        $customer->save();

        return back()->with('success', "Customer account '{$customer->name}' has been unbanned.");
    }

    // ─── Maintenance Mode & 1-Click Cache Utility ─────────────────────────────

    public function maintenance()
    {
        $isMaintenanceMode  = $this->isInMaintenance();
        $maintenanceMessage = SystemSetting::where('key', 'maintenance_message')->first()?->value
            ?? 'We are currently performing scheduled system maintenance. We will be back shortly.';
        $scheduledAt  = SystemSetting::where('key', 'maintenance_scheduled_at')->first()?->value;
        $scheduledEnd = SystemSetting::where('key', 'maintenance_scheduled_end')->first()?->value;

        return view('superadmin.maintenance', compact(
            'isMaintenanceMode', 'maintenanceMessage', 'scheduledAt', 'scheduledEnd'
        ));
    }

    public function toggleMaintenance(Request $request)
    {
        $enable  = $request->input('enable');
        $message = $request->input('message', 'Scheduled system maintenance in progress.');

        SystemSetting::updateOrCreate(['key' => 'maintenance_message'], ['value' => $message]);

        if ($enable === '1') {
            SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '1']);
            try {
                Artisan::call('down', ['--render' => 'errors.503', '--secret' => 'lumbarong-superadmin']);
            } catch (\Exception $e) {
                Log::warning('Artisan down error: ' . $e->getMessage());
            }
            return back()->with('success', 'Maintenance mode has been ENABLED across the platform.');
        } else {
            SystemSetting::updateOrCreate(['key' => 'maintenance_mode'], ['value' => '0']);
            try {
                Artisan::call('up');
            } catch (\Exception $e) {
                Log::warning('Artisan up error: ' . $e->getMessage());
            }
            return back()->with('success', 'Maintenance mode has been DISABLED. Platform is live.');
        }
    }

    public function clearCache()
    {
        try {
            Artisan::call('optimize:clear');
            Artisan::call('view:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('cache:clear');
            return back()->with('success', '✨ All system caches (Views, Routes, Config, App Cache) successfully cleared!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error clearing cache: ' . $e->getMessage());
        }
    }

    // ─── Audit Logs ───────────────────────────────────────────────────────────

    public function auditLogs(Request $request)
    {
        $search  = strtolower(trim($request->input('search', '')));
        $tab     = $request->input('tab', 'all');
        $page    = (int) $request->input('page', 1);
        $perPage = 15;

        $orders   = $this->orderLogEntries();
        $products = $this->productLogEntries();
        $users    = $this->userLogEntries();

        $filterFn = function ($collection) use ($search) {
            if (!$search) return $collection;
            return $collection->filter(fn ($l) => str_contains(
                strtolower(($l['actor'] ?? '') . ($l['action'] ?? '') . ($l['detail'] ?? '')),
                $search
            ))->values();
        };

        $ordersFiltered   = $filterFn($orders);
        $productsFiltered = $filterFn($products);
        $usersFiltered    = $filterFn($users);

        $active = match ($tab) {
            'orders'   => $ordersFiltered,
            'products' => $productsFiltered,
            'users'    => $usersFiltered,
            default    => $ordersFiltered->concat($productsFiltered)->concat($usersFiltered)->sortByDesc('time')->values(),
        };

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $active->slice(($page - 1) * $perPage, $perPage)->values(),
            $active->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('superadmin.audit_logs', [
            'logs'   => $paginator,
            'search' => $search,
            'tab'    => $tab,
            'counts' => [
                'all'      => $ordersFiltered->count() + $productsFiltered->count() + $usersFiltered->count(),
                'orders'   => $ordersFiltered->count(),
                'products' => $productsFiltered->count(),
                'users'    => $usersFiltered->count(),
            ],
        ]);
    }

    // ─── Live System Error Logs Viewer ────────────────────────────────────────

    public function errorLogs(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        $entries = [];

        if (File::exists($logPath)) {
            $content = File::get($logPath);
            $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+([a-zA-Z0-9_-]+)\.([A-Z]+):\s+(.*?)(?=\n\[\d{4}-\d{2}-\d{2}|\z)/s';
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach (array_reverse($matches) as $match) {
                    $entries[] = [
                        'timestamp'   => $match[1],
                        'environment' => $match[2],
                        'level'       => strtoupper($match[3]),
                        'message'     => Str::limit(trim($match[4]), 400),
                        'full_text'   => trim($match[4]),
                    ];
                    if (count($entries) >= 80) break;
                }
            }
        }

        $logSize = File::exists($logPath) ? round(File::size($logPath) / 1024 / 1024, 2) . ' MB' : '0 MB';

        return view('superadmin.error_logs', compact('entries', 'logSize'));
    }

    public function clearErrorLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }
        return back()->with('success', 'System error log (laravel.log) has been cleared.');
    }

    // ─── Server & Platform Info ───────────────────────────────────────────────

    public function platform()
    {
        $info = [
            'app' => [
                'name'        => config('app.name', 'LumBarong'),
                'version'     => app()->version(),
                'environment' => app()->environment(),
                'debug'       => config('app.debug') ? 'Enabled' : 'Disabled',
                'url'         => config('app.url'),
                'timezone'    => config('app.timezone'),
                'locale'      => config('app.locale'),
            ],
            'php' => [
                'version'             => PHP_VERSION,
                'os'                  => PHP_OS,
                'extensions'          => implode(', ', array_slice(get_loaded_extensions(), 0, 12)) . '...',
                'memory_limit'        => ini_get('memory_limit'),
                'max_execution_time'  => ini_get('max_execution_time') . 's',
                'upload_max_filesize' => ini_get('upload_max_filesize'),
            ],
            'database' => [
                'driver'   => config('database.default'),
                'host'     => config('database.connections.' . config('database.default') . '.host', 'N/A'),
                'database' => config('database.connections.' . config('database.default') . '.database', 'N/A'),
                'version'  => $this->getDbVersion(),
            ],
            'stats' => [
                'total_users'    => User::count(),
                'total_sellers'  => User::where('role', 'seller')->count(),
                'total_orders'   => Order::count(),
                'total_products' => Product::count(),
                'total_revenue'  => '₱' . number_format((float)Order::whereNotIn('status', ['Cancelled'])->sum('totalAmount'), 2),
                'db_size'        => $this->getDbSize(),
            ],
        ];

        $dbName = config('database.connections.' . config('database.default') . '.database');
        $tables = [];
        try {
            $tables = DB::select("
                SELECT table_name AS `name`, table_rows AS `rows`, 
                       ROUND((data_length + index_length) / 1024 / 1024, 2) AS `size_mb`
                FROM information_schema.tables
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
            ", [$dbName]);
        } catch (\Exception $e) {
            $tables = [];
        }

        return view('superadmin.platform', compact('info', 'tables'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function isInMaintenance(): bool
    {
        if (app()->isDownForMaintenance()) {
            return true;
        }
        $flag = SystemSetting::where('key', 'maintenance_mode')->first()?->value;
        return $flag === '1' || $flag === true || $flag === 1;
    }

    private function getDbVersion(): string
    {
        try {
            return DB::select('SELECT VERSION() as v')[0]->v ?? 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function getDbSize(): string
    {
        try {
            $dbName = config('database.connections.' . config('database.default') . '.database');
            $mb = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
                              FROM information_schema.tables
                              WHERE table_schema = ?", [$dbName])[0]->size ?? 0;
            return "{$mb} MB";
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function getDiskFree(): string
    {
        try {
            $bytes = disk_free_space(base_path());
            if ($bytes === false) return 'N/A';
            return round($bytes / 1024 / 1024 / 1024, 1) . ' GB';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function countLogErrors(): int
    {
        $logPath = storage_path('logs/laravel.log');
        if (!File::exists($logPath)) return 0;
        try {
            $content = File::get($logPath);
            return substr_count($content, '.ERROR:') + substr_count($content, '.CRITICAL:');
        } catch (\Exception $e) {
            return 0;
        }
    }

    /** @return \Illuminate\Support\Collection<int, array{time: mixed, actor: string, action: string, detail: string, type: string}> */
    private function orderLogEntries(): \Illuminate\Support\Collection
    {
        return Order::with('customer:id,name', 'seller:id,name')
            ->orderBy('createdAt', 'desc')
            ->limit(50)
            ->get()
            ->map(fn (Order $o): array => [
                'time'   => $o->createdAt,
                'actor'  => $o->customer->name ?? 'Guest',
                'action' => 'Placed Order',
                'detail' => 'Order #' . $o->id . ' — ₱' . number_format((float) $o->totalAmount, 2) . ' (' . $o->status . ')',
                'type'   => 'order',
            ]);
    }

    /** @return \Illuminate\Support\Collection<int, array{time: mixed, actor: string, action: string, detail: string, type: string}> */
    private function productLogEntries(): \Illuminate\Support\Collection
    {
        return Product::with('seller:id,name')
            ->orderBy('createdAt', 'desc')
            ->limit(30)
            ->get()
            ->map(fn (Product $p): array => [
                'time'   => $p->createdAt,
                'actor'  => $p->seller->name ?? 'Unknown Seller',
                'action' => 'Listed Product',
                'detail' => '"' . $p->name . '" — Status: ' . $p->status,
                'type'   => 'product',
            ]);
    }

    /** @return \Illuminate\Support\Collection<int, array{time: mixed, actor: string, action: string, detail: string, type: string}> */
    private function userLogEntries(): \Illuminate\Support\Collection
    {
        return User::orderBy('createdAt', 'desc')
            ->limit(30)
            ->get()
            ->map(fn (User $u): array => [
                'time'   => $u->createdAt,
                'actor'  => $u->name,
                'action' => 'Registered Account',
                'detail' => $u->email . ' — Role: ' . $u->role,
                'type'   => 'user',
            ]);
    }

    private function getCommissionRate(): float
    {
        return (float) (SystemSetting::where('key', 'commission_rate')->value('value') ?? 5.00);
    }

    private function sendNotification(string $userId, string $title, string $message, string $type = 'system', ?string $link = null, string $role = 'seller'): void
    {
        try {
            Notification::create([
                'userId'     => $userId,
                'title'      => $title,
                'message'    => $message,
                'type'       => $type,
                'link'       => $link,
                'targetRole' => $role,
                'isRead'     => false,
            ]);
        } catch (\Exception $e) {
            Log::error('SuperAdmin notification error: ' . $e->getMessage());
        }
    }
}
