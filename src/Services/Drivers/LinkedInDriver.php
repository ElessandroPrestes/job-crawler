<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Config\App;
use App\Exceptions\CrawlerException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

final class LinkedInDriver
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
            $url = $this->buildUrl($keyword, $location, $page * 25);

            try {
                $response = $this->http->get($url);
                $html     = (string) $response->getBody();
            } catch (GuzzleException $e) {
                throw new CrawlerException("LinkedIn fetch falhou: {$e->getMessage()}", 0, $e);
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

    private function buildUrl(string $keyword, ?string $location, int $start): string
    {
        $params = [
            'keywords' => $keyword,
            'start'    => $start,
        ];

        if ($location) {
            $params['location'] = $location;
        }

        return 'https://www.linkedin.com/jobs/search?' . http_build_query($params);
    }

    private function parse(string $html): array
    {
        $jobs    = [];
        $crawler = new Crawler($html);

        $crawler->filter('.job-search-card')->each(static function (Crawler $node) use (&$jobs): void {
            $externalId = $node->attr('data-entity-urn') ?? uniqid('li_', true);
            $title      = trim($node->filter('.base-search-card__title')->text(''));
            $company    = trim($node->filter('.base-search-card__subtitle')->text(''));
            $location   = trim($node->filter('.job-search-card__location')->text(''));
            $url        = $node->filter('a.base-card__full-link')->attr('href') ?? '';

            if ($title === '' || $company === '') {
                return;
            }

            $jobs[] = [
                'external_id' => $externalId,
                'source'      => 'linkedin',
                'title'       => $title,
                'company'     => $company,
                'location'    => $location ?: null,
                'url'         => $url,
            ];
        });

        return $jobs;
    }
}
