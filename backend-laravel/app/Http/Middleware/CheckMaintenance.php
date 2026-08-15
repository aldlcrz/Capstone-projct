<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    /**
     * Returns true when the site is in maintenance mode via either
     * Laravel's native down-file OR the DB flag stored by the admin panel.
     */
    private function isInMaintenance(): bool
    {
        if (app()->isDownForMaintenance()) {
            return true;
        }

        $flag = SystemSetting::where('key', 'maintenance_mode')->first()?->value;
        return $flag === '1' || $flag === true || $flag === 1;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check bypass patterns
        $bypassPatterns = [
            'up',
            'admin*',
            'api/v1/admin*',
            'login*',
            'logout*',
            'api/v1/auth*',
        ];

        foreach ($bypassPatterns as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // 2. Always let logged-in admins through
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->role === 'admin') {
            return $next($request);
        }

        // 3. Block other requests if maintenance is active
        if ($this->isInMaintenance()) {
            $message = SystemSetting::where('key', 'maintenance_message')->first()?->value
                ?? 'We are currently performing scheduled maintenance. We\'ll be back shortly.';

            // Return JSON for API/expectsJson requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $message,
                    'maintenance' => true
                ], 503);
            }

            return response()->view('errors.maintenance', [
                'message' => $message,
            ], 503);
        }

        return $next($request);
    }
}

