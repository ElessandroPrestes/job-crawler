<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\JsonResponse;

final class CsrfGuard
{
    private const SESSION_KEY = '_csrf_token';
    private const HEADER_KEY  = 'X-CSRF-Token';

    public function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public function verify(string $incoming): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $stored = $_SESSION[self::SESSION_KEY] ?? '';

        if (!hash_equals($stored, $incoming)) {
            JsonResponse::error(403, 'Token CSRF inválido.');
        }
    }

    public function verifyFromHeader(array $serverVars): void
    {
        $token = $serverVars['HTTP_X_CSRF_TOKEN'] ?? '';
        $this->verify((string) $token);
    }
}
