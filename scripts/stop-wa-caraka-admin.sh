#!/usr/bin/env bash
set -euo pipefail

ROOT="/var/www/html/lawangsewu/wa-caraka/dashboard-ci4-admin"
PORT="${1:-8792}"
PID_FILE="$ROOT/writable/logs/ci4-dashboard-${PORT}.pid"

if [ ! -f "$PID_FILE" ]; then
  echo "PID file not found: $PID_FILE"
  exit 0
fi

PID="$(cat "$PID_FILE")"
if kill -0 "$PID" 2>/dev/null; then
  kill "$PID"
  sleep 1
fi

rm -f "$PID_FILE"
echo "Stopped dashboard-ci4-admin (PID $PID)"
