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
LATEST_FILE="${1:-}"

if [[ -z "${BACKUP_PASSPHRASE:-}" ]]; then
  echo "ERROR: BACKUP_PASSPHRASE belum di-set."
  exit 1
fi

if [[ -z "${LATEST_FILE}" ]]; then
  LATEST_FILE="$(ls -1t "${BACKUP_ROOT}"/lawangsewu_*.tar.enc 2>/dev/null | head -n1 || true)"
fi

if [[ -z "${LATEST_FILE}" || ! -f "${LATEST_FILE}" ]]; then
  echo "ERROR: file backup terenkripsi tidak ditemukan."
  exit 1
fi

export BACKUP_PASSPHRASE
WORK_DIR="$(mktemp -d)"
trap 'rm -rf "${WORK_DIR}"' EXIT

DECRYPTED_TAR="${WORK_DIR}/payload.tar"
EXTRACT_DIR="${WORK_DIR}/extract"
mkdir -p "${EXTRACT_DIR}"

openssl enc -d -aes-256-cbc -pbkdf2 -iter 100000 \
  -in "${LATEST_FILE}" \
  -out "${DECRYPTED_TAR}" \
  -pass env:BACKUP_PASSPHRASE

tar -xf "${DECRYPTED_TAR}" -C "${EXTRACT_DIR}"

BASENAME="$(basename "${LATEST_FILE}")"
TIMESTAMP="${BASENAME#lawangsewu_}"
TIMESTAMP="${TIMESTAMP%.tar.enc}"

REPO_BUNDLE="${EXTRACT_DIR}/repo_${TIMESTAMP}.bundle"
SOURCE_TGZ="${EXTRACT_DIR}/source_${TIMESTAMP}.tar.gz"

[[ -f "${REPO_BUNDLE}" ]] || { echo "ERROR: repo bundle tidak ada di backup."; exit 1; }
[[ -f "${SOURCE_TGZ}" ]] || { echo "ERROR: source snapshot tidak ada di backup."; exit 1; }

git bundle verify "${REPO_BUNDLE}" >/dev/null

tar -tzf "${SOURCE_TGZ}" >/dev/null

echo "Backup OK: ${LATEST_FILE}"
echo "- repo bundle valid"
echo "- source snapshot valid"
if ls "${EXTRACT_DIR}"/db_*.sql >/dev/null 2>&1; then
  echo "- database dump tersedia"
else
  echo "- database dump tidak tersedia (opsional)"
fi
if ls "${EXTRACT_DIR}"/env_*.env >/dev/null 2>&1; then
  echo "- env backup tersedia"
else
  echo "- env backup tidak tersedia (opsional)"
fi
