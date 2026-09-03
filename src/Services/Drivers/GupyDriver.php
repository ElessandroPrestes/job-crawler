<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Exceptions\CrawlerException;

/**
 * Driver Gupy — extração via Portal de Vagas público com dados SSR (__NEXT_DATA__).
 * URL: https://portal.gupy.io/job-search/term=KEYWORD
 * SPEC-014 / SPEC-021
 */
final class GupyDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'gupy'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];
        $url = 'https://portal.gupy.io/job-search/term=' . urlencode($keyword);

        try {
            $html = $this->get($url);
        } catch (CrawlerException) {
            return [];
        }

        if (preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            try {
                $data = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
                $items = $data['props']['pageProps']['initialJobList']['data'] ?? [];

                foreach ($items as $item) {
                    $id      = (string) ($item['id'] ?? '');
                    $title   = $item['name'] ?? '';
                    $company = $item['careerPageName'] ?? ($item['company']['name'] ?? 'Não informado');
                    $city    = $item['city'] ?? '';
                    $state   = $item['state'] ?? '';
                    $workplace = $item['workplaceType'] ?? '';

                    $locParts = array_filter([$city, $state, $workplace]);
                    $loc      = !empty($locParts) ? implode(' - ', $locParts) : 'Brasil';

                    $jobUrl  = $item['jobUrl'] ?? ('https://portal.gupy.io/job/' . $id);
                    $pubAt   = isset($item['publishedDate'])
                        ? date('Y-m-d H:i:s', strtotime($item['publishedDate']))
                        : null;
                    $desc    = $item['description'] ?? null;
                    $type    = $item['type'] ?? null;

                    if ($title === '' || $id === '') {
                        continue;
                    }

                    $jobs[] = $this->job(
                        'gup_' . $id,
                        $title,
                        $company,
                        $loc,
                        $jobUrl,
                        $pubAt,
                        $desc,
                        $type
                    );
                }
            } catch (\Throwable) {
                // Fallback silencioso para não interromper outros drivers
            }
        }

        return $jobs;
    }
}
