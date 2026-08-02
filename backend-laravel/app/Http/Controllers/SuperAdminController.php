<?php

namespace App\Http\Controllers;

use App\Models\CommissionRecord;
use App\Models\Notification;
use App\Models\Order;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $frozenCount   = User::where('role', 'seller')->where('status', 'frozen')->count();
        $unpaidCount   = CommissionRecord::where('status', 'unpaid')->count();
        $sellerCount   = User::where('role', 'seller')->where('isVerified', true)->count();

        // Recent commission records
        $recentRecords = CommissionRecord::with('seller')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('superadmin.dashboard', compact(
            'rate', 'totalSalesAllTime', 'totalSalesThisMonth',
            'totalCommissionAllTime', 'totalCommissionThisMonth',
            'totalCollected', 'totalOutstanding',
            'frozenCount', 'unpaidCount', 'sellerCount',
            'recentRecords', 'currentPeriod'
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
            $path = $request->file('gcash_qr')->store('qr_codes', 'public');
            SystemSetting::updateOrCreate(['key' => 'superadmin_gcash_qr'], ['value' => $path]);
        }

        if ($request->has('maya_number')) {
            SystemSetting::updateOrCreate(['key' => 'superadmin_maya_number'], ['value' => $request->maya_number ?? '']);
        }

        if ($request->hasFile('maya_qr')) {
            $path = $request->file('maya_qr')->store('qr_codes', 'public');
            SystemSetting::updateOrCreate(['key' => 'superadmin_maya_qr'], ['value' => $path]);
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

    // ─── Helpers ─────────────────────────────────────────────────────────────

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
