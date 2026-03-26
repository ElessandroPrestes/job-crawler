<?php

declare(strict_types=1);

namespace Tests\Support;

final class ResponseInterruptedException extends \RuntimeException
{
    public function __construct(
        public readonly int    $status,
        public readonly array  $body,
        public readonly array  $headers,
    ) {
        parent::__construct();
    }
}
