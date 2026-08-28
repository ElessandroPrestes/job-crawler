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
