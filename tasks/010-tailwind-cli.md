# TASK-010: Aplicar Tailwind via CLI e remover CDN

## Passos
- [ ] Criar `specs/010-tailwind-cli.md`.
- [ ] Rodar `npm init -y` e instalar `tailwindcss` no diretório raiz.
- [ ] Criar `tailwind.config.js`.
- [ ] Criar `public/css/input.css`.
- [ ] Alterar `public/index.html` removendo a tag `<script src="cdn.tailwindcss.com">` e adicionando `<link rel="stylesheet" href="/css/style.css">`.
- [ ] Rodar o comando de build para gerar o CSS final e incluir um atalho no `Makefile`.
- [ ] Testar acesso à página.
- [ ] Atualizar `PROJECT.md` e commitar.
