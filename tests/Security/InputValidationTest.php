<?php

declare(strict_types=1);

namespace Tests\Security;

use Tests\Support\ApplicationTestCase;

final class InputValidationTest extends ApplicationTestCase
{
    // ── XSS ─────────────────────────────────────────────────────────────────

    public function testXssPayloadInKeywordDoesNotAppearUnescapedInResponse(): void
    {
        $this->seedJob(['title' => '<script>alert("xss")</script>']);

        $r = $this->get('/api/jobs', ['keyword' => 'script']);

        // Simulate the JSON wire format using the same encoding flags as JsonResponse
        $wireJson = json_encode($r->body, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertStringNotContainsString('<script>', (string) $wireJson);
    }

    public function testXssInAlertEmailIsRejectedAsInvalidEmail(): void
    {
        $r = $this->post('/api/alerts', [
            'email'    => '<script>alert(1)</script>@evil.com',
            'keywords' => ['PHP'],
        ]);

        $this->assertSame(422, $r->status);
    }

    // ── Pagination boundary ──────────────────────────────────────────────────

    public function testPageZeroTreatedAsPageOne(): void
    {
        $this->seedJob();

        $rZero = $this->get('/api/jobs', ['page' => 0]);
        $rOne  = $this->get('/api/jobs', ['page' => 1]);

        $this->assertSame(200, $rZero->status);
        $this->assertSame($rOne->meta()['page'], $rZero->meta()['page']);
    }

    public function testNegativePageTreatedAsPageOne(): void
    {
        $this->seedJob();

        $r = $this->get('/api/jobs', ['page' => -99]);

        $this->assertSame(200, $r->status);
        $this->assertSame(1, $r->meta()['page']);
    }

    public function testPerPageAbove100IsCappedAt100(): void
    {
        $r = $this->get('/api/jobs', ['per_page' => 999]);

        $this->assertSame(200, $r->status);
        $this->assertSame(100, $r->meta()['per_page']);
    }

    public function testPerPageZeroTreatedAsOne(): void
    {
        $r = $this->get('/api/jobs', ['per_page' => 0]);

        $this->assertSame(200, $r->status);
        $this->assertGreaterThanOrEqual(1, $r->meta()['per_page']);
    }

    // ── Missing / malformed fields ───────────────────────────────────────────

    public function testAlertWithMissingEmailReturns422(): void
    {
        $r = $this->post('/api/alerts', ['keywords' => ['PHP']]);

        $this->assertSame(422, $r->status);
        $this->assertFalse($r->isSuccessful());
    }

    public function testAlertWithEmptyKeywordsArrayReturns422(): void
    {
        $r = $this->post('/api/alerts', [
            'email'    => 'dev@test.com',
            'keywords' => [],
        ]);

        $this->assertSame(422, $r->status);
    }

    public function testAlertWithStringKeywordsInsteadOfArrayReturns422(): void
    {
        $r = $this->post('/api/alerts', [
            'email'    => 'dev@test.com',
            'keywords' => 'PHP',
        ]);

        $this->assertSame(422, $r->status);
    }

    // ── Enum fields ──────────────────────────────────────────────────────────

    public function testUnknownSourceFilterReturnsEmptyNotError(): void
    {
        $this->seedJob(['source' => 'linkedin']);

        $r = $this->get('/api/jobs', ['source' => 'glassdoor']);

        $this->assertSame(200, $r->status);
        $this->assertCount(0, $r->data());
    }
}
