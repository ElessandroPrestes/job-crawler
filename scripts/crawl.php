#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$opts = getopt('', ['source:', 'keyword:', 'location:', 'max-pages:', 'url:', 'help']);

if (isset($opts['help'])) {
    echo <<<HELP
    Usage: php crawl.php --source=<source> --keyword=<keyword> [OPTIONS]

    Options:
      --source=<source>      Platform to crawl: linkedin, indeed, custom (required)
      --keyword=<keyword>    Search keyword (required)
      --location=<location>  Location filter (optional)
      --max-pages=<n>        Maximum pages to crawl (default: 3)
      --url=<url>            Base URL for custom driver (required when source=custom)
      --help                 Show this help

    HELP;
    exit(0);
}

$source   = $opts['source'] ?? '';
$keyword  = $opts['keyword'] ?? '';
$location = $opts['location'] ?? null;
$maxPages = (int) ($opts['max-pages'] ?? 3);
$url      = $opts['url'] ?? null;

if ($source === '' || $keyword === '') {
    fwrite(STDERR, "Erro: --source e --keyword são obrigatórios.\n");
    exit(1);
}

try {
    $service = new App\Services\CrawlerService();
    $result  = $service->execute(array_filter([
        'source'    => $source,
        'keyword'   => $keyword,
        'location'  => $location,
        'max_pages' => $maxPages,
        'url'       => $url,
    ]));

    printf(
        "Crawl concluído: %d vagas encontradas, %d novas, %d notificações enviadas (%.0fms)\n",
        $result['jobs_found'],
        $result['jobs_new'],
        $result['notifications_sent'],
        $result['duration_ms']
    );

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Erro: {$e->getMessage()}\n");
    exit(1);
}
