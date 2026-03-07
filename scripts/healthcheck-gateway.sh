#!/usr/bin/env bash
set -euo pipefail

LOG_FILE="/var/www/html/lawangsewu/logs/gateway-health.log"
NOW="$(date '+%Y-%m-%d %H:%M:%S')"

check_health() {
  local url="$1"
  curl -fsS --max-time 4 "$url" >/dev/null 2>&1
}

restart_service() {
  local service="$1"
  if systemctl restart "$service" >/dev/null 2>&1; then
    echo "[$NOW] restarted $service" >> "$LOG_FILE"
  else
    echo "[$NOW] failed restarting $service" >> "$LOG_FILE"
  fi
}

if ! check_health "http://127.0.0.1:8787/health"; then
  restart_service "lawangsewu-gateway"
fi

if ! check_health "http://127.0.0.1:8788/health"; then
  restart_service "lawangsewu-gateway-fallback"
fi
