<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Config\App;
use App\Exceptions\CrawlerException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

final class CustomDriver
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
            'headers'         => [
                'User-Agent' => 'Mozilla/5.0 (compatible; JobCrawler/1.0)',
                'Accept'     => 'text/html,application/xhtml+xml,application/json',
            ],
            'verify' => true,
        ]);
    }

    public function fetch(string $url, string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $pageUrl = $this->buildPageUrl($url, $keyword, $location, $page);

            try {
                $response    = $this->http->get($pageUrl);
                $contentType = $response->getHeaderLine('Content-Type');
                $body        = (string) $response->getBody();
            } catch (GuzzleException $e) {
                throw new CrawlerException("Custom driver fetch falhou: {$e->getMessage()}", 0, $e);
            }

            $parsed = str_contains($contentType, 'application/json')
                ? $this->parseJson($body)
                : $this->parseHtml($body);

            if (empty($parsed)) {
                break;
            }

            $jobs = array_merge($jobs, $parsed);

            $delayMs = App::crawlDelayMs();
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        return $jobs;
    }

    private function buildPageUrl(string $base, string $keyword, ?string $location, int $page): string
    {
        $params = ['q' => $keyword, 'page' => $page];
        if ($location) {
            $params['l'] = $location;
        }

        $separator = str_contains($base, '?') ? '&' : '?';
        return $base . $separator . http_build_query($params);
    }

    private function parseHtml(string $html): array
    {
        $jobs    = [];
        $crawler = new Crawler($html);

        $crawler->filter('[data-job-id], .job-listing, .vacancy')->each(
            static function (Crawler $node) use (&$jobs): void {
                $externalId = $node->attr('data-job-id') ?? uniqid('custom_', true);
                $title      = trim($node->filter('h2, h3, .job-title, .vacancy-title')->text(''));
                $company    = trim($node->filter('.company, .employer, .company-name')->text(''));
                $location   = trim($node->filter('.location, .job-location')->text(''));
                $href       = $node->filter('a')->attr('href') ?? '';

                if ($title === '') {
                    return;
                }

                $jobs[] = [
                    'external_id' => $externalId,
                    'source'      => 'custom',
                    'title'       => $title,
                    'company'     => $company ?: 'N/A',
                    'location'    => $location ?: null,
                    'url'         => $href,
                ];
            }
        );

        return $jobs;
    }

    private function parseJson(string $body): array
    {
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return [];
        }

        $items = $data['jobs'] ?? $data['results'] ?? $data['data'] ?? $data;

        if (!is_array($items)) {
            return [];
        }

        $jobs = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $jobs[] = [
                'external_id'   => (string) ($item['id'] ?? uniqid('custom_', true)),
                'source'        => 'custom',
                'title'         => (string) ($item['title'] ?? $item['name'] ?? ''),
                'company'       => (string) ($item['company'] ?? $item['employer'] ?? 'N/A'),
                'location'      => $item['location'] ?? null,
                'contract_type' => $item['contract_type'] ?? $item['type'] ?? null,
                'description'   => $item['description'] ?? null,
                'requirements'  => $item['requirements'] ?? null,
                'salary_range'  => $item['salary'] ?? $item['salary_range'] ?? null,
                'url'           => (string) ($item['url'] ?? $item['link'] ?? ''),
                'published_at'  => $item['published_at'] ?? $item['date'] ?? null,
            ];
        }

        return $jobs;
    }
}
