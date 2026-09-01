<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Exceptions\CrawlerException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Driver Indeed Brasil — scraping HTML.
 * URL: https://br.indeed.com/jobs?q=KEYWORD&l=Brasil&fromage=3&start=N
 * SPEC-014
 */
final class IndeedDriver extends AbstractDriver
{
    public static ?object $mockClient = null;

    protected function sourceName(): string { return 'indeed'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];

        for ($page = 0; $page < $maxPages; $page++) {
            $url  = $this->buildUrl($keyword, $location, $page * 10);
            $html = '';

            try {
                $html = $this->get($url);
            } catch (CrawlerException) {
                break;
            }

            $parsed = $this->parse($html);
            if (empty($parsed)) {
                break;
            }

            $jobs = array_merge($jobs, $parsed);
            $this->delay();
        }

        return $jobs;
    }

    private function buildUrl(string $keyword, ?string $location, int $start): string
    {
        return 'https://br.indeed.com/jobs?' . http_build_query([
            'q'       => $keyword,
            'l'       => $location ?? 'Brasil',
            'fromage' => '3',
            'start'   => $start,
            'sort'    => 'date',
        ]);
    }

    private function parse(string $html): array
    {
        $jobs    = [];
        $crawler = new Crawler($html);

        // Indeed renderiza cards com data-jk como ID único
        $crawler->filter('[data-jk]')->each(static function (Crawler $node) use (&$jobs): void {
            $jk      = $node->attr('data-jk') ?? '';
            if ($jk === '') {
                return;
            }

            $titleNode   = $node->filter('.jobTitle span[title]');
            $title       = $titleNode->count() > 0
                ? trim($titleNode->attr('title') ?? $titleNode->text(''))
                : trim($node->filter('.jobTitle')->text(''));

            $company  = trim($node->filter('[data-testid="company-name"]')->text(''));
            $location = trim($node->filter('[data-testid="text-location"]')->text(''));

            if ($title === '' || $company === '') {
                return;
            }

            $jobs[] = [
                'external_id' => $jk,
                'source'      => 'indeed',
                'title'       => $title,
                'company'     => $company,
                'location'    => $location ?: null,
                'url'         => 'https://br.indeed.com/viewjob?jk=' . $jk,
            ];
        });

        return $jobs;
    }
}
