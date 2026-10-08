# ─────────────────────────────────────────────────────────────────────────────
# Makefile — Lawangsewu Docker Management
# Penggunaan: make <target>
# ─────────────────────────────────────────────────────────────────────────────

DOCKER_HUB_IMAGE  = zhayyn/lawangsewu
GHCR_IMAGE        = ghcr.io/zhayyn/lawangsewu
VERSION           ?= $(shell git describe --tags --always --dirty 2>/dev/null || echo "latest")

.PHONY: help build push up down restart logs shell backup restore setup

# ── Default: tampilkan bantuan ────────────────────────────────────────────────
help:
	@echo ""
	@echo "  🐳 Lawangsewu Docker Commands"
	@echo "  ─────────────────────────────────────────"
	@echo "  make setup    → Setup pertama kali (env, key, migrate, storage)"
	@echo "  make build    → Build Docker image"
	@echo "  make push     → Push image ke Docker Hub & GHCR"
	@echo "  make up       → Jalankan semua container"
	@echo "  make down     → Hentikan semua container"
	@echo "  make restart  → Restart semua container"
	@echo "  make logs     → Lihat logs semua container"
	@echo "  make shell    → Masuk ke container app"
	@echo "  make backup   → Backup database"
	@echo "  make restore  → Restore database (perlu BACKUP_FILE=...)"
	@echo ""

# ── Setup pertama kali ────────────────────────────────────────────────────────
setup:
	@echo "⚙️  Setup Lawangsewu..."
	@[ -f .env ] || (cp .env.docker .env && echo "📝 .env dibuat dari .env.docker — EDIT DULU sebelum lanjut!" && exit 1)
	docker compose up -d db redis
	@echo "⏳ Menunggu database siap..."
	@sleep 10
	docker compose run --rm app php artisan key:generate --force
	docker compose run --rm app php artisan migrate --force
	docker compose run --rm app php artisan storage:link
	docker compose run --rm app php artisan optimize
	docker compose up -d
	@echo "✅ Lawangsewu siap di http://localhost (atau domain yang dikonfigurasi)"

# ── Build ─────────────────────────────────────────────────────────────────────
build:
	@echo "🔨 Building image $(GHCR_IMAGE):$(VERSION)..."
	docker build --no-cache --target app -t lawangsewu-app .
	docker tag lawangsewu-app $(GHCR_IMAGE):$(VERSION)
	docker tag lawangsewu-app $(GHCR_IMAGE):latest
	docker tag lawangsewu-app $(DOCKER_HUB_IMAGE):$(VERSION)
	docker tag lawangsewu-app $(DOCKER_HUB_IMAGE):latest
	@echo "✅ Build selesai: $(GHCR_IMAGE):$(VERSION)"

# ── Push ke Docker Hub & GHCR ─────────────────────────────────────────────────
push: build
	@echo "🚀 Push ke Docker Hub..."
	docker push $(DOCKER_HUB_IMAGE):$(VERSION)
	docker push $(DOCKER_HUB_IMAGE):latest
	@echo "🚀 Push ke GitHub Container Registry..."
	docker push $(GHCR_IMAGE):$(VERSION)
	docker push $(GHCR_IMAGE):latest
	@echo "✅ Push selesai!"

# ── Container management ──────────────────────────────────────────────────────
up:
	docker compose up -d
	@echo "✅ Containers berjalan. Cek: docker compose ps"

down:
	docker compose down

restart:
	docker compose restart

logs:
	docker compose logs -f --tail=100

# ── Shell ─────────────────────────────────────────────────────────────────────
shell:
	docker compose exec app bash

# ── Database backup & restore ─────────────────────────────────────────────────
backup:
	@bash docker/scripts/backup-db.sh

restore:
	@[ -n "$(BACKUP_FILE)" ] || (echo "❌ Tentukan file: make restore BACKUP_FILE=docker/backups/file.sql.gz" && exit 1)
	@bash docker/scripts/restore-db.sh $(BACKUP_FILE)

# ── Artisan shortcuts ─────────────────────────────────────────────────────────
migrate:
	docker compose exec app php artisan migrate --force

optimize:
	docker compose exec app php artisan optimize

clear:
	docker compose exec app php artisan optimize:clear
