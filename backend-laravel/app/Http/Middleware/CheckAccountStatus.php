<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user status is suspended, blocked, or banned
            if ($user && in_array(strtolower($user->status ?? ''), ['blocked', 'banned', 'suspended'])) {
                $reason = !empty($user->violationReason)
                    ? $user->violationReason
                    : 'Violation of platform seller policies';
                $notice = "Your account has been suspended for 1 month due to a policy violation. Reason: {$reason}. Repeated violations could result in a permanent ban from LumBarong.";

                // Force logout and destroy active session safely
                Auth::guard('web')->logout();

                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message'           => $notice,
                        'error'             => 'account_suspended',
                        'banned_reason'     => $reason,
                        'suspension_notice' => $notice,
                        'redirect'          => route('login'),
                    ], 403);
                }

                if ($request->hasSession()) {
                    return redirect()->route('login')
                        ->with('banned_reason', $reason)
                        ->with('suspension_notice', $notice)
                        ->withErrors([
                            'email' => $notice,
                        ]);
                }

                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
