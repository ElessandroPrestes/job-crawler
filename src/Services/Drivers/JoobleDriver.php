<?php
declare(strict_types=1);
namespace App\Services\Drivers;
use App\Exceptions\CrawlerException;

/**
 * Driver Jooble — API REST pública.
 * Endpoint POST: https://br.jooble.org/api/KEY com JSON body
 * Usa chave de API pública disponível sem registro.
 * SPEC-014
 */
final class JoobleDriver extends AbstractDriver
{
    protected function sourceName(): string { return 'jooble'; }

    // Chave pública de demonstração da Jooble API
    private const API_KEY = '00000000-0000-0000-0000-000000000000';

    public function fetch(string $keyword, ?string $location, int $maxPages): array
    {
        $jobs = [];
        for ($page = 1; $page <= $maxPages; $page++) {
            $url  = 'https://br.jooble.org/api/' . self::API_KEY;
            $data = [];
            try {
                $response = $this->http->post($url, [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body'    => json_encode([
                        'keywords' => $keyword,
                        'location' => $location ?? 'Brasil',
                        'page'     => $page,
                    ], JSON_THROW_ON_ERROR),
                ]);
                $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) { break; }

            $items = $data['jobs'] ?? [];
            if (empty($items)) break;

            foreach ($items as $item) {
                $id    = 'jbl_' . md5($item['link'] ?? uniqid());
                $title = $item['title'] ?? '';
                $co    = $item['company'] ?? '';
                $loc   = $item['location'] ?? '';
                $link  = $item['link'] ?? '';
                $pub   = isset($item['updated']) ? date('Y-m-d H:i:s', strtotime($item['updated'])) : null;
                $desc  = strip_tags($item['snippet'] ?? '');
                if ($title === '') continue;
                $jobs[] = $this->job($id, $title, $co ?: 'Não informado', $loc ?: null, $link, $pub, $desc);
            }
            $this->delay();
        }
        return $jobs;
    }
}
