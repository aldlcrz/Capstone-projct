<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SyncUserCart
{
    /**
     * Handle an incoming request.
     * Synchronizes the user's database cart with their current session
     * so that multi-device sessions stay completely in sync.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user && isset($user->cart) && $user->cart !== null) {
                    $rawCart = $user->cart;
                    $dbCart = is_array($rawCart) ? $rawCart : json_decode($rawCart, true);
                    if (is_array($dbCart) && !empty($dbCart)) {
                        session(['cart' => $dbCart]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore if cart column does not exist on production DB
        }

        return $next($request);
    }
}
