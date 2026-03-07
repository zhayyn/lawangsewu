#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${1:-8793}"
HOST="${2:-127.0.0.1}"
READY_URL="http://${HOST}:${PORT}/ready"
LOG_FILE="$ROOT_DIR/logs/wa-caraka-health.log"

mkdir -p "$(dirname "$LOG_FILE")"

timestamp() {
  date '+%Y-%m-%d %H:%M:%S'
}

code="$(curl -sS -o /tmp/wa_caraka_ready.json -w '%{http_code}' --max-time 10 "$READY_URL" || true)"

if [[ "$code" == "200" ]]; then
  echo "[$(timestamp)] OK /ready ${HOST}:${PORT}" >> "$LOG_FILE"
  exit 0
fi

echo "[$(timestamp)] FAIL /ready code=${code:-none} ${HOST}:${PORT} -> restart" >> "$LOG_FILE"
bash "$ROOT_DIR/scripts/stop-wa-caraka.sh" >> "$LOG_FILE" 2>&1 || true
bash "$ROOT_DIR/scripts/start-wa-caraka.sh" "$PORT" "$HOST" >> "$LOG_FILE" 2>&1 || true

sleep 3
post_code="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 10 "$READY_URL" || true)"
if [[ "$post_code" == "200" ]]; then
  echo "[$(timestamp)] OK /ready ${HOST}:${PORT} after-restart" >> "$LOG_FILE"
  exit 0
fi

echo "[$(timestamp)] FAIL /ready code=${post_code:-none} ${HOST}:${PORT} after-restart" >> "$LOG_FILE"
exit 1
