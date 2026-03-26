<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\JsonResponse;

final class RateLimiter
{
    private const DEFAULT_LIMIT = 60;
    private const WINDOW        = 60;

    public function check(string $ip, int $limit = self::DEFAULT_LIMIT): void
    {
        $key       = 'rl_' . md5($ip);
        $now       = time();
        $windowEnd = $now - self::WINDOW;

        if (!function_exists('apcu_fetch') || !apcu_enabled()) {
            $this->sendHeaders($limit, $limit - 1, $now + self::WINDOW);
            return;
        }

        $hits = apcu_fetch($key);
        if ($hits === false) {
            $hits = [];
        }

        $hits = array_filter($hits, static fn (int $t) => $t > $windowEnd);
        $hits[] = $now;

        apcu_store($key, $hits, self::WINDOW * 2);

        $remaining = $limit - count($hits);
        $reset     = $now + self::WINDOW;

        $this->sendHeaders($limit, max(0, $remaining), $reset);

        if ($remaining < 0) {
            JsonResponse::tooManyRequests(self::WINDOW);
        }
    }

    private function sendHeaders(int $limit, int $remaining, int $reset): void
    {
        header("X-RateLimit-Limit: {$limit}");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: {$reset}");
    }
}
