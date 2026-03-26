<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Middleware\InputSanitizer;
use PHPUnit\Framework\TestCase;

final class InputSanitizerTest extends TestCase
{
    private InputSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new InputSanitizer();
    }

    public function testStripsNulBytes(): void
    {
        $result = $this->sanitizer->sanitize("hello\0world");

        $this->assertSame('helloworld', $result);
    }

    public function testTrimsWhitespace(): void
    {
        $result = $this->sanitizer->sanitize('  hello  ');

        $this->assertSame('hello', $result);
    }

    public function testEscapesHtmlEntities(): void
    {
        $result = $this->sanitizer->sanitize('<script>alert("xss")</script>');

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testSanitizesArrayRecursively(): void
    {
        $input  = ['name' => '  foo  ', 'nested' => ['bar' => "\0baz"]];
        $result = $this->sanitizer->sanitize($input);

        $this->assertSame('foo', $result['name']);
        $this->assertSame('baz', $result['nested']['bar']);
    }

    public function testPassesThroughNonStringValues(): void
    {
        $this->assertSame(42, $this->sanitizer->sanitize(42));
        $this->assertNull($this->sanitizer->sanitize(null));
        $this->assertTrue($this->sanitizer->sanitize(true));
    }

    public function testHandlesMultipleNulBytes(): void
    {
        $result = $this->sanitizer->sanitize("a\0b\0c\0d");

        $this->assertSame('abcd', $result);
    }
}
