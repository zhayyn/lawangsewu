#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="/var/www/lawangsewu"
RUNTIME_DIR="$APP_ROOT/wa-runtime"
PID_FILE="$RUNTIME_DIR/server.pid"
LOG_FILE="$RUNTIME_DIR/server.log"
NODE_BIN="${NODE_BIN:-$(command -v node || true)}"

if [[ -z "${NODE_BIN}" ]]; then
  echo "node tidak ditemukan di PATH" >&2
  exit 1
fi

cmd_matches() {
  local pid="$1"
  ps -p "$pid" -o args= 2>/dev/null | grep -Fq "$RUNTIME_DIR/server.mjs"
}

status() {
  if [[ -f "$PID_FILE" ]]; then
    local pid
    pid="$(cat "$PID_FILE")"
    if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null && cmd_matches "$pid"; then
      echo "WA Caraka runtime aktif (pid=$pid)"
      exit 0
    fi
  fi

  echo "WA Caraka runtime tidak aktif"
}

start() {
  if [[ -f "$PID_FILE" ]]; then
    local pid
    pid="$(cat "$PID_FILE")"
    if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null && cmd_matches "$pid"; then
      echo "WA Caraka runtime sudah aktif (pid=$pid)"
      exit 0
    fi
  fi

  cd "$RUNTIME_DIR"
  nohup "$NODE_BIN" server.mjs >> "$LOG_FILE" 2>&1 &
  local pid=$!
  echo "$pid" > "$PID_FILE"
  echo "WA Caraka runtime dijalankan (pid=$pid)"
}

stop() {
  if [[ ! -f "$PID_FILE" ]]; then
    echo "PID file tidak ada, runtime dianggap sudah berhenti"
    exit 0
  fi

  local pid
  pid="$(cat "$PID_FILE")"
  if [[ -z "$pid" ]] || ! kill -0 "$pid" 2>/dev/null; then
    rm -f "$PID_FILE"
    echo "Runtime sudah tidak aktif"
    exit 0
  fi

  if ! cmd_matches "$pid"; then
    echo "PID $pid bukan proses WA Caraka runtime, stop dibatalkan demi keamanan" >&2
    exit 1
  fi

  kill "$pid"
  sleep 1
  if kill -0 "$pid" 2>/dev/null; then
    kill -9 "$pid"
  fi
  rm -f "$PID_FILE"
  echo "WA Caraka runtime dihentikan (pid=$pid)"
}

restart() {
  stop || true
  start
}

case "${1:-status}" in
  start) start ;;
  stop) stop ;;
  restart) restart ;;
  status) status ;;
  *)
    echo "Gunakan: $0 {start|stop|restart|status}" >&2
    exit 1
    ;;
esac
