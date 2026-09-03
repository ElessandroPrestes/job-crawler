# Project: Job Crawler
## Estado Canônico do Projeto

## ⚠️ Dívidas Técnicas e Pontos de Atenção
- ~~**[Testes/SPEC-014]**: A Migration 014 alterou a restrição `UNIQUE` de `(source, external_id)` para `(company_normalized, title_normalized)`. No entanto, os testes de integração em `JobRepositoryTest`, `JobRepositoryExtraTest`, `JobsApiTest`, entre outros, ainda inseriam vagas mockadas com o mesmo título e mesma empresa, causando falhas.~~ **[RESOLVIDO]**: As *Test Cases* base foram atualizadas para utilizar `uniqid()` na geração dos nomes de empresas e títulos. Adicionalmente, erros de Strict Comparison do PHPStan nos drivers Catho e Programathor foram corrigidos. A suíte de testes volta a passar com sucesso.

**Descrição**: API RESTful para coleta automatizada, consulta e exportação de vagas de emprego.
**Repositório**: ElessandroPrestes/job-crawler
**Status**: Em desenvolvimento

### Stack Tecnológica
- **Linguagem**: PHP 8.2
- **Dependências Principais**: Guzzle, Monolog, PHPMailer, Symfony DOM Crawler, vlucas/phpdotenv
- **Infraestrutura**: Docker, Nginx 1.25, MySQL 8.0
- **Documentação**: OpenAPI 3.1 + Swagger UI

### Visão Geral da Arquitetura
O sistema possui crawling multi-fonte (LinkedIn, Indeed), API RESTful com listagem e filtros, alertas por email, e exportação (CSV/JSON). Segue princípios REST e segurança baseada na OWASP Top 10.

### Últimas Atualizações
- Redirecionamento da raiz (`/`) para a documentação do Swagger (`/docs/`) (SPEC-003).
- Dashboard visual minimalista na raiz (`/`) com UX/UI modernos compilado estaticamente via Tailwind CLI (SPEC-010).
- Adoção do Makefile como task runner local (SPEC-001).
- Setup inicial do framework Universal SDD.
- Setup de testes E2E com Playwright (SPEC-009).
- Implementação de hard-limit (Filtro 24h) na API e Drivers do Crawler (SPEC-011).
- Integração de múltiplas novas fontes de vagas (Gupy, Vagas, Catho, etc) (SPEC-012).
- **[Bugfix]** `LinkedInDriver`: User-Agent corrigido para simular browser real (Chrome/Windows) — eliminava bloqueio 403 do LinkedIn.
- **[Bugfix]** `LinkedInDriver`: Acesso a `->attr('href')` em node vazio causava `RuntimeException`; corrigido com verificação `count() > 0`.
- **[Bugfix]** `CrawlerService::filterRelevant()`: Filtro restritivo de título (`php`/`node`) removido — vagas válidas com skill na descrição eram descartadas.
- **[Bugfix]** `public/index.html`: Frontend corrigido para chamar `source: 'linkedin'` (estava `'custom'` com URL de exemplo inválida).
- **[Feature/SPEC-014]** Crawling Multi-Source com Deduplicação: 13 plataformas integradas via `MultiSourceCrawlerService` e endpoint consolidado (`/api/crawl/all`). Drivers reescritos utilizando APIs JSON reais (Gupy, GeekHunter, Jooble) e scraping robusto (LinkedIn, Indeed, Glassdoor, Vagas, Programathor, Catho, InfoJobs, Sólides, Trampos, Jobbol, Empregos). Deduplicação cross-source garantida por novo índice UNIQUE `(company_normalized, title_normalized)` via Migration 014.
- **[Feature]** Adicionado filtro dinâmico de 24h e 3 dias na interface (`public/index.html`) e no repositório (`JobRepository`). O crawler volta a focar em 3 dias (`LinkedInDriver`) enquanto a view filtra nativamente via parâmetro `since`. Resolvido problema visual onde badges poderiam ocultar fontes secundárias como Indeed em detrimento do volume do LinkedIn.
- **[Feature/SPEC-015]** Ordenação Cronológica e Rastreio da Primeira Publicação: O dashboard agora prioriza a data de publicação (`published_at`) para exibir as vagas da mais atual para a mais antiga. A lógica de deduplicação no `JobRepository` foi atualizada para preservar e sobrescrever a fonte original (`source`) se uma plataforma registrar a mesma vaga com uma data de publicação mais antiga.
- **[Bugfix/SPEC-016]** Sincronização do Filtro de Data: Corrigido o filtro de vagas de 24h/3d no `JobRepository` para avaliar a data real de publicação (`published_at`) em vez da data de coleta (`scraped_at`), garantindo consistência visual no dashboard.
- **[Feature/SPEC-019]** Busca Automatizada Baseada no Currículo: O crawler agora itera automaticamente sobre os cargos principais do currículo (`desenvolvedor php`, `desenvolvedor node.js`) em todas as plataformas, sem exigir que o usuário digite os termos manualmente. A interface foi revertida para o modo "One-Click" (input removido), garantindo que apenas vagas aderentes (>= 80% de score) sejam listadas.
- **[Bugfix/SPEC-018]** Relaxamento de Desqualificação no Scorer: Removida a desqualificação sumária de vagas Full-Stack que mencionavam tecnologias do ecossistema React (`next.js`, etc.) no corpo do texto, evitando que vagas adequadas fossem ocultadas indevidamente do pipeline.
- **[Bugfix/SPEC-020]** Ajuste da Linha de Base do Scorer e Ordenação Recente: A base de cálculo do `CompatibilityScorer` foi ajustada para pontuar adequadamente vagas focadas em apenas uma stack do currículo (Node.js OU PHP). O driver do LinkedIn foi forçado a usar a ordenação cronológica (`sortBy=DD`) para que as primeiras vagas coletadas sejam sempre as postadas nas últimas horas.
- **[Feature/SPEC-021]** Pipeline de Vagas UX/UI e Correção do Scorer de Vagas sem Descrição: O dashboard foi completamente reformulado com boas práticas de design (Tailwind CSS compilado, métricas do pipeline, abas de período 24h/3d/Todas, filtro por stack, busca em tempo real e toasts de status). O botão "Atualizar Vagas" exibe permanentemente seu rótulo e ícone com feedback de loading em todas as resoluções. A linha de base do `CompatibilityScorer` para vagas de listagem (título sem descrição) foi corrigida de 25 para 10, permitindo que cargos legítimos (PHP, Node) alcancem >= 80% de match sem serem descartados. Os drivers Catho (`article.offer`), Gupy (SSR `__NEXT_DATA__`) e Sólides (`vagas.solides.com.br`) foram corrigidos e reativados.

### Decisões Arquiteturais Vigentes
- Banco de dados relacional MySQL 8.0 para armazenamento de vagas e configurações de alertas.
- Guzzle para chamadas HTTP.
- Symfony DOM Crawler para extração dos dados das páginas HTML.
