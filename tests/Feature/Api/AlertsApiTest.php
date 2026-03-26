<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\Support\ApplicationTestCase;

final class AlertsApiTest extends ApplicationTestCase
{
    // ── GET /api/alerts ──────────────────────────────────────────────────────

    public function testIndexReturns200WithSuccessEnvelope(): void
    {
        $r = $this->get('/api/alerts');

        $this->assertSame(200, $r->status);
        $this->assertTrue($r->isSuccessful());
        $this->assertIsArray($r->data());
    }

    public function testIndexReturnsAllCreatedAlerts(): void
    {
        $this->seedAlert(['email' => 'a@test.com']);
        $this->seedAlert(['email' => 'b@test.com']);

        $r = $this->get('/api/alerts');

        $this->assertCount(2, $r->data());
    }

    // ── POST /api/alerts ─────────────────────────────────────────────────────

    public function testStoreReturns201WithLocationHeader(): void
    {
        $r = $this->post('/api/alerts', [
            'email'    => 'dev@example.com',
            'keywords' => ['PHP', 'Laravel'],
        ]);

        $this->assertSame(201, $r->status);
        $this->assertTrue($r->isSuccessful());
        $this->assertNotNull($r->header('Location'));
        $this->assertStringStartsWith('/api/alerts/', $r->header('Location'));
    }

    public function testStoreLocationHeaderContainsNewId(): void
    {
        $r  = $this->post('/api/alerts', [
            'email'    => 'dev@example.com',
            'keywords' => ['PHP'],
        ]);
        $id = $r->data()['id'] ?? null;

        $this->assertSame("/api/alerts/{$id}", $r->header('Location'));
    }

    public function testStorePersistsKeywordsCorrectly(): void
    {
        $this->post('/api/alerts', [
            'email'    => 'kw@test.com',
            'keywords' => ['PHP', 'Laravel', 'Docker'],
        ]);

        $r = $this->get('/api/alerts');

        $this->assertSame(['PHP', 'Laravel', 'Docker'], $r->data()[0]['keywords']);
    }

    // ── POST validation ──────────────────────────────────────────────────────

    public function testStoreReturns422ForInvalidEmail(): void
    {
        $r = $this->post('/api/alerts', [
            'email'    => 'not-an-email',
            'keywords' => ['PHP'],
        ]);

        $this->assertSame(422, $r->status);
        $this->assertFalse($r->isSuccessful());
    }

    public function testStoreReturns422ForMissingKeywords(): void
    {
        $r = $this->post('/api/alerts', ['email' => 'dev@test.com']);

        $this->assertSame(422, $r->status);
    }

    public function testStoreReturns422ForEmptyKeywordsArray(): void
    {
        $r = $this->post('/api/alerts', [
            'email'    => 'dev@test.com',
            'keywords' => [],
        ]);

        $this->assertSame(422, $r->status);
    }

    // ── DELETE /api/alerts/{id} ──────────────────────────────────────────────

    public function testDestroyReturns200WithDeletedTrue(): void
    {
        $filter = $this->seedAlert();

        $r = $this->delete("/api/alerts/{$filter->id}");

        $this->assertSame(200, $r->status);
        $this->assertTrue($r->isSuccessful());
        $this->assertTrue($r->data()['deleted']);
    }

    public function testDestroyReturns404ForUnknownId(): void
    {
        $r = $this->delete('/api/alerts/99999');

        $this->assertSame(404, $r->status);
        $this->assertFalse($r->isSuccessful());
    }

    public function testDestroyActuallyRemovesTheFilter(): void
    {
        $filter = $this->seedAlert();
        $this->delete("/api/alerts/{$filter->id}");

        $r = $this->get('/api/alerts');

        $this->assertCount(0, $r->data());
    }
}
