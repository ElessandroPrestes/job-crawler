<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\CrawlerException;
use App\Repositories\CrawlLogRepository;
use App\Services\CrawlerService;
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
    }

    // ─── Indeed lança CrawlerException ────────────────────────────────────────

    public function testExecuteWithIndeedThrowsCrawlerException(): void
    {
        $this->expectException(CrawlerException::class);
        $this->expectExceptionMessageMatches('/Indeed fetch falhou/');

        $this->service->execute([
            'source'  => 'indeed',
            'keyword' => 'Node.js',
        ]);
    }

    public function testCrawlLogCreatedAndMarkedFailedWhenIndeedThrows(): void
    {
        try {
            $this->service->execute([
                'source'   => 'indeed',
                'keyword'  => 'PHP Developer',
                'location' => 'Brasil',
            ]);
        } catch (CrawlerException) {
            // esperado
        }

        $logRepo = new CrawlLogRepository();
        $logs    = $logRepo->findAll();

        $this->assertCount(1, $logs);
        $this->assertSame('indeed', $logs[0]->source);
        $this->assertSame('PHP Developer', $logs[0]->keyword);
        $this->assertSame('Brasil', $logs[0]->location);
        $this->assertSame('failed', $logs[0]->status);
        $this->assertNotNull($logs[0]->errorMsg);
        $this->assertNotNull($logs[0]->finishedAt);
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
        $this->expectException(CrawlerException::class);

        $this->service->execute([
            'source'    => 'indeed',
            'keyword'   => 'PHP',
            'max_pages' => 99, // será limitado pelo App::crawlMaxPages()
        ]);
    }
}
