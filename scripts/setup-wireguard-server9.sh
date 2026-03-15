#!/usr/bin/env bash
set -euo pipefail

# Setup WireGuard on Server 9 with safe defaults.
# Usage:
#   sudo bash scripts/setup-wireguard-server9.sh
#
# What it does:
# - installs wireguard + qrencode
# - generates server keypair if missing
# - writes /etc/wireguard/wg0.conf from template placeholders
# - enables and starts wg-quick@wg0

WG_CONF="/etc/wireguard/wg0.conf"
WG_DIR="/etc/wireguard"
WG_IFACE="wg0"
WG_PORT="${WG_PORT:-51820}"
WG_SUBNET="${WG_SUBNET:-10.9.0.0/24}"
WG_SERVER_ADDR="${WG_SERVER_ADDR:-10.9.0.1/24}"

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root: sudo bash $0"
  exit 1
fi

if ! command -v apt >/dev/null 2>&1; then
  echo "This script currently supports Debian/Ubuntu (apt)."
  exit 1
fi

apt update
DEBIAN_FRONTEND=noninteractive apt install -y wireguard wireguard-tools qrencode

mkdir -p "${WG_DIR}"
chmod 700 "${WG_DIR}"

if [[ ! -f "${WG_DIR}/server_private.key" ]]; then
  umask 077
  wg genkey | tee "${WG_DIR}/server_private.key" | wg pubkey > "${WG_DIR}/server_public.key"
  chmod 600 "${WG_DIR}/server_private.key" "${WG_DIR}/server_public.key"
fi

SERVER_PRIVATE_KEY="$(cat "${WG_DIR}/server_private.key")"

cat > "${WG_CONF}" <<EOF
[Interface]
Address = ${WG_SERVER_ADDR}
ListenPort = ${WG_PORT}
PrivateKey = ${SERVER_PRIVATE_KEY}

PostUp = ufw route allow in on ${WG_IFACE} out on eth0
PostUp = iptables -t nat -A POSTROUTING -s ${WG_SUBNET%/*}.0/24 -o eth0 -j MASQUERADE
PostDown = iptables -t nat -D POSTROUTING -s ${WG_SUBNET%/*}.0/24 -o eth0 -j MASQUERADE

# Add peers here (one block per admin device)
# [Peer]
# PublicKey = <CLIENT_PUBLIC_KEY>
# AllowedIPs = 10.9.0.10/32
EOF

chmod 600 "${WG_CONF}"

systemctl daemon-reload
systemctl enable --now "wg-quick@${WG_IFACE}"
systemctl restart "wg-quick@${WG_IFACE}"

echo "WireGuard setup complete."
echo "Server public key:"
cat "${WG_DIR}/server_public.key"
echo
echo "Next: add [Peer] blocks into ${WG_CONF}, then restart wg-quick@${WG_IFACE}."
