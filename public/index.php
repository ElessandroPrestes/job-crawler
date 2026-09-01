<?php

declare(strict_types=1);

use App\Config\App;
use App\Controllers\AlertController;
use App\Controllers\CrawlerController;
use App\Controllers\ExportController;
use App\Controllers\HealthController;
use App\Controllers\JobController;
use App\Exceptions\HttpException;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Router;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

set_exception_handler(static function (Throwable $e): void {
    if ($e instanceof HttpException) {
        JsonResponse::error($e->getStatusCode(), $e->getMessage());
    }

    if (App::debug()) {
        JsonResponse::serverError($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }

    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    JsonResponse::serverError();
});

$request = new Request();
$router  = new Router();

$router->get('/health', [new HealthController(), 'index']);

$router->get('/api/jobs',          [new JobController(), 'index']);
$router->get('/api/jobs/{id:\d+}', [new JobController(), 'show']);

$router->post('/api/crawl',      [new CrawlerController(), 'run']);
$router->post('/api/crawl/all',  [new CrawlerController(), 'runAll']);
$router->get('/api/crawl/logs',  [new CrawlerController(), 'logs']);

$router->get('/api/alerts',              [new AlertController(), 'index']);
$router->post('/api/alerts',             [new AlertController(), 'store']);
$router->delete('/api/alerts/{id:\d+}',  [new AlertController(), 'destroy']);

$router->get('/api/export', [new ExportController(), 'download']);

$router->dispatch($request);