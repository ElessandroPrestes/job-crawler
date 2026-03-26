<?php

declare(strict_types=1);

namespace App\Config;

use PDO;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::build();
        }

        return self::$instance;
    }

    private static function build(): PDO
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . ($_ENV['DB_DATABASE'] ?? ':memory:');
            $pdo = new PDO($dsn);
        } else {
            $host = $_ENV['DB_HOST'] ?? 'mysql';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $name = $_ENV['DB_DATABASE'] ?? 'job_crawler';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
            $pdo = new PDO(
                $dsn,
                $_ENV['DB_USERNAME'] ?? 'crawler',
                $_ENV['DB_PASSWORD'] ?? '',
            );
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
