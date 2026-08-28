# Job Crawler

API RESTful para coleta automatizada, consulta e exportação de vagas de emprego.
Suporta múltiplas fontes (LinkedIn, Indeed, drivers customizados), notificações por e-mail e documentação interativa via Swagger UI.

[![CI](https://github.com/ElessandroPrestes/job-crawler/actions/workflows/ci.yml/badge.svg)](https://github.com/ElessandroPrestes/job-crawler/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2-8892BF)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

---

## Índice

- [Funcionalidades](#funcionalidades)
- [Stack](#stack)
- [Pré-requisitos](#pré-requisitos)
- [Instalação e execução local](#instalação-e-execução-local)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Endpoints da API](#endpoints-da-api)
- [Exemplos de uso](#exemplos-de-uso)
- [Testes](#testes)
- [Scripts CLI](#scripts-cli)
- [Estrutura do projeto](#estrutura-do-projeto)
- [Segurança](#segurança)
- [CI/CD](#cicd)

---

## Funcionalidades

- **Crawling multi-fonte** — coleta vagas do LinkedIn, Indeed e URLs customizadas
- **API RESTful** — listagem paginada, filtros por keyword, localização, tipo de contrato e fonte
- **Alertas por e-mail** — notificações automáticas ao detectar vagas que correspondem a filtros cadastrados
- **Exportação** — download das vagas em CSV ou JSON com os mesmos filtros da listagem
- **Swagger UI** — documentação interativa disponível em `/docs`
- **Health check** — endpoint `/health` com verificação de banco e storage
- **Rate limiting** — controle de requisições por IP com headers `X-RateLimit-*`
- **Segurança** — OWASP Top 10 aplicado em todas as camadas

---

## Stack

| Componente     | Tecnologia               |
|----------------|--------------------------|
| Runtime        | PHP 8.2                  |
| Web Server     | Nginx 1.25               |
| Banco de dados | MySQL 8.0                |
| Ambiente       | Docker + Docker Compose  |
| Documentação   | OpenAPI 3.1 + Swagger UI |
| E-mail (dev)   | MailHog                  |
| Testes         | PHPUnit 11               |
| Análise estática | PHPStan level 8        |

---

## Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) 24+
- [Docker Compose](https://docs.docker.com/compose/) v2+
- Git

> Não é necessário PHP, Composer ou qualquer outra dependência instalada localmente.

---

## Instalação e execução local

### 1. Clone o repositório

```bash
git clone git@github.com:ElessandroPrestes/job-crawler.git
cd job-crawler
```

### 2. Configure o ambiente

```bash
cp .env.example .env
```

> As configurações padrão do `.env.example` funcionam diretamente com o Docker Compose.
> Edite apenas se precisar alterar portas ou credenciais.

### 3. Suba os containers

```bash
make build
make up
```

Aguarde todos os healthchecks ficarem `healthy` (~2-3 minutos — MySQL inicializa o banco na primeira execução):

```bash
docker compose ps
```

### 4. Instale as dependências PHP

```bash
make install
```

### 5. Crie os diretórios de storage

```bash
make setup-storage
```

> Necessário porque o volume mount `.:/var/www/html` sobrescreve os diretórios criados pelo Dockerfile.

### 6. Acesse os serviços

| Serviço      | URL                          |
|--------------|------------------------------|
| API          | http://localhost:8080        |
| Swagger UI   | http://localhost:8080/docs   |
| MailHog (UI) | http://localhost:8025        |

### 7. Verifique a instalação

```bash
curl http://localhost:8080/health
```

Resposta esperada:

```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "version": "1.0.0",
    "checks": {
      "database": "ok",
      "storage": "ok"
    }
  }
}
```

---

## Variáveis de ambiente

Copie `.env.example` para `.env` e ajuste conforme necessário:

| Variável               | Padrão                  | Descrição                              |
|------------------------|-------------------------|----------------------------------------|
| `APP_ENV`              | `development`           | Ambiente da aplicação                  |
| `APP_DEBUG`            | `false`                 | Exibe stack trace nas respostas        |
| `DB_HOST`              | `mysql`                 | Host do banco de dados                 |
| `DB_DATABASE`          | `job_crawler`           | Nome do banco                          |
| `DB_USERNAME`          | `crawler`               | Usuário do banco                       |
| `DB_PASSWORD`          | `crawlersecret`         | Senha do banco                         |
| `MAIL_HOST`            | `mailhog`               | Host SMTP                              |
| `MAIL_PORT`            | `1025`                  | Porta SMTP                             |
| `MAIL_FROM_ADDRESS`    | `noreply@jobcrawler.local` | Remetente dos e-mails               |
| `CRAWL_DELAY_MS`       | `1500`                  | Delay entre requests do crawler (ms)   |
| `CRAWL_ALLOWED_DOMAINS`| `linkedin.com,indeed.com` | Whitelist de domínios para crawl     |

---

## Endpoints da API

| Método   | Rota                  | Status | Descrição                        |
|----------|-----------------------|--------|----------------------------------|
| `GET`    | `/health`             | 200    | Health check                     |
| `GET`    | `/api/jobs`           | 200    | Listar vagas com filtros e paginação |
| `GET`    | `/api/jobs/{id}`      | 200    | Detalhe de uma vaga              |
| `POST`   | `/api/crawl`          | 200    | Disparar crawl síncrono          |
| `GET`    | `/api/crawl/logs`     | 200    | Histórico de execuções           |
| `GET`    | `/api/alerts`         | 200    | Listar filtros de alerta         |
| `POST`   | `/api/alerts`         | 201    | Criar filtro de alerta           |
| `DELETE` | `/api/alerts/{id}`    | 200    | Remover filtro de alerta         |
| `GET`    | `/api/export`         | 200    | Exportar vagas em CSV ou JSON    |
| `GET`    | `/docs`               | 200    | Swagger UI                       |

### Parâmetros de paginação e filtro (`GET /api/jobs`)

| Parâmetro       | Tipo   | Descrição                          |
|-----------------|--------|------------------------------------|
| `page`          | int    | Página (padrão: 1)                 |
| `per_page`      | int    | Itens por página (máx: 100, padrão: 20) |
| `keyword`       | string | Busca em título, empresa e requisitos |
| `location`      | string | Filtro por localização (busca parcial) |
| `source`        | string | `linkedin`, `indeed`, `custom`     |
| `contract_type` | string | `CLT`, `PJ`, `Remote`, `Hybrid`    |
| `since`         | string | Data mínima ISO 8601               |

### Envelope de resposta

**Sucesso:**
```json
{
  "success": true,
  "data": {},
  "meta": {
    "total": 248,
    "page": 1,
    "per_page": 20,
    "last_page": 13
  }
}
```

**Erro:**
```json
{
  "success": false,
  "error": "Vaga não encontrada.",
  "code": 404
}
```

---

## Exemplos de uso

### Listar vagas de PHP e Node.js no Brasil

```bash
# Desenvolvedor PHP no Brasil
curl "http://localhost:8080/api/jobs?keyword=desenvolvedor+php&location=brazil&per_page=10"

# Desenvolvedor Node no Brasil
curl "http://localhost:8080/api/jobs?keyword=desenvolvedor+node&location=brazil&per_page=10"

# Laravel — remoto, ordenado por mais recentes
curl "http://localhost:8080/api/jobs?keyword=laravel&location=remote&per_page=10"
```

### Disparar crawl no LinkedIn (Brasil · remoto · últimos 3 dias)

```bash
# Vagas de Desenvolvedor PHP
curl -X POST http://localhost:8080/api/crawl \
  -H "Content-Type: application/json" \
  -d '{
    "source": "linkedin",
    "keyword": "desenvolvedor php",
    "location": "brasil",
    "max_pages": 3
  }'

# Vagas de Desenvolvedor Node
curl -X POST http://localhost:8080/api/crawl \
  -H "Content-Type: application/json" \
  -d '{
    "source": "linkedin",
    "keyword": "desenvolvedor node",
    "location": "brasil",
    "max_pages": 3
  }'
```

> O crawler filtra automaticamente títulos que contenham **php** ou **node** e localizações
> restritas a **Brasil**, **remoto** ou **home office** — publicações dos últimos 3 dias.

### Criar alerta de e-mail

Receba notificações automáticas quando novas vagas forem encontradas:

```bash
curl -X POST http://localhost:8080/api/alerts \
  -H "Content-Type: application/json" \
  -d '{
    "email": "dev@exemplo.com.br",
    "keywords": ["desenvolvedor php", "desenvolvedor node", "php", "laravel", "node"],
    "locations": ["brasil", "brazil", "remoto", "remote", "home office"],
    "contract_types": ["PJ", "CLT"]
  }'
```

Os e-mails são capturados localmente pelo **MailHog** disponível em:

> **http://localhost:8025**

Nenhuma mensagem sai para a internet em ambiente de desenvolvimento.

### Exportar vagas em CSV

```bash
# Todas as vagas de PHP via API
curl "http://localhost:8080/api/export?format=csv&keyword=php" -o vagas-php.csv

# Todas as vagas via script (salva em storage/exports/)
make export ARGS="--format=csv --output=storage/exports/vagas.csv"
```

### Exportar vagas em JSON

```bash
# Somente vagas do LinkedIn via API
curl "http://localhost:8080/api/export?format=json&source=linkedin" -o vagas.json

# Com filtro por keyword
curl "http://localhost:8080/api/export?format=json&keyword=node" -o vagas-node.json
```

---

## Testes

```bash
# Todos os testes
make test

# Apenas unitários
make test-unit

# Apenas integração
make test-integration

# Apenas feature
make test-feature

# Com relatório de cobertura (HTML em /coverage)
make test-coverage
```

> Os testes de integração e feature usam SQLite in-memory — sem dependência do MySQL.
> Cobertura mínima exigida: **70%**.

---

## Scripts CLI (Makefile)

O projeto utiliza um `Makefile` para abstrair os comandos do Docker e do Composer. 
Para ver todos os comandos disponíveis, rode apenas `make`.

Principais comandos:
- `make up`: Inicia os containers.
- `make down`: Para os containers.
- `make bash`: Entra no container da aplicação.
- `make test`: Roda a suíte de testes.
- `make analyse`: Roda a análise estática com PHPStan.
- `make swagger`: Gera o OpenAPI yaml.

## Estrutura do projeto

```
job-crawler/
├── public/             # Front controller (index.php)
├── src/
│   ├── Config/         # App e Database
│   ├── Controllers/    # HealthController, JobController, etc.
│   ├── Exceptions/     # HttpException, ValidationException, CrawlerException
│   ├── Http/           # Request e JsonResponse
│   ├── Middleware/     # RateLimiter, InputSanitizer, CsrfGuard
│   ├── Models/         # Job, CrawlLog, AlertFilter
│   ├── Repositories/   # Acesso ao banco via PDO
│   ├── Services/       # CrawlerService, EmailService, ExportService
│   │   └── Drivers/    # LinkedInDriver, IndeedDriver, CustomDriver
│   └── Router.php
├── tests/
│   ├── Unit/           # Testes sem dependências externas
│   ├── Integration/    # Testes com SQLite in-memory
│   └── Feature/        # Testes de comportamento da API
├── docs/
│   ├── swagger/        # openapi.yaml + Swagger UI
│   ├── api.md          # Guia de uso com exemplos curl
│   └── deploy.md       # Guia de deploy em produção
├── scripts/            # CLI: crawl, export, migrate, notify
├── nginx/              # nginx.conf + security-headers.conf
├── mysql/              # init.sql + my.cnf
├── docker/             # php.ini customizado
├── .github/workflows/  # CI, CD e auditoria de segurança
├── docker-compose.yml
├── Dockerfile
└── .env.example
```

---

## Segurança

| Risco OWASP         | Mitigação aplicada                                              |
|---------------------|-----------------------------------------------------------------|
| A03 — Injection     | PDO com prepared statements; zero concatenação SQL             |
| A05 — Misconfiguration | `server_tokens off`; `.env` fora do VCS; headers via Nginx |
| A06 — Vulnerable Components | `composer audit` no CI a cada push e semanalmente   |
| A07 — Auth Failures | Rate limiting por IP; CSRF com `random_bytes(32)`              |
| A09 — Logging       | Monolog em arquivo; nunca loga senhas, tokens ou PII           |
| A10 — SSRF          | URLs de crawl validadas contra whitelist de domínios           |

---

## CI/CD

| Pipeline        | Trigger               | Jobs                                          |
|-----------------|-----------------------|-----------------------------------------------|
| `ci.yml`        | Push / Pull Request   | PHPStan level 8, testes (PHP 8.2/8.3), swagger-validate, docker build |
| `cd.yml`        | Push `main` / tag `v*` | Build + push GHCR, deploy staging/produção  |
| `security.yml`  | Semanal (segundas)    | `composer audit` + notificação Slack          |

Deploy em produção exige tag semântica (`v1.0.0`) e **aprovação manual** no GitHub Environment.
