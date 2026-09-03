# SPEC-019: Busca Automatizada Baseada no Currículo (Sem Input Manual)

## Objetivo
Remover a necessidade do usuário digitar palavras-chave manualmente no dashboard. O Crawler deve ser inteligente o suficiente para utilizar os cargos principais definidos no perfil do candidato e rodar as buscas em lote para cada um deles, filtrando automaticamente as vagas com >= 80% de aderência.

## Problema Atual
A SPEC-017 introduziu um campo de busca para contornar o limite das plataformas (que falhavam com strings gigantes). Porém, isso exigia trabalho manual do usuário (ter que lembrar de buscar por "node.js", depois por "php", etc). O usuário deseja um fluxo "One-Click", onde o sistema já sabe o que buscar com base no currículo.

## Requisitos

### R1 — Termos de Busca no Perfil
Adicionar a constante `SEARCH_TERMS` no `ResumeProfile.php` com as variações de cargos principais do currículo (ex: `['desenvolvedor php', 'desenvolvedor node.js']`).

### R2 — Orquestrador Multi-Termo
O `MultiSourceCrawlerService::executeAll()` deve ser refatorado para:
- Descartar o parâmetro único de `keyword`.
- Iterar sobre todos os `SEARCH_TERMS` do currículo.
- Para cada termo, iterar sobre todos os `SOURCES_BY_TIER`.
- Acumular os resultados.

### R3 — Reversão de Interface (UI)
- Remover o `<input type="text" id="keyword-input">` criado na SPEC-017 do `public/index.html`.
- O payload para `/api/crawl/all` não precisa mais enviar `keyword`.

## Quality Gates
- O clique em "Atualizar Vagas" desencadeia múltiplas buscas precisas nas plataformas sem requerer input textual.
- A restrição de aderência (>= 80% de score no `CompatibilityScorer`) já existente continua garantindo que apenas vagas relevantes cheguem ao usuário.
