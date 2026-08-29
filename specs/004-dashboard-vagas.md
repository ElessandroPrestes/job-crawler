# SPEC-004: Dashboard Visual de Vagas na Raiz

## Objetivo
Atender ao requisito do usuário de visualizar as vagas encontradas diretamente ao acessar a raiz do projeto (`http://localhost:8080/`), sem precisar consumir os endpoints JSON manualmente.

## Requisitos
1. A rota raiz `/` deve servir uma página HTML simples (Single Page).
2. A página deve consumir o endpoint `/api/jobs` via JavaScript (fetch API).
3. A página deve renderizar as vagas em formato de lista ou grid, mostrando Título, Empresa, Localização e Tipo de Contrato.
4. A documentação do Swagger continuará disponível em `/docs/`.

## Implementação
- Criar `public/index.html` com HTML, CSS (Tailwind via CDN para agilidade) e Vanilla JS.
- Atualizar `nginx/nginx.conf` para servir o `index.html` na rota `/` no lugar do redirecionamento para `/docs/`.
