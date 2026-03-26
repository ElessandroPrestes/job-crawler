<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Exceptions\ValidationException;
use App\Models\Job;
use App\Repositories\JobRepository;

final class ExportService
{
    private JobRepository $jobs;

    public function __construct()
    {
        $this->jobs = new JobRepository();
    }

    public function export(string $format, array $filters = []): string
    {
        if (!in_array($format, ['csv', 'json'], true)) {
            throw new ValidationException("Formato inválido: '{$format}'. Use csv ou json.");
        }

        $jobs = $this->jobs->findAll($filters, 1, 10000);

        return match ($format) {
            'csv'  => $this->toCsv($jobs),
            'json' => $this->toJson($jobs),
        };
    }

    public function filename(string $format): string
    {
        return 'jobs-' . date('Y-m-d') . '.' . $format;
    }

    private function toCsv(array $jobs): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['id', 'title', 'company', 'location', 'contract_type', 'source', 'url', 'scraped_at']);

        foreach ($jobs as $job) {
            /** @var Job $job */
            fputcsv($handle, [
                $job->id,
                $this->sanitizeField($job->title),
                $this->sanitizeField($job->company),
                $this->sanitizeField($job->location ?? ''),
                $this->sanitizeField($job->contractType ?? ''),
                $this->sanitizeField($job->source),
                $this->sanitizeField($job->url),
                $job->scrapedAt,
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return (string) $content;
    }

    private function toJson(array $jobs): string
    {
        $data = array_map(static fn (Job $job) => $job->toArray(), $jobs);

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '[]';
    }

    private function sanitizeField(string $value): string
    {
        if (str_starts_with($value, '=') || str_starts_with($value, '+') ||
            str_starts_with($value, '-') || str_starts_with($value, '@')) {
            $value = "'" . $value;
        }

        return $value;
    }
}
