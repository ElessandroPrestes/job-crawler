<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Job;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class JobTest extends TestCase
{
    // ── fromArray ───────────────────────────────────────────────────────────

    public function testFromArrayPopulatesAllFields(): void
    {
        $job = Job::fromArray($this->baseRow());

        $this->assertSame(42, $job->id);
        $this->assertSame('urn:li:123', $job->externalId);
        $this->assertSame('linkedin', $job->source);
        $this->assertSame('Senior PHP Developer', $job->title);
        $this->assertSame('Tech Corp', $job->company);
        $this->assertSame('São Paulo, SP', $job->location);
        $this->assertSame('PJ', $job->contractType);
        $this->assertSame('PHP 8, Laravel', $job->requirements);
        $this->assertFalse($job->isNotified);
    }

    public function testFromArrayWithMissingOptionalFieldsDefaultsToNull(): void
    {
        $job = Job::fromArray([
            'id'          => 1,
            'external_id' => 'x',
            'source'      => 'custom',
            'title'       => 'Dev',
            'company'     => 'Acme',
            'url'         => 'https://acme.com/job/1',
            'scraped_at'  => '2024-01-01 00:00:00',
        ]);

        $this->assertNull($job->location);
        $this->assertNull($job->contractType);
        $this->assertNull($job->description);
        $this->assertNull($job->requirements);
        $this->assertNull($job->salaryRange);
        $this->assertNull($job->publishedAt);
    }

    public function testIsNotifiedCastsIntegerCorrectly(): void
    {
        $notified    = Job::fromArray(array_merge($this->baseRow(), ['is_notified' => 1]));
        $notNotified = Job::fromArray(array_merge($this->baseRow(), ['is_notified' => 0]));

        $this->assertTrue($notified->isNotified);
        $this->assertFalse($notNotified->isNotified);
    }

    public function testToArrayRoundTripPreservesAllValues(): void
    {
        $job   = Job::fromArray($this->baseRow());
        $array = $job->toArray();

        $this->assertSame($job->id, $array['id']);
        $this->assertSame($job->externalId, $array['external_id']);
        $this->assertSame($job->source, $array['source']);
        $this->assertSame($job->title, $array['title']);
        $this->assertSame($job->location, $array['location']);
        $this->assertSame($job->contractType, $array['contract_type']);
        $this->assertSame($job->isNotified, $array['is_notified']);
    }

    // ── matchesKeywords ─────────────────────────────────────────────────────

    #[DataProvider('keywordMatchProvider')]
    public function testMatchesKeywordsBehavesCorrectly(array $keywords, string $title, string $requirements, bool $expected): void
    {
        $job = Job::fromArray(array_merge($this->baseRow(), [
            'title'        => $title,
            'requirements' => $requirements,
        ]));

        $this->assertSame($expected, $job->matchesKeywords($keywords));
    }

    public static function keywordMatchProvider(): array
    {
        return [
            'exact match in title'           => [['PHP'], 'PHP Developer', '', true],
            'case-insensitive match'          => [['php'], 'PHP Developer', '', true],
            'match in requirements'           => [['Laravel'], 'Backend Dev', 'PHP, Laravel, MySQL', true],
            'no match anywhere'              => [['Java'], 'PHP Developer', 'PHP, MySQL', false],
            'empty keywords always matches'  => [[], 'Java Developer', '', true],
            'first of multiple matches'      => [['Java', 'PHP'], 'PHP Developer', '', true],
            'none of multiple match'         => [['Java', 'Go'], 'PHP Developer', '', false],
            'partial substring match'        => [['veloper'], 'PHP Developer', '', true],
        ];
    }

    public function testMatchesKeywordsSearchesDescription(): void
    {
        $job = Job::fromArray(array_merge($this->baseRow(), [
            'title'       => 'Backend Engineer',
            'description' => 'Experience with Laravel framework required',
        ]));

        $this->assertTrue($job->matchesKeywords(['laravel']));
    }

    // ── matchesLocation ─────────────────────────────────────────────────────

    #[DataProvider('locationMatchProvider')]
    public function testMatchesLocationBehavesCorrectly(array $locations, ?string $jobLocation, bool $expected): void
    {
        $job = Job::fromArray(array_merge($this->baseRow(), ['location' => $jobLocation]));

        $this->assertSame($expected, $job->matchesLocation($locations));
    }

    public static function locationMatchProvider(): array
    {
        return [
            'exact match'                        => [['São Paulo'], 'São Paulo, SP', true],
            'partial match'                      => [['SP'], 'São Paulo, SP', true],
            'case-insensitive'                   => [['são paulo'], 'São Paulo, SP', true],
            'no match'                           => [['Rio'], 'São Paulo, SP', false],
            'empty locations always matches'     => [[], 'São Paulo', true],
            'null location always matches'       => [['São Paulo'], null, true],
            'first of multiple matches'          => [['Rio', 'São Paulo'], 'São Paulo, SP', true],
            'none of multiple match'             => [['Rio', 'Curitiba'], 'São Paulo, SP', false],
            'remote keyword match'               => [['Remote'], 'São Paulo, SP (Remoto)', false],
            'remote keyword case insensitive'    => [['remoto'], 'São Paulo, SP (Remoto)', true],
        ];
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function baseRow(): array
    {
        return [
            'id'            => 42,
            'external_id'   => 'urn:li:123',
            'source'        => 'linkedin',
            'title'         => 'Senior PHP Developer',
            'company'       => 'Tech Corp',
            'location'      => 'São Paulo, SP',
            'contract_type' => 'PJ',
            'description'   => 'Vaga de backend PHP',
            'requirements'  => 'PHP 8, Laravel',
            'salary_range'  => null,
            'url'           => 'https://linkedin.com/jobs/123',
            'published_at'  => null,
            'scraped_at'    => '2024-01-01 10:00:00',
            'is_notified'   => 0,
        ];
    }
}
