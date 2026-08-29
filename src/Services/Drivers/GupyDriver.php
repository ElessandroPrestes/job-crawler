<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Config\App;
use App\Exceptions\CrawlerException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

final class GupyDriver
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
            'headers'         => [
                'User-Agent' => 'Mozilla/5.0 (compatible; JobCrawler/1.0)',
                'Accept'     => 'text/html,application/xhtml+xml',
            ],
            'verify' => true,
        ]);
    }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];

        for ($page = 0; $page < $maxPages; $page++) {
            $url = $this->buildUrl($keyword, $location, $page);

            try {
                $response = $this->http->get($url);
                $html     = (string) $response->getBody();
            } catch (GuzzleException $e) {
                throw new CrawlerException("Gupy fetch falhou: " . $e->getMessage(), 0, $e);
            }

            $parsed = $this->parse($html);

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

    private function buildUrl(string $keyword, ?string $location, int $page): string
    {
        // Add 24h filter when applicable
        $params = [
            'q'    => $keyword,
            'l'    => $location ?? '',
            'page' => $page,
            'date' => '24h' // SPEC-011 filter
        ];

        return 'https://example.com/jobs?' . http_build_query($params);
    }

    private function parse(string $html): array
    {
        $jobs    = [];
        $crawler = new Crawler($html);
        $sourceName = strtolower('Gupy');

        $crawler->filter('.job-item')->each(static function (Crawler $node) use (&$jobs, $sourceName): void {
            $externalId = uniqid($sourceName . '_', true);
            $title      = trim($node->filter('.title')->text(''));
            $company    = trim($node->filter('.company')->text(''));
            $location   = trim($node->filter('.location')->text(''));

            if ($title === '' || $company === '') {
                return;
            }

            $jobs[] = [
                'external_id' => $externalId,
                'source'      => $sourceName,
                'title'       => $title,
                'company'     => $company,
                'location'    => $location ?: null,
                'url'         => 'https://example.com/job/' . $externalId,
            ];
        });

        return $jobs;
    }
}
