#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/var/www/lawangsewu"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
BASE_URL="${BASE_URL:-https://lawangsewu.pa-semarang.go.id}"

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root: sudo bash ops/scripts/refresh_web_runtime.sh"
  exit 1
fi

cd "${REPO_DIR}"

echo "[1/6] Clear Laravel optimization caches"
php artisan optimize:clear

echo "[2/6] Rebuild production caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[3/6] Reload PHP-FPM to flush OPCache"
if systemctl list-units --full -all | grep -Fq "${PHP_FPM_SERVICE}.service"; then
  systemctl reload "${PHP_FPM_SERVICE}"
else
  echo "PHP-FPM service ${PHP_FPM_SERVICE} not found"
  exit 1
fi

echo "[4/6] Reload Apache runtime"
if systemctl is-active --quiet apache2; then
  systemctl reload apache2
elif pgrep -x apache2 >/dev/null 2>&1; then
  apachectl -k graceful
else
  echo "Apache is not running"
  exit 1
fi

echo "[5/6] Verify public endpoints"
health_code="$(curl -k -sS -o /dev/null -w '%{http_code}' "${BASE_URL}/health")"
login_code="$(curl -k -sS -o /dev/null -w '%{http_code}' "${BASE_URL}/login")"
tailscale_code="$(curl -k -sS -o /dev/null -w '%{http_code}' "${BASE_URL}/tailscale")"
system_monitor_code="$(curl -k -sS -o /dev/null -w '%{http_code}' "${BASE_URL}/admin/system-monitor")"

echo "health=${health_code}"
echo "login=${login_code}"
echo "tailscale=${tailscale_code}"
echo "admin_system_monitor=${system_monitor_code}"

echo "[6/6] Expected results"
echo "- health should be 200"
echo "- login should be 200"
echo "- tailscale should be 302 or 200"
echo "- admin/system-monitor should be 302 or 200"

echo "Refresh complete"
