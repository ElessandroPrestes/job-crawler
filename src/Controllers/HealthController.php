<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Http\JsonResponse;
use App\Http\Request;

final class HealthController
{
    public function index(Request $request, array $params): void
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage'  => $this->checkStorage(),
        ];

        $status = in_array('fail', $checks, true) ? 'degraded' : 'healthy';

        $data = [
            'status'  => $status,
            'version' => App::version(),
            'checks'  => $checks,
        ];

        if ($status === 'healthy') {
            JsonResponse::ok($data);
        }

        JsonResponse::error(503, 'Serviço indisponível.');
    }

    private function checkDatabase(): string
    {
        try {
            Database::connection()->query('SELECT 1');
            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }

    private function checkStorage(): string
    {
        $path = App::logPath();
        $dir  = dirname($path);

        return (is_dir($dir) && is_writable($dir)) ? 'ok' : 'fail';
    }
}
