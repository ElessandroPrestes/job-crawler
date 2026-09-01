<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Exceptions\CrawlerException;

/**
 * Driver GeekHunter — API REST pública.
 * Endpoint: https://www.geekhunter.com.br/api/v1/vacancies?search=KEYWORD&per_page=20&page=N
 * SPEC-014
 */
final class GeekHunterDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'geekhunter'; }

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $url  = $this->buildUrl($keyword, $page);
            $data = [];

            try {
                $data = $this->getJson($url, [
                    'Accept' => 'application/json',
                    'Origin' => 'https://www.geekhunter.com.br',
                ]);
            } catch (CrawlerException) {
                break;
            }

            $items = $data['vacancies'] ?? ($data['data'] ?? []);
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $id      = (string) ($item['id'] ?? '');
                $title   = $item['title'] ?? ($item['name'] ?? '');
                $company = $item['company']['name'] ?? ($item['company_name'] ?? '');
                $loc     = $item['city'] ?? ($item['location'] ?? '');
                $jobUrl  = $item['url'] ?? ('https://www.geekhunter.com.br/vagas/' . $id);
                $pubAt   = isset($item['created_at'])
                    ? date('Y-m-d H:i:s', strtotime($item['created_at']))
                    : null;
                $desc    = $item['description'] ?? null;

                if ($title === '' || $company === '' || $id === '') {
                    continue;
                }

                $jobs[] = $this->job($id, $title, $company, $loc ?: null, $jobUrl, $pubAt, $desc);
            }

            $this->delay();
        }

        return $jobs;
    }

    private function buildUrl(string $keyword, int $page): string
    {
        return 'https://www.geekhunter.com.br/api/v1/vacancies?' . http_build_query([
            'search'   => $keyword,
            'per_page' => 20,
            'page'     => $page,
            'country'  => 'BR',
        ]);
    }
}
