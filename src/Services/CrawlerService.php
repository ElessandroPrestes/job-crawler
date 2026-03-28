<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Exceptions\CrawlerException;
use App\Exceptions\ValidationException;
use App\Repositories\CrawlLogRepository;
use App\Repositories\JobRepository;
use App\Services\Drivers\CustomDriver;
use App\Services\Drivers\IndeedDriver;
use App\Services\Drivers\LinkedInDriver;

final class CrawlerService
{
    private JobRepository      $jobs;
    private CrawlLogRepository $logs;
    private EmailService       $email;

    public function __construct()
    {
        $this->jobs  = new JobRepository();
        $this->logs  = new CrawlLogRepository();
        $this->email = new EmailService();
    }

    public function execute(array $params): array
    {
        $source   = $params['source'] ?? '';
        $keyword  = $params['keyword'] ?? '';
        $location = $params['location'] ?? null;
        $maxPages = min((int) ($params['max_pages'] ?? 3), App::crawlMaxPages());

        if (!in_array($source, ['linkedin', 'indeed', 'custom'], true)) {
            throw new ValidationException("Source inválido: '{$source}'.");
        }

        if (strlen($keyword) < 2) {
            throw new ValidationException("O campo 'keyword' deve ter ao menos 2 caracteres.");
        }

        if (isset($params['url'])) {
            $this->validateUrl((string) $params['url']);
        }

        $logId = $this->logs->start($source, $keyword, $location);
        $start = microtime(true);

        try {
            $rawJobs = $this->fetchJobs($source, $keyword, $location, $maxPages, $params['url'] ?? null);
            $rawJobs = $this->filterRelevant($rawJobs);
        } catch (CrawlerException $e) {
            $this->logs->finish($logId, 'failed', 0, 0, $e->getMessage());
            throw $e;
        }

        $jobsFound = count($rawJobs);
        $jobsNew   = 0;

        foreach ($rawJobs as $job) {
            $inserted = $this->jobs->upsert($job);
            if ($inserted > 0) {
                $jobsNew++;
            }
        }

        $notified = $this->email->notifyNewJobs();

        $this->logs->finish($logId, 'success', $jobsFound, $jobsNew);

        return [
            'log_id'             => $logId,
            'source'             => $source,
            'keyword'            => $keyword,
            'location'           => $location,
            'status'             => 'success',
            'jobs_found'         => $jobsFound,
            'jobs_new'           => $jobsNew,
            'notifications_sent' => $notified,
            'duration_ms'        => (int) ((microtime(true) - $start) * 1000),
        ];
    }

    private function filterRelevant(array $jobs): array
    {
        return array_values(array_filter($jobs, static function (array $job): bool {
            $title = strtolower($job['title'] ?? '');

            $titleMatch = str_contains($title, 'php') || str_contains($title, 'node');

            if (!$titleMatch) {
                return false;
            }

            if (!empty($job['published_at'])) {
                $ts = strtotime($job['published_at']);
                if ($ts !== false && $ts < strtotime('-3 days')) {
                    return false;
                }
            }

            $loc = strtolower($job['location'] ?? '');

            if ($loc === '') {
                return true;
            }

            foreach (['brasil', 'brazil', 'remoto', 'remote', 'home office'] as $term) {
                if (str_contains($loc, $term)) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function fetchJobs(string $source, string $keyword, ?string $location, int $maxPages, ?string $url): array
    {
        return match ($source) {
            'linkedin' => (new LinkedInDriver())->fetch($keyword, $location, $maxPages),
            'indeed'   => (new IndeedDriver())->fetch($keyword, $location, $maxPages),
            'custom'   => (new CustomDriver())->fetch($url ?? '', $keyword, $location, $maxPages),
            default    => throw new CrawlerException("Driver não encontrado: {$source}"),
        };
    }

    private function validateUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ValidationException("URL inválida: '{$url}'.");
        }

        $host    = parse_url($url, PHP_URL_HOST);
        $allowed = App::crawlAllowedDomains();

        foreach ($allowed as $domain) {
            if ($host === $domain || str_ends_with((string) $host, ".{$domain}")) {
                return;
            }
        }

        throw new ValidationException("Domínio não permitido para crawl: '{$host}'.");
    }
}
