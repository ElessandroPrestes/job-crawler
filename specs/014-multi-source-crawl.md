# SPEC-014: Crawling Multi-Source com Deduplicação Cross-Plataforma

## Objetivo
Ampliar a coleta de vagas para 13 fontes organizadas em 3 tiers, garantindo que a mesma vaga
publicada em múltiplas plataformas (LinkedIn + Indeed, por exemplo) seja armazenada apenas uma
vez. O candidato vê cada oportunidade única, com a fonte prioritária registrada.

## Fontes por Tier
- **Tier 1 (obrigatório):** LinkedIn, Indeed, Gupy, GeekHunter, Programathor, Vagas.com.br
- **Tier 2 (recomendado):** Glassdoor, Sólides, Catho, InfoJobs
- **Tier 3 (complementar):** Jooble, Jobbol, Trampos

## Requisitos

### R1 — Drivers reais
Todos os 13 drivers devem possuir URLs reais e parsers funcionais.
Drivers de plataformas com API pública (Gupy, GeekHunter, Programathor) usam JSON.
Drivers de scraping (LinkedIn, Indeed, Glassdoor, etc.) usam DOM crawler.

### R2 — Deduplicação cross-source
Uma vaga é considerada duplicata de outra se `company_normalized` E `title_normalized`
forem iguais (normalização: lowercase + strip acentos + colapso de espaços).
Campos novos na tabela: `company_normalized` VARCHAR(255), `title_normalized` VARCHAR(500).
Índice UNIQUE em `(company_normalized, title_normalized)`.
O primeiro source a inserir "ganha"; os subsequentes fazem UPDATE apenas de `scraped_at`.

### R3 — Orquestrador MultiSourceCrawlerService
Novo método `executeAll(array $params): array` que:
- Percorre todos os sources ativos em ordem de tier
- Agrega resultados, aplica filterRelevant + scoreAndFilter globalmente
- Retorna resumo consolidado por source

### R4 — Endpoint /api/crawl/all (POST)
Dispara crawl em todos os 13 sources com uma única chamada.
Body: `{ "keyword": "php laravel node.js typescript backend", "location": "brasil" }`

### R5 — Frontend
Botão "Atualizar Vagas" usa /api/crawl/all.
Cards exibem badge da fonte (linkedin, gupy, geekhunter, etc.).

## Quality Gates
- Mesma vaga inserida por LinkedIn e depois pelo Indeed → apenas 1 registro no banco
- Todos os 13 drivers retornam array (pode ser vazio se site bloqueou) sem exceção fatal
- /api/crawl/all retorna sumário com jobs_found e jobs_new por source
- Nenhuma regressão no pipeline existente (filterRelevant + scoreAndFilter)
