# Job Crawler

API RESTful para coleta, consulta e exportação de vagas de emprego, com notificações por e-mail e documentação OpenAPI.

[![CI](https://github.com/amura/job-crawler/actions/workflows/ci.yml/badge.svg)](https://github.com/amura/job-crawler/actions/workflows/ci.yml)

---

## Stack

| Componente | Tecnologia |
|------------|-----------|
| Runtime    | PHP 8.2 |
| Web Server | Nginx 1.25 |
| Banco      | MySQL 8.0 |
| Ambiente   | Docker / Compose |
| Docs       | OpenAPI 3.1 + Swagger UI |
| E-mail (dev) | MailHog |

---

## Setup rápido

```bash
cp .env.example .env
docker compose up -d --build
```

Aguarde os healthchecks e acesse:

| Serviço | URL |
|---------|-----|
| API | http://localhost:8080 |
| Swagger UI | http://localhost:8080/docs |
| MailHog | http://localhost:8025 |

---

## Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/health` | Health check |
| GET | `/api/jobs` | Listar vagas (paginado) |
| GET | `/api/jobs/{id}` | Detalhe de vaga |
| POST | `/api/crawl` | Disparar crawl |
| GET | `/api/crawl/logs` | Histórico de execuções |
| GET | `/api/alerts` | Listar filtros de alerta |
| POST | `/api/alerts` | Criar filtro de alerta |
| DELETE | `/api/alerts/{id}` | Remover filtro |
| GET | `/api/export` | Exportar vagas (csv/json) |

Documentação completa em: **http://localhost:8080/docs**

---

## Comandos

```bash
# Ambiente
docker compose up -d --build
docker compose exec app bash

# Dependências e análise
docker compose exec app composer install
docker compose exec app composer analyse
docker compose exec app composer audit

# Testes
docker compose exec app composer test
docker compose exec app composer test:coverage

# Swagger
docker compose exec app ./vendor/bin/openapi src -o docs/swagger/openapi.yaml

# Scripts CLI
docker compose exec app php scripts/crawl.php --source=linkedin --keyword="PHP Developer"
docker compose exec app php scripts/export.php --format=csv --output=/tmp/jobs.csv
docker compose exec app php scripts/migrate.php
docker compose exec app php scripts/notify.php
```

---

## Segurança

- OWASP Top 10 aplicado (ver `Claude.md`)
- Rate limiting por IP via APCu
- Prepared statements em todas as queries
- Headers de segurança via Nginx
- Whitelist de domínios para crawl (anti-SSRF)
- `composer audit` no CI a cada push
