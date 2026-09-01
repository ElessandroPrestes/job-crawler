<?php
declare(strict_types=1);
namespace App\Services\Drivers;
use App\Exceptions\CrawlerException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Driver Glassdoor — scraping HTML.
 * URL: https://www.glassdoor.com.br/Emprego/brasil-KEYWORD-SRCH_IL.0,6_IN36_KO7,N.htm
 * SPEC-014
 */
final class GlassdoorDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'glassdoor'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];
        $slug = urlencode(str_replace(' ', '-', $keyword));

        for ($page = 1; $page <= $maxPages; $page++) {
            $url  = "https://www.glassdoor.com.br/Emprego/brasil-{$slug}-empregos-SRCH_IL.0,6_IN36_KO7,50_IP{$page}.htm";
            $html = '';
            try {
                $html = $this->get($url);
            } catch (CrawlerException) { break; }

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
        $crawler->filter('li[data-jobid]')->each(static function (Crawler $node) use (&$jobs): void {
            $id      = $node->attr('data-jobid') ?? '';
            $title   = trim($node->filter('[data-test="job-title"]')->text(''));
            $company = trim($node->filter('[data-test="employer-short-name"]')->text(''));
            $loc     = trim($node->filter('[data-test="emp-location"]')->text(''));
            if ($title === '' || $id === '') return;
            $jobs[] = [
                'external_id' => 'gd_' . $id,
                'source'      => 'glassdoor',
                'title'       => $title,
                'company'     => $company ?: 'Não informado',
                'location'    => $loc ?: null,
                'url'         => 'https://www.glassdoor.com.br/job-listing/j?jl=' . $id,
            ];
        });
        return $jobs;
    }
}
