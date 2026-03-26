<?php

declare(strict_types=1);

namespace App\Config;

final class App
{
    public static function env(): string
    {
        return (string) ($_ENV['APP_ENV'] ?? 'production');
    }

    public static function debug(): bool
    {
        return filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function version(): string
    {
        return (string) ($_ENV['APP_VERSION'] ?? '1.0.0');
    }

    public static function logPath(): string
    {
        return (string) ($_ENV['LOG_PATH'] ?? '/var/www/html/storage/logs/app.log');
    }

    public static function logLevel(): string
    {
        return (string) ($_ENV['LOG_LEVEL'] ?? 'info');
    }

    public static function exportPath(): string
    {
        return (string) ($_ENV['EXPORT_PATH'] ?? '/var/www/html/storage/exports');
    }

    public static function crawlDelayMs(): int
    {
        return (int) ($_ENV['CRAWL_DELAY_MS'] ?? 1500);
    }

    public static function crawlMaxPages(): int
    {
        return (int) ($_ENV['CRAWL_MAX_PAGES'] ?? 5);
    }

    public static function crawlAllowedDomains(): array
    {
        $raw = (string) ($_ENV['CRAWL_ALLOWED_DOMAINS'] ?? 'linkedin.com,indeed.com');
        return array_filter(array_map('trim', explode(',', $raw)));
    }
}
