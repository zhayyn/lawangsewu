#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
BACKUP_SCRIPT="${PROJECT_DIR}/scripts/backup-daily.sh"
LOG_FILE="${PROJECT_DIR}/logs/backup.log"
CRON_SCHEDULE="${CRON_SCHEDULE:-30 2 * * *}"
MARKER="# lawangsewu-daily-backup"
CRON_LINE="${CRON_SCHEDULE} ${BACKUP_SCRIPT} >> ${LOG_FILE} 2>&1 ${MARKER}"

mkdir -p "${PROJECT_DIR}/logs"

CURRENT_CRON="$(crontab -l 2>/dev/null || true)"
NEW_CRON="$(printf "%s\n" "${CURRENT_CRON}" | sed '/lawangsewu-daily-backup/d')"
NEW_CRON="$(printf "%s\n%s\n" "${NEW_CRON}" "${CRON_LINE}" | sed '/^$/N;/^\n$/D')"

printf "%s\n" "${NEW_CRON}" | crontab -

echo "Cron backup harian terpasang."
echo "Schedule : ${CRON_SCHEDULE}"
echo "Command  : ${BACKUP_SCRIPT}"
echo "Log file : ${LOG_FILE}"
