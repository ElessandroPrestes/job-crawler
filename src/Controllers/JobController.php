<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Middleware\RateLimiter;
use App\Repositories\JobRepository;

final class JobController
{
    private JobRepository $jobs;
    private RateLimiter   $limiter;

    public function __construct()
    {
        $this->jobs    = new JobRepository();
        $this->limiter = new RateLimiter();
    }

    public function index(Request $request, array $params): void
    {
        $this->limiter->check($request->ip());

        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $filters = array_filter([
            'keyword'       => $request->query('keyword'),
            'location'      => $request->query('location'),
            'source'        => $request->query('source'),
            'contract_type' => $request->query('contract_type'),
            'since'         => $request->query('since'),
        ]);

        $total = $this->jobs->count($filters);
        $items = $this->jobs->findAll($filters, $page, $perPage);

        header("X-Total-Count: {$total}");
        header("X-Page: {$page}");
        header("X-Per-Page: {$perPage}");

        JsonResponse::ok(
            array_map(static fn ($j) => $j->toArray(), $items),
            [
                'total'     => $total,
                'page'      => $page,
                'per_page'  => $perPage,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ]
        );
    }

    public function show(Request $request, array $params): void
    {
        $this->limiter->check($request->ip());

        $id  = (int) ($params['id'] ?? 0);
        $job = $this->jobs->findById($id);

        if ($job === null) {
            JsonResponse::notFound('Vaga não encontrada.');
        }

        JsonResponse::ok($job->toArray());
    }
}
