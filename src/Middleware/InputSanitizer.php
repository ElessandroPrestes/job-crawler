<?php

declare(strict_types=1);

namespace App\Middleware;

final class InputSanitizer
{
    public function sanitize(mixed $input): mixed
    {
        if (is_string($input)) {
            return $this->sanitizeString($input);
        }

        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }

        return $input;
    }

    private function sanitizeString(string $value): string
    {
        $value = str_replace("\0", '', $value);
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = trim($value);
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return $value;
    }
}
