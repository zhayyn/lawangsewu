#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/lawangsewu"
APP_USER="dbprakom"
WEB_USER="www-data"

cd "${APP_DIR}"

sudo chown -R "${APP_USER}:${WEB_USER}" storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 2775 {} +
sudo find storage bootstrap/cache -type f -exec chmod 664 {} +

if command -v setfacl >/dev/null 2>&1; then
  sudo setfacl -R -m "u:${APP_USER}:rwX" -m "u:${WEB_USER}:rwX" storage bootstrap/cache
  sudo setfacl -R -d -m "u:${APP_USER}:rwX" -m "u:${WEB_USER}:rwX" storage bootstrap/cache
fi

echo "Laravel runtime permissions repaired."
echo "Verify with:"
echo "  find storage/framework bootstrap/cache -maxdepth 2 -printf '%M %u %g %p\\n' | sed -n '1,120p'"
