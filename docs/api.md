# Job Crawler — Guia de Uso da API

Base URL local: `http://localhost:8080`

---

## Health Check

```bash
curl http://localhost:8080/health
```

---

## Vagas

### Listar vagas (paginado)

```bash
curl "http://localhost:8080/api/jobs?page=1&per_page=20"
```

### Filtrar por palavra-chave e localização

```bash
curl "http://localhost:8080/api/jobs?keyword=PHP+Developer&location=São+Paulo&source=linkedin"
```

### Detalhe de uma vaga

```bash
curl http://localhost:8080/api/jobs/42
```

---

## Crawler

### Disparar crawl

```bash
curl -X POST http://localhost:8080/api/crawl \
  -H "Content-Type: application/json" \
  -d '{"source":"linkedin","keyword":"PHP Developer","location":"São Paulo","max_pages":3}'
```

### Histórico de execuções

```bash
curl "http://localhost:8080/api/crawl/logs?status=success&page=1"
```

---

## Alertas

### Listar filtros ativos

```bash
curl http://localhost:8080/api/alerts
```

### Criar filtro de alerta

```bash
curl -X POST http://localhost:8080/api/alerts \
  -H "Content-Type: application/json" \
  -d '{
    "email": "dev@exemplo.com.br",
    "keywords": ["PHP", "Laravel"],
    "locations": ["São Paulo", "Remote"],
    "contract_types": ["PJ", "CLT"]
  }'
```

### Remover filtro

```bash
curl -X DELETE http://localhost:8080/api/alerts/3
```

---

## Exportação

### Exportar CSV

```bash
curl "http://localhost:8080/api/export?format=csv&keyword=PHP" \
  -o jobs.csv
```

### Exportar JSON

```bash
curl "http://localhost:8080/api/export?format=json&source=linkedin" \
  -o jobs.json
```

---

## Headers de paginação

Todas as listagens retornam:

```
X-Total-Count: 248
X-Page: 1
X-Per-Page: 20
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1710512400
```

## Envelope de erro

```json
{
  "success": false,
  "error": "Vaga não encontrada.",
  "code": 404
}
```
