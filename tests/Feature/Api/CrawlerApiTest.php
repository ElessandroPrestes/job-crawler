<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\Support\ApplicationTestCase;

final class CrawlerApiTest extends ApplicationTestCase
{
    // ── GET /api/crawl/logs ──────────────────────────────────────────────────

    public function testLogsReturnsEmptyPaginatedList(): void
    {
        $r = $this->get('/api/crawl/logs');

        $this->assertSame(200, $r->status);
        $this->assertTrue($r->isSuccessful());
        $this->assertIsArray($r->data());
        $this->assertEmpty($r->data());
    }

    public function testLogsReturnsPaginationMeta(): void
    {
        $r = $this->get('/api/crawl/logs');

        $meta = $r->meta();

        $this->assertNotNull($meta);
        $this->assertArrayHasKey('total', $meta);
        $this->assertArrayHasKey('page', $meta);
        $this->assertArrayHasKey('per_page', $meta);
        $this->assertArrayHasKey('last_page', $meta);
        $this->assertSame(0, $meta['total']);
        $this->assertSame(1, $meta['page']);
        $this->assertSame(1, $meta['last_page']);
    }

    // ── POST /api/crawl ──────────────────────────────────────────────────────

    public function testRunReturns422ForInvalidSource(): void
    {
        $r = $this->post('/api/crawl', [
            'source'  => 'invalid_source',
            'keyword' => 'PHP Developer',
        ]);

        $this->assertSame(422, $r->status);
        $this->assertFalse($r->isSuccessful());
    }

    public function testRunReturns422ForShortKeyword(): void
    {
        $r = $this->post('/api/crawl', [
            'source'  => 'linkedin',
            'keyword' => 'P',
        ]);

        $this->assertSame(422, $r->status);
        $this->assertFalse($r->isSuccessful());
    }

    public function testRunReturns422ForDisallowedUrl(): void
    {
        $r = $this->post('/api/crawl', [
            'source'  => 'custom',
            'keyword' => 'PHP Developer',
            'url'     => 'http://evil.example.com/jobs',
        ]);

        $this->assertSame(422, $r->status);
        $this->assertFalse($r->isSuccessful());
    }
}
