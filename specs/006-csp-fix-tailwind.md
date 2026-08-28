# SPEC-006: Fix Content Security Policy for Tailwind CSS

## Objetivo
Permitir o carregamento do CDN do Tailwind CSS no frontend, resolvendo o bloqueio causado pela diretiva `script-src` do Content Security Policy (CSP).

## Requisitos
1. A diretiva `script-src` no arquivo `nginx/security-headers.conf` deve permitir o domínio `cdn.tailwindcss.com`.
2. A política não deve afrouxar desnecessariamente outras proteções já existentes (manter `default-src 'self'`, etc).

## Implementação
- Adicionar `cdn.tailwindcss.com` à lista de domínios permitidos em `script-src` no Nginx.
