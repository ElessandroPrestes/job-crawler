<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Middleware\RateLimiter;
use App\Repositories\AlertFilterRepository;

final class AlertController
{
    private AlertFilterRepository $filters;
    private RateLimiter           $limiter;

    public function __construct()
    {
        $this->filters = new AlertFilterRepository();
        $this->limiter = new RateLimiter();
    }

    public function index(Request $request, array $params): void
    {
        $this->limiter->check($request->ip());

        $items = $this->filters->findAll();

        JsonResponse::ok(array_map(static fn ($f) => $f->toArray(), $items));
    }

    public function store(Request $request, array $params): void
    {
        $this->limiter->check($request->ip());

        $body = $request->body();

        $email = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if ($email === false) {
            throw new ValidationException("O campo 'email' deve ser um endereço válido.");
        }

        $keywords = $body['keywords'] ?? [];
        if (!is_array($keywords) || count($keywords) < 1) {
            throw new ValidationException("O campo 'keywords' deve conter ao menos uma palavra-chave.");
        }

        $filter = $this->filters->create([
            'email'          => $email,
            'keywords'       => $keywords,
            'locations'      => $body['locations'] ?? [],
            'contract_types' => $body['contract_types'] ?? [],
        ]);

        JsonResponse::created($filter->toArray(), "/api/alerts/{$filter->id}");
    }

    public function destroy(Request $request, array $params): void
    {
        $this->limiter->check($request->ip());

        $id      = (int) ($params['id'] ?? 0);
        $deleted = $this->filters->delete($id);

        if (!$deleted) {
            JsonResponse::notFound('Filtro não encontrado.');
        }

        JsonResponse::ok(['deleted' => true]);
    }
}
