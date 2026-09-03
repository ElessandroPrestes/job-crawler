<?php
declare(strict_types=1);
namespace App\Services\Drivers;
use App\Exceptions\CrawlerException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Driver Catho — scraping HTML.
 * URL: https://www.catho.com.br/vagas/KEYWORD/?page=N
 * SPEC-014
 */
final class CathoDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'catho'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];
        $slug = str_replace([' ', '+'], '-', strtolower(urlencode($keyword)));
        for ($page = 1; $page <= $maxPages; $page++) {
            $url  = "https://www.catho.com.br/vagas/{$slug}/?page={$page}&q[sort_by]=date";
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
        $crawler->filter('article.offer, article[class*="offer"], [data-jobid], .job-card, article[class*="job"]')->each(static function (Crawler $node) use (&$jobs): void {
            $link    = $node->filter('h2 a, h3 a, a[data-navigation-offer], a')->first();
            $href    = $link->count() > 0 ? ($link->attr('href') ?? '') : '';
            if (preg_match('/\/(\d+)(?:\?|$)/', $href, $m)) {
                $id = $m[1];
            } else {
                $id = $node->attr('data-jobid') ?? md5($node->html());
            }
            $titleNode = $node->filter('h2, h3, .title_offer, .job-title, [class*="title"]')->first();
            $title   = $titleNode->count() > 0 ? trim($titleNode->text('')) : '';
            $companyNode = $node->filter('p span.text-12, .company-name, [class*="company"]')->first();
            $company = $companyNode->count() > 0 ? trim($companyNode->text('')) : 'Não informado';
            $locNode = $node->filter('.location, [class*="location"]')->first();
            $loc     = $locNode->count() > 0 ? trim($locNode->text('')) : 'Brasil';
            $url     = str_starts_with($href, 'http') ? $href : 'https://www.catho.com.br' . $href;
            if ($title === '') return;
            $jobs[] = [
                'external_id' => 'cat_' . $id, 'source' => 'catho',
                'title' => $title, 'company' => $company,
                'location' => $loc ?: null, 'url' => $url,
            ];
        });
        return $jobs;
    }
}
