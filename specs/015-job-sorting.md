# SPEC-015: Ordenação Cronológica e Rastreio da Primeira Publicação

## Objetivo
Garantir que as vagas na pipeline visual (dashboard) sejam exibidas em ordem estritamente cronológica decrescente (a mais atual primeiro), baseada prioritariamente na data de publicação original da vaga. Adicionalmente, quando uma vaga é identificada em múltiplas fontes (deduplicação), o sistema deve identificar e registrar a fonte onde a vaga foi publicada primeiro (a publicação mais antiga).

## Problema Atual
1. A interface (frontend) está priorizando `scraped_at` sobre `published_at` na exibição da data.
2. A lógica de deduplicação no banco de dados (`ON DUPLICATE KEY UPDATE`) apenas atualiza a data de scraping e mantém a `source` e `published_at` da primeira vez que o crawler encontrou a vaga, que não é necessariamente a fonte original (mais antiga).

## Requisitos

### R1 — Correção da Exibição Visual (Frontend)
Na renderização dos cards em `public/index.html`, a data exibida deve usar prioritariamente `job.published_at`. Se ausente, realiza o fallback para `job.scraped_at`.
*Implementação:* Alterar `job.scraped_at || job.published_at` para `job.published_at || job.scraped_at`.

### R2 — Rastreamento da Primeira Plataforma (Backend)
No repositório de vagas (`JobRepository::upsert`), durante um conflito de deduplicação (UNIQUE constraint em `company_normalized, title_normalized`), o sistema deve comparar a data de publicação da nova fonte com a data já registrada. Se a nova for mais antiga (ou se a atual for nula), deve-se sobrescrever a `source` e o `published_at` com os dados da nova fonte.
*Implementação:* 
- Para MySQL: Adicionar lógica `IF(VALUES(published_at) < published_at OR published_at IS NULL, ...)` no `ON DUPLICATE KEY UPDATE`.
- Para SQLite: Adicionar lógica `CASE WHEN excluded.published_at < published_at ...` no `ON CONFLICT DO UPDATE`.

### R3 — Ordenação
A consulta `findAll` no `JobRepository` já utiliza `ORDER BY COALESCE(published_at, scraped_at) DESC`, o que atende perfeitamente ao requisito de ordenar pela mais atual primeiro após as correções de R1 e R2 entrarem em vigor.

## Quality Gates
- O dashboard exibe as datas reais de publicação quando disponíveis.
- Vagas duplicadas atualizam sua `source` se uma plataforma secundária tiver uma data de publicação mais antiga.
- Testes continuam passando sem regressões (as mudanças SQL são transparentes para inserções padrão).
