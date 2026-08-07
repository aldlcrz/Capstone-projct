<?php
// TEMPORARY DEBUG SCRIPT - DELETE AFTER USE
// Access via: https://lumbarong.shop/debug_500.php

// Security: only allow from localhost or specific IP
$allowed = ['127.0.0.1', '::1', '31.220.110.128'];
// Uncomment below if you want to restrict access:
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowed)) { die('Forbidden'); }

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';

try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Check DB connection
    echo "<h2>DB Connection</h2>";
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        echo "✅ Database connected OK<br>";
    } catch (\Throwable $e) {
        echo "❌ DB Error: " . $e->getMessage() . "<br>";
    }

    // Check tables
    echo "<h2>Key Tables</h2>";
    $tables = ['users', 'products', 'orders', 'reviews', 'categories', 'addresses', 'banners'];
    foreach ($tables as $table) {
        try {
            $exists = \Illuminate\Support\Facades\Schema::hasTable($table);
            echo ($exists ? "✅" : "❌") . " Table `{$table}`: " . ($exists ? "exists" : "MISSING") . "<br>";
        } catch (\Throwable $e) {
            echo "❌ Error checking `{$table}`: " . $e->getMessage() . "<br>";
        }
    }

    // Check users table columns
    echo "<h2>Users Table Columns</h2>";
    try {
        $cols = \Illuminate\Support\Facades\Schema::getColumnListing('users');
        echo implode(', ', $cols) . "<br>";
    } catch (\Throwable $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }

    // Try rendering the home route
    echo "<h2>Home Route Test</h2>";
    try {
        $req = \Illuminate\Http\Request::create('/', 'GET');
        $res = $app->make(\Illuminate\Contracts\Http\Kernel::class)->handle($req);
        $status = $res->getStatusCode();
        echo ($status === 200 ? "✅" : "❌") . " HTTP Status: {$status}<br>";
        if ($status !== 200) {
            echo "<pre>" . htmlspecialchars(substr($res->getContent(), 0, 2000)) . "</pre>";
        }
    } catch (\Throwable $e) {
        echo "❌ Exception: " . $e->getMessage() . "<br>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }

    // Check latest Laravel log - show the LAST full exception block
    echo "<h2>Latest Error in Laravel Log</h2>";
    $logPath = __DIR__ . '/../storage/logs/laravel.log';
    if (file_exists($logPath)) {
        $content = file_get_contents($logPath);
        // Find last occurrence of [20 (start of a log timestamp like [2026-)
        $lastBlock = '';
        // Split by log entry separator and get last entry
        $entries = preg_split('/^\[20\d\d-/m', $content);
        if (count($entries) > 1) {
            // Get last 2 entries
            $lastEntries = array_slice($entries, -2);
            $lastBlock = implode('[20', $lastEntries);
        } else {
            $lastBlock = substr($content, -3000);
        }
        echo "<pre style='font-size:11px;max-height:600px;overflow:auto;background:#111;color:#f88;padding:12px;white-space:pre-wrap;'>"
            . htmlspecialchars($lastBlock)
            . "</pre>";
    } else {
        echo "No log file found at: {$logPath}<br>";
    }


} catch (\Throwable $e) {
    echo "<h2>Bootstrap Exception</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . "</pre>";
}
