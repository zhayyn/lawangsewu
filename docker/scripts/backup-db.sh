#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# backup-db.sh — Backup database Lawangsewu dari Docker container
#
# Penggunaan:
#   ./docker/scripts/backup-db.sh
#   ./docker/scripts/backup-db.sh mybackup.sql.gz
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="./docker/backups"
OUTPUT="${1:-${BACKUP_DIR}/lawangsewu-db-${TIMESTAMP}.sql.gz}"

# Baca dari .env
DB_DATABASE="${DB_DATABASE:-lawangsewu}"
DB_USERNAME="${DB_USERNAME:-lawangsewu}"
DB_PASSWORD="${DB_PASSWORD:-changeme}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-changeme_root}"

mkdir -p "${BACKUP_DIR}"

echo "🗄️  Backup database '${DB_DATABASE}' → ${OUTPUT}"

docker compose exec -T db \
    mysqldump \
    --user=root \
    --password="${DB_ROOT_PASSWORD}" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-database \
    --databases "${DB_DATABASE}" \
    | gzip > "${OUTPUT}"

SIZE=$(du -sh "${OUTPUT}" | cut -f1)
echo "✅ Backup selesai: ${OUTPUT} (${SIZE})"
echo ""
echo "📋 Cara restore:"
echo "   ./docker/scripts/restore-db.sh ${OUTPUT}"
