#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
BACKUP_SCRIPT="${PROJECT_DIR}/scripts/backup-daily.sh"
VERIFY_SCRIPT="${PROJECT_DIR}/scripts/verify-backup.sh"
CONF_FILE="${PROJECT_DIR}/scripts/restore-drill.conf"
LOG_DIR="${PROJECT_DIR}/logs"
LOG_FILE="${LOG_DIR}/restore-drill.log"
HOSTNAME_VALUE="$(hostname -f 2>/dev/null || hostname)"

# Optional local config so alert settings survive shell sessions.
if [[ -f "${CONF_FILE}" ]]; then
  # shellcheck source=/dev/null
  source "${CONF_FILE}"
fi

ALERT_WEBHOOK_URL="${RESTORE_DRILL_ALERT_WEBHOOK_URL:-}"
ALERT_COMMAND="${RESTORE_DRILL_ALERT_COMMAND:-}"
ALERT_WA_API_URL="${RESTORE_DRILL_ALERT_WA_API_URL:-http://127.0.0.1:8793/send-text}"
ALERT_WA_TOKEN="${RESTORE_DRILL_ALERT_WA_TOKEN:-}"
ALERT_WA_SUPERADMIN_NUMBERS="${RESTORE_DRILL_ALERT_WA_SUPERADMIN_NUMBERS:-}"
ALERT_WA_MODE="${RESTORE_DRILL_ALERT_WA_MODE:-error}"

mkdir -p "${LOG_DIR}"

log() {
  printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" | tee -a "${LOG_FILE}"
}

notify_alert() {
  local level="$1"
  local message="$2"

  if [[ -n "${ALERT_WEBHOOK_URL}" ]]; then
    curl -sS -m 10 -X POST "${ALERT_WEBHOOK_URL}" \
      -H 'Content-Type: application/json' \
      -d "{\"service\":\"restore-drill\",\"level\":\"${level}\",\"host\":\"${HOSTNAME_VALUE}\",\"message\":\"${message}\"}" \
      >/dev/null || true
  fi

  if [[ -n "${ALERT_COMMAND}" ]]; then
    ALERT_LEVEL="${level}" ALERT_MESSAGE="${message}" ALERT_HOST="${HOSTNAME_VALUE}" bash -lc "${ALERT_COMMAND}" || true
  fi

  notify_wa_superadmin "${level}" "${message}"
}

notify_wa_superadmin() {
  local level="$1"
  local message="$2"
  local mode="${ALERT_WA_MODE,,}"

  if [[ -z "${ALERT_WA_SUPERADMIN_NUMBERS}" ]]; then
    return
  fi

  case "${mode}" in
    off|disabled|none)
      return
      ;;
    all)
      ;;
    *)
      # Default: only notify WA on error-level events.
      if [[ "${level}" != "error" ]]; then
        return
      fi
      ;;
  esac

  local payload_text
  payload_text="[restore-drill][${level}][${HOSTNAME_VALUE}] ${message}"

  local number token_header
  token_header=()
  if [[ -n "${ALERT_WA_TOKEN}" ]]; then
    token_header=(-H "x-device-token: ${ALERT_WA_TOKEN}")
  fi

  IFS=',' read -r -a numbers <<< "${ALERT_WA_SUPERADMIN_NUMBERS}"
  for number in "${numbers[@]}"; do
    number="$(echo "${number}" | tr -d '[:space:]')"
    if [[ -z "${number}" ]]; then
      continue
    fi

    curl -sS -m 12 -X POST "${ALERT_WA_API_URL}" \
      -H 'Content-Type: application/json' \
      "${token_header[@]}" \
      -d "{\"to\":\"${number}\",\"text\":\"${payload_text//\"/\\\"}\"}" \
      >/dev/null || true
  done
}

on_error() {
  local line_no="$1"
  local msg="Restore drill FAILED di line ${line_no}. Cek ${LOG_FILE}."
  log "${msg}"
  notify_alert "error" "${msg}"
}

trap 'on_error ${LINENO}' ERR

log "Mulai restore drill bulanan"

if [[ "${RUN_BACKUP_FIRST:-1}" == "1" ]]; then
  log "Menjalankan backup harian terlebih dahulu"
  "${BACKUP_SCRIPT}"
fi

log "Verifikasi restore backup terenkripsi"
"${VERIFY_SCRIPT}"

log "Restore drill selesai: PASS"
notify_alert "info" "Restore drill PASS di ${HOSTNAME_VALUE}."
