<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Exceptions\CrawlerException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Driver Programathor — scraping HTML.
 * URL: https://programathor.com.br/jobs?search=KEYWORD&page=N
 * SPEC-014
 */
final class ProgramathorDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'programathor'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $url  = $this->buildUrl($keyword, $page);
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

    private function buildUrl(string $keyword, int $page): string
    {
        return 'https://programathor.com.br/jobs?' . http_build_query([
            'search' => $keyword,
            'page'   => $page,
        ]);
    }

    private function parse(string $html): array
    {
        $jobs    = [];
        $crawler = new Crawler($html);

        $crawler->filter('a.cell-list-developer')->each(static function (Crawler $node) use (&$jobs): void {
            $href    = $node->attr('href') ?? '';
            $id      = md5($href);
            $title   = trim($node->filter('h2')->text(''));
            $company = trim($node->filter('.tag-list span')->first()->text(''));
            $loc     = trim($node->filter('.city')->text(''));
            $url     = str_starts_with($href, 'http') ? $href : 'https://programathor.com.br' . $href;

            if ($title === '') {
                return;
            }

            $jobs[] = [
                'external_id' => 'pth_' . $id,
                'source'      => 'programathor',
                'title'       => $title,
                'company'     => $company ?: 'Não informado',
                'location'    => $loc ?: null,
                'url'         => $url,
            ];
        });

        return $jobs;
    }
}
