<?php
declare(strict_types=1);
namespace App\Services\Drivers;
use App\Exceptions\CrawlerException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Driver Jobbol — scraping HTML.
 * URL: https://www.jobbol.com.br/vagas?q=KEYWORD&page=N
 * SPEC-014
 */
final class JobbolDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'jobbol'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];
        for ($page = 1; $page <= $maxPages; $page++) {
            $url  = 'https://www.jobbol.com.br/vagas?' . http_build_query(['q' => $keyword, 'page' => $page]);
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
        $crawler->filter('.job-card, article.vacancy, .vacancy-item, [class*="job-item"]')->each(static function (Crawler $node) use (&$jobs): void {
            $link  = $node->filter('a')->first();
            $href  = $link->count() > 0 ? ($link->attr('href') ?? '') : '';
            $id    = 'jbb_' . md5($href ?: uniqid());
            $title = trim($node->filter('h2, h3, .title, [class*="title"]')->first()->text(''));
            $co    = trim($node->filter('.company, [class*="company"]')->first()->text(''));
            $loc   = trim($node->filter('.location, [class*="location"]')->first()->text(''));
            $url   = str_starts_with($href, 'http') ? $href : 'https://www.jobbol.com.br' . $href;
            if ($title === '') return;
            $jobs[] = [
                'external_id' => $id, 'source' => 'jobbol',
                'title' => $title, 'company' => $co ?: 'Não informado',
                'location' => $loc ?: null, 'url' => $url,
            ];
        });
        return $jobs;
    }
}
