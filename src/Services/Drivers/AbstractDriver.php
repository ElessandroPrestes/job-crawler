<?php

declare(strict_types=1);

namespace App\Services\Drivers;

use App\Config\App;
use App\Exceptions\CrawlerException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Base para todos os drivers de crawling.
 * Provê HTTP client com User-Agent real, fetch com retry e delay configurável.
 * SPEC-014
 */
abstract class AbstractDriver
{
    protected Client $http;

    /** Nome da fonte — sobrescreva em cada driver. */
    abstract protected function sourceName(): string;

    public function __construct(?Client $client = null)
    {
        $this->http = $client ?? new Client([
            'timeout'         => 30,
            'connect_timeout' => 10,
            'allow_redirects' => true,
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
            ],
            'verify' => true,
        ]);
    }

    /**
     * Faz GET em uma URL, retorna o body como string.
     * Em caso de erro de rede, lança CrawlerException com o nome do driver.
     */
    protected function get(string $url): string
    {
        try {
            $response = $this->http->get($url);
            return (string) $response->getBody();
        } catch (GuzzleException $e) {
            throw new CrawlerException(
                sprintf('[%s] Fetch falhou: %s', $this->sourceName(), $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Faz GET JSON e retorna o array decodificado.
     * Útil para drivers com API REST (Gupy, GeekHunter, Jooble).
     */
    protected function getJson(string $url, array $headers = []): array
    {
        try {
            $response = $this->http->get($url, ['headers' => $headers]);
            $body     = (string) $response->getBody();
            $decoded  = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (GuzzleException $e) {
            throw new CrawlerException(
                sprintf('[%s] JSON fetch falhou: %s', $this->sourceName(), $e->getMessage()),
                0,
                $e
            );
        } catch (\JsonException $e) {
            throw new CrawlerException(
                sprintf('[%s] JSON inválido: %s', $this->sourceName(), $e->getMessage()),
                0,
                $e
            );
        }
    }

    /** Delay entre páginas para não sobrecarregar o servidor alvo. */
    protected function delay(): void
    {
        $ms = App::crawlDelayMs();
        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }

    /** Constrói entrada padrão de vaga para o pipeline. */
    protected function job(
        string $externalId,
        string $title,
        string $company,
        ?string $location,
        string $url,
        ?string $publishedAt = null,
        ?string $description = null,
        ?string $contractType = null
    ): array {
        return [
            'external_id'   => $externalId,
            'source'        => $this->sourceName(),
            'title'         => trim($title),
            'company'       => trim($company),
            'location'      => $location ? trim($location) : null,
            'url'           => trim($url),
            'published_at'  => $publishedAt,
            'description'   => $description,
            'contract_type' => $contractType,
        ];
    }
}
