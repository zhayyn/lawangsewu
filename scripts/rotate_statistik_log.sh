#!/usr/bin/env bash
# developed by dubes favour-it

set -euo pipefail

BASE_DIR="/var/www/html/lawangsewu"
LOG_DIR="$BASE_DIR/logs"
LOG_FILE="$LOG_DIR/statistik-data.log"
STAMP="$(date +%Y%m%d-%H%M%S)"

mkdir -p "$LOG_DIR"

if [[ -f "$LOG_FILE" && -s "$LOG_FILE" ]]; then
  cp "$LOG_FILE" "$LOG_DIR/statistik-data.$STAMP.log"
fi

: > "$LOG_FILE"

# simpan arsip maksimal 30 hari
find "$LOG_DIR" -maxdepth 1 -type f -name 'statistik-data.*.log' -mtime +30 -delete

# pastikan owner log aktif tetap ramah untuk apache
chown www-data:www-data "$LOG_FILE" 2>/dev/null || true
