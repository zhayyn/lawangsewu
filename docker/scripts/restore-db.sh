#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# restore-db.sh — Restore database Lawangsewu ke Docker container
#
# Penggunaan:
#   ./docker/scripts/restore-db.sh docker/backups/lawangsewu-db-20260101.sql.gz
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

BACKUP_FILE="${1:-}"

if [[ -z "${BACKUP_FILE}" ]]; then
    echo "❌ Error: Tentukan file backup!"
    echo "   Penggunaan: $0 <file.sql.gz>"
    echo ""
    echo "   File backup tersedia:"
    ls docker/backups/*.sql.gz 2>/dev/null || echo "   (tidak ada)"
    exit 1
fi

if [[ ! -f "${BACKUP_FILE}" ]]; then
    echo "❌ Error: File '${BACKUP_FILE}' tidak ditemukan!"
    exit 1
fi

DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-changeme_root}"

echo "⚠️  PERHATIAN: Ini akan menimpa database yang ada!"
read -r -p "Lanjutkan? (ketik 'ya' untuk konfirmasi): " CONFIRM

if [[ "${CONFIRM}" != "ya" ]]; then
    echo "❌ Dibatalkan."
    exit 0
fi

echo "🔄 Restore database dari: ${BACKUP_FILE}"

gunzip -c "${BACKUP_FILE}" | docker compose exec -T db \
    mysql \
    --user=root \
    --password="${DB_ROOT_PASSWORD}"

echo "✅ Restore selesai!"
echo ""
echo "🔧 Jalankan migrations jika perlu:"
echo "   docker compose exec app php artisan migrate --force"
