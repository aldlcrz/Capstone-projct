<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SingleDeviceSession
{
    /**
     * Handle an incoming request to enforce single-device active logins.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $request->hasSession()) {
            $user = Auth::user();
            $storedVersion = $request->session()->get('login_session_version');

            // If the user's DB sessionVersion is higher than this session's version,
            // another device has logged in with this account.
            if ($storedVersion !== null && $user && (int) $user->sessionVersion > (int) $storedVersion) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $warningMsg = 'You have been logged out because your account was logged in from another device.';

                if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                    return response()->json([
                        'status'   => 'session_terminated',
                        'message'  => $warningMsg,
                        'redirect' => route('login'),
                    ], 401);
                }

                return redirect()->route('login')
                    ->with('session_terminated', true)
                    ->with('warning', $warningMsg);
            }

            // Sync version if not yet set in session (e.g. existing session)
            if ($storedVersion === null && $user) {
                $currentVer = (int) ($user->sessionVersion ?: 1);
                $request->session()->put('login_session_version', $currentVer);
            }
        }

        return $next($request);
    }
}
