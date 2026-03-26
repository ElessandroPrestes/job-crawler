<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\Support\ApplicationTestCase;

final class JobsApiTest extends ApplicationTestCase
{
    // ── Contract: envelope ───────────────────────────────────────────────────

    public function testIndexReturns200WithSuccessEnvelope(): void
    {
        $r = $this->get('/api/jobs');

        $this->assertSame(200, $r->status);
        $this->assertTrue($r->isSuccessful());
        $this->assertIsArray($r->data());
    }

    public function testIndexIncludesPaginationMetaFields(): void
    {
        $r    = $this->get('/api/jobs');
        $meta = $r->meta();

        $this->assertNotNull($meta);
        $this->assertArrayHasKey('total', $meta);
        $this->assertArrayHasKey('page', $meta);
        $this->assertArrayHasKey('per_page', $meta);
        $this->assertArrayHasKey('last_page', $meta);
    }

    public function testIndexMetaReflectsActualCount(): void
    {
        $this->seedJob(['title' => 'Job A']);
        $this->seedJob(['title' => 'Job B']);

        $r = $this->get('/api/jobs');

        $this->assertSame(2, $r->meta()['total']);
    }

    // ── Pagination ───────────────────────────────────────────────────────────

    public function testIndexRespectsPerPageParam(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->seedJob(['title' => "Job {$i}"]);
        }

        $r = $this->get('/api/jobs', ['per_page' => 2]);

        $this->assertCount(2, $r->data());
        $this->assertSame(5, $r->meta()['total']);
        $this->assertSame(3, $r->meta()['last_page']);
    }

    public function testIndexPage2ReturnsDifferentItems(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->seedJob(['title' => "Job {$i}"]);
        }

        $page1 = $this->get('/api/jobs', ['per_page' => 2, 'page' => 1]);
        $page2 = $this->get('/api/jobs', ['per_page' => 2, 'page' => 2]);

        $ids1 = array_column($page1->data(), 'id');
        $ids2 = array_column($page2->data(), 'id');

        $this->assertEmpty(array_intersect($ids1, $ids2));
    }

    // ── Filters ──────────────────────────────────────────────────────────────

    public function testIndexFilterByKeywordReturnsOnlyMatching(): void
    {
        $this->seedJob(['title' => 'PHP Developer']);
        $this->seedJob(['title' => 'Java Developer']);

        $r = $this->get('/api/jobs', ['keyword' => 'PHP']);

        $this->assertCount(1, $r->data());
        $this->assertSame('PHP Developer', $r->data()[0]['title']);
    }

    public function testIndexFilterBySourceReturnsOnlyMatching(): void
    {
        $this->seedJob(['source' => 'linkedin']);
        $this->seedJob(['source' => 'indeed']);

        $r = $this->get('/api/jobs', ['source' => 'indeed']);

        $this->assertCount(1, $r->data());
        $this->assertSame('indeed', $r->data()[0]['source']);
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function testShowReturns200ForExistingJob(): void
    {
        $id = $this->seedJob(['title' => 'Backend Engineer']);

        $r = $this->get("/api/jobs/{$id}");

        $this->assertSame(200, $r->status);
        $this->assertTrue($r->isSuccessful());
        $this->assertSame('Backend Engineer', $r->data()['title']);
    }

    public function testShowReturns404ForUnknownId(): void
    {
        $r = $this->get('/api/jobs/99999');

        $this->assertSame(404, $r->status);
        $this->assertFalse($r->isSuccessful());
        $this->assertNotNull($r->error());
    }

    public function testShowResponseContainsExpectedFields(): void
    {
        $id = $this->seedJob();
        $r  = $this->get("/api/jobs/{$id}");
        $d  = $r->data();

        foreach (['id', 'title', 'company', 'source', 'url', 'scraped_at', 'is_notified'] as $field) {
            $this->assertArrayHasKey($field, $d, "Missing field: {$field}");
        }
    }
}
