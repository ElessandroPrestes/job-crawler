<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\CrawlLog;
use PHPUnit\Framework\TestCase;

final class CrawlLogTest extends TestCase
{
    public function testFromArrayAndToArray(): void
    {
        $data = [
            'id'          => 42,
            'source'      => 'linkedin',
            'keyword'     => 'PHP Developer',
            'location'    => 'São Paulo',
            'status'      => 'success',
            'jobs_found'  => 15,
            'jobs_new'    => 5,
            'error_msg'   => null,
            'started_at'  => '2026-03-27 10:00:00',
            'finished_at' => '2026-03-27 10:00:30',
        ];

        $log    = CrawlLog::fromArray($data);
        $result = $log->toArray();

        $this->assertSame(42, $log->id);
        $this->assertSame('linkedin', $log->source);
        $this->assertSame('PHP Developer', $log->keyword);
        $this->assertSame('São Paulo', $log->location);
        $this->assertSame('success', $log->status);
        $this->assertSame(15, $log->jobsFound);
        $this->assertSame(5, $log->jobsNew);
        $this->assertNull($log->errorMsg);
        $this->assertSame('2026-03-27 10:00:00', $log->startedAt);
        $this->assertSame('2026-03-27 10:00:30', $log->finishedAt);

        // toArray deve conter as mesmas chaves
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('source', $result);
        $this->assertArrayHasKey('keyword', $result);
        $this->assertArrayHasKey('location', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('jobs_found', $result);
        $this->assertArrayHasKey('jobs_new', $result);
        $this->assertArrayHasKey('error_msg', $result);
        $this->assertArrayHasKey('started_at', $result);
        $this->assertArrayHasKey('finished_at', $result);

        $this->assertSame(42, $result['id']);
        $this->assertSame('linkedin', $result['source']);
        $this->assertSame(15, $result['jobs_found']);
        $this->assertSame(5, $result['jobs_new']);
    }

    public function testFromArrayWithNullFields(): void
    {
        $data = [
            'id'          => 1,
            'source'      => 'indeed',
            'status'      => 'running',
            'jobs_found'  => 0,
            'jobs_new'    => 0,
            'started_at'  => '2026-03-27 09:00:00',
            // keyword, location, error_msg, finished_at são null / ausentes
        ];

        $log = CrawlLog::fromArray($data);

        $this->assertSame(1, $log->id);
        $this->assertSame('indeed', $log->source);
        $this->assertNull($log->keyword);
        $this->assertNull($log->location);
        $this->assertNull($log->errorMsg);
        $this->assertNull($log->finishedAt);
        $this->assertSame('running', $log->status);

        $arr = $log->toArray();
        $this->assertNull($arr['keyword']);
        $this->assertNull($arr['location']);
        $this->assertNull($arr['error_msg']);
        $this->assertNull($arr['finished_at']);
    }

    public function testDefaultValuesWhenArrayIsEmpty(): void
    {
        $log = CrawlLog::fromArray([]);

        $this->assertSame(0, $log->id);
        $this->assertSame('', $log->source);
        $this->assertSame('running', $log->status);
        $this->assertSame(0, $log->jobsFound);
        $this->assertSame(0, $log->jobsNew);
    }
}
