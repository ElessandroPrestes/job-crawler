<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\JsonResponse;
use PHPUnit\Framework\TestCase;
use Tests\Support\ResponseInterruptedException;

final class JsonResponseTest extends TestCase
{
    protected function setUp(): void
    {
        JsonResponse::$onSend = function (int $status, array $body, array $headers): never {
            throw new ResponseInterruptedException($status, $body, $headers);
        };
    }

    protected function tearDown(): void
    {
        JsonResponse::$onSend = null;
    }

    public function testOkReturns200WithSuccessTrue(): void
    {
        $e = $this->capture(static fn () => JsonResponse::ok(['id' => 1]));

        $this->assertSame(200, $e->status);
        $this->assertTrue($e->body['success']);
        $this->assertSame(['id' => 1], $e->body['data']);
    }

    public function testOkWithMetaIncludesMeta(): void
    {
        $meta = ['total' => 10, 'page' => 1];
        $e    = $this->capture(static fn () => JsonResponse::ok([], $meta));

        $this->assertSame($meta, $e->body['meta']);
    }

    public function testOkWithNullMetaOmitsMetaKey(): void
    {
        $e = $this->capture(static fn () => JsonResponse::ok(['x' => 1]));

        $this->assertArrayNotHasKey('meta', $e->body);
    }

    public function testCreatedReturns201WithLocationHeader(): void
    {
        $e = $this->capture(static fn () => JsonResponse::created(['id' => 7], '/api/alerts/7'));

        $this->assertSame(201, $e->status);
        $this->assertTrue($e->body['success']);
        $this->assertSame('/api/alerts/7', $e->headers['Location']);
    }

    public function testErrorReturnsCorrectStatusAndEnvelope(): void
    {
        $e = $this->capture(static fn () => JsonResponse::error(422, 'Campo inválido.'));

        $this->assertSame(422, $e->status);
        $this->assertFalse($e->body['success']);
        $this->assertSame('Campo inválido.', $e->body['error']);
        $this->assertSame(422, $e->body['code']);
    }

    public function testNotFoundReturns404(): void
    {
        $e = $this->capture(static fn () => JsonResponse::notFound());

        $this->assertSame(404, $e->status);
        $this->assertFalse($e->body['success']);
    }

    public function testBadRequestReturns400(): void
    {
        $e = $this->capture(static fn () => JsonResponse::badRequest('Parâmetro ausente.'));

        $this->assertSame(400, $e->status);
        $this->assertSame('Parâmetro ausente.', $e->body['error']);
    }

    public function testTooManyRequestsIncludesRetryAfterHeader(): void
    {
        $e = $this->capture(static fn () => JsonResponse::tooManyRequests(30));

        $this->assertSame(429, $e->status);
        $this->assertSame('30', $e->headers['Retry-After']);
    }

    public function testServerErrorReturns500(): void
    {
        $e = $this->capture(static fn () => JsonResponse::serverError());

        $this->assertSame(500, $e->status);
        $this->assertFalse($e->body['success']);
    }

    private function capture(callable $fn): ResponseInterruptedException
    {
        try {
            $fn();
        } catch (ResponseInterruptedException $e) {
            return $e;
        }

        $this->fail('JsonResponse::$onSend was not triggered.');
    }
}
