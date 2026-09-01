<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Exceptions\CrawlerException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Driver Vagas.com.br — scraping HTML.
 * URL: https://www.vagas.com.br/vagas-de-KEYWORD?pagina=N
 * SPEC-014
 */
final class VagasDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'vagas'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];
        // Vagas.com.br usa slugs de keyword na URL
        $slug = str_replace([' ', '+'], '-', strtolower($keyword));

        for ($page = 1; $page <= $maxPages; $page++) {
            $url  = "https://www.vagas.com.br/vagas-de-{$slug}?pagina={$page}";
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

    private function parse(string $html): array
    {
        $jobs    = [];
        $crawler = new Crawler($html);

        $crawler->filter('.vaga')->each(static function (Crawler $node) use (&$jobs): void {
            $link    = $node->filter('a.link-detalhes-vaga');
            if ($link->count() === 0) {
                return;
            }

            $href    = $link->attr('href') ?? '';
            $id      = 'vagas_' . md5($href);
            $title   = trim($link->text(''));
            $company = trim($node->filter('.nome-empresa')->text(''));
            $loc     = trim($node->filter('.cidade-estado-pais')->text(''));
            $url     = str_starts_with($href, 'http') ? $href : 'https://www.vagas.com.br' . $href;

            if ($title === '') {
                return;
            }

            $jobs[] = [
                'external_id' => $id,
                'source'      => 'vagas',
                'title'       => $title,
                'company'     => $company ?: 'Não informado',
                'location'    => $loc ?: null,
                'url'         => $url,
            ];
        });

        return $jobs;
    }
}
