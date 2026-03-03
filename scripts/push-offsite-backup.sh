#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
SCRIPT_DIR="${PROJECT_DIR}/scripts"
CONFIG_FILE="${SCRIPT_DIR}/offsite.conf"

if [[ -f "${CONFIG_FILE}" ]]; then
  # shellcheck disable=SC1090
  source "${CONFIG_FILE}"
fi

LOCAL_BACKUP_DIR="${LOCAL_BACKUP_DIR:-/var/backups/lawangsewu}"
OFFSITE_REMOTE="${OFFSITE_REMOTE:-privatecloud:lawangsewu-backup}"
FILE_PATTERN="${FILE_PATTERN:-lawangsewu_*.tar.enc}"
KEEP_DAYS_LOCAL="${KEEP_DAYS_LOCAL:-14}"

if ! command -v rclone >/dev/null 2>&1; then
  echo "ERROR: rclone belum terpasang. Install dulu lalu jalankan 'rclone config'."
  exit 1
fi

if [[ ! -d "${LOCAL_BACKUP_DIR}" ]]; then
  echo "ERROR: folder backup lokal tidak ditemukan: ${LOCAL_BACKUP_DIR}"
  exit 1
fi

echo "[1/3] Cek file backup lokal..."
count_local=$(find "${LOCAL_BACKUP_DIR}" -maxdepth 1 -type f -name "${FILE_PATTERN}" | wc -l)
if [[ "${count_local}" -eq 0 ]]; then
  echo "Tidak ada file backup dengan pola ${FILE_PATTERN} di ${LOCAL_BACKUP_DIR}"
  exit 1
fi

echo "[2/3] Upload ke offsite cloud: ${OFFSITE_REMOTE}"
rclone copy "${LOCAL_BACKUP_DIR}" "${OFFSITE_REMOTE}" \
  --include "${FILE_PATTERN}" \
  --check-first \
  --transfers 4 \
  --checkers 8 \
  --progress

echo "[3/3] Verifikasi list offsite"
rclone ls "${OFFSITE_REMOTE}" | tail -n 20 || true

find "${LOCAL_BACKUP_DIR}" -maxdepth 1 -type f -name "${FILE_PATTERN}" -mtime +"${KEEP_DAYS_LOCAL}" -delete

echo "Push offsite selesai."
