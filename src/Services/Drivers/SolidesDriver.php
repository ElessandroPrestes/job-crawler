<?php
declare(strict_types=1);
namespace App\Services\Drivers;
use App\Exceptions\CrawlerException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Driver Sólides Vagas — scraping HTML.
 * URL: https://vaga.solides.com.br/?q=KEYWORD&page=N
 * SPEC-014
 */
final class SolidesDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'solides'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];
        for ($page = 1; $page <= $maxPages; $page++) {
            $url  = 'https://vaga.solides.com.br/?' . http_build_query(['q' => $keyword, 'page' => $page]);
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
        $crawler->filter('.vacancy-card, .job-card, article.job')->each(static function (Crawler $node) use (&$jobs): void {
            $link    = $node->filter('a')->first();
            $href    = $link->count() > 0 ? ($link->attr('href') ?? '') : '';
            $id      = 'sol_' . md5($href ?: uniqid());
            $title   = trim($node->filter('h2, h3, .title, .vacancy-title')->first()->text(''));
            $company = trim($node->filter('.company, .empresa, .vacancy-company')->first()->text(''));
            $loc     = trim($node->filter('.location, .cidade, .vacancy-location')->first()->text(''));
            $url     = str_starts_with($href, 'http') ? $href : 'https://vaga.solides.com.br' . $href;
            if ($title === '') return;
            $jobs[] = [
                'external_id' => $id, 'source' => 'solides',
                'title' => $title, 'company' => $company ?: 'Não informado',
                'location' => $loc ?: null, 'url' => $url,
            ];
        });
        return $jobs;
    }
}
