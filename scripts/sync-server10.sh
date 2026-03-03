#!/usr/bin/env bash
set -euo pipefail

REMOTE_HOST="192.168.88.10"
REMOTE_USER="root"
REMOTE_PATH="/var/www/html/lawangsewu/"
LOCAL_PATH="/var/www/html/lawangsewu/"
SSH_KEY="${HOME}/.ssh/id_rsa"

DRY_RUN="${1:-}"
RSYNC_DRY=""
if [[ "$DRY_RUN" == "--dry-run" ]]; then
  RSYNC_DRY="--dry-run"
fi

rsync -avz --delete $RSYNC_DRY \
  --exclude='.git/' \
  --exclude='logs/' \
  -e "ssh -i ${SSH_KEY} -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new" \
  "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}" \
  "${LOCAL_PATH}"

echo "Sync selesai ${RSYNC_DRY:+(dry-run)}"
