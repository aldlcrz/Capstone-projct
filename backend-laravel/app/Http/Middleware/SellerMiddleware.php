<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SellerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Allow administrators and superadmins full access to seller portal
            if (in_array($user->role, ['admin', 'superadmin'])) {
                return $next($request);
            }

            // For sellers, verify status and verification state
            if ($user->role === 'seller') {
                if (in_array(strtolower($user->status ?? ''), ['blocked', 'banned', 'suspended'])) {
                    $reason = !empty($user->violationReason) ? $user->violationReason : 'Violation of platform seller policies.';
                    $msg = "Your account has been suspended for a policy violation. Reason: {$reason}";
                    Auth::logout();
                    if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                        return response()->json(['message' => $msg], 403);
                    }
                    return redirect('/login')->withErrors(['email' => $msg]);
                }

                if ($user->status === 'frozen') {
                    // Allow frozen sellers to access subscription/commission payment settlement routes
                    if ($request->is('seller/subscription*') || $request->is('seller/commissions*') || $request->is('api/seller/subscription*') || $request->is('api/seller/commissions*')) {
                        return $next($request);
                    }

                    $overdue = \App\Models\CommissionRecord::where('sellerId', $user->id)
                        ->where('status', 'unpaid')
                        ->orderByDesc('period')
                        ->first();
                    $amount = $overdue ? number_format($overdue->commissionAmount, 2) : '0.00';
                    $period = $overdue ? $overdue->period : 'current';
                    $msg = "Your shop is temporarily frozen due to an unpaid monthly commission of ₱{$amount} for {$period}. Please settle your outstanding commission to restore access.";

                    Auth::logout();
                    if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                        return response()->json(['message' => $msg], 403);
                    }
                    return redirect('/login')->withErrors(['email' => $msg]);
                }

                if (!$user->isVerified || $user->status === 'pending') {
                    // Allow pending sellers to access the dedicated document re-upload & verification portal
                    if ($request->is('seller/verification-pending*') || $request->is('api/seller/verification-pending*')) {
                        return $next($request);
                    }

                    if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                        return response()->json([
                            'message'  => 'Your artisan application is awaiting document verification.',
                            'redirect' => route('seller.verification-pending'),
                        ], 403);
                    }

                    return redirect()->route('seller.verification-pending');
                }

                return $next($request);
            }
        }

        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json(['message' => 'Access denied. Artisans only.'], 403);
        }

        return redirect('/')->with('error', 'Access denied. Artisans only.');
    }
}
