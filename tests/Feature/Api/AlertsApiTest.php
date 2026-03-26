<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Config\Database;
use App\Repositories\AlertFilterRepository;
use PHPUnit\Framework\TestCase;

final class AlertsApiTest extends TestCase
{
    private AlertFilterRepository $repo;

    protected function setUp(): void
    {
        Database::reset();
        $pdo = Database::connection();
        $this->migrate($pdo);
        $this->repo = new AlertFilterRepository();
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    private function migrate(\PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS alert_filters (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                email          TEXT    NOT NULL,
                keywords       TEXT    NOT NULL DEFAULT \'[]\',
                locations      TEXT,
                contract_types TEXT,
                is_active      INTEGER NOT NULL DEFAULT 1,
                created_at     TEXT    NOT NULL DEFAULT (datetime(\'now\'))
            )
        ');
    }

    public function testCreateAndRetrieveAlert(): void
    {
        $filter = $this->repo->create([
            'email'          => 'user@test.com',
            'keywords'       => ['PHP', 'Laravel'],
            'locations'      => ['São Paulo'],
            'contract_types' => ['PJ'],
        ]);

        $this->assertSame('user@test.com', $filter->email);
        $this->assertSame(['PHP', 'Laravel'], $filter->keywords);
        $this->assertTrue($filter->isActive);
    }

    public function testListAllAlerts(): void
    {
        $this->repo->create(['email' => 'a@test.com', 'keywords' => ['PHP']]);
        $this->repo->create(['email' => 'b@test.com', 'keywords' => ['Go']]);

        $all = $this->repo->findAll();

        $this->assertCount(2, $all);
    }

    public function testDeleteAlert(): void
    {
        $filter  = $this->repo->create(['email' => 'x@test.com', 'keywords' => ['Rust']]);
        $deleted = $this->repo->delete($filter->id);

        $this->assertTrue($deleted);
        $this->assertCount(0, $this->repo->findAll());
    }

    public function testDeleteReturnsFalseForNonExistentAlert(): void
    {
        $this->assertFalse($this->repo->delete(9999));
    }

    public function testFindActiveFiltersOutInactive(): void
    {
        $active   = $this->repo->create(['email' => 'active@test.com', 'keywords' => ['PHP']]);
        $inactive = $this->repo->create(['email' => 'inactive@test.com', 'keywords' => ['Java']]);

        Database::connection()->exec(
            "UPDATE alert_filters SET is_active = 0 WHERE id = {$inactive->id}"
        );

        $results = $this->repo->findActive();

        $this->assertCount(1, $results);
        $this->assertSame('active@test.com', $results[0]->email);
    }
}
