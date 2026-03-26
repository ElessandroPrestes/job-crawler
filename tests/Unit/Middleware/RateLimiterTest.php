<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Middleware\RateLimiter;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    public function testInstantiatesWithoutError(): void
    {
        $limiter = new RateLimiter();

        $this->assertInstanceOf(RateLimiter::class, $limiter);
    }

    public function testCheckDoesNotThrowWhenApcuUnavailable(): void
    {
        if (function_exists('apcu_enabled') && apcu_enabled()) {
            $this->markTestSkipped('APCu está habilitado neste ambiente.');
        }

        $limiter = new RateLimiter();

        $this->expectOutputRegex('//');

        ob_start();
        $limiter->check('127.0.0.1', 100);
        ob_end_clean();

        $this->assertTrue(true);
    }
}
