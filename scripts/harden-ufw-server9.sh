#!/usr/bin/env bash
set -euo pipefail

# Harden UFW on Server 9.
# Usage:
#   sudo ADMIN_IPS="1.2.3.4 5.6.7.8" bash scripts/harden-ufw-server9.sh
# Optional:
#   SSH_PORT=22 WG_PORT=51820

SSH_PORT="${SSH_PORT:-22}"
WG_PORT="${WG_PORT:-51820}"
ADMIN_IPS="${ADMIN_IPS:-}"

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root: sudo bash $0"
  exit 1
fi

if ! command -v ufw >/dev/null 2>&1; then
  apt update
  DEBIAN_FRONTEND=noninteractive apt install -y ufw
fi

# Base defaults
ufw --force reset
ufw default deny incoming
ufw default allow outgoing

# Public web only
ufw allow 80/tcp comment 'public-http'
ufw allow 443/tcp comment 'public-https'

# VPN ingress
ufw allow "${WG_PORT}"/udp comment 'wireguard-vpn'

# SSH restricted to known admin IPs (if provided)
if [[ -n "${ADMIN_IPS}" ]]; then
  for ip in ${ADMIN_IPS}; do
    ufw allow from "${ip}" to any port "${SSH_PORT}" proto tcp comment "ssh-admin-${ip}"
  done
else
  echo "WARNING: ADMIN_IPS not set, SSH rule not added."
  echo "Add SSH allow rule manually before enabling UFW to avoid lockout."
fi

ufw --force enable
ufw status verbose

echo
echo "UFW hardening applied."
echo "Tip: keep admin apps behind VPN/Cloudflare Access and do not open DB port 3306 publicly."
