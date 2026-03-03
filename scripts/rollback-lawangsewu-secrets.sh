#!/usr/bin/env bash
set -euo pipefail

SNAPSHOT_BASE="/root/lawangsewu-rollback"
ACTIVE_ENV="/var/www/html/lawangsewu/projects/website-pa-semarang/lumpiapasar-s9-active/.env"
GATEWAY_ENV="/var/www/html/lawangsewu/gateway/.env"

if [[ "${EUID}" -ne 0 ]]; then
  echo "ERROR: jalankan sebagai root" >&2
  exit 1
fi

SNAPSHOT_DIR="${1:-}"
if [[ -z "${SNAPSHOT_DIR}" ]]; then
  SNAPSHOT_DIR="$(ls -1dt "${SNAPSHOT_BASE}"/snapshot-* 2>/dev/null | head -n1 || true)"
fi

if [[ -z "${SNAPSHOT_DIR}" || ! -d "${SNAPSHOT_DIR}" ]]; then
  echo "ERROR: snapshot tidak ditemukan. Berikan path snapshot sebagai argumen." >&2
  exit 1
fi

if [[ ! -f "${SNAPSHOT_DIR}/lumpiapasar-s9-active.env" || ! -f "${SNAPSHOT_DIR}/gateway.env" ]]; then
  echo "ERROR: isi snapshot tidak lengkap: ${SNAPSHOT_DIR}" >&2
  exit 1
fi

cp "${SNAPSHOT_DIR}/lumpiapasar-s9-active.env" "${ACTIVE_ENV}"
cp "${SNAPSHOT_DIR}/gateway.env" "${GATEWAY_ENV}"

chown www-data:www-data "${ACTIVE_ENV}" "${GATEWAY_ENV}"
chmod 640 "${ACTIVE_ENV}" "${GATEWAY_ENV}"

LUMPIA_CODE="$(curl -s -o /dev/null -w '%{http_code}' http://192.168.88.9/lumpiapasar-s9/)"
INFO_CODE="$(curl -s -o /dev/null -w '%{http_code}' https://lawangsewu.pa-semarang.go.id/info-persidangan)"

echo "Rollback selesai dari: ${SNAPSHOT_DIR}"
echo "LUMPIA_HTTP=${LUMPIA_CODE}"
echo "INFO_HTTP=${INFO_CODE}"

if [[ "${LUMPIA_CODE}" != "200" || "${INFO_CODE}" != "200" ]]; then
  echo "WARNING: salah satu endpoint tidak 200, cek log aplikasi/web server." >&2
  exit 1
fi

echo "Status: OK"
