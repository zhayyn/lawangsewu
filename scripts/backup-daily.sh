#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
SCRIPT_DIR="${PROJECT_DIR}/scripts"
CONFIG_FILE="${SCRIPT_DIR}/backup.conf"

if [[ -f "${CONFIG_FILE}" ]]; then
  # shellcheck disable=SC1090
  source "${CONFIG_FILE}"
fi

BACKUP_ROOT="${BACKUP_ROOT:-/var/backups/lawangsewu}"
KEEP_DAYS="${KEEP_DAYS:-14}"
ENV_FILE="${ENV_FILE:-${PROJECT_DIR}/.env}"

if [[ -z "${BACKUP_PASSPHRASE:-}" ]]; then
  echo "ERROR: BACKUP_PASSPHRASE belum di-set. Isi di scripts/backup.conf"
  exit 1
fi

export BACKUP_PASSPHRASE

TIMESTAMP="$(date +%F_%H%M%S)"
WORK_DIR="${BACKUP_ROOT}/work_${TIMESTAMP}"
mkdir -p "${WORK_DIR}" "${BACKUP_ROOT}"

cleanup() {
  rm -rf "${WORK_DIR}"
}
trap cleanup EXIT

echo "[1/5] Backup git bundle..."
git -C "${PROJECT_DIR}" bundle create "${WORK_DIR}/repo_${TIMESTAMP}.bundle" --all

echo "[2/5] Backup source snapshot..."
tar \
  --exclude='.git' \
  --exclude='logs' \
  -czf "${WORK_DIR}/source_${TIMESTAMP}.tar.gz" \
  -C "${PROJECT_DIR}" .

if [[ -n "${DB_NAME:-}" && -n "${DB_USER:-}" ]]; then
  echo "[3/5] Backup database..."
  DB_HOST="${DB_HOST:-127.0.0.1}"
  DB_PORT="${DB_PORT:-3306}"
  DB_PASS_OPT=""
  if [[ -n "${DB_PASSWORD:-}" ]]; then
    DB_PASS_OPT="-p${DB_PASSWORD}"
  fi
  mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" ${DB_PASS_OPT} "${DB_NAME}" > "${WORK_DIR}/db_${TIMESTAMP}.sql"
else
  echo "[3/5] Skip database backup (DB_NAME/DB_USER belum diisi)."
fi

if [[ -f "${ENV_FILE}" ]]; then
  echo "[4/5] Backup env file..."
  cp "${ENV_FILE}" "${WORK_DIR}/env_${TIMESTAMP}.env"
else
  echo "[4/5] Skip env backup (file tidak ditemukan: ${ENV_FILE})."
fi

echo "[5/5] Encrypt backup package..."
PAYLOAD_TAR="${BACKUP_ROOT}/lawangsewu_${TIMESTAMP}.tar"
ENCRYPTED_FILE="${BACKUP_ROOT}/lawangsewu_${TIMESTAMP}.tar.enc"
tar -cf "${PAYLOAD_TAR}" -C "${WORK_DIR}" .
openssl enc -aes-256-cbc -salt -pbkdf2 -iter 100000 \
  -in "${PAYLOAD_TAR}" \
  -out "${ENCRYPTED_FILE}" \
  -pass env:BACKUP_PASSPHRASE
rm -f "${PAYLOAD_TAR}"

find "${BACKUP_ROOT}" -type f -name 'lawangsewu_*.tar.enc' -mtime +"${KEEP_DAYS}" -delete

echo "Backup selesai: ${ENCRYPTED_FILE}"
