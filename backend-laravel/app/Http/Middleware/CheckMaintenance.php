<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    /**
     * Cache key for maintenance settings
     */
    const CACHE_KEY = 'system_maintenance_config';

    /**
     * Retrieve current maintenance configuration from Cache / DB
     */
    public static function getMaintenanceConfig(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                if (app()->isDownForMaintenance()) {
                    return [
                        'active'        => true,
                        'message'       => 'We are currently performing scheduled maintenance. We will be back shortly.',
                        'estimated_end' => null,
                        'enabled_by'    => 'System CLI',
                        'enabled_at'    => now()->toIso8601String(),
                    ];
                }

                $flag = SystemSetting::where('key', 'maintenance_mode')->first()?->value;
                $isActive = ($flag === '1' || $flag === true || $flag === 1 || $flag === 'true');

                $message = SystemSetting::where('key', 'maintenance_message')->first()?->value 
                    ?? 'We are currently performing scheduled system maintenance. We will be back shortly.';
                $estimatedEnd = SystemSetting::where('key', 'maintenance_scheduled_end')->first()?->value;
                $enabledBy = SystemSetting::where('key', 'maintenance_enabled_by')->first()?->value ?? 'Super Administrator';
                $enabledAt = SystemSetting::where('key', 'maintenance_enabled_at')->first()?->value;

                return [
                    'active'        => $isActive,
                    'message'       => $message,
                    'estimated_end' => $estimatedEnd,
                    'enabled_by'    => $enabledBy,
                    'enabled_at'    => $enabledAt,
                ];
            });
        } catch (\Throwable $e) {
            return [
                'active'        => false,
                'message'       => 'System operational',
                'estimated_end' => null,
                'enabled_by'    => null,
                'enabled_at'    => null,
            ];
        }
    }

    /**
     * Returns true when the site is currently in maintenance mode.
     */
    public static function isInMaintenance(): bool
    {
        $config = static::getMaintenanceConfig();
        return !empty($config['active']);
    }

    /**
     * Invalidate the maintenance settings cache immediately.
     */
    public static function clearMaintenanceCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Always allow critical system infrastructure and health checks
        $whitelistPatterns = [
            'up',
            'build/*',
            'images/*',
            'storage/*',
            'favicon.ico',
            'livewire/*',
            'superadmin*',
            'api/v1/superadmin*',
        ];

        foreach ($whitelistPatterns as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // 2. If maintenance mode is NOT active, proceed normally
        if (!static::isInMaintenance()) {
            return $next($request);
        }

        // 3. Maintenance mode is ACTIVE:
        // Always allow Super Administrators full uninterrupted access
        try {
            if (Auth::check() && Auth::user()->role === 'superadmin') {
                return $next($request);
            }
        } catch (\Throwable $e) {
            // Ignore auth check exceptions during early boot
        }

        // 4. Whitelist login endpoints so Super Admins can authenticate if session was cleared
        $authRoutes = [
            'login',
            'superadmin/login',
            'api/v1/auth/login',
            'api/v1/auth/google',
        ];

        foreach ($authRoutes as $authRoute) {
            if ($request->is($authRoute)) {
                return $next($request);
            }
        }

        // 5. Block all other users (guests, customers, sellers, standard admins) with HTTP 503
        $config = static::getMaintenanceConfig();
        $message = $config['message'] ?? 'We are currently performing scheduled system maintenance. We will be back shortly.';
        $estimatedEnd = $config['estimated_end'] ?? null;

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message'          => $message,
                'maintenance'      => true,
                'estimated_end_at' => $estimatedEnd,
            ], 503);
        }

        return response()->view('errors.maintenance', [
            'message'       => $message,
            'estimated_end' => $estimatedEnd,
        ], 503);
    }
}
