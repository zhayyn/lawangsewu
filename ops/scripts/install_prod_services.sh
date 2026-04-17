#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/var/www/lawangsewu"
SYSTEMD_DIR="/etc/systemd/system"

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root: sudo bash ops/scripts/install_prod_services.sh"
  exit 1
fi

cp "${REPO_DIR}/ops/systemd/lawangsewu-reverb.service" "${SYSTEMD_DIR}/"
cp "${REPO_DIR}/ops/systemd/lawangsewu-queue.service" "${SYSTEMD_DIR}/"

systemctl daemon-reload
systemctl enable lawangsewu-reverb.service
systemctl enable lawangsewu-queue.service

systemctl restart lawangsewu-reverb.service
systemctl restart lawangsewu-queue.service

echo "Installed and restarted: lawangsewu-reverb, lawangsewu-queue"
systemctl --no-pager --full status lawangsewu-reverb.service | head -30
systemctl --no-pager --full status lawangsewu-queue.service | head -30
