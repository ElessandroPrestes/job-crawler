<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\AlertFilterRepository;
use Tests\Support\DatabaseTestCase;

final class AlertFilterRepositoryTest extends DatabaseTestCase
{
    private AlertFilterRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AlertFilterRepository();
    }

    public function testCreatePersistsAllFields(): void
    {
        $filter = $this->repo->create([
            'email'          => 'dev@test.com',
            'keywords'       => ['PHP', 'Laravel'],
            'locations'      => ['São Paulo', 'Remote'],
            'contract_types' => ['PJ', 'CLT'],
        ]);

        $this->assertGreaterThan(0, $filter->id);
        $this->assertSame('dev@test.com', $filter->email);
        $this->assertSame(['PHP', 'Laravel'], $filter->keywords);
        $this->assertSame(['São Paulo', 'Remote'], $filter->locations);
        $this->assertSame(['PJ', 'CLT'], $filter->contractTypes);
        $this->assertTrue($filter->isActive);
    }

    public function testCreateWithEmptyOptionalFieldsDefaultsToEmptyArrays(): void
    {
        $filter = $this->repo->create(['email' => 'a@test.com', 'keywords' => ['Go']]);

        $this->assertSame([], $filter->locations);
        $this->assertSame([], $filter->contractTypes);
    }

    public function testFindAllReturnsAllFiltersRegardlessOfStatus(): void
    {
        $this->repo->create(['email' => 'a@test.com', 'keywords' => ['PHP']]);
        $inactive = $this->repo->create(['email' => 'b@test.com', 'keywords' => ['Go']]);

        $this->pdo->exec("UPDATE alert_filters SET is_active = 0 WHERE id = {$inactive->id}");

        $all = $this->repo->findAll();

        $this->assertCount(2, $all);
    }

    public function testFindActiveExcludesInactiveFilters(): void
    {
        $this->repo->create(['email' => 'active@test.com', 'keywords' => ['PHP']]);
        $inactive = $this->repo->create(['email' => 'inactive@test.com', 'keywords' => ['Java']]);

        $this->pdo->exec("UPDATE alert_filters SET is_active = 0 WHERE id = {$inactive->id}");

        $results = $this->repo->findActive();

        $this->assertCount(1, $results);
        $this->assertSame('active@test.com', $results[0]->email);
        $this->assertTrue($results[0]->isActive);
    }

    public function testDeleteRemovesTheFilter(): void
    {
        $filter = $this->repo->create(['email' => 'x@test.com', 'keywords' => ['Rust']]);

        $this->assertTrue($this->repo->delete($filter->id));
        $this->assertEmpty($this->repo->findAll());
    }

    public function testDeleteReturnsFalseForNonExistentId(): void
    {
        $this->assertFalse($this->repo->delete(99999));
    }

    public function testKeywordsAreDecodedFromJsonString(): void
    {
        $filter = $this->repo->create(['email' => 'q@test.com', 'keywords' => ['A', 'B', 'C']]);

        $found = $this->repo->findById($filter->id);

        $this->assertSame(['A', 'B', 'C'], $found->keywords);
    }
}
