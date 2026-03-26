<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Config\Database;
use App\Repositories\AlertFilterRepository;
use PHPUnit\Framework\TestCase;

final class AlertFilterRepositoryTest extends TestCase
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

    public function testCreateReturnsAlertFilter(): void
    {
        $filter = $this->repo->create([
            'email'    => 'dev@test.com',
            'keywords' => ['PHP', 'Laravel'],
        ]);

        $this->assertGreaterThan(0, $filter->id);
        $this->assertSame('dev@test.com', $filter->email);
        $this->assertSame(['PHP', 'Laravel'], $filter->keywords);
    }

    public function testFindActiveReturnsOnlyActiveFilters(): void
    {
        $filterA = $this->repo->create(['email' => 'a@test.com', 'keywords' => ['PHP']]);
        $filterB = $this->repo->create(['email' => 'b@test.com', 'keywords' => ['Java']]);

        Database::connection()->exec(
            "UPDATE alert_filters SET is_active = 0 WHERE id = {$filterB->id}"
        );

        $active = $this->repo->findActive();

        $this->assertCount(1, $active);
        $this->assertSame('a@test.com', $active[0]->email);
    }

    public function testDeleteRemovesFilter(): void
    {
        $filter  = $this->repo->create(['email' => 'x@test.com', 'keywords' => ['Go']]);
        $deleted = $this->repo->delete($filter->id);

        $this->assertTrue($deleted);
        $this->assertEmpty($this->repo->findAll());
    }

    public function testDeleteReturnsFalseForMissingFilter(): void
    {
        $deleted = $this->repo->delete(99999);

        $this->assertFalse($deleted);
    }
}
