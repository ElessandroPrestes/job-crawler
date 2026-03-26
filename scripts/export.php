#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$opts = getopt('', ['format:', 'output:', 'keyword:', 'location:', 'source:', 'help']);

if (isset($opts['help'])) {
    echo <<<HELP
    Usage: php export.php --format=<format> [OPTIONS]

    Options:
      --format=<format>      Output format: csv or json (required)
      --output=<path>        Output file path (default: stdout)
      --keyword=<keyword>    Filter by keyword
      --location=<location>  Filter by location
      --source=<source>      Filter by source
      --help                 Show this help

    HELP;
    exit(0);
}

$format = $opts['format'] ?? '';
$output = $opts['output'] ?? null;

if (!in_array($format, ['csv', 'json'], true)) {
    fwrite(STDERR, "Erro: --format deve ser csv ou json.\n");
    exit(1);
}

$filters = array_filter([
    'keyword'  => $opts['keyword'] ?? null,
    'location' => $opts['location'] ?? null,
    'source'   => $opts['source'] ?? null,
]);

try {
    $service = new App\Services\ExportService();
    $content = $service->export($format, $filters);

    if ($output !== null) {
        file_put_contents($output, $content);
        echo "Exportado para: {$output}\n";
    } else {
        echo $content;
    }

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Erro: {$e->getMessage()}\n");
    exit(1);
}
