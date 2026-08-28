# SPEC-003: Fix Localhost Root Endpoint

## Objetivo
Corrigir o comportamento da raiz do projeto (`http://localhost:8080/`) para que exiba a documentação do Swagger UI ao invés de retornar erro 404 (Rota não encontrada), garantindo que o acesso sem `/docs` não resulte em uma tela "vazia" ou erro inesperado.

## Requisitos
1. O Nginx deve interceptar chamadas para a rota raiz `/`.
2. O Nginx deve redirecionar o usuário (301 Moved Permanently) de `/` para `/docs/`.
3. O Nginx deve ser configurado com `absolute_redirect off;` para garantir que redirects internos (como a adição de trailing slash) não percam a porta mapeada pelo Docker (`8080`).
