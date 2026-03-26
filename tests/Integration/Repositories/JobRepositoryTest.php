<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Config\Database;
use App\Repositories\JobRepository;
use PHPUnit\Framework\TestCase;

final class JobRepositoryTest extends TestCase
{
    private JobRepository $repo;

    protected function setUp(): void
    {
        Database::reset();
        $pdo = Database::connection();
        $this->migrate($pdo);
        $this->repo = new JobRepository();
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    private function migrate(\PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS jobs (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                external_id   TEXT    NOT NULL,
                source        TEXT    NOT NULL,
                title         TEXT    NOT NULL,
                company       TEXT    NOT NULL,
                location      TEXT,
                contract_type TEXT,
                description   TEXT,
                requirements  TEXT,
                salary_range  TEXT,
                url           TEXT    NOT NULL DEFAULT \'\',
                published_at  TEXT,
                scraped_at    TEXT    NOT NULL DEFAULT (datetime(\'now\')),
                is_notified   INTEGER NOT NULL DEFAULT 0,
                UNIQUE (source, external_id)
            )
        ');
    }

    private function insertJob(array $overrides = []): int
    {
        return $this->repo->upsert(array_merge([
            'external_id' => 'ext-' . uniqid(),
            'source'      => 'linkedin',
            'title'       => 'PHP Developer',
            'company'     => 'Tech Corp',
            'location'    => 'São Paulo',
            'url'         => 'https://linkedin.com/jobs/1',
        ], $overrides));
    }

    public function testUpsertInsertsNewJob(): void
    {
        $id = $this->insertJob();

        $this->assertGreaterThan(0, $id);
    }

    public function testFindByIdReturnsJob(): void
    {
        $id  = $this->insertJob(['title' => 'Unique Title']);
        $job = $this->repo->findById($id);

        $this->assertNotNull($job);
        $this->assertSame('Unique Title', $job->title);
    }

    public function testFindByIdReturnsNullForMissingJob(): void
    {
        $job = $this->repo->findById(99999);

        $this->assertNull($job);
    }

    public function testFindAllReturnsPaginatedResults(): void
    {
        $this->insertJob(['title' => 'Job A']);
        $this->insertJob(['title' => 'Job B']);
        $this->insertJob(['title' => 'Job C']);

        $page1 = $this->repo->findAll([], 1, 2);
        $page2 = $this->repo->findAll([], 2, 2);

        $this->assertCount(2, $page1);
        $this->assertCount(1, $page2);
    }

    public function testCountReturnsTotal(): void
    {
        $this->insertJob();
        $this->insertJob();

        $this->assertSame(2, $this->repo->count());
    }

    public function testFindAllWithKeywordFilter(): void
    {
        $this->insertJob(['title' => 'PHP Developer']);
        $this->insertJob(['title' => 'Java Developer']);

        $results = $this->repo->findAll(['keyword' => 'PHP']);

        $this->assertCount(1, $results);
        $this->assertSame('PHP Developer', $results[0]->title);
    }

    public function testFindUnnotifiedReturnsOnlyUnnotified(): void
    {
        $idA = $this->insertJob(['title' => 'Job A']);
        $idB = $this->insertJob(['title' => 'Job B']);

        $this->repo->markNotified([$idA]);

        $unnotified = $this->repo->findUnnotified();

        $this->assertCount(1, $unnotified);
        $this->assertSame($idB, $unnotified[0]->id);
    }

    public function testMarkNotifiedUpdatesFlag(): void
    {
        $id  = $this->insertJob();
        $this->repo->markNotified([$id]);
        $job = $this->repo->findById($id);

        $this->assertTrue($job->isNotified);
    }
}
