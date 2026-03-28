<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Config\App;
use PHPUnit\Framework\TestCase;

final class AppConfigTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        // Salva env vars relevantes
        foreach (['APP_ENV', 'APP_DEBUG', 'APP_VERSION', 'LOG_PATH', 'LOG_LEVEL',
                  'EXPORT_PATH', 'CRAWL_DELAY_MS', 'CRAWL_MAX_PAGES', 'CRAWL_ALLOWED_DOMAINS'] as $key) {
            $this->originalEnv[$key] = $_ENV[$key] ?? null;
        }
    }

    protected function tearDown(): void
    {
        // Restaura env vars
        foreach ($this->originalEnv as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
        parent::tearDown();
    }

    public function testEnvReturnsDefaultProduction(): void
    {
        unset($_ENV['APP_ENV']);
        $this->assertSame('production', App::env());
    }

    public function testEnvReturnsCustomValue(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $this->assertSame('testing', App::env());
    }

    public function testDebugReturnsFalseByDefault(): void
    {
        unset($_ENV['APP_DEBUG']);
        $this->assertFalse(App::debug());
    }

    public function testDebugReturnsTrueWhenSet(): void
    {
        $_ENV['APP_DEBUG'] = 'true';
        $this->assertTrue(App::debug());
    }

    public function testVersionReturnsDefault(): void
    {
        unset($_ENV['APP_VERSION']);
        $this->assertSame('1.0.0', App::version());
    }

    public function testVersionReturnsCustomValue(): void
    {
        $_ENV['APP_VERSION'] = '2.5.0';
        $this->assertSame('2.5.0', App::version());
    }

    public function testLogPathReturnsDefault(): void
    {
        unset($_ENV['LOG_PATH']);
        $this->assertSame('/var/www/html/storage/logs/app.log', App::logPath());
    }

    public function testLogLevelReturnsDefault(): void
    {
        unset($_ENV['LOG_LEVEL']);
        $this->assertSame('info', App::logLevel());
    }

    public function testLogLevelReturnsCustomValue(): void
    {
        $_ENV['LOG_LEVEL'] = 'debug';
        $this->assertSame('debug', App::logLevel());
    }

    public function testExportPathReturnsDefault(): void
    {
        unset($_ENV['EXPORT_PATH']);
        $this->assertSame('/var/www/html/storage/exports', App::exportPath());
    }

    public function testCrawlDelayMsReturnsDefault(): void
    {
        unset($_ENV['CRAWL_DELAY_MS']);
        $this->assertSame(1500, App::crawlDelayMs());
    }

    public function testCrawlDelayMsReturnsCustomValue(): void
    {
        $_ENV['CRAWL_DELAY_MS'] = '2000';
        $this->assertSame(2000, App::crawlDelayMs());
    }

    public function testCrawlMaxPagesReturnsDefault(): void
    {
        unset($_ENV['CRAWL_MAX_PAGES']);
        $this->assertSame(5, App::crawlMaxPages());
    }

    public function testCrawlMaxPagesReturnsCustomValue(): void
    {
        $_ENV['CRAWL_MAX_PAGES'] = '10';
        $this->assertSame(10, App::crawlMaxPages());
    }

    public function testCrawlAllowedDomainsReturnsDefault(): void
    {
        unset($_ENV['CRAWL_ALLOWED_DOMAINS']);
        $domains = App::crawlAllowedDomains();
        $this->assertContains('linkedin.com', $domains);
        $this->assertContains('indeed.com', $domains);
    }

    public function testCrawlAllowedDomainsReturnsCustomValue(): void
    {
        $_ENV['CRAWL_ALLOWED_DOMAINS'] = 'glassdoor.com, monster.com';
        $domains = App::crawlAllowedDomains();
        $this->assertContains('glassdoor.com', $domains);
        $this->assertContains('monster.com', $domains);
    }
}
