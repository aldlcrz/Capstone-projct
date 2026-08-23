<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            try {
                $pdo = DB::connection()->getPdo();
                if ($pdo instanceof \PDO && method_exists($pdo, 'sqliteCreateCollation')) {
                    $pdo->sqliteCreateCollation('utf8mb4_bin', 'strcmp');
                    $pdo->sqliteCreateCollation('utf8mb4_general_ci', 'strcasecmp');
                    $pdo->sqliteCreateCollation('utf8mb4_unicode_ci', 'strcasecmp');
                }
            } catch (\Throwable $e) {
                // Ignore if not supported
            }
        }
    }
}
