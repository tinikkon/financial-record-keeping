# Короткие команды проекта. Все они работают через docker compose,
# локальные версии PHP и Node на машине не используются и не меняются.

UID := $(shell id -u)
GID := $(shell id -g)
export UID
export GID

COMPOSE := docker compose

.DEFAULT_GOAL := help

.PHONY: help
help: ## Список команд
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2}'

.PHONY: init
init: ## Первичная настройка: файл окружения и зависимости
	@test -f .env || cp .env.example .env
	$(COMPOSE) build
	$(MAKE) install

.PHONY: install
install: ## Установить зависимости всех частей проекта
	$(COMPOSE) run --rm --no-deps sheet-app composer install
	$(COMPOSE) run --rm --no-deps history-app composer install
	$(COMPOSE) run --rm --no-deps --workdir /app/packages/formula-engine sheet-app composer install
	$(COMPOSE) run --rm --no-deps frontend npm install

.PHONY: up
up: ## Поднять всё окружение
	$(COMPOSE) up -d

.PHONY: down
down: ## Остановить окружение
	$(COMPOSE) down --remove-orphans

.PHONY: restart
restart: down up ## Перезапустить окружение

.PHONY: logs
logs: ## Логи всех контейнеров
	$(COMPOSE) logs -f --tail=100

.PHONY: shell
shell: ## Оболочка внутри контейнера сервиса таблиц
	$(COMPOSE) exec sheet-app bash

.PHONY: mongo
mongo: ## Консоль MongoDB
	$(COMPOSE) exec mongo mongosh "mongodb://$$(grep MONGO_ROOT_USER .env | cut -d= -f2):$$(grep MONGO_ROOT_PASSWORD .env | cut -d= -f2)@localhost:27017/?authSource=admin"

.PHONY: test
test: test-engine test-sheet test-history test-frontend ## Все тесты

.PHONY: test-engine
test-engine: ## Тесты движка формул
	$(COMPOSE) run --rm --no-deps --workdir /app/packages/formula-engine sheet-app vendor/bin/pest

.PHONY: test-sheet
test-sheet: ## Тесты сервиса таблиц
	$(COMPOSE) exec sheet-app vendor/bin/pest

.PHONY: test-history
test-history: ## Тесты сервиса истории
	$(COMPOSE) exec history-app vendor/bin/pest

.PHONY: test-frontend
test-frontend: ## Тесты фронтенда
	$(COMPOSE) run --rm --no-deps frontend npm run test

.PHONY: test-e2e
test-e2e: ## Сквозные тесты в браузере
	cd e2e && npx playwright test

.PHONY: analyse
analyse: ## Статический анализ и стиль
	$(COMPOSE) run --rm --no-deps --workdir /app/packages/formula-engine sheet-app vendor/bin/phpstan analyse
	$(COMPOSE) exec sheet-app vendor/bin/phpstan analyse
	$(COMPOSE) exec history-app vendor/bin/phpstan analyse
