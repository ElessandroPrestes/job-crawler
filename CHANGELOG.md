# Changelog

## [1.0.0] — 2026-03-26

### Adicionado

- API RESTful com endpoints para vagas, crawl, alertas e exportação
- Suporte a múltiplos drivers de crawl: LinkedIn, Indeed, Custom
- Sistema de alertas por e-mail com filtros por keyword, localização e tipo de contrato
- Exportação em CSV e JSON
- Documentação OpenAPI 3.1 com Swagger UI self-hosted em `/docs`
- Rate limiting por IP via APCu com headers `X-RateLimit-*`
- Proteção CSRF com token criptograficamente seguro
- Middleware de sanitização de input (anti-XSS, anti-null-byte)
- Pipeline CI/CD com GitHub Actions (PHPStan level 8, testes matrix, swagger-validate)
- Auditoria de segurança semanal com Composer audit
- Scripts CLI: `crawl.php`, `export.php`, `migrate.php`, `notify.php`
- Ambiente Docker completo: PHP-FPM, Nginx, MySQL 8, MailHog
- Health check em todos os containers e endpoint `/health`
