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

### Decisões Arquiteturais Vigentes
- Banco de dados relacional MySQL 8.0 para armazenamento de vagas e configurações de alertas.
- Guzzle para chamadas HTTP.
- Symfony DOM Crawler para extração dos dados das páginas HTML.
