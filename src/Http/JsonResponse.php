<?php

declare(strict_types=1);

namespace App\Http;

final class JsonResponse
{
    /** @var (callable(int, array<string,mixed>, array<string,string>): never)|null */
    public static $onSend = null;

    public static function ok(mixed $data, array $meta = [], array $headers = []): never
    {
        self::send(200, ['success' => true, 'data' => $data, 'meta' => $meta ?: null], $headers);
    }

    public static function created(mixed $data, string $location): never
    {
        self::send(201, ['success' => true, 'data' => $data], ['Location' => $location]);
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }

    public static function error(int $status, string $message): never
    {
        self::send($status, ['success' => false, 'error' => $message, 'code' => $status]);
    }

    public static function badRequest(string $message = 'Requisição inválida.'): never
    {
        self::error(400, $message);
    }

    public static function notFound(string $message = 'Recurso não encontrado.'): never
    {
        self::error(404, $message);
    }

    public static function unprocessable(string $message): never
    {
        self::error(422, $message);
    }

    public static function tooManyRequests(int $retryAfter = 60): never
    {
        self::send(
            429,
            ['success' => false, 'error' => "Limite de requisições excedido. Tente novamente em {$retryAfter} segundos.", 'code' => 429],
            ['Retry-After' => (string) $retryAfter],
        );
    }

    public static function serverError(string $message = 'Erro interno do servidor.'): never
    {
        self::error(500, $message);
    }

    private static function send(int $status, array $body, array $extraHeaders = []): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($extraHeaders as $name => $value) {
            header("{$name}: {$value}");
        }

        $payload = array_filter($body, static fn ($v) => $v !== null);

        if (self::$onSend !== null) {
            (self::$onSend)($status, $payload, $extraHeaders);
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
