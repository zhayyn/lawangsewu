#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-https://lawangsewu.pa-semarang.go.id}"

echo "[1/4] Health endpoint"
curl -fsS "${BASE_URL}/health" | head -c 500; echo

echo "[2/4] App up endpoint"
curl -fsS "${BASE_URL}/up" | head -c 500; echo

echo "[3/4] Reverb env flag"
php /var/www/lawangsewu/artisan env | cat

echo "[4/4] Service status"
systemctl --no-pager --full status lawangsewu-reverb.service | head -20

echo "Smoke test complete"
