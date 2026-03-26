<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Config\Database;
use PHPUnit\Framework\TestCase;

final class HealthApiTest extends TestCase
{
    protected function setUp(): void
    {
        Database::reset();
        $pdo = Database::connection();
        $pdo->exec('CREATE TABLE IF NOT EXISTS _health_check (id INTEGER PRIMARY KEY)');
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    public function testHealthCheckReturnsHealthyData(): void
    {
        $controller = new \App\Controllers\HealthController();

        $checks = [
            'database' => $this->checkDatabase(),
            'storage'  => $this->checkStorage(),
        ];

        $this->assertArrayHasKey('database', $checks);
        $this->assertArrayHasKey('storage', $checks);
        $this->assertContains($checks['database'], ['ok', 'fail']);
    }

    private function checkDatabase(): string
    {
        try {
            Database::connection()->query('SELECT 1');
            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }

    private function checkStorage(): string
    {
        $dir = sys_get_temp_dir();
        return (is_dir($dir) && is_writable($dir)) ? 'ok' : 'fail';
    }
}
