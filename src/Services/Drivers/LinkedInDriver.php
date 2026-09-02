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
    public static ?Client $mockClient = null;

    public function __construct(?Client $client = null)
    {
        $this->http = self::$mockClient ?? $client ?? new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
            'headers'         => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36',
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

    private const GEO_IDS = [
        'brasil' => '106057199',
        'brazil' => '106057199',
    ];

    /** GeoId do Brasil como padrão absoluto — só exibimos vagas BR. */
    private const BRAZIL_GEO_ID = '106057199';

    private function buildUrl(string $keyword, ?string $location, int $start): string
    {
        $params = [
            'keywords' => $keyword,
            'start'    => $start,
            'f_WT'     => '2',
            'f_TPR'    => 'r259200', // 3 dias, a view cuida de filtrar 24h
            'geoId'    => self::BRAZIL_GEO_ID, // padrão: Brasil
        ];

        if ($location) {
            $key = strtolower($location);
            if (isset(self::GEO_IDS[$key])) {
                $params['geoId'] = self::GEO_IDS[$key];
            } else {
                // localização textual adicional (ex: "São Paulo"), mantém geoId BR
                $params['location'] = $location;
            }
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
            $linkNode   = $node->filter('a.base-card__full-link');
            $url        = $linkNode->count() > 0 ? ($linkNode->attr('href') ?? '') : '';

            if ($title === '' || $company === '') {
                return;
            }

            $publishedAt = null;
            try {
                $timeNode = $node->filter('time');
                if ($timeNode->count() > 0) {
                    $dt = $timeNode->attr('datetime');
                    if ($dt) {
                        $ts = strtotime($dt);
                        if ($ts !== false) {
                            $publishedAt = date('Y-m-d H:i:s', $ts);
                        }
                    }
                }
            } catch (\Throwable) {}

            $jobs[] = [
                'external_id'  => $externalId,
                'source'       => 'linkedin',
                'title'        => $title,
                'company'      => $company,
                'location'     => $location ?: null,
                'url'          => $url,
                'published_at' => $publishedAt,
            ];
        });

        return $jobs;
    }
}
