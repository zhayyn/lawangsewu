#!/usr/bin/env bash
set -euo pipefail

ROOT="/var/www/html/lawangsewu/wa-caraka/dashboard-ci4-admin"
PORT="${1:-8792}"
PID_FILE="$ROOT/writable/logs/ci4-dashboard-${PORT}.pid"

if [ -f "$PID_FILE" ]; then
  PID="$(cat "$PID_FILE")"
  if kill -0 "$PID" 2>/dev/null; then
    echo "RUNNING pid=$PID port=$PORT"
    ss -ltnp | grep ":$PORT" || true
    exit 0
  fi
fi

echo "NOT RUNNING port=$PORT"
exit 1
