#!/usr/bin/env bash
set -euo pipefail

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SOURCE_DIR="${BASE_DIR}/projects/website-pa-semarang/lumpiapasar-base"
ACTIVE_DIR="${BASE_DIR}/projects/website-pa-semarang/lumpiapasar-s9-active"
WEB_LINK="/var/www/html/lumpiapasar-s9"

MODE="dry-run"
if [[ "${1:-}" == "--apply" ]]; then
  MODE="apply"
fi

if [[ ! -d "${SOURCE_DIR}" ]]; then
  echo "ERROR: source tidak ditemukan: ${SOURCE_DIR}" >&2
  exit 1
fi

if [[ ! -d "${ACTIVE_DIR}" ]]; then
  echo "ERROR: active tidak ditemukan: ${ACTIVE_DIR}" >&2
  exit 1
fi

if [[ ! -L "${WEB_LINK}" ]]; then
  echo "WARNING: ${WEB_LINK} bukan symlink. Lanjut deploy tetap bisa, tapi mohon cek struktur aktif." >&2
fi

RSYNC_ARGS=(
  -av --delete
  --exclude ".git/"
  --exclude ".env"
  --exclude "error_log"
)

if [[ "${MODE}" == "dry-run" ]]; then
  RSYNC_ARGS+=(--dry-run)
  echo "Mode: DRY-RUN (tidak ada perubahan file)"
else
  echo "Mode: APPLY (sinkronisasi akan dijalankan)"
fi

echo "Source: ${SOURCE_DIR}/"
echo "Active: ${ACTIVE_DIR}/"
echo

rsync "${RSYNC_ARGS[@]}" "${SOURCE_DIR}/" "${ACTIVE_DIR}/"

if [[ "${MODE}" == "apply" ]]; then
  chown -R www-data:www-data "${ACTIVE_DIR}"
  echo
  echo "Deploy selesai. Ownership diset ke www-data:www-data"
else
  echo
  echo "Dry-run selesai. Jalankan dengan --apply untuk eksekusi nyata."
fi
