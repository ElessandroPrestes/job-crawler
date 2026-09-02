<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Exceptions\CrawlerException;
use App\Exceptions\ValidationException;
use App\Repositories\CrawlLogRepository;
use App\Repositories\JobRepository;
use App\Services\Drivers\CustomDriver;
use App\Services\Drivers\IndeedDriver;
use App\Services\Drivers\LinkedInDriver;
use App\Services\Drivers\GupyDriver;
use App\Services\Drivers\VagasDriver;
use App\Services\Drivers\CathoDriver;
use App\Services\Drivers\InfoJobsDriver;
use App\Services\Drivers\GlassdoorDriver;
use App\Services\Drivers\SolidesDriver;
use App\Services\Drivers\ProgramathorDriver;
use App\Services\Drivers\GeekHunterDriver;
use App\Services\Drivers\TramposDriver;
use App\Services\Drivers\JoobleDriver;
use App\Services\Drivers\EmpregosDriver;
use App\Services\CompatibilityScorer;
use App\Services\ResumeProfile;

final class CrawlerService
{
    private JobRepository      $jobs;
    private CrawlLogRepository $logs;
    private EmailService       $email;

    public function __construct()
    {
        $this->jobs  = new JobRepository();
        $this->logs  = new CrawlLogRepository();
        $this->email = new EmailService();
    }

    public function execute(array $params): array
    {
        $source   = $params['source'] ?? '';
        $keyword  = $params['keyword'] ?? '';
        $location = $params['location'] ?? null;
        $maxPages = min((int) ($params['max_pages'] ?? 3), App::crawlMaxPages());

        $validSources = [
            'linkedin', 'indeed', 'custom',
            'gupy', 'vagas', 'catho', 'infojobs', 'glassdoor',
            'solides', 'programathor', 'geekhunter', 'trampos',
            'jooble', 'empregos'
        ];
        if (!in_array($source, $validSources, true)) {
            throw new ValidationException("Source inválido: '{$source}'.");
        }

        if (strlen($keyword) < 2) {
            throw new ValidationException("O campo 'keyword' deve ter ao menos 2 caracteres.");
        }

        if (isset($params['url'])) {
            $this->validateUrl((string) $params['url']);
        }

        $logId = $this->logs->start($source, $keyword, $location);
        $start = microtime(true);

        try {
            $rawJobs = $this->fetchJobs($source, $keyword, $location, $maxPages, $params['url'] ?? null);
            $rawJobs = $this->filterRelevant($rawJobs);
            $rawJobs = $this->scoreAndFilter($rawJobs); // SPEC-013: descarta vagas < 80% de match
        } catch (CrawlerException $e) {
            $this->logs->finish($logId, 'failed', 0, 0, $e->getMessage());
            throw $e;
        }

        $jobsFound = count($rawJobs);
        $jobsNew   = 0;

        foreach ($rawJobs as $job) {
            $inserted = $this->jobs->upsert($job);
            if ($inserted > 0) {
                $jobsNew++;
            }
        }

        $notified = $this->email->notifyNewJobs();

        $this->logs->finish($logId, 'success', $jobsFound, $jobsNew);

        return [
            'log_id'             => $logId,
            'source'             => $source,
            'keyword'            => $keyword,
            'location'           => $location,
            'status'             => 'success',
            'jobs_found'         => $jobsFound,
            'jobs_new'           => $jobsNew,
            'notifications_sent' => $notified,
            'duration_ms'        => (int) ((microtime(true) - $start) * 1000),
        ];
    }

    /**
     * Pontua as vagas por compatibilidade com o currículo e descarta as abaixo do mínimo.
     * Ordena as aprovadas por score decrescente (melhor match primeiro).
     * SPEC-013 / LLM Classifier
     */
    private function scoreAndFilter(array $jobs): array
    {
        $scorer  = new LlmCompatibilityScorer();
        $scoredJobs = $scorer->scoreBatch($jobs);
        
        $results = [];
        foreach ($scoredJobs as $job) {
            // Usa 80 como fallback se não houver MIN_SCORE, mas referenciando ResumeProfile
            if (($job['compatibility_score'] ?? 0) < ResumeProfile::MIN_SCORE) {
                continue;
            }
            $results[] = $job;
        }

        usort($results, static fn ($a, $b) => ($b['compatibility_score'] ?? 0) <=> ($a['compatibility_score'] ?? 0));

        return $results;
    }

    /**
     * Termos que confirmam que a vaga é do Brasil ou aceita candidatos BR.
     */
    private const BRAZIL_TERMS = [
        'brasil', 'brazil', 'br',
        'são paulo', 'sao paulo', 'rio de janeiro',
        'belo horizonte', 'curitiba', 'brasília', 'brasilia',
        'florianópolis', 'florianopolis', 'porto alegre', 'salvador',
        'fortaleza', 'recife', 'manaus', 'goiânia', 'goiania',
        'remoto', 'remote', 'home office', 'híbrido', 'hibrido',
        '100% remoto', 'trabalho remoto',
    ];

    /**
     * Termos que indicam que a vaga é explicitamente internacional/fora do Brasil.
     * Se um desses aparecer na localização, a vaga é descartada.
     */
    private const INTERNATIONAL_TERMS = [
        'united states', 'united kingdom', 'worldwide', 'global',
        'europe', 'north america', 'latam', 'anywhere',
        'canada', 'germany', 'france', 'spain', 'portugal',
        'india', 'mexico', 'argentina', 'colombia', 'chile',
        ', us', '(us)', '(usa)', ', uk', '(uk)',
    ];

    private function filterRelevant(array $jobs): array
    {
        return array_values(array_filter($jobs, static function (array $job): bool {
            // Filtro temporal: descartar vagas com mais de 3 dias
            if (!empty($job['published_at'])) {
                $ts = strtotime($job['published_at']);
                if ($ts !== false && $ts < strtotime('-3 days')) {
                    return false;
                }
            }

            $loc = strtolower(trim($job['location'] ?? ''));

            // Sem localização: o LinkedIn já filtrou por geoId=BR, aceitar
            if ($loc === '') {
                return true;
            }

            // Rejeitar explicitamente vagas com localização internacional conhecida
            foreach (self::INTERNATIONAL_TERMS as $intl) {
                if (str_contains($loc, $intl)) {
                    return false;
                }
            }

            // Aceitar apenas se encontrar algum termo brasileiro na localização
            foreach (self::BRAZIL_TERMS as $term) {
                if (str_contains($loc, $term)) {
                    return true;
                }
            }

            // Localização desconhecida: descartar por segurança
            return false;
        }));
    }

    private function fetchJobs(string $source, string $keyword, ?string $location, int $maxPages, ?string $url): array
    {
        return match ($source) {
            'linkedin'     => (new LinkedInDriver())->fetch($keyword, $location, $maxPages),
            'indeed'       => (new IndeedDriver())->fetch($keyword, $location, $maxPages),
            'custom'       => (new CustomDriver())->fetch($url ?? '', $keyword, $location, $maxPages),
            'gupy'         => (new GupyDriver())->fetch($keyword, $location, $maxPages),
            'vagas'        => (new VagasDriver())->fetch($keyword, $location, $maxPages),
            'catho'        => (new CathoDriver())->fetch($keyword, $location, $maxPages),
            'infojobs'     => (new InfoJobsDriver())->fetch($keyword, $location, $maxPages),
            'glassdoor'    => (new GlassdoorDriver())->fetch($keyword, $location, $maxPages),
            'solides'      => (new SolidesDriver())->fetch($keyword, $location, $maxPages),
            'programathor' => (new ProgramathorDriver())->fetch($keyword, $location, $maxPages),
            'geekhunter'   => (new GeekHunterDriver())->fetch($keyword, $location, $maxPages),
            'trampos'      => (new TramposDriver())->fetch($keyword, $location, $maxPages),
            'jooble'       => (new JoobleDriver())->fetch($keyword, $location, $maxPages),
            'empregos'     => (new EmpregosDriver())->fetch($keyword, $location, $maxPages),
            default        => throw new CrawlerException("Driver não encontrado: {$source}"),
        };
    }

    private function validateUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ValidationException("URL inválida: '{$url}'.");
        }

        $host    = parse_url($url, PHP_URL_HOST);
        $allowed = App::crawlAllowedDomains();

        foreach ($allowed as $domain) {
            if ($host === $domain || str_ends_with((string) $host, ".{$domain}")) {
                return;
            }
        }

        throw new ValidationException("Domínio não permitido para crawl: '{$host}'.");
    }
}
