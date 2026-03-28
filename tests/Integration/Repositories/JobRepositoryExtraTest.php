<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Repositories\JobRepository;
use Tests\Support\DatabaseTestCase;

final class JobRepositoryExtraTest extends DatabaseTestCase
{
    private JobRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new JobRepository();
    }

    private function insertJob(string $externalId, string $source, string $title, string $company,
                               string $location = 'Brasil', string $contractType = 'PJ',
                               string $url = 'http://example.com', bool $notified = false): void
    {
        $this->pdo->exec("
            INSERT INTO jobs (external_id, source, title, company, location, contract_type, url, scraped_at, is_notified)
            VALUES ('{$externalId}', '{$source}', '{$title}', '{$company}', '{$location}', '{$contractType}', '{$url}', datetime('now'), " . ($notified ? 1 : 0) . ")
        ");
    }

    public function testFindAllWithKeywordFilter(): void
    {
        $this->insertJob('ext-1', 'linkedin', 'PHP Developer', 'Tech Corp');
        $this->insertJob('ext-2', 'linkedin', 'Java Developer', 'Corp B');

        $results = $this->repo->findAll(['keyword' => 'PHP']);

        $this->assertCount(1, $results);
        $this->assertSame('PHP Developer', $results[0]->title);
    }

    public function testFindAllWithLocationFilter(): void
    {
        $this->insertJob('ext-1', 'linkedin', 'PHP Dev', 'Corp A', 'São Paulo');
        $this->insertJob('ext-2', 'linkedin', 'PHP Dev', 'Corp B', 'Rio de Janeiro');

        $results = $this->repo->findAll(['location' => 'São Paulo']);

        $this->assertCount(1, $results);
    }

    public function testFindAllWithContractTypeFilter(): void
    {
        $this->insertJob('ext-1', 'linkedin', 'PHP Dev', 'Corp A', 'Brasil', 'CLT');
        $this->insertJob('ext-2', 'linkedin', 'PHP Dev', 'Corp B', 'Brasil', 'PJ');

        $results = $this->repo->findAll(['contract_type' => 'CLT']);

        $this->assertCount(1, $results);
        $this->assertSame('CLT', $results[0]->contractType);
    }

    public function testFindAllWithSinceFilter(): void
    {
        $this->insertJob('ext-1', 'linkedin', 'PHP Dev', 'Corp A');

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $results = $this->repo->findAll(['since' => $yesterday]);

        $this->assertCount(1, $results);
    }

    public function testCountWithSourceFilter(): void
    {
        $this->insertJob('ext-1', 'linkedin', 'PHP Dev', 'Corp A');
        $this->insertJob('ext-2', 'indeed', 'PHP Dev', 'Corp B');
        $this->insertJob('ext-3', 'linkedin', 'Node Dev', 'Corp C');

        $this->assertSame(2, $this->repo->count(['source' => 'linkedin']));
        $this->assertSame(1, $this->repo->count(['source' => 'indeed']));
    }

    public function testMarkNotifiedUpdatesRecords(): void
    {
        $this->insertJob('ext-1', 'linkedin', 'PHP Dev', 'Corp A');
        $this->insertJob('ext-2', 'linkedin', 'Node Dev', 'Corp B');

        $jobs = $this->repo->findUnnotified();
        $this->assertCount(2, $jobs);

        $ids = array_map(fn($j) => $j->id, $jobs);
        $this->repo->markNotified($ids);

        $unnotified = $this->repo->findUnnotified();
        $this->assertCount(0, $unnotified);
    }

    public function testMarkNotifiedWithEmptyArrayDoesNothing(): void
    {
        $this->insertJob('ext-1', 'linkedin', 'PHP Dev', 'Corp');

        // Chamar com array vazio não deve alterar nada
        $this->repo->markNotified([]);

        $unnotified = $this->repo->findUnnotified();
        $this->assertCount(1, $unnotified);
    }
}
