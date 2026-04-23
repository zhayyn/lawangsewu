#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/lawangsewu"
APP_LOG_DIR="${APP_DIR}/storage/logs"
LOCK_FILE="/tmp/lawangsewu-queue-safe.lock"
CRON_LINE="* * * * * cd ${APP_DIR} && /usr/bin/flock -n ${LOCK_FILE} /usr/bin/php -d memory_limit=256M artisan queue:work database --queue=default --stop-when-empty --sleep=1 --tries=3 --backoff=5 --timeout=120 --max-jobs=50 --max-time=50 --memory=192 --no-interaction >> ${APP_LOG_DIR}/queue-safe.log 2>&1"

mkdir -p "${APP_LOG_DIR}"

TMP_CRON="$(mktemp)"
crontab -l 2>/dev/null | grep -v "lawangsewu-queue-safe.lock" | grep -v "queue-safe.log" > "${TMP_CRON}" || true
printf "%s\n" "${CRON_LINE}" >> "${TMP_CRON}"
crontab "${TMP_CRON}"
rm -f "${TMP_CRON}"

echo "Installed safe queue cron:"
echo "${CRON_LINE}"
