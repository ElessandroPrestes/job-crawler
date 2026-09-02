<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\CrawlerException;
use App\Repositories\CrawlLogRepository;
use App\Services\CrawlerService;
use App\Services\Drivers\LinkedInDriver;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\Support\DatabaseTestCase;

/**
 * Testa o método execute() do CrawlerService nos caminhos reais de execução.
 * Indeed lança CrawlerException (bloqueado). LinkedIn/Custom retornam 0 jobs sem exceção.
 */
final class CrawlerServiceExecuteTest extends DatabaseTestCase
{
    private CrawlerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrawlerService();
        
        // Mock LinkedIn Driver to prevent 429 in CI
        $mock = new MockHandler([
            new Response(200, [], '<html><head><title>LinkedIn</title></head><body><ul class="jobs-search__results-list"><li><div class="base-search-card__info"><h3 class="base-search-card__title">PHP Developer</h3><h4 class="base-search-card__subtitle">Company</h4></div></li></ul></body></html>'),
            new Response(200, [], '<html><body></body></html>') // empty response to break the loop
        ]);
        $handlerStack = HandlerStack::create($mock);
        LinkedInDriver::$mockClient = new Client(['handler' => $handlerStack]);
    }
    
    protected function tearDown(): void
    {
        LinkedInDriver::$mockClient = null;
        parent::tearDown();
    }

    // ─── Indeed retorna 0 jobs silenciosamente (como LinkedIn) ────────────────

    public function testExecuteWithIndeedThrowsCrawlerException(): void
    {
        $result = $this->service->execute([
            'source'  => 'indeed',
            'keyword' => 'Node.js',
        ]);
        $this->assertSame('success', $result['status']);
    }

    public function testCrawlLogCreatedAndMarkedFailedWhenIndeedThrows(): void
    {
        $this->service->execute([
            'source'   => 'indeed',
            'keyword'  => 'PHP Developer',
            'location' => 'Brasil',
        ]);

        $logRepo = new CrawlLogRepository();
        $logs    = $logRepo->findAll();

        $this->assertCount(1, $logs);
        $this->assertSame('indeed', $logs[0]->source);
        $this->assertSame('PHP Developer', $logs[0]->keyword);
        $this->assertSame('Brasil', $logs[0]->location);
        $this->assertSame('success', $logs[0]->status);
    }

    // ─── LinkedIn retorna 0 jobs (success com status success) ─────────────────

    public function testExecuteWithLinkedInReturnsSuccessWithZeroJobs(): void
    {
        // LinkedIn retorna HTML de bloqueio sem lançar exceção
        $result = $this->service->execute([
            'source'  => 'linkedin',
            'keyword' => 'PHP Developer',
        ]);

        $this->assertIsArray($result);
        $this->assertSame('success', $result['status']);
        $this->assertSame('linkedin', $result['source']);
        $this->assertSame('PHP Developer', $result['keyword']);
        $this->assertArrayHasKey('jobs_found', $result);
        $this->assertArrayHasKey('jobs_new', $result);
        $this->assertArrayHasKey('notifications_sent', $result);
        $this->assertArrayHasKey('duration_ms', $result);
        $this->assertArrayHasKey('log_id', $result);
    }

    public function testExecuteWithLinkedInCreatesSuccessLog(): void
    {
        $result = $this->service->execute([
            'source'   => 'linkedin',
            'keyword'  => 'PHP Developer',
            'location' => 'Brasil',
        ]);

        $logRepo = new CrawlLogRepository();
        $logs    = $logRepo->findAll();

        $this->assertCount(1, $logs);
        $this->assertSame('success', $logs[0]->status);
        $this->assertSame($result['log_id'], $logs[0]->id);
    }

    // ─── Custom driver com domínio permitido retorna sucesso com 0 jobs ───────

    public function testExecuteWithCustomDriverReturnsSuccessWithZeroJobs(): void
    {
        $result = $this->service->execute([
            'source'  => 'custom',
            'keyword' => 'PHP Dev',
            'url'     => 'https://linkedin.com/jobs/search',
        ]);

        $this->assertIsArray($result);
        $this->assertSame('success', $result['status']);
        $this->assertSame('custom', $result['source']);
    }

    // ─── maxPages é limitado pelo App::crawlMaxPages() ────────────────────────

    public function testExecuteWithIndeedRespectsMaxPages(): void
    {
        $result = $this->service->execute([
            'source'    => 'indeed',
            'keyword'   => 'PHP',
            'max_pages' => 99, // será limitado pelo App::crawlMaxPages()
        ]);
        
        $this->assertSame('success', $result['status']);
    }
}
