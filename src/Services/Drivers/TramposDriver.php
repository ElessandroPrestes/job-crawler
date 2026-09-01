<?php
declare(strict_types=1);
namespace App\Services\Drivers;
use App\Exceptions\CrawlerException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Driver Trampos — scraping HTML.
 * URL: https://trampos.co/oportunidades?term=KEYWORD&page=N
 * SPEC-014
 */
final class TramposDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'trampos'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];
        for ($page = 1; $page <= $maxPages; $page++) {
            $url  = 'https://trampos.co/oportunidades?' . http_build_query(['term' => $keyword, 'page' => $page]);
            $html = '';
            try { $html = $this->get($url); } catch (CrawlerException) { break; }
            $parsed = $this->parse($html);
            if (empty($parsed)) break;
            $jobs = array_merge($jobs, $parsed);
            $this->delay();
        }
        return $jobs;
    }

    private function parse(string $html): array
    {
        $jobs    = [];
        $crawler = new Crawler($html);
        $crawler->filter('article.opportunity, .opportunity-item, .job-listing')->each(static function (Crawler $node) use (&$jobs): void {
            $link  = $node->filter('a')->first();
            $href  = $link->count() > 0 ? ($link->attr('href') ?? '') : '';
            $id    = 'trp_' . md5($href ?: uniqid());
            $title = trim($node->filter('h2, h3, .title, .opportunity-title')->first()->text(''));
            $co    = trim($node->filter('.company, .employer, [class*="company"]')->first()->text(''));
            $loc   = trim($node->filter('.location, .place, [class*="location"]')->first()->text(''));
            $url   = str_starts_with($href, 'http') ? $href : 'https://trampos.co' . $href;
            if ($title === '') return;
            $jobs[] = [
                'external_id' => $id, 'source' => 'trampos',
                'title' => $title, 'company' => $co ?: 'Não informado',
                'location' => $loc ?: null, 'url' => $url,
            ];
        });
        return $jobs;
    }
}
