#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/lawangsewu"
SYSTEM_LOG_DIR="/var/log/lawangsewu"
APP_LOG_DIR="${APP_DIR}/storage/logs"
LOG_DIR="${SYSTEM_LOG_DIR}"
CRON_LINE="* * * * * cd ${APP_DIR} && /usr/bin/php artisan schedule:run >> ${LOG_DIR}/scheduler.log 2>&1"

resolve_log_dir() {
    if mkdir -p "${SYSTEM_LOG_DIR}" 2>/dev/null; then
        printf '%s\n' "${SYSTEM_LOG_DIR}"
        return
    fi

    mkdir -p "${APP_LOG_DIR}"
    printf '%s\n' "${APP_LOG_DIR}"
}

LOG_DIR="$(resolve_log_dir)"
CRON_LINE="* * * * * cd ${APP_DIR} && /usr/bin/php artisan schedule:run >> ${LOG_DIR}/scheduler.log 2>&1"

TMP_CRON="$(mktemp)"
crontab -l 2>/dev/null | grep -v "artisan schedule:run" > "${TMP_CRON}" || true
printf "%s\n" "${CRON_LINE}" >> "${TMP_CRON}"
crontab "${TMP_CRON}"
rm -f "${TMP_CRON}"

echo "Installed scheduler cron:"
echo "${CRON_LINE}"
