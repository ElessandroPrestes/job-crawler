<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Config\Database;
use App\Controllers\AlertController;
use App\Controllers\CrawlerController;
use App\Controllers\ExportController;
use App\Controllers\HealthController;
use App\Controllers\JobController;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Router;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class ApplicationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Database::reset();
        $this->createSchema(Database::connection());

        JsonResponse::$onSend = function (int $status, array $body, array $headers): never {
            throw new ResponseInterruptedException($status, $body, $headers);
        };
    }

    protected function tearDown(): void
    {
        JsonResponse::$onSend = null;
        Database::reset();
        $_GET    = [];
        $_POST   = [];
        $_SERVER = [];
        parent::tearDown();
    }

    protected function get(string $uri, array $query = []): TestResponse
    {
        return $this->dispatch('GET', $uri, $query);
    }

    protected function post(string $uri, array $body = []): TestResponse
    {
        return $this->dispatch('POST', $uri, [], $body);
    }

    protected function delete(string $uri): TestResponse
    {
        return $this->dispatch('DELETE', $uri);
    }

    private function dispatch(string $method, string $uri, array $query = [], array $body = []): TestResponse
    {
        $_SERVER['REQUEST_METHOD']  = $method;
        $_SERVER['REQUEST_URI']     = $uri . ($query ? '?' . http_build_query($query) : '');
        $_SERVER['CONTENT_TYPE']    = 'application/x-www-form-urlencoded';
        $_SERVER['HTTP_ACCEPT']     = 'application/json';
        $_SERVER['REMOTE_ADDR']     = '127.0.0.1';
        $_GET  = $query;
        $_POST = $body;

        $request = new Request();
        $router  = $this->buildRouter();

        try {
            $router->dispatch($request);
        } catch (ResponseInterruptedException $e) {
            return new TestResponse($e->status, $e->body, $e->headers);
        }

        throw new \LogicException('Router terminated without sending a response.');
    }

    private function buildRouter(): Router
    {
        $router = new Router();
        $router->get('/health',                   [new HealthController(), 'index']);
        $router->get('/api/jobs',                 [new JobController(), 'index']);
        $router->get('/api/jobs/{id:\d+}',        [new JobController(), 'show']);
        $router->post('/api/crawl',               [new CrawlerController(), 'run']);
        $router->get('/api/crawl/logs',           [new CrawlerController(), 'logs']);
        $router->get('/api/alerts',               [new AlertController(), 'index']);
        $router->post('/api/alerts',              [new AlertController(), 'store']);
        $router->delete('/api/alerts/{id:\d+}',   [new AlertController(), 'destroy']);
        return $router;
    }

    private function createSchema(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS jobs (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                external_id   TEXT    NOT NULL,
                source        TEXT    NOT NULL,
                title         TEXT    NOT NULL,
                company       TEXT    NOT NULL,
                location      TEXT,
                contract_type TEXT,
                description   TEXT,
                requirements  TEXT,
                salary_range  TEXT,
                url           TEXT    NOT NULL DEFAULT \'\',
                published_at  TEXT,
                scraped_at    TEXT    NOT NULL DEFAULT (datetime(\'now\')),
                is_notified   INTEGER NOT NULL DEFAULT 0,
                UNIQUE (source, external_id)
            )
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS crawl_logs (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                source      TEXT    NOT NULL,
                keyword     TEXT,
                location    TEXT,
                status      TEXT    NOT NULL DEFAULT \'running\',
                jobs_found  INTEGER NOT NULL DEFAULT 0,
                jobs_new    INTEGER NOT NULL DEFAULT 0,
                error_msg   TEXT,
                started_at  TEXT    NOT NULL DEFAULT (datetime(\'now\')),
                finished_at TEXT
            )
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS alert_filters (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                email          TEXT    NOT NULL,
                keywords       TEXT    NOT NULL DEFAULT \'[]\',
                locations      TEXT,
                contract_types TEXT,
                is_active      INTEGER NOT NULL DEFAULT 1,
                created_at     TEXT    NOT NULL DEFAULT (datetime(\'now\'))
            )
        ');
    }

    protected function seedJob(array $overrides = []): int
    {
        $repo = new \App\Repositories\JobRepository();
        $data = array_merge([
            'external_id'   => 'ext-' . uniqid(),
            'source'        => 'linkedin',
            'title'         => 'PHP Developer',
            'company'       => 'Tech Corp',
            'location'      => 'São Paulo',
            'contract_type' => 'PJ',
            'url'           => 'https://linkedin.com/jobs/1',
        ], $overrides);
        $repo->upsert($data);
        return (int) \App\Config\Database::connection()->lastInsertId();
    }

    protected function seedAlert(array $overrides = []): \App\Models\AlertFilter
    {
        $repo = new \App\Repositories\AlertFilterRepository();
        return $repo->create(array_merge([
            'email'    => 'dev@test.com',
            'keywords' => ['PHP'],
        ], $overrides));
    }
}