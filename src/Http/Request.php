<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    private array $query;
    private array $body;
    private array $server;

    public function __construct()
    {
        $this->query  = $_GET ?? [];
        $this->body   = $this->parseBody();
        $this->server = $_SERVER ?? [];
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function uri(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $pos = strpos($uri, '?');
        return $pos !== false ? substr($uri, 0, $pos) : $uri;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        $value = $this->query[$key] ?? $default;
        return is_string($value) ? $this->sanitize($value) : $value;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $default;
        return is_string($value) ? $this->sanitize($value) : $value;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function body(): array
    {
        return $this->body;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? null;
        return $value !== null ? $this->sanitize((string) $value) : null;
    }

    public function ip(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($this->server[$key])) {
                $ip = trim(explode(',', (string) $this->server[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    private function parseBody(): array
    {
        $contentType = (string) ($this->server['CONTENT_TYPE'] ?? '');

        if (str_contains($contentType, 'application/json')) {
            $raw  = file_get_contents('php://input') ?: '{}';
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }

        return $_POST ?? [];
    }

    private function sanitize(string $value): string
    {
        $value = str_replace("\0", '', $value);
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        return trim($value);
    }
}
