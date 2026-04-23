#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="${1:-/var/www/lawangsewu}"
ENV_FILE="${ROOT_DIR}/.env"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "File .env tidak ditemukan di ${ENV_FILE}" >&2
  exit 1
fi

set -a
source "${ENV_FILE}"
set +a

SOURCE_HOST="${DB_HOST:-127.0.0.1}"
SOURCE_PORT="${DB_PORT:-3306}"
SOURCE_DB="${DB_DATABASE:-}"
SOURCE_USER="${DB_USERNAME:-}"
SOURCE_PASS="${DB_PASSWORD:-}"
SOURCE_SOCKET="${DB_SOCKET:-}"

TARGET_HOST="${WA_CARAKA_DB_HOST:-${SOURCE_HOST}}"
TARGET_PORT="${WA_CARAKA_DB_PORT:-${SOURCE_PORT}}"
TARGET_DB="${WA_CARAKA_DB_DATABASE:-}"

if [[ -z "${SOURCE_DB}" || -z "${TARGET_DB}" ]]; then
  echo "DB_DATABASE dan WA_CARAKA_DB_DATABASE harus terisi di .env" >&2
  exit 1
fi

if [[ "${SOURCE_HOST}" != "${TARGET_HOST}" || "${SOURCE_PORT}" != "${TARGET_PORT}" ]]; then
  echo "Script ini hanya mendukung source dan target MySQL pada host/port yang sama." >&2
  exit 1
fi

MYSQL_ARGS=()
if [[ -n "${SOURCE_SOCKET}" ]]; then
  MYSQL_ARGS+=(--socket="${SOURCE_SOCKET}")
else
  MYSQL_ARGS+=(--host="${SOURCE_HOST}" --port="${SOURCE_PORT}")
fi
MYSQL_ARGS+=(--user="${SOURCE_USER}")
if [[ -n "${SOURCE_PASS}" ]]; then
  MYSQL_ARGS+=(--password="${SOURCE_PASS}")
fi

TABLES=(
  wa_caraka_logs
  wa_caraka_messages
  wa_caraka_conversations
  wa_caraka_handovers
  wa_caraka_conversation_marks
  wa_caraka_menus
  wa_caraka_sessions
  wa_caraka_tickets
  wa_caraka_settings
  wa_caraka_sync_runs
  wa_caraka_daily_metrics
  wa_caraka_monthly_snapshots
)

echo "Membuat database target jika belum ada: ${TARGET_DB}"
mysql "${MYSQL_ARGS[@]}" -e "CREATE DATABASE IF NOT EXISTS \`${TARGET_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

for table in "${TABLES[@]}"; do
  echo "Menyalin ${SOURCE_DB}.${table} -> ${TARGET_DB}.${table}"
  mysql "${MYSQL_ARGS[@]}" -e "DROP TABLE IF EXISTS \`${TARGET_DB}\`.\`${table}\`;"
  mysql "${MYSQL_ARGS[@]}" -e "CREATE TABLE \`${TARGET_DB}\`.\`${table}\` LIKE \`${SOURCE_DB}\`.\`${table}\`;"
  mysql "${MYSQL_ARGS[@]}" -e "INSERT INTO \`${TARGET_DB}\`.\`${table}\` SELECT * FROM \`${SOURCE_DB}\`.\`${table}\`;"
done

cat <<EOF

Selesai menyalin tabel WA Caraka.

Langkah berikutnya:
1. Set WA_CARAKA_DB_CONNECTION=wa_caraka
2. Pastikan WA_CARAKA_DB_DATABASE=${TARGET_DB}
3. Jalankan:
   php artisan optimize:clear
   php artisan config:cache

Catatan:
- Script ini tidak menghapus data WA di database utama.
- Gunakan saat source dan target berada pada server MySQL yang sama.
EOF
