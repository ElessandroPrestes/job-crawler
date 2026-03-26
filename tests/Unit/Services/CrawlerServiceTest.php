<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class CrawlerServiceTest extends TestCase
{
    public function testThrowsValidationExceptionForInvalidSource(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Source inválido/');

        $service = new \App\Services\CrawlerService();
        $service->execute(['source' => 'invalid', 'keyword' => 'PHP']);
    }

    public function testThrowsValidationExceptionForShortKeyword(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/keyword/');

        $service = new \App\Services\CrawlerService();
        $service->execute(['source' => 'linkedin', 'keyword' => 'P']);
    }

    public function testThrowsValidationExceptionForDisallowedDomain(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Domínio não permitido/');

        $service = new \App\Services\CrawlerService();
        $service->execute([
            'source'  => 'custom',
            'keyword' => 'PHP Developer',
            'url'     => 'http://evil.example.com/jobs',
        ]);
    }

    public function testThrowsValidationExceptionForInvalidUrl(): void
    {
        $this->expectException(ValidationException::class);

        $service = new \App\Services\CrawlerService();
        $service->execute([
            'source'  => 'custom',
            'keyword' => 'PHP Developer',
            'url'     => 'not-a-url',
        ]);
    }
}
