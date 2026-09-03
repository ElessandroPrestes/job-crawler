# SPEC-018: Relaxamento de Desqualificação para Ecossistema React em Vagas Full-Stack

## Objetivo
Evitar que vagas perfeitamente alinhadas com o stack principal (Node.js/Backend) sejam descartadas sumariamente apenas por mencionarem tecnologias do ecossistema React (ex: Next.js) na descrição como alternativas ou diferenciais.

## Problema Atual
O `CompatibilityScorer` (via `ResumeProfile::FULLTEXT_DISQUALIFYING`) bloqueava qualquer vaga que contivesse termos como `next.js`, `nextjs` e `react` em qualquer parte do texto. Vagas como "Desenvolvedor(a) Full-Stack" que exigiam forte domínio de Node.js no backend, mas mencionavam "React/Next.js ou equivalentes" no frontend, estavam sendo descartadas erroneamente e não apareciam na pipeline, mesmo quando o candidato as julgava aderentes ao currículo.

## Requisitos

### R1 — Ajuste no ResumeProfile
Remover os frameworks exclusivos do ecossistema React (como `next.js`, `nextjs`, `react hooks`, `remix`, `gatsby`, `react native`) da constante `FULLTEXT_DISQUALIFYING` no `ResumeProfile.php`.
A desqualificação pelo cargo principal na `TITLE_DISQUALIFYING` será mantida, garantindo que vagas puramente de frontend React (ex: "Desenvolvedor Next.js") continuem sendo descartadas.

## Quality Gates
- Vagas de Node.js/Full-Stack que listem "Next.js" na descrição passam a ser processadas e pontuadas normalmente.
- O classificador não descarta a vaga precipitadamente antes do cálculo de match de skills base.
