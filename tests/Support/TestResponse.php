<?php

declare(strict_types=1);

namespace Tests\Support;

final class TestResponse
{
    public function __construct(
        public readonly int   $status,
        public readonly array $body,
        public readonly array $headers = [],
    ) {}

    public function isSuccessful(): bool
    {
        return (bool) ($this->body['success'] ?? false);
    }

    public function data(): mixed
    {
        return $this->body['data'] ?? null;
    }

    public function meta(): ?array
    {
        return isset($this->body['meta']) && is_array($this->body['meta'])
            ? $this->body['meta']
            : null;
    }

    public function error(): ?string
    {
        return $this->body['error'] ?? null;
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function get(string $dotPath): mixed
    {
        $keys  = explode('.', $dotPath);
        $value = $this->body;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }
}
