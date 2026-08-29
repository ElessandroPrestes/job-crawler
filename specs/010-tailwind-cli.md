# SPEC-010: Substituição do Tailwind CDN pela CLI (Build Estático)

## Objetivo
Resolver o aviso de segurança e performance do navegador informando que o CDN do Tailwind CSS (`cdn.tailwindcss.com`) não deve ser utilizado em produção. O projeto passará a compilar e servir um arquivo CSS estático minificado.

## Requisitos
1. O CDN do Tailwind deve ser removido do arquivo `public/index.html`.
2. O framework Tailwind CSS deve ser configurado via NPM/CLI no repositório.
3. O CSS deve ser compilado e minificado em um arquivo estático (`public/css/style.css`).
4. O frontend deve referenciar o arquivo estático gerado.
5. As configurações e o CSS compilado farão parte do repositório para garantir que a API continue rodando imediatamente sem exigir que outros desenvolvedores precisem do Node.js.

## Implementação
- Inicializar `package.json` e instalar `tailwindcss`.
- Criar configuração `tailwind.config.js` apontando para os arquivos em `public/*.html`.
- Criar um arquivo base `public/css/input.css`.
- Configurar comando de _build_ no `Makefile` (ex: `make build-css`).
- Executar o build inicial e atualizar o `index.html`.
