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
        if (Auth::check() && Auth::user()->role === 'seller') {
            if (!Auth::user()->isVerified) {
                Auth::logout();
                return redirect('/login')->with('error', 'Your artisan application is still awaiting approval. You will be notified once it is reviewed.');
            }
            return $next($request);
        }

        return redirect('/')->with('error', 'Access denied. Artisans only.');
    }
}
