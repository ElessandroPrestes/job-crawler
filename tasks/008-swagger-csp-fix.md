# TASK-008: Corrigir erros de console no Swagger UI

## Passos
- [ ] Criar `specs/008-swagger-csp-fix.md`.
- [ ] Editar `nginx/security-headers.conf` adicionando unpkg.com em `connect-src`.
- [ ] Editar `nginx/nginx.conf` bloqueando log do `favicon.ico` e retornando 204.
- [ ] Reiniciar nginx.
