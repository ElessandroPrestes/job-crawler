# SPEC-009: Testes End-to-End com Playwright

## Objetivo
Adicionar uma suíte de testes E2E (Ponta-a-Ponta) automatizados utilizando o framework Playwright para validar a renderização correta do Dashboard UI na raiz (`/`) e a API Docs (`/docs/`), garantindo que a infraestrutura e os roteamentos (sem redirects indesejados) funcionem.

## Requisitos
1. Instalar o Playwright no diretório de testes (`tests/e2e/`).
2. Criar testes automatizados que naveguem até `http://localhost:8080/`.
3. Validar se o Dashboard carrega (deve existir o elemento de título ou o texto "Radar de Vagas"), provando que não há redirecionamento para `/docs/`.
4. Validar se `http://localhost:8080/docs/` carrega o Swagger UI corretamente.

## Implementação
- Inicializar projeto NPM em `tests/e2e` e adicionar `@playwright/test`.
- Escrever os cenários E2E.
- Opcionalmente adicionar um comando `make test-e2e` no `Makefile`.
