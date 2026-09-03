<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Repositories\CrawlLogRepository;
use App\Repositories\JobRepository;
use App\Exceptions\CrawlerException;
use App\Services\Drivers\LinkedInDriver;
use App\Services\Drivers\IndeedDriver;
use App\Services\Drivers\GupyDriver;
use App\Services\Drivers\GeekHunterDriver;
use App\Services\Drivers\ProgramathorDriver;
use App\Services\Drivers\VagasDriver;
use App\Services\Drivers\GlassdoorDriver;
use App\Services\Drivers\SolidesDriver;
use App\Services\Drivers\CathoDriver;
use App\Services\Drivers\InfoJobsDriver;
use App\Services\Drivers\JoobleDriver;
use App\Services\Drivers\TramposDriver;
use App\Services\Drivers\JobbolDriver;

/**
 * Orquestra o crawling de todos os 13 sources em ordem de tier,
 * aplica o pipeline de filtros (BR + compatibilidade currículo) e
 * garante deduplicação cross-source via índice UNIQUE no banco.
 *
 * SPEC-014
 */
final class MultiSourceCrawlerService
{
    private EmailService       $email;
    private CrawlerService     $crawlerService;

    /**
     * Sources organizados por tier — executados nesta ordem.
     * Tier 1 tem prioridade: se uma vaga já entrou pelo LinkedIn,
     * a mesma vaga do Indeed será deduplicada automaticamente pelo índice.
     */
    private const SOURCES_BY_TIER = [
        // Tier 1 — obrigatório
        'linkedin', 'indeed', 'gupy', 'glassdoor',
        
        // Tier 2 — alta relevância
        'geekhunter', 'vagas', 'programathor', 'catho',
        
        // Tier 3 — massivos / menor precisão
        'infojobs', 'solides', 'trampos', 'jobbol', 'empregos', 'jooble'
    ];

    public function __construct()
    {
        $this->email          = new EmailService();
        $this->crawlerService = new CrawlerService();
    }

    /**
     * Executa crawling em todos os 13 sources e retorna sumário consolidado.
     *
     * @param array $params ['keyword', 'location', 'max_pages']
     * @return array sumário geral + resultados por source
     */
    public function executeAll(array $params): array
    {
        $location = $params['location'] ?? 'brasil';
        $maxPages = min((int) ($params['max_pages'] ?? 2), App::crawlMaxPages());

        $startAll    = microtime(true);
        $totalFound  = 0;
        $totalNew    = 0;
        $bySource    = [];
        $errors      = [];

        foreach (ResumeProfile::SEARCH_TERMS as $keyword) {
            foreach (self::SOURCES_BY_TIER as $source) {
                $start = microtime(true);
                try {
                    $result = $this->crawlerService->execute([
                        'source'    => $source,
                        'keyword'   => $keyword,
                        'location'  => $location,
                        'max_pages' => $maxPages,
                    ]);

                    if (!isset($bySource[$source])) {
                        $bySource[$source] = [
                            'status'     => 'success',
                            'jobs_found' => 0,
                            'jobs_new'   => 0,
                            'duration_ms'=> 0,
                        ];
                    }

                    $bySource[$source]['jobs_found']  += $result['jobs_found'];
                    $bySource[$source]['jobs_new']    += $result['jobs_new'];
                    $bySource[$source]['duration_ms'] += $result['duration_ms'];

                    $totalFound += $result['jobs_found'];
                    $totalNew   += $result['jobs_new'];

                } catch (CrawlerException $e) {
                    $errors[] = "[{$source} | {$keyword}] " . $e->getMessage();
                    if (!isset($bySource[$source])) {
                        $bySource[$source] = [
                            'status'      => 'error',
                            'jobs_found'  => 0,
                            'jobs_new'    => 0,
                            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                            'error'       => $e->getMessage(),
                        ];
                    }
                } catch (\Throwable $e) {
                    $errors[] = "[{$source} | {$keyword}] Erro inesperado: " . $e->getMessage();
                    if (!isset($bySource[$source])) {
                        $bySource[$source] = [
                            'status'      => 'error',
                            'jobs_found'  => 0,
                            'jobs_new'    => 0,
                            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                            'error'       => $e->getMessage(),
                        ];
                    }
                }
            }
        }

        // Notificar novas vagas (apenas uma vez no final)
        $notified = 0;
        try {
            $notified = $this->email->notifyNewJobs();
        } catch (\Throwable) {}

        return [
            'status'             => empty($errors) ? 'success' : 'partial',
            'sources_count'      => count(self::SOURCES_BY_TIER),
            'jobs_found'         => $totalFound,
            'jobs_new'           => $totalNew,
            'notifications_sent' => $notified,
            'duration_ms'        => (int) ((microtime(true) - $startAll) * 1000),
            'by_source'          => $bySource,
            'errors'             => $errors,
        ];
    }
}
