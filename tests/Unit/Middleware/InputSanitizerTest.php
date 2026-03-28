<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Middleware\InputSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InputSanitizerTest extends TestCase
{
    private InputSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new InputSanitizer();
    }

    // ── NUL bytes ───────────────────────────────────────────────────────────

    #[DataProvider('nullByteProvider')]
    public function testStripsNullBytes(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->sanitizer->sanitize($input));
    }

    public static function nullByteProvider(): array
    {
        return [
            'single NUL byte'        => ["hello\0world", 'helloworld'],
            'multiple NUL bytes'     => ["a\0b\0c", 'abc'],
            'NUL at start'           => ["\0foo", 'foo'],
            'NUL at end'             => ["foo\0", 'foo'],
            'only NUL bytes'         => ["\0\0\0", ''],
        ];
    }

    // ── XSS payloads ────────────────────────────────────────────────────────

    #[DataProvider('xssPayloadProvider')]
    public function testEscapesXssPayloads(string $payload): void
    {
        $result = $this->sanitizer->sanitize($payload);

        // After htmlspecialchars, no raw HTML opening tag should remain executable
        $this->assertDoesNotMatchRegularExpression('/<[a-zA-Z]/', $result);
    }

    public static function xssPayloadProvider(): array
    {
        return [
            'script tag'             => ['<script>alert("xss")</script>'],
            'script tag uppercase'   => ['<SCRIPT>alert(1)</SCRIPT>'],
            'img onerror'            => ['<img src=x onerror=alert(1)>'],
            'javascript href'        => ['<a href="javascript:alert(1)">click</a>'],
            'event handler'          => ['<div onload=alert(1)>'],
        ];
    }

    // ── Whitespace ──────────────────────────────────────────────────────────

    public function testTrimsLeadingAndTrailingWhitespace(): void
    {
        $this->assertSame('hello', $this->sanitizer->sanitize('  hello  '));
        $this->assertSame('hello', $this->sanitizer->sanitize("\thello\n"));
    }

    // ── Arrays ──────────────────────────────────────────────────────────────

    public function testSanitizesArrayRecursively(): void
    {
        $input = [
            'name'   => "  foo\0  ",
            'nested' => ['bar' => '<script>'],
        ];

        $result = $this->sanitizer->sanitize($input);

        $this->assertSame('foo', $result['name']);
        $this->assertStringNotContainsString('<script', $result['nested']['bar']);
    }

    // ── Non-string passthrough ───────────────────────────────────────────────

    #[DataProvider('nonStringProvider')]
    public function testPassesThroughNonStringValuesUnchanged(mixed $input): void
    {
        $this->assertSame($input, $this->sanitizer->sanitize($input));
    }

    public static function nonStringProvider(): array
    {
        return [
            'integer' => [42],
            'float'   => [3.14],
            'true'    => [true],
            'false'   => [false],
            'null'    => [null],
        ];
    }

    // ── Unicode ─────────────────────────────────────────────────────────────

    public function testUnicodeCharactersArePreserved(): void
    {
        $input  = 'Desenvolvedor Sênior — São Paulo';
        $result = $this->sanitizer->sanitize($input);

        $this->assertSame($input, $result);
    }
}
