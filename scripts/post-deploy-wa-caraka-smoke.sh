#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
WA_DIR="${PROJECT_DIR}/wa-caraka"
LOG_DIR="${PROJECT_DIR}/logs/post-deploy-smoke"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
REPORT_FILE="${LOG_DIR}/wa-caraka-post-deploy-${TIMESTAMP}.log"
LATEST_FILE="${LOG_DIR}/wa-caraka-post-deploy-latest.log"

WA_HOST="${WA_HOST:-127.0.0.1}"
WA_PORT="${WA_PORT:-8793}"
DASH_HOST="${DASH_HOST:-127.0.0.1}"
DASH_PORT="${DASH_PORT:-8792}"

WA_BASE_URL="${WA_BASE_URL:-http://${WA_HOST}:${WA_PORT}}"
DASH_BASE_URL="${DASH_BASE_URL:-http://${DASH_HOST}:${DASH_PORT}}"

ALERT_ON_FAIL="${ALERT_ON_FAIL:-1}"

mkdir -p "${LOG_DIR}"

log() {
  printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" | tee -a "${REPORT_FILE}"
}

run_and_log() {
  local title="$1"
  shift
  log "START ${title}"
  if "$@" >> "${REPORT_FILE}" 2>&1; then
    log "PASS ${title}"
    return 0
  fi

  local exit_code=$?
  log "FAIL ${title} exit=${exit_code}"
  return "${exit_code}"
}

send_failure_alert() {
  local message="$1"
  local alert_script="${WA_DIR}/scripts/send-admin-alert.sh"
  if [[ "${ALERT_ON_FAIL}" != "1" || ! -x "${alert_script}" ]]; then
    return 0
  fi

  bash "${alert_script}" error post_deploy_smoke "${message}" >> "${REPORT_FILE}" 2>&1 || true
}

ensure_dashboard_running() {
  if bash "${PROJECT_DIR}/scripts/status-wa-caraka-admin.sh" "${DASH_PORT}" >> "${REPORT_FILE}" 2>&1; then
    return 0
  fi

  log "Dashboard admin belum aktif, mencoba start internal runner ${DASH_HOST}:${DASH_PORT}"
  bash "${PROJECT_DIR}/scripts/start-wa-caraka-admin.sh" "${DASH_PORT}" "${DASH_HOST}" >> "${REPORT_FILE}" 2>&1
  sleep 2
  bash "${PROJECT_DIR}/scripts/status-wa-caraka-admin.sh" "${DASH_PORT}" >> "${REPORT_FILE}" 2>&1
}

printf '' > "${REPORT_FILE}"
log "Post-deploy smoke dimulai"
log "WA_BASE_URL=${WA_BASE_URL}"
log "DASH_BASE_URL=${DASH_BASE_URL}"

if ! run_and_log 'wa healthcheck' bash "${PROJECT_DIR}/scripts/healthcheck-wa-caraka.sh" "${WA_PORT}" "${WA_HOST}"; then
  send_failure_alert "Healthcheck WA Caraka gagal. Cek ${REPORT_FILE}"
  cp -f "${REPORT_FILE}" "${LATEST_FILE}"
  exit 1
fi

if ! run_and_log 'dashboard runner status/start' ensure_dashboard_running; then
  send_failure_alert "Runner dashboard admin gagal aktif. Cek ${REPORT_FILE}"
  cp -f "${REPORT_FILE}" "${LATEST_FILE}"
  exit 1
fi

if ! run_and_log 'runtime smoke' env SMOKE_RUNTIME_BASE_URL="${WA_BASE_URL}" bash "${WA_DIR}/scripts/smoke-runtime-endpoints.sh"; then
  send_failure_alert "Smoke runtime WA Caraka gagal. Cek ${REPORT_FILE}"
  cp -f "${REPORT_FILE}" "${LATEST_FILE}"
  exit 1
fi

if ! run_and_log 'dashboard smoke' env SMOKE_AUTO_SERVE=0 SMOKE_BASE_URL="${DASH_BASE_URL}" bash "${PROJECT_DIR}/scripts/smoke-login-process-report.sh"; then
  send_failure_alert "Smoke dashboard WA Caraka gagal. Cek ${REPORT_FILE}"
  cp -f "${REPORT_FILE}" "${LATEST_FILE}"
  exit 1
fi

cp -f "${REPORT_FILE}" "${LATEST_FILE}"
log "Post-deploy smoke selesai: PASS"
log "Report: ${REPORT_FILE}"