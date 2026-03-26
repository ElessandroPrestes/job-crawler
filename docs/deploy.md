# Guia de Deploy em Produção

## Pré-requisitos

- Docker e Docker Compose instalados no servidor
- Acesso SSH configurado
- Segredos configurados no GitHub: `PROD_HOST`, `PROD_USER`, `PROD_SSH_KEY`, `SLACK_WEBHOOK_URL`
- GitHub Environment `production` com aprovação manual ativada

---

## Deploy via tag semântica

```bash
git tag v1.0.0
git push origin v1.0.0
```

O pipeline CD executa automaticamente após aprovação manual no GitHub.

---

## Deploy manual

```bash
ssh user@prod-server
cd /opt/job-crawler
docker compose pull
docker compose up -d --remove-orphans
docker compose exec -T app php scripts/migrate.php
curl -sf http://localhost:8080/health
```

---

## Checklist de produção

- [ ] `APP_DEBUG=false` no `.env`
- [ ] `APP_ENV=production`
- [ ] `.env` não está no repositório (`git ls-files .env` vazio)
- [ ] `composer.lock` commitado
- [ ] `composer audit` sem vulnerabilidades críticas
- [ ] Todos os secrets configurados no GitHub Environment
- [ ] Health check respondendo 200 após deploy
- [ ] Swagger UI acessível em `/docs`

---

## Rollback

```bash
# Listar imagens disponíveis
docker images ghcr.io/amura/job-crawler

# Fazer rollback para versão anterior
docker compose down
docker compose pull ghcr.io/amura/job-crawler:v0.9.0
# editar docker-compose.yml para usar a tag anterior
docker compose up -d
curl -sf http://localhost:8080/health
```

---

## Monitoramento

- **Logs app:** `docker compose logs -f app`
- **Logs nginx:** `docker compose logs -f nginx`
- **Health:** `curl http://localhost:8080/health`
