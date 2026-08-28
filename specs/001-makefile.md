# SPEC-001: Implementação do Makefile

## Objetivo
Adicionar um `Makefile` na raiz do projeto para padronizar e facilitar a execução de comandos frequentes (Docker, Composer, Testes e Análise Estática).

## Motivação
Desenvolvedores e CI/CD precisam de uma interface simples para interagir com o projeto sem precisar decorar comandos complexos do `docker-compose` ou `composer`.

## Escopo
- Criar o arquivo `Makefile` com targets: `up`, `down`, `build`, `bash`, `test`, `analyse`, `swagger`.
- Todos os comandos relacionados ao PHP (composer, testes) devem rodar dentro do container `app`.
- Atualizar a documentação `README.md` mencionando o Makefile.

## Critérios de Aceite
- `make up` sobe os containers.
- `make test` executa testes unitários.
- `README.md` reflete as instruções atualizadas.
