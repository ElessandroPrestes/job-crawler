# SPEC-008: Correção de CSP para o Swagger UI

## Objetivo
Resolver o erro de carregamento (CSP violation) do Source Map do CSS do Swagger UI (`swagger-ui.css.map`) quando acessado via `/docs/`.

## Problema
O Swagger UI, que é carregado via CDN (unpkg), tenta baixar o seu próprio arquivo de mapeamento fonte (`.map`). No entanto, o `Content-Security-Policy` (CSP) configurado no Nginx limitava as conexões (`connect-src`) apenas ao próprio domínio (`'self'`), bloqueando a requisição e poluindo o console do navegador.
Além disso, requisições de `favicon.ico` geravam erros 404 nos logs.

## Solução
1. Atualizar o `nginx/security-headers.conf` para permitir conexões `connect-src` ao domínio `unpkg.com` e `https://unpkg.com`.
2. Adicionar uma rota silenciosa para o `favicon.ico` no `nginx.conf` que retorne `204 No Content`, evitando o erro 404 no console.
