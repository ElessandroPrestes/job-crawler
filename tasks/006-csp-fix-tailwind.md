# TASK-006: Corrigir bloqueio de CSP do Tailwind

## Descrição
O navegador bloqueou a execução do Tailwind CDN (`https://cdn.tailwindcss.com/`) devido às configurações estritas do cabeçalho de segurança do Nginx.

## Passos
- [ ] Criar `specs/006-csp-fix-tailwind.md`.
- [ ] Atualizar `nginx/security-headers.conf` liberando o script.
- [ ] Reiniciar Nginx.
- [ ] Registrar alteração.
