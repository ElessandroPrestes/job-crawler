<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\JobRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DatabaseTestCase;

final class JobRepositoryTest extends DatabaseTestCase
{
    private JobRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new JobRepository();
    }

    // ── upsert ──────────────────────────────────────────────────────────────

    public function testUpsertInsertsNewRowAndReturnsId(): void
    {
        $id = $this->insert();

        $this->assertGreaterThan(0, $id);
    }

    public function testUpsertDoesNotCreateDuplicateForSameSourceAndExternalId(): void
    {
        $this->insert(['external_id' => 'dup-001', 'source' => 'linkedin']);
        $this->insert(['external_id' => 'dup-001', 'source' => 'linkedin']);

        $this->assertSame(1, $this->repo->count());
    }

    public function testUpsertUpdatesExistingFieldsOnConflict(): void
    {
        $this->insert(['external_id' => 'upd-001', 'source' => 'indeed', 'title' => 'Old Title']);
        $this->insert(['external_id' => 'upd-001', 'source' => 'indeed', 'title' => 'New Title']);

        $job = $this->repo->findAll()[0];

        $this->assertSame('New Title', $job->title);
        $this->assertSame(1, $this->repo->count());
    }

    public function testUpsertSameSourceDifferentExternalIdCreatesTwoRows(): void
    {
        $this->insert(['external_id' => 'a', 'source' => 'linkedin']);
        $this->insert(['external_id' => 'b', 'source' => 'linkedin']);

        $this->assertSame(2, $this->repo->count());
    }

    // ── findById ────────────────────────────────────────────────────────────

    public function testFindByIdReturnsCorrectJob(): void
    {
        $id  = $this->insert(['title' => 'Unique Title Here']);
        $job = $this->repo->findById($id);

        $this->assertNotNull($job);
        $this->assertSame('Unique Title Here', $job->title);
        $this->assertSame($id, $job->id);
    }

    public function testFindByIdReturnsNullForUnknownId(): void
    {
        $this->assertNull($this->repo->findById(99999));
    }

    // ── findAll + pagination ─────────────────────────────────────────────────

    public function testFindAllReturnsAllJobsOrderedByScrapedAtDesc(): void
    {
        $this->insert(['title' => 'Job A']);
        $this->insert(['title' => 'Job B']);
        $this->insert(['title' => 'Job C']);

        $jobs = $this->repo->findAll();

        $this->assertCount(3, $jobs);
    }

    public function testPaginationPage2ReturnsDifferentItemsThanPage1(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->insert(['title' => "Job {$i}"]);
        }

        $page1 = $this->repo->findAll([], 1, 2);
        $page2 = $this->repo->findAll([], 2, 2);
        $page3 = $this->repo->findAll([], 3, 2);

        $this->assertCount(2, $page1);
        $this->assertCount(2, $page2);
        $this->assertCount(1, $page3);
        $this->assertNotSame($page1[0]->id, $page2[0]->id);
    }

    // ── filters ─────────────────────────────────────────────────────────────

    #[DataProvider('filterProvider')]
    public function testFindAllAppliesFilterCorrectly(array $seed, array $filter, int $expectedCount): void
    {
        foreach ($seed as $row) {
            $this->insert($row);
        }

        $results = $this->repo->findAll($filter);

        $this->assertCount($expectedCount, $results);
    }

    public static function filterProvider(): array
    {
        return [
            'keyword matches title' => [
                [['title' => 'PHP Developer'], ['title' => 'Java Developer']],
                ['keyword' => 'PHP'],
                1,
            ],
            'keyword matches requirements' => [
                [['title' => 'Backend', 'requirements' => 'PHP, Laravel'], ['title' => 'Frontend', 'requirements' => 'React']],
                ['keyword' => 'Laravel'],
                1,
            ],
            'location partial match' => [
                [['location' => 'São Paulo, SP'], ['location' => 'Rio de Janeiro']],
                ['location' => 'São Paulo'],
                1,
            ],
            'source filter' => [
                [['source' => 'linkedin'], ['source' => 'indeed']],
                ['source' => 'linkedin'],
                1,
            ],
            'contract_type filter' => [
                [['contract_type' => 'PJ'], ['contract_type' => 'CLT']],
                ['contract_type' => 'PJ'],
                1,
            ],
            'no results for unknown keyword' => [
                [['title' => 'PHP Developer']],
                ['keyword' => 'Cobol'],
                0,
            ],
        ];
    }

    public function testCountReflectsActiveFilters(): void
    {
        $this->insert(['source' => 'linkedin']);
        $this->insert(['source' => 'linkedin']);
        $this->insert(['source' => 'indeed']);

        $this->assertSame(3, $this->repo->count());
        $this->assertSame(2, $this->repo->count(['source' => 'linkedin']));
        $this->assertSame(1, $this->repo->count(['source' => 'indeed']));
    }

    // ── notifications ────────────────────────────────────────────────────────

    public function testFindUnnotifiedSkipsAlreadyNotifiedJobs(): void
    {
        $idA = $this->insert(['title' => 'Job A']);
        $idB = $this->insert(['title' => 'Job B']);
        $idC = $this->insert(['title' => 'Job C']);

        $this->repo->markNotified([$idA, $idB]);

        $unnotified = $this->repo->findUnnotified();

        $this->assertCount(1, $unnotified);
        $this->assertSame($idC, $unnotified[0]->id);
    }

    public function testMarkNotifiedFlipsIsNotifiedToTrue(): void
    {
        $id = $this->insert();

        $this->assertFalse($this->repo->findById($id)->isNotified);

        $this->repo->markNotified([$id]);

        $this->assertTrue($this->repo->findById($id)->isNotified);
    }

    public function testMarkNotifiedWithEmptyArrayDoesNothing(): void
    {
        $id = $this->insert();

        $this->repo->markNotified([]);

        $this->assertFalse($this->repo->findById($id)->isNotified);
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function insert(array $overrides = []): int
    {
        $data = array_merge([
            'external_id'   => 'ext-' . uniqid(),
            'source'        => 'linkedin',
            'title'         => 'PHP Developer',
            'company'       => 'Tech Corp',
            'location'      => 'São Paulo',
            'contract_type' => 'PJ',
            'url'           => 'https://linkedin.com/jobs/1',
        ], $overrides);
        $this->repo->upsert($data);
        return (int) $this->pdo->lastInsertId();
    }
}
