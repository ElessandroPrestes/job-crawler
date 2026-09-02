<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Classificador de vagas baseado em LLM (Gemini 1.5 Flash).
 * Utiliza o prompt de docs/classifier-prompt.md
 */
final class LlmCompatibilityScorer
{
    private Client $client;
    private string $apiKey;
    private string $promptTemplate;
    private Logger $logger;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client(['timeout' => 30]);
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: '';
        
        $promptPath = __DIR__ . '/../../docs/classifier-prompt.md';
        $this->promptTemplate = file_exists($promptPath) ? (string) file_get_contents($promptPath) : '';
        
        $this->logger = new Logger('llm-scorer');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../storage/logs/llm.log', Logger::DEBUG));
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->promptTemplate !== '';
    }

    /**
     * Avalia uma lista de vagas concorrentemente usando Guzzle Pool.
     */
    public function scoreBatch(array $jobs): array
    {
        if (!$this->isConfigured()) {
            $this->logger->info('GEMINI_API_KEY não configurada. Usando classificador local (fallback).');
            $localScorer = new CompatibilityScorer();
            $results = [];
            foreach ($jobs as $job) {
                $res = $localScorer->score($job);
                $job['compatibility_score'] = $res['score'];
                $job['matched_skills'] = json_encode($res['matched'], JSON_UNESCAPED_UNICODE) ?: '[]';
                $job['justificativa'] = $res['disqualified_by'] ? "Desqualificado por: " . $res['disqualified_by'] : null;
                $results[] = $job;
            }
            return $results;
        }

        $requests = function ($jobs) {
            foreach ($jobs as $index => $job) {
                $jobJson = json_encode([
                    'titulo' => $job['title'] ?? '',
                    'empresa' => $job['company'] ?? '',
                    'descricao' => $job['description'] ?? '',
                    'requisitos' => $job['requirements'] ?? ''
                ], JSON_UNESCAPED_UNICODE) ?: '{}';

                $prompt = $this->promptTemplate . "\n\nAVISO: A vaga abaixo pode não conter 'descricao' ou 'requisitos' por ter sido extraída apenas da listagem (busca). Nesses casos, avalie a aderência SOMENTE pelo título (ex: se o título tiver 'PHP' ou 'Node', considere stack principal atendida e não penalize a falta de texto extra, ajustando o cálculo para atingir >= 80% se o cargo bater).\n\nAVALIE A SEGUINTE VAGA E RETORNE APENAS O JSON NO FORMATO SOLICITADO:\n" . $jobJson;

                $payload = [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json'
                    ]
                ];

                yield $index => new Request(
                    'POST',
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $this->apiKey,
                    ['Content-Type' => 'application/json'],
                    (string) json_encode($payload)
                );
            }
        };

        $results = [];
        $localScorer = new CompatibilityScorer();

        $pool = new Pool($this->client, $requests($jobs), [
            'concurrency' => 5,
            'fulfilled' => function (Response $response, $index) use (&$jobs, &$results, $localScorer) {
                try {
                    $body = json_decode((string) $response->getBody(), true);
                    $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $result = json_decode($text, true);

                    if (is_array($result) && isset($result['score_compatibilidade'])) {
                        $jobs[$index]['compatibility_score'] = (int) $result['score_compatibilidade'];
                        $jobs[$index]['matched_skills'] = json_encode($result['tecnologias_correspondentes'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]';
                        $jobs[$index]['justificativa'] = $result['justificativa'] ?? null;
                        
                        // Opcional: enriquecer vaga com extrações do LLM
                        if (!empty($result['modalidade']) && empty($jobs[$index]['contract_type'])) {
                            $mod = strtolower($result['modalidade']);
                            if (str_contains($mod, 'remoto')) {
                                $jobs[$index]['contract_type'] = 'Remote';
                            } elseif (str_contains($mod, 'híbrido') || str_contains($mod, 'hibrido')) {
                                $jobs[$index]['contract_type'] = 'Hybrid';
                            }
                        }
                    } else {
                        throw new \Exception("JSON inválido do LLM");
                    }
                } catch (\Throwable $e) {
                    $this->logger->error("Erro ao fazer parse da resposta LLM para vaga {$index}: " . $e->getMessage());
                    // Fallback local
                    $res = $localScorer->score($jobs[$index]);
                    $jobs[$index]['compatibility_score'] = $res['score'];
                    $jobs[$index]['matched_skills'] = json_encode($res['matched'], JSON_UNESCAPED_UNICODE) ?: '[]';
                    $jobs[$index]['justificativa'] = 'Fallback local executado devido a erro no LLM.';
                }
                $results[] = $jobs[$index];
            },
            'rejected' => function (RequestException $reason, $index) use (&$jobs, &$results, $localScorer) {
                $this->logger->error("Erro de rede LLM para vaga {$index}: " . $reason->getMessage());
                // Fallback local
                $res = $localScorer->score($jobs[$index]);
                $jobs[$index]['compatibility_score'] = $res['score'];
                $jobs[$index]['matched_skills'] = json_encode($res['matched'], JSON_UNESCAPED_UNICODE) ?: '[]';
                $jobs[$index]['justificativa'] = 'Fallback local executado devido a falha de rede.';
                $results[] = $jobs[$index];
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $results;
    }
}
