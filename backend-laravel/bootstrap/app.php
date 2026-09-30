<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = env('TRUSTED_PROXIES');
        if (!empty($trustedProxies)) {
            $proxies = array_map('trim', explode(',', $trustedProxies));
            $middleware->trustProxies(at: $proxies);
        }

        $middleware->validateCsrfTokens(except: [
            'logout',
            'superadmin/logout',
        ]);

        $middleware->alias([
            'admin'        => \App\Http\Middleware\AdminMiddleware::class,
            'seller'       => \App\Http\Middleware\SellerMiddleware::class,
            'superadmin'   => \App\Http\Middleware\SuperAdminMiddleware::class,
            'prevent.back' => \App\Http\Middleware\PreventBackHistory::class,
            'check.status'  => \App\Http\Middleware\CheckAccountStatus::class,
            'single.device' => \App\Http\Middleware\SingleDeviceSession::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\SingleDeviceSession::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckAccountStatus::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckMaintenance::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\PreventBackHistory::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SyncUserCart::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\SingleDeviceSession::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\CheckAccountStatus::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\CheckMaintenance::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $handle419 = function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('logout') || $request->is('superadmin/logout')) {
                \Illuminate\Support\Facades\Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('info', 'You have been logged out.');
            }

            if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'message' => 'Your session has expired. Reloading page...',
                    'reload' => true,
                    'status' => 419
                ], 419);
            }

            $targetUrl = $request->header('Referer') ?: url()->previous();
            if (empty($targetUrl) || $targetUrl === $request->fullUrl()) {
                $targetUrl = route('home');
            }

            return redirect()->to($targetUrl)
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('info', 'Your session was automatically refreshed.');
        };

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) use ($handle419) {
            return $handle419($e, $request);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) use ($handle419) {
            if ($e->getStatusCode() === 419) {
                return $handle419($e, $request);
            }
        });
    })->create();
