<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Config\App;
use App\Exceptions\CrawlerException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

final class IndeedDriver
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
            $url = $this->buildUrl($keyword, $location, $page * 10);

            try {
                $response = $this->http->get($url);
                $html     = (string) $response->getBody();
            } catch (GuzzleException $e) {
                throw new CrawlerException("Indeed fetch falhou: {$e->getMessage()}", 0, $e);
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
            'q'    => $keyword,
            'l'    => $location ?? '',
            'fromage' => '1',
            'start' => $start,
        ];

        return 'https://www.indeed.com/jobs?' . http_build_query($params);
    }

    private function parse(string $html): array
    {
        $jobs    = [];
        $crawler = new Crawler($html);

        $crawler->filter('[data-jk]')->each(static function (Crawler $node) use (&$jobs): void {
            $externalId = $node->attr('data-jk') ?? uniqid('ind_', true);
            $title      = trim($node->filter('.jobTitle')->text(''));
            $company    = trim($node->filter('[data-testid="company-name"]')->text(''));
            $location   = trim($node->filter('[data-testid="text-location"]')->text(''));

            if ($title === '' || $company === '') {
                return;
            }

            $jobs[] = [
                'external_id' => $externalId,
                'source'      => 'indeed',
                'title'       => $title,
                'company'     => $company,
                'location'    => $location ?: null,
                'url'         => 'https://www.indeed.com/viewjob?jk=' . $externalId,
            ];
        });

        return $jobs;
    }
}
