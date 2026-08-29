.PHONY: help up down build bash test analyse audit swagger

# Cores para o help
CYAN=\033[0;36m
NC=\033[0m

help: ## Mostra os comandos disponíveis
	@echo "${CYAN}Comandos disponíveis no Makefile:${NC}"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Inicia os containers Docker em background
	docker-compose up -d

down: ## Derruba os containers e remove as redes
	docker-compose down

build: ## Constrói as imagens do docker-compose
	docker-compose build

bash: ## Acessa o terminal bash do container app
	docker-compose exec app bash

test: ## Executa os testes usando PHPUnit (dentro do container app)
	docker-compose exec app composer test

analyse: ## Roda a análise estática (PHPStan) no container app
	docker-compose exec app composer analyse

audit: ## Verifica vulnerabilidades de dependências no container app
	docker-compose exec app composer audit

swagger: ## Gera a documentação Swagger OpenAPI
	docker-compose exec app composer swagger:generate

install: ## Instala as dependências via Composer no container
	docker-compose exec app composer install

setup-storage: ## Cria e ajusta permissões dos diretórios de storage
	docker-compose exec -u root app mkdir -p /var/www/html/storage/logs /var/www/html/storage/exports
	docker-compose exec -u root app chown -R www-data:www-data /var/www/html/storage

test-unit: ## Executa apenas testes unitários
	docker-compose exec app composer test:unit

test-integration: ## Executa apenas testes de integração
	docker-compose exec app composer test:integration

test-feature: ## Executa apenas testes de feature
	docker-compose exec app composer test:feature

test-coverage: ## Executa testes com relatório de cobertura HTML
	docker-compose exec app composer test:coverage

export: ## Executa o script de exportação (use ARGS="--format=csv")
	docker-compose exec app php scripts/export.php $(ARGS)
test-e2e:
	docker run --rm --network host -v "$(PWD)/tests/e2e:/e2e" -w /e2e mcr.microsoft.com/playwright:v1.44.0-jammy npx playwright test

build-css:
	npx tailwindcss -i ./public/css/input.css -o ./public/css/style.css --minify
