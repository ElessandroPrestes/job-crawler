<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER = [];
        $_GET    = [];
        $_POST   = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = [];
        $_GET    = [];
        $_POST   = [];
        parent::tearDown();
    }

    public function testMethodReturnsUppercase(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $request = new Request();
        $this->assertSame('POST', $request->method());
    }

    public function testMethodDefaultsToGet(): void
    {
        $request = new Request();
        $this->assertSame('GET', $request->method());
    }

    public function testUriStripQueryString(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/jobs?page=2&keyword=PHP';
        $request = new Request();
        $this->assertSame('/api/jobs', $request->uri());
    }

    public function testUriWithoutQueryString(): void
    {
        $_SERVER['REQUEST_URI'] = '/health';
        $request = new Request();
        $this->assertSame('/health', $request->uri());
    }

    public function testUriDefaultsToSlash(): void
    {
        $request = new Request();
        $this->assertSame('/', $request->uri());
    }

    public function testQueryReturnsValueFromGet(): void
    {
        $_GET['keyword'] = 'PHP Developer';
        $request = new Request();
        $this->assertSame('PHP Developer', $request->query('keyword'));
    }

    public function testQueryReturnsDefaultWhenKeyMissing(): void
    {
        $request = new Request();
        $this->assertSame('default', $request->query('missing', 'default'));
        $this->assertNull($request->query('missing'));
    }

    public function testQuerySanitizesNullBytes(): void
    {
        $_GET['key'] = "value\0injected";
        $request = new Request();
        $this->assertSame('valueinjected', $request->query('key'));
    }

    public function testInputReturnsValueFromPost(): void
    {
        $_POST['email'] = 'test@example.com';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $request = new Request();
        $this->assertSame('test@example.com', $request->input('email'));
    }

    public function testBodyReturnsPostArray(): void
    {
        $_POST = ['a' => '1', 'b' => '2'];
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $request = new Request();
        $body = $request->body();
        $this->assertArrayHasKey('a', $body);
        $this->assertArrayHasKey('b', $body);
    }

    public function testAllMergesQueryAndBody(): void
    {
        $_GET['page'] = '1';
        $_POST['name'] = 'test';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $request = new Request();
        $all = $request->all();
        $this->assertArrayHasKey('page', $all);
        $this->assertArrayHasKey('name', $all);
    }

    public function testHeaderReturnsValueFromServer(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $request = new Request();
        $this->assertSame('application/json', $request->header('Accept'));
    }

    public function testHeaderReturnsNullWhenMissing(): void
    {
        $request = new Request();
        $this->assertNull($request->header('X-Not-Present'));
    }

    public function testIpFromRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $request = new Request();
        $this->assertSame('192.168.1.100', $request->ip());
    }

    public function testIpFromXForwardedFor(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.10, 10.0.0.1';
        $request = new Request();
        $this->assertSame('203.0.113.10', $request->ip());
    }

    public function testIpFallsBackToZeroWhenInvalid(): void
    {
        // Sem nenhum header de IP válido
        $request = new Request();
        $this->assertSame('0.0.0.0', $request->ip());
    }

    public function testIpFromClientIp(): void
    {
        $_SERVER['HTTP_CLIENT_IP'] = '198.51.100.25';
        $request = new Request();
        $this->assertSame('198.51.100.25', $request->ip());
    }
}
