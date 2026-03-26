<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\CrawlLogRepository;
use Tests\Support\DatabaseTestCase;

final class CrawlLogRepositoryTest extends DatabaseTestCase
{
    private CrawlLogRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new CrawlLogRepository();
    }

    public function testStartCreatesLogWithRunningStatus(): void
    {
        $id  = $this->repo->start('linkedin', 'PHP Developer', 'São Paulo');
        $log = $this->repo->findAll()[0];

        $this->assertGreaterThan(0, $id);
        $this->assertSame('running', $log->status);
        $this->assertSame('linkedin', $log->source);
        $this->assertSame('PHP Developer', $log->keyword);
        $this->assertNull($log->finishedAt);
    }

    public function testFinishUpdatesStatusAndCounters(): void
    {
        $id = $this->repo->start('indeed', 'Java', null);
        $this->repo->finish($id, 'success', 42, 8);

        $log = $this->repo->findAll()[0];

        $this->assertSame('success', $log->status);
        $this->assertSame(42, $log->jobsFound);
        $this->assertSame(8, $log->jobsNew);
        $this->assertNull($log->errorMsg);
        $this->assertNotNull($log->finishedAt);
    }

    public function testFinishWithErrorPersistsMessage(): void
    {
        $id = $this->repo->start('custom', 'Go', null);
        $this->repo->finish($id, 'failed', 0, 0, 'Connection timeout after 30s');

        $log = $this->repo->findAll()[0];

        $this->assertSame('failed', $log->status);
        $this->assertSame('Connection timeout after 30s', $log->errorMsg);
    }

    public function testFindAllWithStatusFilterReturnsOnlyMatching(): void
    {
        $a = $this->repo->start('linkedin', 'PHP', null);
        $b = $this->repo->start('indeed', 'Java', null);

        $this->repo->finish($a, 'success', 10, 2);

        $successes = $this->repo->findAll(['status' => 'success']);
        $running   = $this->repo->findAll(['status' => 'running']);

        $this->assertCount(1, $successes);
        $this->assertCount(1, $running);
    }

    public function testFindAllPaginatesCorrectly(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->repo->start('linkedin', "keyword-{$i}", null);
        }

        $page1 = $this->repo->findAll([], 1, 2);
        $page2 = $this->repo->findAll([], 2, 2);
        $page3 = $this->repo->findAll([], 3, 2);

        $this->assertCount(2, $page1);
        $this->assertCount(2, $page2);
        $this->assertCount(1, $page3);
    }

    public function testCountWithFilterMatchesExpected(): void
    {
        $a = $this->repo->start('linkedin', 'PHP', null);
        $b = $this->repo->start('indeed', 'Java', null);

        $this->repo->finish($a, 'success', 5, 1);

        $this->assertSame(2, $this->repo->count());
        $this->assertSame(1, $this->repo->count(['status' => 'success']));
        $this->assertSame(1, $this->repo->count(['source' => 'indeed']));
    }
}
