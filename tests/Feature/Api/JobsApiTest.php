<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Config\Database;
use App\Repositories\JobRepository;
use PHPUnit\Framework\TestCase;

final class JobsApiTest extends TestCase
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

    private function seed(): void
    {
        $this->repo->upsert([
            'external_id'   => 'ext-001',
            'source'        => 'linkedin',
            'title'         => 'PHP Developer',
            'company'       => 'Tech Corp',
            'location'      => 'São Paulo',
            'contract_type' => 'PJ',
            'url'           => 'https://linkedin.com/jobs/1',
        ]);

        $this->repo->upsert([
            'external_id'   => 'ext-002',
            'source'        => 'indeed',
            'title'         => 'Java Developer',
            'company'       => 'Other Corp',
            'location'      => 'Rio de Janeiro',
            'contract_type' => 'CLT',
            'url'           => 'https://indeed.com/jobs/2',
        ]);
    }

    public function testFindAllReturnsAllJobs(): void
    {
        $this->seed();

        $jobs = $this->repo->findAll();

        $this->assertCount(2, $jobs);
    }

    public function testCountReturnsCorrectTotal(): void
    {
        $this->seed();

        $this->assertSame(2, $this->repo->count());
    }

    public function testFilterBySource(): void
    {
        $this->seed();

        $jobs = $this->repo->findAll(['source' => 'linkedin']);

        $this->assertCount(1, $jobs);
        $this->assertSame('linkedin', $jobs[0]->source);
    }

    public function testFilterByContractType(): void
    {
        $this->seed();

        $jobs = $this->repo->findAll(['contract_type' => 'PJ']);

        $this->assertCount(1, $jobs);
        $this->assertSame('PJ', $jobs[0]->contractType);
    }

    public function testFindByIdReturnsCorrectJob(): void
    {
        $this->seed();

        $all = $this->repo->findAll();
        $job = $this->repo->findById($all[0]->id);

        $this->assertNotNull($job);
        $this->assertSame($all[0]->id, $job->id);
    }

    public function testPaginationReturnsCorrectPage(): void
    {
        $this->seed();

        $page1 = $this->repo->findAll([], 1, 1);
        $page2 = $this->repo->findAll([], 2, 1);

        $this->assertCount(1, $page1);
        $this->assertCount(1, $page2);
        $this->assertNotSame($page1[0]->id, $page2[0]->id);
    }
}
