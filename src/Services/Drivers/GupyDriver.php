<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Exceptions\CrawlerException;

/**
 * Driver Gupy — API REST pública de vagas.
 * Endpoint: https://portal.api.gupy.io/api/job?name=KEYWORD&limit=20&offset=N
 * SPEC-014
 */
final class GupyDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'gupy'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs  = [];
        $limit = 20;

        for ($page = 0; $page < $maxPages; $page++) {
            $url    = $this->buildUrl($keyword, $page * $limit, $limit);
            $data   = [];

            try {
                $data = $this->getJson($url, [
                    'Accept' => 'application/json',
                ]);
            } catch (CrawlerException) {
                break; // fonte indisponível — não bloqueia os outros sources
            }

            $items = $data['data'] ?? [];
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $id      = (string) ($item['id'] ?? '');
                $title   = $item['name'] ?? '';
                $company = $item['company']['name'] ?? ($item['careerPageName'] ?? '');
                $loc     = $item['city'] ?? ($item['state'] ?? '');
                $jobUrl  = $item['jobUrl'] ?? ('https://portal.gupy.io/job/' . $id);
                $pubAt   = isset($item['publishedDate'])
                    ? date('Y-m-d H:i:s', strtotime($item['publishedDate']))
                    : null;

                if ($title === '' || $company === '' || $id === '') {
                    continue;
                }

                $jobs[] = $this->job($id, $title, $company, $loc ?: null, $jobUrl, $pubAt);
            }

            $this->delay();
        }

        return $jobs;
    }

    private function buildUrl(string $keyword, int $offset, int $limit): string
    {
        return 'https://portal.api.gupy.io/api/job?' . http_build_query([
            'name'   => $keyword,
            'offset' => $offset,
            'limit'  => $limit,
        ]);
    }
}
