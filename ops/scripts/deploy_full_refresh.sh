#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/var/www/lawangsewu"
BRANCH="${BRANCH:-main}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
BASE_URL="${BASE_URL:-https://lawangsewu.pa-semarang.go.id}"

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root: sudo bash ops/scripts/deploy_full_refresh.sh"
  exit 1
fi

if ! command -v sudo >/dev/null 2>&1; then
  echo "sudo command not found"
  exit 1
fi

echo "[0/9] Resolve deploy user"
DEPLOY_USER="${SUDO_USER:-$(id -un)}"
if [[ "${DEPLOY_USER}" == "root" ]]; then
  DEPLOY_USER="www-data"
fi

echo "[1/9] Pull latest source"
cd "${REPO_DIR}"
sudo -u "${DEPLOY_USER}" git fetch --all --prune
sudo -u "${DEPLOY_USER}" git checkout "${BRANCH}"
sudo -u "${DEPLOY_USER}" git pull --ff-only origin "${BRANCH}"

echo "[2/9] Install PHP dependencies"
sudo -u "${DEPLOY_USER}" composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader

echo "[3/9] Install Node dependencies"
sudo -u "${DEPLOY_USER}" npm ci

echo "[4/9] Build frontend assets"
sudo -u "${DEPLOY_USER}" npm run build

echo "[5/9] Run database migration"
php artisan migrate --force

echo "[6/9] Rebuild Laravel caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[7/9] Reload runtime services"
if systemctl list-units --full -all | grep -Fq "${PHP_FPM_SERVICE}.service"; then
  systemctl reload "${PHP_FPM_SERVICE}"
else
  echo "PHP-FPM service ${PHP_FPM_SERVICE} not found"
  exit 1
fi

if systemctl is-active --quiet apache2; then
  systemctl reload apache2
elif pgrep -x apache2 >/dev/null 2>&1; then
  apachectl -k graceful
else
  echo "Apache is not running"
  exit 1
fi

echo "[8/9] Verify critical endpoints"
health_code="$(curl -k -sS -o /dev/null -w '%{http_code}' "${BASE_URL}/health")"
login_code="$(curl -k -sS -o /dev/null -w '%{http_code}' "${BASE_URL}/login")"
tailscale_code="$(curl -k -sS -o /dev/null -w '%{http_code}' "${BASE_URL}/tailscale")"
system_monitor_code="$(curl -k -sS -o /dev/null -w '%{http_code}' "${BASE_URL}/admin/system-monitor")"

echo "health=${health_code}"
echo "login=${login_code}"
echo "tailscale=${tailscale_code}"
echo "admin_system_monitor=${system_monitor_code}"

echo "[9/9] Deployment complete"
echo "Expected: health=200, login=200, tailscale=302/200, admin_system_monitor=302/200"
