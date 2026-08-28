# SPEC-002: Refatoração do README.md e Makefile

## Objetivo
Garantir que toda a documentação no `README.md` reflita o uso do `Makefile` recém-criado, eliminando os comandos crus do `docker-compose` para uma experiência de usuário (DX) mais coesa.

## Escopo
- Expandir o `Makefile` para incluir comandos mencionados no `README.md` que ainda não estavam cobertos (ex: `make install`, `make test-unit`, setup de permissões).
- Atualizar o arquivo `README.md` alterando os exemplos na seção de "Instalação", "Testes" e "Scripts" para utilizarem o `make`.

## Critérios de Aceite
- `README.md` não deve conter `docker compose up -d` na instalação, e sim `make up`.
- Comandos de testes devem ser `make test`, `make test-unit`, etc.
