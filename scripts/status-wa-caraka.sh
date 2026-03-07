#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${1:-8793}"
HOST="${2:-127.0.0.1}"

bash "$ROOT_DIR/wa-caraka/scripts/status.sh" "$PORT" "$HOST"
