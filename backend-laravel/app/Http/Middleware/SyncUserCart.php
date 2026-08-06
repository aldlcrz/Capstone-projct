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
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            if ($user instanceof User && $user->cart !== null) {
                $dbCart = json_decode($user->cart, true);
                if (is_array($dbCart)) {
                    session(['cart' => $dbCart]);
                }
            }
        }

        return $next($request);
    }
}
