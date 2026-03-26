<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ValidationException;
use App\Services\CrawlerService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\DatabaseTestCase;

final class CrawlerServiceTest extends DatabaseTestCase
{
    private CrawlerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrawlerService();
    }

    // ── Source validation ────────────────────────────────────────────────────

    #[DataProvider('invalidSourceProvider')]
    public function testThrowsValidationExceptionForInvalidSource(string $source): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Source inválido/');

        $this->service->execute(['source' => $source, 'keyword' => 'PHP Developer']);
    }

    public static function invalidSourceProvider(): array
    {
        return [
            'empty string'  => [''],
            'unknown source' => ['glassdoor'],
            'sql injection' => ["linkedin'; DROP TABLE jobs; --"],
            'whitespace'    => ['  '],
        ];
    }

    // ── Keyword validation ────────────────────────────────────────────────────

    #[DataProvider('shortKeywordProvider')]
    public function testThrowsValidationExceptionForKeywordTooShort(string $keyword): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/keyword/');

        $this->service->execute(['source' => 'linkedin', 'keyword' => $keyword]);
    }

    public static function shortKeywordProvider(): array
    {
        return [
            'empty'          => [''],
            'single char'    => ['P'],
            'only whitespace' => [' '],
        ];
    }

    // ── URL / SSRF validation ─────────────────────────────────────────────────

    public function testThrowsForInvalidUrl(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->execute([
            'source'  => 'custom',
            'keyword' => 'PHP Developer',
            'url'     => 'not-a-url',
        ]);
    }

    #[DataProvider('disallowedDomainProvider')]
    public function testThrowsForDisallowedDomain(string $url): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Domínio não permitido/');

        $this->service->execute([
            'source'  => 'custom',
            'keyword' => 'PHP Developer',
            'url'     => $url,
        ]);
    }

    public static function disallowedDomainProvider(): array
    {
        return [
            'internal host'     => ['http://localhost/admin'],
            'metadata endpoint' => ['http://169.254.169.254/latest/meta-data/'],
            'arbitrary domain'  => ['http://evil.example.com/jobs'],
            'internal ip'       => ['http://192.168.0.1/jobs'],
        ];
    }
}
