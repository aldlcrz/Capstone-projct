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
                if ($user->status === 'frozen') {
                    Auth::logout();
                    if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                        return response()->json(['message' => 'Pay commission to continue'], 403);
                    }
                    return redirect('/login')->withErrors(['email' => 'Pay commission to continue']);
                }

                if (!$user->isVerified) {
                    Auth::logout();
                    if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                        return response()->json(['message' => 'Your artisan application is still awaiting approval.'], 403);
                    }
                    return redirect('/login')->withErrors(['email' => 'Your artisan application is still awaiting approval. You will be notified once it is reviewed.']);
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
