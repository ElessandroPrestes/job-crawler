# SPEC-020: Ajuste da Linha de Base do Scorer e Ordenação Recente

## Objetivo
Garantir que vagas excelentes (>= 80%) não sejam descartadas por focar apenas em uma stack do currículo (Node.js OU PHP), além de assegurar que as plataformas (como o LinkedIn) enviem sempre as vagas mais recentes.

## Problema Atual
1. A baseline de aprovação do currículo (`ResumeProfile::SCORE_BASELINE`) exigia **67 pontos** (a soma de PHP, Laravel, Node.js, TS, AWS, Docker, MySQL, Redis simultaneamente). Assim, uma vaga perfeita e recente 100% Node.js pontuava ~32 pontos, falhando a nota mínima de 80% (32/67 = 47%). A vaga era descartada mesmo sendo excelente para o perfil.
2. O crawler do LinkedIn não estava utilizando a ordenação por data (`sortBy=DD`). Como nossa API busca apenas as 50 primeiras vagas (`maxPages=2`), o LinkedIn retornava 50 vagas "Relevantes" (Relevance) aleatórias dos últimos 3 dias, soterrando as vagas recém-publicadas (ex: as vagas postadas há 12 horas).

## Requisitos
### R1 — Correção da Baseline (Score)
O valor de `SCORE_BASELINE` em `ResumeProfile.php` deve ser ajustado para `35`, simulando a expectativa realista de que uma vaga se concentrará em apenas uma das stacks (ex: Node.js + REST + CI/CD = ~35 pontos). 

### R2 — Ordenação Cronológica no LinkedIn
O driver do LinkedIn (`LinkedInDriver.php`) deve ter o parâmetro `sortBy=DD` forçado na construção da URL de busca, obrigando a plataforma a listar do mais recente para o mais antigo, garantindo que o Crawler extraia as vagas que apareceram há poucas horas no painel web.

## Quality Gates
- Vagas contendo apenas o perfil Node.js (ex: Node, REST, Next.js, CI/CD) vão exceder 80% de match.
- Vagas com data de hoje (postadas há minutos/horas) são priorizadas nos motores do LinkedIn.
