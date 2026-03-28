<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\CrawlLogRepository;
use Tests\Support\DatabaseTestCase;

final class CrawlLogRepositoryExtraTest extends DatabaseTestCase
{
    private CrawlLogRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new CrawlLogRepository();
    }

    public function testFindAllWithStatusFilter(): void
    {
        $id1 = $this->repo->start('linkedin', 'PHP', null);
        $id2 = $this->repo->start('indeed', 'Node', null);

        $this->repo->finish($id1, 'success', 5, 3);
        $this->repo->finish($id2, 'failed', 0, 0, 'Network error');

        $successLogs = $this->repo->findAll(['status' => 'success']);
        $failedLogs  = $this->repo->findAll(['status' => 'failed']);

        $this->assertCount(1, $successLogs);
        $this->assertSame('success', $successLogs[0]->status);

        $this->assertCount(1, $failedLogs);
        $this->assertSame('failed', $failedLogs[0]->status);
    }

    public function testFindAllWithSourceFilter(): void
    {
        $id1 = $this->repo->start('linkedin', 'PHP', null);
        $id2 = $this->repo->start('indeed', 'Node', null);
        $this->repo->finish($id1, 'success', 5, 3);
        $this->repo->finish($id2, 'success', 3, 1);

        $linkedinLogs = $this->repo->findAll(['source' => 'linkedin']);
        $indeedLogs   = $this->repo->findAll(['source' => 'indeed']);

        $this->assertCount(1, $linkedinLogs);
        $this->assertSame('linkedin', $linkedinLogs[0]->source);

        $this->assertCount(1, $indeedLogs);
        $this->assertSame('indeed', $indeedLogs[0]->source);
    }

    public function testCountWithFilters(): void
    {
        $id1 = $this->repo->start('linkedin', 'PHP', null);
        $id2 = $this->repo->start('linkedin', 'Node', null);
        $id3 = $this->repo->start('indeed', 'PHP', null);
        $this->repo->finish($id1, 'success', 5, 3);
        $this->repo->finish($id2, 'success', 3, 1);
        $this->repo->finish($id3, 'failed', 0, 0, 'Error');

        $this->assertSame(2, $this->repo->count(['source' => 'linkedin']));
        $this->assertSame(1, $this->repo->count(['source' => 'indeed']));
        $this->assertSame(1, $this->repo->count(['status' => 'failed']));
        $this->assertSame(3, $this->repo->count());
    }
}
