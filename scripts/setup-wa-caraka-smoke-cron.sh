#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
SMOKE_SCRIPT="${PROJECT_DIR}/scripts/post-deploy-wa-caraka-smoke.sh"
LOG_FILE="${PROJECT_DIR}/logs/wa-caraka-smoke-cron.log"
LOCK_FILE="/tmp/lawangsewu-wa-caraka-smoke.lock"
CRON_SCHEDULE="${CRON_SCHEDULE:-20 4 * * *}"
MARKER="# lawangsewu-wa-caraka-smoke"
CRON_LINE="${CRON_SCHEDULE} /usr/bin/flock -n ${LOCK_FILE} ${SMOKE_SCRIPT} >> ${LOG_FILE} 2>&1 ${MARKER}"

mkdir -p "${PROJECT_DIR}/logs"

CURRENT_CRON="$(crontab -l 2>/dev/null || true)"
NEW_CRON="$(printf '%s\n' "${CURRENT_CRON}" | sed '/lawangsewu-wa-caraka-smoke/d')"
NEW_CRON="$(printf '%s\n%s\n' "${NEW_CRON}" "${CRON_LINE}" | sed '/^$/N;/^\n$/D')"

printf '%s\n' "${NEW_CRON}" | crontab -

echo "Cron smoke WA Caraka terpasang."
echo "Schedule : ${CRON_SCHEDULE}"
echo "Command  : ${SMOKE_SCRIPT}"
echo "Log file : ${LOG_FILE}"