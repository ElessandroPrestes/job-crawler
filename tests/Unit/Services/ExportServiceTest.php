<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ValidationException;
use App\Services\ExportService;
use Tests\Support\DatabaseTestCase;

final class ExportServiceTest extends DatabaseTestCase
{
    private ExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExportService();
    }

    public function testExportThrowsForInvalidFormat(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Formato inválido/');

        $this->service->export('xml');
    }

    public function testExportCsvReturnsEmptyWithHeadersOnly(): void
    {
        $csv = $this->service->export('csv');

        $lines = array_filter(explode("\n", trim($csv)));
        // Deve conter apenas a linha de cabeçalho
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('id', $csv);
        $this->assertStringContainsString('title', $csv);
        $this->assertStringContainsString('company', $csv);
    }

    public function testExportCsvContainsAllFields(): void
    {
        $this->pdo->exec("
            INSERT INTO jobs (external_id, source, title, company, location, contract_type, url, scraped_at, is_notified)
            VALUES ('ext-csv-001', 'linkedin', 'PHP Developer', 'Tech Corp', 'São Paulo', 'PJ', 'https://linkedin.com/1', datetime('now'), 0)
        ");

        $csv = $this->service->export('csv');

        $this->assertStringContainsString('PHP Developer', $csv);
        $this->assertStringContainsString('Tech Corp', $csv);
        $this->assertStringContainsString('São Paulo', $csv);
        $this->assertStringContainsString('PJ', $csv);
        $this->assertStringContainsString('linkedin', $csv);
        $this->assertStringContainsString('https://linkedin.com/1', $csv);
    }

    public function testExportCsvSanitizesFormulaInjection(): void
    {
        $this->pdo->exec("
            INSERT INTO jobs (external_id, source, title, company, url, scraped_at, is_notified)
            VALUES ('ext-csv-002', 'linkedin', '=SUM(1+1)', 'Corp', 'https://linkedin.com/2', datetime('now'), 0)
        ");

        $csv = $this->service->export('csv');

        // O título com "=" deve ser prefixado com "'"
        $this->assertStringContainsString("'=SUM(1+1)", $csv);
        $this->assertStringNotContainsString('"=SUM(1+1)"', $csv);
    }

    public function testExportJsonReturnsEmptyArray(): void
    {
        $json = $this->service->export('json');
        $data = json_decode($json, true);

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    public function testExportJsonContainsJobData(): void
    {
        $this->pdo->exec("
            INSERT INTO jobs (external_id, source, title, company, location, url, scraped_at, is_notified)
            VALUES ('ext-json-001', 'indeed', 'Node.js Dev', 'Startup', 'Rio de Janeiro', 'https://indeed.com/1', datetime('now'), 0)
        ");

        $json = $this->service->export('json');
        $data = json_decode($json, true);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertSame('Node.js Dev', $data[0]['title']);
        $this->assertSame('Startup', $data[0]['company']);
        $this->assertSame('Rio de Janeiro', $data[0]['location']);
        $this->assertSame('indeed', $data[0]['source']);
    }

    public function testFilenameHasCorrectExtension(): void
    {
        $csvFilename  = $this->service->filename('csv');
        $jsonFilename = $this->service->filename('json');

        $this->assertStringEndsWith('.csv', $csvFilename);
        $this->assertStringEndsWith('.json', $jsonFilename);
        $this->assertStringStartsWith('jobs-', $csvFilename);
        $this->assertStringStartsWith('jobs-', $jsonFilename);
    }
}
