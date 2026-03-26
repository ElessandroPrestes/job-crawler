#!/usr/bin/env bash
# Smoke test — gate de rollback no CD
# Uso: ./scripts/smoke-test.sh [BASE_URL]
# Retorna: 0 = tudo OK, 1 = alguma falha

set -euo pipefail

BASE="${1:-http://localhost:8080}"
PASS=0
FAIL=0

check() {
    local desc="$1"
    local expected="$2"
    local actual="$3"

    if [ "$actual" = "$expected" ]; then
        echo "  [OK]   $desc"
        PASS=$((PASS + 1))
    else
        echo "  [FAIL] $desc — esperado: $expected, obtido: $actual"
        FAIL=$((FAIL + 1))
    fi
}

echo ""
echo "Smoke test → $BASE"
echo "────────────────────────────────────────"

# 1. Health check
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/health")
check "GET /health retorna 200" "200" "$STATUS"

# 2. Health body contém status healthy
BODY=$(curl -s "$BASE/health")
HEALTHY=$(echo "$BODY" | grep -c '"status":"healthy"' || true)
check "GET /health body contém status:healthy" "1" "$HEALTHY"

# 3. Listagem de vagas retorna 200
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/api/jobs")
check "GET /api/jobs retorna 200" "200" "$STATUS"

# 4. Listagem de vagas contém envelope success
BODY=$(curl -s "$BASE/api/jobs")
SUCCESS=$(echo "$BODY" | grep -c '"success":true' || true)
check "GET /api/jobs body contém success:true" "1" "$SUCCESS"

# 5. Listagem de alertas retorna 200
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/api/alerts")
check "GET /api/alerts retorna 200" "200" "$STATUS"

# 6. Swagger UI acessível
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/docs")
check "GET /docs retorna 200" "200" "$STATUS"

# 7. openapi.yaml acessível
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/docs/swagger/openapi.yaml")
check "GET /docs/swagger/openapi.yaml retorna 200" "200" "$STATUS"

echo "────────────────────────────────────────"
echo "Resultado: $PASS OK, $FAIL FALHA(s)"
echo ""

[ "$FAIL" -eq 0 ]
