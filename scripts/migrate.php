#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$opts = getopt('', ['help']);

if (isset($opts['help'])) {
    echo <<<HELP
    Usage: php migrate.php

    Executes pending SQL migrations from mysql/migrations/ in lexicographic order.

    Options:
      --help  Show this help

    HELP;
    exit(0);
}

$migrationsDir = dirname(__DIR__) . '/mysql/migrations';

if (!is_dir($migrationsDir)) {
    echo "Nenhuma migration encontrada em {$migrationsDir}.\n";
    exit(0);
}

$files = glob($migrationsDir . '/*.sql');
sort($files);

if (empty($files)) {
    echo "Nenhum arquivo .sql encontrado.\n";
    exit(0);
}

try {
    $pdo = App\Config\Database::connection();

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS migrations (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            filename   VARCHAR(255) NOT NULL UNIQUE,
            applied_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )
    ');

    $applied = $pdo->query('SELECT filename FROM migrations')->fetchAll(\PDO::FETCH_COLUMN);
    $applied = array_flip($applied);

    foreach ($files as $file) {
        $name = basename($file);

        if (isset($applied[$name])) {
            echo "  [skip] {$name}\n";
            continue;
        }

        $sql = file_get_contents($file);
        $pdo->exec($sql);

        $stmt = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
        $stmt->execute([$name]);

        echo "  [done] {$name}\n";
    }

    echo "Migrations concluídas.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Erro: {$e->getMessage()}\n");
    exit(1);
}
