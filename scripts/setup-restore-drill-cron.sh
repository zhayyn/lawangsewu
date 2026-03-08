#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
DRILL_SCRIPT="${PROJECT_DIR}/scripts/restore-drill-monthly.sh"
LOG_FILE="${PROJECT_DIR}/logs/restore-drill-cron.log"
LOCK_FILE="/tmp/lawangsewu-restore-drill.lock"
CRON_SCHEDULE="${CRON_SCHEDULE:-30 3 1 * *}"
MARKER="# lawangsewu-monthly-restore-drill"
CRON_LINE="${CRON_SCHEDULE} /usr/bin/flock -n ${LOCK_FILE} ${DRILL_SCRIPT} >> ${LOG_FILE} 2>&1 ${MARKER}"

mkdir -p "${PROJECT_DIR}/logs"

CURRENT_CRON="$(crontab -l 2>/dev/null || true)"
NEW_CRON="$(printf "%s\n" "${CURRENT_CRON}" | sed '/lawangsewu-monthly-restore-drill/d')"
NEW_CRON="$(printf "%s\n%s\n" "${NEW_CRON}" "${CRON_LINE}" | sed '/^$/N;/^\n$/D')"

printf "%s\n" "${NEW_CRON}" | crontab -

echo "Cron restore drill bulanan terpasang."
echo "Schedule : ${CRON_SCHEDULE}"
echo "Command  : ${DRILL_SCRIPT}"
echo "Log file : ${LOG_FILE}"
