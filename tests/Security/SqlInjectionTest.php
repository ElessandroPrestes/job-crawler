<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Repositories\JobRepository;
use Tests\Support\ApplicationTestCase;

final class SqlInjectionTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedJob(['title' => 'PHP Developer', 'source' => 'linkedin']);
        $this->seedJob(['title' => 'Java Developer', 'source' => 'indeed']);
    }

    // ── keyword filter ───────────────────────────────────────────────────────

    public function testSqlInjectionInKeywordReturnsEmptyNotAllRows(): void
    {
        $r = $this->get('/api/jobs', ['keyword' => "' OR '1'='1"]);

        // Prepared statements must not leak all rows
        $this->assertSame(200, $r->status);
        $this->assertCount(0, $r->data());
    }

    public function testUnionBasedInjectionInKeywordReturnsEmpty(): void
    {
        $r = $this->get('/api/jobs', [
            'keyword' => "php' UNION SELECT 1,2,3,4,5,6,7,8,9,10,11,12,13,14 --",
        ]);

        $this->assertSame(200, $r->status);
        $this->assertCount(0, $r->data());
    }

    public function testDropTableInjectionInKeywordDoesNotBreakDatabase(): void
    {
        $r = $this->get('/api/jobs', [
            'keyword' => "'; DROP TABLE jobs; --",
        ]);

        // Table must still exist and return no matches for the injected string
        $this->assertSame(200, $r->status);

        $all = $this->get('/api/jobs');
        $this->assertSame(200, $all->status);
        $this->assertSame(2, $all->meta()['total']);
    }

    // ── location filter ──────────────────────────────────────────────────────

    public function testSqlInjectionInLocationReturnsEmpty(): void
    {
        $r = $this->get('/api/jobs', ['location' => "' OR '1'='1"]);

        $this->assertSame(200, $r->status);
        $this->assertCount(0, $r->data());
    }

    // ── path parameter ───────────────────────────────────────────────────────

    public function testNumericIdWithSqlSuffixReturns404(): void
    {
        $r = $this->get('/api/jobs/1 OR 1=1');

        // Router won't match non-integer segment — 404 expected
        $this->assertSame(404, $r->status);
    }

    // ── alert email field ────────────────────────────────────────────────────

    public function testSqlInjectionInAlertEmailIsRejectedByValidation(): void
    {
        $r = $this->post('/api/alerts', [
            'email'    => "'; DROP TABLE alert_filters; --",
            'keywords' => ['PHP'],
        ]);

        // filter_var(FILTER_VALIDATE_EMAIL) rejects this before any SQL runs
        $this->assertSame(422, $r->status);
    }
}
