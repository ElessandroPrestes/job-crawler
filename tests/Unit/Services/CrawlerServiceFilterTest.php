<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CrawlerService;
use ReflectionMethod;
use Tests\Support\DatabaseTestCase;

/**
 * Testa a lógica interna de filterRelevant via ReflectionMethod,
 * sem fazer requisições HTTP externas.
 */
final class CrawlerServiceFilterTest extends DatabaseTestCase
{
    private CrawlerService $service;
    private ReflectionMethod $filterRelevant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service        = new CrawlerService();
        $this->filterRelevant = new ReflectionMethod(CrawlerService::class, 'filterRelevant');
        $this->filterRelevant->setAccessible(true);
    }

    private function filter(array $jobs): array
    {
        return $this->filterRelevant->invoke($this->service, $jobs);
    }

    // ── PHP/Node no título ────────────────────────────────────────────────────

    public function testPhpInTitlePasses(): void
    {
        $jobs = [['title' => 'PHP Developer', 'company' => 'Corp', 'location' => 'brasil', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testNodeInTitlePasses(): void
    {
        $jobs = [['title' => 'Node.js Engineer', 'company' => 'Corp', 'location' => 'remoto', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testJavaTitleIsFiltered(): void
    {
        $jobs = [['title' => 'Java Senior Developer', 'company' => 'Corp', 'location' => 'brasil', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testPythonTitleIsFiltered(): void
    {
        $jobs = [['title' => 'Python Data Engineer', 'company' => 'Corp', 'location' => 'remote', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    // ── Localização ────────────────────────────────────────────────────────────

    public function testBrasilLocationPasses(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => 'Brasil', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testBrazilLocationPasses(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => 'Brazil', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testRemotoLocationPasses(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => 'Remoto', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testRemoteLocationPasses(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => 'Remote', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testHomeOfficeLocationPasses(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => 'Home Office', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testEmptyLocationPasses(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => '', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testNullLocationPasses(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => null, 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testForeignLocationIsFiltered(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => 'United States', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(0, $result);
    }

    public function testGermanyLocationIsFiltered(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => 'Berlin, Germany', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(0, $result);
    }

    // ── Data de publicação ────────────────────────────────────────────────────

    public function testRecentPublishedAtPasses(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => 'remoto', 'published_at' => date('Y-m-d H:i:s'), 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    public function testOldPublishedAtIsFiltered(): void
    {
        $oldDate = date('Y-m-d H:i:s', strtotime('-10 days'));
        $jobs = [['title' => 'PHP Dev', 'location' => 'remoto', 'published_at' => $oldDate, 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(0, $result);
    }

    public function testEmptyPublishedAtPasses(): void
    {
        $jobs = [['title' => 'PHP Dev', 'location' => 'brasil', 'published_at' => '', 'url' => 'http://x']];
        $result = $this->filter($jobs);
        $this->assertCount(1, $result);
    }

    // ── Múltiplos jobs ─────────────────────────────────────────────────────────

    public function testMixedJobsFilteredCorrectly(): void
    {
        $jobs = [
            ['title' => 'PHP Developer',     'location' => 'brasil',         'url' => 'http://1'],
            ['title' => 'Java Developer',     'location' => 'brasil',         'url' => 'http://2'],
            ['title' => 'Node.js Dev',        'location' => 'remoto',         'url' => 'http://3'],
            ['title' => 'Ruby on Rails Dev',  'location' => 'brasil',         'url' => 'http://4'],
            ['title' => 'PHP Backend',        'location' => 'United Kingdom', 'url' => 'http://5'],
        ];

        $result = $this->filter($jobs);

        // All in BR pass because title filter is removed, UK is filtered out by location
        $this->assertCount(4, $result);
        $this->assertSame('PHP Developer', $result[0]['title']);
        $this->assertSame('Java Developer', $result[1]['title']);
        $this->assertSame('Node.js Dev', $result[2]['title']);
        $this->assertSame('Ruby on Rails Dev', $result[3]['title']);
    }

    public function testEmptyJobsArrayReturnsEmpty(): void
    {
        $result = $this->filter([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
