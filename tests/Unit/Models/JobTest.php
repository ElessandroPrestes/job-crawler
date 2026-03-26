<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Job;
use PHPUnit\Framework\TestCase;

final class JobTest extends TestCase
{
    private function makeJob(array $overrides = []): Job
    {
        return Job::fromArray(array_merge([
            'id'            => 1,
            'external_id'   => 'ext-001',
            'source'        => 'linkedin',
            'title'         => 'Desenvolvedor PHP Sênior',
            'company'       => 'Tech Corp',
            'location'      => 'São Paulo, SP (Remoto)',
            'contract_type' => 'PJ',
            'description'   => 'Vaga para desenvolvedor backend',
            'requirements'  => 'PHP 8, Laravel, MySQL',
            'salary_range'  => null,
            'url'           => 'https://linkedin.com/jobs/view/001',
            'published_at'  => null,
            'scraped_at'    => '2024-01-01 10:00:00',
            'is_notified'   => 0,
        ], $overrides));
    }

    public function testFromArrayPopulatesAllFields(): void
    {
        $job = $this->makeJob();

        $this->assertSame(1, $job->id);
        $this->assertSame('ext-001', $job->externalId);
        $this->assertSame('linkedin', $job->source);
        $this->assertSame('Desenvolvedor PHP Sênior', $job->title);
        $this->assertSame('Tech Corp', $job->company);
        $this->assertFalse($job->isNotified);
    }

    public function testToArrayRoundTrip(): void
    {
        $job   = $this->makeJob();
        $array = $job->toArray();

        $this->assertSame($job->id, $array['id']);
        $this->assertSame($job->title, $array['title']);
        $this->assertSame($job->source, $array['source']);
    }

    public function testMatchesKeywordsReturnsTrueOnMatch(): void
    {
        $job = $this->makeJob(['title' => 'PHP Developer Senior']);

        $this->assertTrue($job->matchesKeywords(['php']));
        $this->assertTrue($job->matchesKeywords(['developer']));
    }

    public function testMatchesKeywordsReturnsFalseOnNoMatch(): void
    {
        $job = $this->makeJob(['title' => 'Java Developer']);

        $this->assertFalse($job->matchesKeywords(['php']));
    }

    public function testMatchesKeywordsReturnsTrueForEmptyList(): void
    {
        $job = $this->makeJob();

        $this->assertTrue($job->matchesKeywords([]));
    }

    public function testMatchesLocationReturnsTrueOnPartialMatch(): void
    {
        $job = $this->makeJob(['location' => 'São Paulo, SP']);

        $this->assertTrue($job->matchesLocation(['São Paulo']));
        $this->assertTrue($job->matchesLocation(['SP']));
    }

    public function testMatchesLocationReturnsFalseOnNoMatch(): void
    {
        $job = $this->makeJob(['location' => 'Rio de Janeiro']);

        $this->assertFalse($job->matchesLocation(['São Paulo']));
    }

    public function testMatchesLocationReturnsTrueForEmptyList(): void
    {
        $job = $this->makeJob(['location' => 'São Paulo']);

        $this->assertTrue($job->matchesLocation([]));
    }

    public function testMatchesLocationReturnsTrueWhenLocationIsNull(): void
    {
        $job = $this->makeJob(['location' => null]);

        $this->assertTrue($job->matchesLocation(['São Paulo']));
    }

    public function testMatchesKeywordsSearchesRequirements(): void
    {
        $job = $this->makeJob(['title' => 'Backend Dev', 'requirements' => 'Laravel, MySQL, Redis']);

        $this->assertTrue($job->matchesKeywords(['laravel']));
        $this->assertTrue($job->matchesKeywords(['redis']));
    }
}
