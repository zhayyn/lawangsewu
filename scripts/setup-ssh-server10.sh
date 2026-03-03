#!/usr/bin/env bash
set -euo pipefail

REMOTE="root@192.168.88.10"
KEY_FILE="${HOME}/.ssh/id_rsa.pub"

if [[ ! -f "$KEY_FILE" ]]; then
  echo "Public key tidak ditemukan di $KEY_FILE"
  echo "Jalankan: ssh-keygen -t rsa -b 4096 -N '' -f ${HOME}/.ssh/id_rsa"
  exit 1
fi

echo "Menyalin public key ke ${REMOTE} (akan minta password sekali)..."
ssh-copy-id -i "$KEY_FILE" "$REMOTE"

echo "Verifikasi login tanpa password..."
ssh -o BatchMode=yes -o ConnectTimeout=6 "$REMOTE" 'echo SSH_KEY_OK'

echo "Setup selesai."
