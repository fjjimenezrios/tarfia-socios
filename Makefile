.DEFAULT_GOAL := help
.PHONY: help init up down build restart logs shell db-shell migrate makemigrations \
        superuser dev dev-down lint test clean import-mdb

COMPOSE     := docker compose
COMPOSE_DEV := docker compose -f docker-compose.yml -f docker-compose.dev.yml
PYTHON      := python3

# ── Colores ────────────────────────────────────────────────────────────────────
BOLD  := \033[1m
RESET := \033[0m
GREEN := \033[32m
CYAN  := \033[36m

# ══════════════════════════════════════════════════════════════════════════════
help: ## Muestra esta ayuda
	@echo ""
	@echo "  $(BOLD)TarfíaDB — comandos disponibles$(RESET)"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*##/ \
		{ printf "  $(CYAN)%-20s$(RESET) %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@echo ""

# ── Setup ──────────────────────────────────────────────────────────────────────
init: ## ① Genera .env, construye imágenes y aplica migraciones
	@echo "$(BOLD)→ Generando .env con secretos aleatorios...$(RESET)"
	@$(PYTHON) utils/generate_env.py
	@echo ""
	@echo "$(BOLD)→ Construyendo imágenes Docker...$(RESET)"
	$(COMPOSE) build
	@echo ""
	@echo "$(BOLD)→ Arrancando base de datos...$(RESET)"
	$(COMPOSE) up -d db
	@echo "  Esperando a que PostgreSQL esté listo..."
	@$(COMPOSE) run --rm backend sh -c \
		"until pg_isready -h $$DB_HOST -U $$DB_USER; do sleep 1; done"
	@echo ""
	@echo "$(BOLD)→ Aplicando migraciones...$(RESET)"
	$(COMPOSE) run --rm backend python manage.py migrate
	@echo ""
	@echo "$(GREEN)$(BOLD)✓ Listo. Ejecuta 'make superuser' para crear el admin, luego 'make up'.$(RESET)"
	@echo ""

init-force: ## Genera .env sobreescribiendo el existente (⚠ borra secretos anteriores)
	@$(PYTHON) utils/generate_env.py --force
	@$(MAKE) --no-print-directory init

# ── Producción ─────────────────────────────────────────────────────────────────
up: ## Arranca todos los servicios en producción
	$(COMPOSE) up -d
	@echo "$(GREEN)✓ Servicios arrancados$(RESET)"

down: ## Para todos los servicios
	$(COMPOSE) down

restart: ## Reinicia todos los servicios
	$(COMPOSE) restart

build: ## Reconstruye las imágenes sin caché
	$(COMPOSE) build --no-cache

logs: ## Logs en tiempo real de todos los servicios
	$(COMPOSE) logs -f

logs-backend: ## Logs sólo del backend
	$(COMPOSE) logs -f backend

logs-nginx: ## Logs sólo de nginx
	$(COMPOSE) logs -f nginx

# ── Django ─────────────────────────────────────────────────────────────────────
migrate: ## Aplica migraciones de Django
	$(COMPOSE) exec backend python manage.py migrate

makemigrations: ## Crea nuevas migraciones
	$(COMPOSE) exec backend python manage.py makemigrations

superuser: ## Crea un superusuario de Django
	$(COMPOSE) exec backend python manage.py createsuperuser

shell: ## Abre el shell interactivo de Django
	$(COMPOSE) exec backend python manage.py shell

shell-plus: ## Shell con auto-import de modelos (requiere django-extensions)
	$(COMPOSE) exec backend python manage.py shell_plus

collectstatic: ## Recoge archivos estáticos
	$(COMPOSE) exec backend python manage.py collectstatic --noinput

# ── Base de datos ──────────────────────────────────────────────────────────────
db-shell: ## Abre psql dentro del contenedor de PostgreSQL
	$(COMPOSE) exec db psql -U $${DB_USER:-tarfia} $${DB_NAME:-tarfia_socios}

db-dump: ## Exporta un dump de la base de datos a backups/dump_$(date).sql
	@mkdir -p backups
	$(COMPOSE) exec db pg_dump -U $${DB_USER:-tarfia} $${DB_NAME:-tarfia_socios} \
		> backups/dump_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✓ Dump guardado en backups/$(RESET)"

db-restore: ## Restaura un dump. Uso: make db-restore FILE=backups/dump.sql
	@test -n "$(FILE)" || (echo "Uso: make db-restore FILE=backups/dump.sql" && exit 1)
	$(COMPOSE) exec -T db psql -U $${DB_USER:-tarfia} $${DB_NAME:-tarfia_socios} < $(FILE)

# ── Migración MDB ──────────────────────────────────────────────────────────────
import-mdb: ## Importa un .mdb a PostgreSQL. Uso: make import-mdb MDB=socios.mdb
	@test -n "$(MDB)" || (echo "$(BOLD)Uso:$(RESET) make import-mdb MDB=ruta/archivo.mdb" && exit 1)
	@echo "$(BOLD)→ Migrando $(MDB) a PostgreSQL...$(RESET)"
	$(PYTHON) utils/mdb_to_postgres.py $(MDB)

# ── Desarrollo ─────────────────────────────────────────────────────────────────
dev: ## Arranca el entorno de desarrollo (hot-reload)
	$(COMPOSE_DEV) up

dev-down: ## Para el entorno de desarrollo
	$(COMPOSE_DEV) down

dev-build: ## Reconstruye imágenes de desarrollo
	$(COMPOSE_DEV) build

dev-migrate: ## Migraciones en entorno de desarrollo
	$(COMPOSE_DEV) exec backend python manage.py migrate

dev-shell: ## Shell Django en entorno de desarrollo
	$(COMPOSE_DEV) exec backend python manage.py shell

# ── Calidad ────────────────────────────────────────────────────────────────────
lint: ## Linting del backend (ruff) y frontend (eslint)
	$(COMPOSE) run --rm backend ruff check .
	cd frontend && npm run lint

test: ## Ejecuta los tests de Django
	$(COMPOSE) run --rm backend python manage.py test --verbosity=2

# ── Limpieza ───────────────────────────────────────────────────────────────────
clean: ## ⚠ Elimina contenedores y volúmenes (borra la base de datos)
	@echo "$(BOLD)⚠  Esto eliminará todos los datos. ¿Continuar? [s/N]$(RESET)"; \
	read ans; [ "$$ans" = "s" ] || exit 0
	$(COMPOSE) down -v --remove-orphans
	@echo "$(GREEN)✓ Entorno limpio$(RESET)"

clean-images: ## Elimina imágenes Docker del proyecto
	$(COMPOSE) down --rmi local
