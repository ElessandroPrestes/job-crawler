# SPEC-016: Sincronização do Filtro de Data com a Data de Publicação

## Objetivo
Corrigir o comportamento do filtro de "Últimas 24h" e "Últimos 3 dias" para que avalie a data real de publicação da vaga (`published_at`) em vez da data de coleta (`scraped_at`). 

## Problema Atual
Após a SPEC-015, o dashboard passou a exibir a data de publicação original da vaga (ex: 31/08/2026). No entanto, o backend (`JobRepository`) ainda filtrava as vagas pela data em que o crawler a coletou (`scraped_at`). Isso causava uma inconsistência visual: o usuário selecionava "Últimas 24h", mas via vagas de 3 dias atrás, pois elas foram *coletadas* nas últimas 24h, apesar de *publicadas* há 3 dias.

## Requisitos

### R1 — Correção dos Filtros no Repositório
No arquivo `JobRepository.php`, os métodos `findAll` e `count` devem aplicar o filtro de tempo avaliando primariamente o `published_at`, com fallback para `scraped_at`.
*Implementação:* Substituir `scraped_at >= ...` por `COALESCE(published_at, scraped_at) >= ...` tanto no dialeto MySQL quanto no SQLite, para os filtros de `1 day` (24h) e `3 days` (3d).

## Quality Gates
- Selecionar "Últimas 24h" no dashboard retorna exclusivamente vagas cuja data exibida é no máximo de 24 horas atrás.
- Nenhuma regressão nos testes unitários e de integração (`make test`).
