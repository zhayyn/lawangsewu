#!/usr/bin/env bash
set -euo pipefail

ROOT="/var/www/html/lawangsewu/wa-caraka/dashboard-ci4-admin"
PORT="${1:-8792}"
HOST="${2:-127.0.0.1}"
PID_FILE="$ROOT/writable/logs/ci4-dashboard-${PORT}.pid"
LOG_FILE="$ROOT/writable/logs/ci4-dashboard-${PORT}.log"

mkdir -p "$ROOT/writable/logs"

if [ -f "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
  echo "Dashboard already running on PID $(cat "$PID_FILE")"
  exit 0
fi

nohup /usr/bin/php8.3 -S "$HOST:$PORT" -t "$ROOT/public" "$ROOT/vendor/codeigniter4/framework/system/rewrite.php" >"$LOG_FILE" 2>&1 &
echo $! >"$PID_FILE"
sleep 1
echo "Started dashboard-ci4-admin at http://$HOST:$PORT (PID $(cat "$PID_FILE"))"
