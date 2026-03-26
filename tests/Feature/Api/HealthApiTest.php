<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\Support\ApplicationTestCase;

final class HealthApiTest extends ApplicationTestCase
{
    public function testReturns200WhenDatabaseIsAvailable(): void
    {
        $r = $this->get('/health');

        $this->assertSame(200, $r->status);
        $this->assertTrue($r->isSuccessful());
    }

    public function testResponseContainsStatusField(): void
    {
        $r = $this->get('/health');

        $this->assertSame('healthy', $r->get('data.status'));
    }

    public function testResponseContainsVersionField(): void
    {
        $r = $this->get('/health');

        $this->assertNotNull($r->get('data.version'));
    }

    public function testResponseContainsDatabaseCheck(): void
    {
        $r = $this->get('/health');

        $this->assertSame('ok', $r->get('data.checks.database'));
    }

    public function testResponseContainsStorageCheck(): void
    {
        $r = $this->get('/health');

        $this->assertContains($r->get('data.checks.storage'), ['ok', 'fail']);
    }
}
