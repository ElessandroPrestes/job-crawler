# Project: Job Crawler
## Estado Canônico do Projeto

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

### Decisões Arquiteturais Vigentes
- Banco de dados relacional MySQL 8.0 para armazenamento de vagas e configurações de alertas.
- Guzzle para chamadas HTTP.
- Symfony DOM Crawler para extração dos dados das páginas HTML.
