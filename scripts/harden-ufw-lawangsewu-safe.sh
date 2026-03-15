#!/usr/bin/env bash
set -euo pipefail

# Safe UFW incremental rules for Lawangsewu remote admin.
# - NO ufw reset
# - NO deletion of existing ecosystem rules
# - Add only what is needed for secure remote manage path
#
# Usage:
#   sudo ADMIN_IPS="192.168.88.84" WG_PORT=51830 SSH_PORT=22 bash scripts/harden-ufw-lawangsewu-safe.sh

WG_PORT="${WG_PORT:-51830}"
SSH_PORT="${SSH_PORT:-22}"
ADMIN_IPS="${ADMIN_IPS:-}"

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root: sudo bash $0"
  exit 1
fi

if ! command -v ufw >/dev/null 2>&1; then
  echo "ufw is not installed. Install first."
  exit 1
fi

allow_if_missing() {
  local rule="$1"
  if ufw status | grep -Fq "$rule"; then
    echo "[skip] exists: $rule"
  else
    echo "[add] $rule"
    eval "$rule"
  fi
}

# Ensure required public web ports remain available.
allow_if_missing "ufw allow 80/tcp comment 'public-http'"
allow_if_missing "ufw allow 443/tcp comment 'public-https'"

# WireGuard dedicated port for Lawangsewu remote admin.
allow_if_missing "ufw allow ${WG_PORT}/udp comment 'lawangsewu-wireguard'"

# Optional: add explicit admin ssh source IPs (does not remove old ssh rules).
if [[ -n "${ADMIN_IPS}" ]]; then
  for ip in ${ADMIN_IPS}; do
    allow_if_missing "ufw allow from ${ip} to any port ${SSH_PORT} proto tcp comment 'lawangsewu-admin-ssh-${ip}'"
  done
else
  echo "[info] ADMIN_IPS empty: no extra SSH source rule added."
fi

echo
echo "UFW incremental update complete."
ufw status verbose

echo
echo "NOTE: This script intentionally does not remove existing rules to avoid ecosystem impact."
