<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Middleware\RateLimiter;
use App\Repositories\CrawlLogRepository;
use App\Services\CrawlerService;
use App\Services\MultiSourceCrawlerService;

final class CrawlerController
{
    private CrawlerService            $crawler;
    private MultiSourceCrawlerService $multiCrawler;
    private CrawlLogRepository        $logs;
    private RateLimiter               $limiter;

    public function __construct()
    {
        $this->crawler      = new CrawlerService();
        $this->multiCrawler = new MultiSourceCrawlerService();
        $this->logs         = new CrawlLogRepository();
        $this->limiter      = new RateLimiter();
    }

    public function run(Request $request, array $params): void
    {
        $this->limiter->check($request->ip(), 5);

        $result = $this->crawler->execute($request->body());

        JsonResponse::ok($result);
    }

    public function runAll(Request $request, array $params): void
    {
        $this->limiter->check($request->ip(), 5); // limite de requisições

        // ExecuteAll faz o loop em todos os sources, consolida os resultados e aplica os filtros
        $result = $this->multiCrawler->executeAll($request->body());

        JsonResponse::ok($result);
    }

    public function logs(Request $request, array $params): void
    {
        $this->limiter->check($request->ip());

        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $filters = array_filter([
            'status' => $request->query('status'),
            'source' => $request->query('source'),
        ]);

        $total = $this->logs->count($filters);
        $items = $this->logs->findAll($filters, $page, $perPage);

        header("X-Total-Count: {$total}");
        header("X-Page: {$page}");
        header("X-Per-Page: {$perPage}");

        JsonResponse::ok(
            array_map(static fn ($l) => $l->toArray(), $items),
            [
                'total'     => $total,
                'page'      => $page,
                'per_page'  => $perPage,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ]
        );
    }
}
