<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Calcula compatibilidade entre uma vaga e o perfil do candidato.
 *
 * Algoritmo (3 etapas):
 *   1. Desqualificação por TÍTULO: se a vaga exige Python/Java/React etc. como cargo principal → score 0.
 *   2. Desqualificação por TEXTO COMPLETO: frameworks exclusivos de ecossistemas indesejados
 *      (fastapi, sqlalchemy, spring boot, django…) em qualquer parte da vaga → score 0.
 *      Resolve vagas com título genérico ("Engenheiro de Backend") mas Python na descrição.
 *   3. Score ponderado: soma dos pesos das skills encontradas, normalizado para 0–100.
 *
 * SPEC-013
 */
final class CompatibilityScorer
{
    public function score(array $job): array
    {
        $title        = strtolower($job['title'] ?? '');
        $description  = strtolower($job['description'] ?? '');
        $requirements = strtolower($job['requirements'] ?? '');
        $fullText     = $title . ' ' . $description . ' ' . $requirements;

        // Etapa 1 — desqualificar pelo TÍTULO (tecnologia principal do cargo)
        foreach (ResumeProfile::TITLE_DISQUALIFYING as $bad) {
            if (str_contains($title, $bad)) {
                return $this->disqualified($bad);
            }
        }

        // Etapa 2 — desqualificar por frameworks exclusivos no TEXTO COMPLETO
        // (ex.: "Engenheiro de Backend" com FastAPI/SQLAlchemy na descrição)
        foreach (ResumeProfile::FULLTEXT_DISQUALIFYING as $bad) {
            if (str_contains($fullText, $bad)) {
                return $this->disqualified($bad);
            }
        }

        // Etapa 3 — score ponderado por skills do perfil
        $matched      = [];
        $earnedWeight = 0;

        foreach (ResumeProfile::SKILLS as $skill => $weight) {
            if (str_contains($fullText, $skill)) {
                $matched[]     = $skill;
                $earnedWeight += $weight;
            }
        }

        // Normalização: se não temos descrição, o título avalia a tecnologia principal da vaga.
        // Reduzimos o baseline para 10 se não houver descrição, para permitir que vagas
        // cujo título contenha as palavras-chave principais (ex: PHP peso 10, Node peso 9/18) atinjam >= 80%.
        $baseline = ($description === '' && $requirements === '') ? 10 : ResumeProfile::SCORE_BASELINE;
        $score    = (int) round(($earnedWeight / $baseline) * 100);

        return [
            'score'           => min(100, $score),
            'matched'         => $matched,
            'disqualified_by' => null,
        ];
    }

    private function disqualified(string $reason): array
    {
        return [
            'score'           => 0,
            'matched'         => [],
            'disqualified_by' => $reason,
        ];
    }
}
