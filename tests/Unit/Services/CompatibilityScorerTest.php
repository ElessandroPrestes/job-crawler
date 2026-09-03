<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CompatibilityScorer;
use PHPUnit\Framework\TestCase;

final class CompatibilityScorerTest extends TestCase
{
    private CompatibilityScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new CompatibilityScorer();
    }

    public function testPhpTitleWithoutDescriptionScores100Percent(): void
    {
        $job = [
            'title'        => 'Desenvolvedor PHP Pleno',
            'description'  => '',
            'requirements' => '',
        ];

        $result = $this->scorer->score($job);

        $this->assertSame(100, $result['score']);
        $this->assertContains('php', $result['matched']);
        $this->assertNull($result['disqualified_by']);
    }

    public function testNodeTitleWithoutDescriptionScoresAtLeast80Percent(): void
    {
        $job1 = [
            'title'        => 'Desenvolvedor Node.js Senior',
            'description'  => '',
            'requirements' => '',
        ];
        $result1 = $this->scorer->score($job1);
        $this->assertGreaterThanOrEqual(80, $result1['score']);

        $job2 = [
            'title'        => 'Backend Developer Node',
            'description'  => '',
            'requirements' => '',
        ];
        $result2 = $this->scorer->score($job2);
        $this->assertGreaterThanOrEqual(80, $result2['score']);
    }

    public function testLaravelTitleWithoutDescriptionScores100Percent(): void
    {
        $job = [
            'title'        => 'Programador Laravel',
            'description'  => '',
            'requirements' => '',
        ];

        $result = $this->scorer->score($job);

        $this->assertSame(100, $result['score']);
        $this->assertContains('laravel', $result['matched']);
        $this->assertNull($result['disqualified_by']);
    }

    public function testDisqualifiedTitlesScoreZero(): void
    {
        $pythonJob = ['title' => 'Desenvolvedor Python', 'description' => '', 'requirements' => ''];
        $resPython = $this->scorer->score($pythonJob);
        $this->assertSame(0, $resPython['score']);
        $this->assertSame('python', $resPython['disqualified_by']);

        $reactJob = ['title' => 'Desenvolvedor React Front-end', 'description' => '', 'requirements' => ''];
        $resReact = $this->scorer->score($reactJob);
        $this->assertSame(0, $resReact['score']);
        $this->assertSame('react', $resReact['disqualified_by']);

        $javaJob = ['title' => 'Desenvolvedor Java Spring', 'description' => '', 'requirements' => ''];
        $resJava = $this->scorer->score($javaJob);
        $this->assertSame(0, $resJava['score']);
    }

    public function testGenericTitleWithoutStackScoresZero(): void
    {
        $job = [
            'title'        => 'Desenvolvedor de Software Backend',
            'description'  => '',
            'requirements' => '',
        ];

        $result = $this->scorer->score($job);

        $this->assertSame(0, $result['score']);
        $this->assertEmpty($result['matched']);
    }

    public function testJobWithDescriptionUsesBaseline(): void
    {
        $job = [
            'title'        => 'Desenvolvedor Backend',
            'description'  => 'Vaga para atuar com Node.js, Express, Docker e MySQL em ambiente de microsserviços.',
            'requirements' => 'Experiência sólida com TypeScript, AWS e APIs REST.',
        ];

        $result = $this->scorer->score($job);

        $this->assertGreaterThanOrEqual(80, $result['score']);
        $this->assertContains('node.js', $result['matched']);
        $this->assertContains('docker', $result['matched']);
        $this->assertContains('mysql', $result['matched']);
    }
}
