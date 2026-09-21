<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json(['message' => 'Access denied. Administrator only.'], 403);
        }

        return redirect('/')->with('error', 'Access denied. Administrator only.');
    }
}
