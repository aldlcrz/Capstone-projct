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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user status is suspended, blocked, or banned
            if ($user && in_array(strtolower($user->status ?? ''), ['blocked', 'banned', 'suspended'])) {
                $reason = !empty($user->violationReason)
                    ? $user->violationReason
                    : 'Your account has been suspended by an administrator for policy violations.';

                // Force logout and destroy active session safely
                Auth::guard('web')->logout();

                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message'       => "Your account has been suspended. Reason: {$reason}",
                        'error'         => 'account_suspended',
                        'banned_reason' => $reason,
                        'redirect'      => route('login'),
                    ], 403);
                }

                if ($request->hasSession()) {
                    return redirect()->route('login')
                        ->with('banned_reason', $reason)
                        ->withErrors([
                            'email' => "Your account has been suspended. Reason: {$reason}",
                        ]);
                }

                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
