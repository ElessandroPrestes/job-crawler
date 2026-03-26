#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$opts = getopt('', ['help']);

if (isset($opts['help'])) {
    echo <<<HELP
    Usage: php notify.php

    Manually triggers e-mail notifications for all unnotified jobs
    that match active alert filters.

    Options:
      --help  Show this help

    HELP;
    exit(0);
}

try {
    $service = new App\Services\EmailService();
    $sent    = $service->notifyNewJobs();

    printf("Notificações enviadas: %d\n", $sent);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Erro: {$e->getMessage()}\n");
    exit(1);
}
