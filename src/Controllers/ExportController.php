<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Middleware\RateLimiter;
use App\Services\ExportService;

final class ExportController
{
    private ExportService $exporter;
    private RateLimiter   $limiter;

    public function __construct()
    {
        $this->exporter = new ExportService();
        $this->limiter  = new RateLimiter();
    }

    public function download(Request $request, array $params): void
    {
        $this->limiter->check($request->ip());

        $format = $request->query('format', '');

        if (!in_array($format, ['csv', 'json'], true)) {
            JsonResponse::badRequest("O parâmetro 'format' deve ser csv ou json.");
        }

        $filters = array_filter([
            'keyword'       => $request->query('keyword'),
            'location'      => $request->query('location'),
            'source'        => $request->query('source'),
            'contract_type' => $request->query('contract_type'),
            'since'         => $request->query('since'),
        ]);

        $content  = $this->exporter->export($format, $filters);
        $filename = $this->exporter->filename($format);

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
        } else {
            header('Content-Type: application/json; charset=utf-8');
        }

        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: no-cache, no-store, must-revalidate');

        echo $content;
        exit;
    }
}
