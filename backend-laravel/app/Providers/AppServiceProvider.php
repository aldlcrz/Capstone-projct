<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\ConnectionEstablished;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connection->getDriverName() === 'sqlite') {
                try {
                    $pdo = $event->connection->getPdo();
                    if ($pdo instanceof \PDO && method_exists($pdo, 'sqliteCreateCollation')) {
                        $pdo->sqliteCreateCollation('utf8mb4_bin', 'strcmp');
                        $pdo->sqliteCreateCollation('utf8mb4_general_ci', 'strcasecmp');
                        $pdo->sqliteCreateCollation('utf8mb4_unicode_ci', 'strcasecmp');
                    }
                } catch (\Throwable $e) {
                    // Ignore if not supported
                }
            }
        });
    }
}
