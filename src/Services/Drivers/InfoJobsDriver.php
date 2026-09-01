<?php
declare(strict_types=1);
namespace App\Services\Drivers;
use App\Exceptions\CrawlerException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Driver InfoJobs — scraping HTML.
 * URL: https://www.infojobs.com.br/empregos-em-,LOCATION/KEYWORD.aspx?Page=N
 * SPEC-014
 */
final class InfoJobsDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'infojobs'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];
        $kwSlug  = str_replace(' ', '-', strtolower($keyword));
        $locSlug = $location ? str_replace(' ', '-', strtolower($location)) : 'brasil';
        for ($page = 1; $page <= $maxPages; $page++) {
            $url  = "https://www.infojobs.com.br/empregos-em-{$locSlug}/{$kwSlug}.aspx?Page={$page}&Sort=1";
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
        $crawler->filter('ul#listaVagas li, .vacancy-item, .job-item')->each(static function (Crawler $node) use (&$jobs): void {
            $link  = $node->filter('a.vaga-nome, a[class*="job"], h2 a')->first();
            if ($link->count() === 0) return;
            $href  = $link->attr('href') ?? '';
            $id    = 'ij_' . md5($href ?: uniqid());
            $title = trim($link->text(''));
            $co    = trim($node->filter('.nome-empresa, .company, [class*="company"]')->first()->text(''));
            $loc   = trim($node->filter('.localidade, .location, [class*="location"]')->first()->text(''));
            $url   = str_starts_with($href, 'http') ? $href : 'https://www.infojobs.com.br' . $href;
            if ($title === '') return;
            $jobs[] = [
                'external_id' => $id, 'source' => 'infojobs',
                'title' => $title, 'company' => $co ?: 'Não informado',
                'location' => $loc ?: null, 'url' => $url,
            ];
        });
        return $jobs;
    }
}
