#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
PUSH_SCRIPT="${PROJECT_DIR}/scripts/push-offsite-backup.sh"
LOG_FILE="${PROJECT_DIR}/logs/offsite-backup.log"
CRON_SCHEDULE="${CRON_SCHEDULE:-0 4 * * *}"
MARKER="# lawangsewu-offsite-backup"
CRON_LINE="${CRON_SCHEDULE} ${PUSH_SCRIPT} >> ${LOG_FILE} 2>&1 ${MARKER}"

mkdir -p "${PROJECT_DIR}/logs"

CURRENT_CRON="$(crontab -l 2>/dev/null || true)"
NEW_CRON="$(printf "%s\n" "${CURRENT_CRON}" | sed '/lawangsewu-offsite-backup/d')"
NEW_CRON="$(printf "%s\n%s\n" "${NEW_CRON}" "${CRON_LINE}" | sed '/^$/N;/^\n$/D')"

printf "%s\n" "${NEW_CRON}" | crontab -

echo "Cron offsite backup terpasang."
echo "Schedule : ${CRON_SCHEDULE}"
echo "Command  : ${PUSH_SCRIPT}"
echo "Log file : ${LOG_FILE}"
