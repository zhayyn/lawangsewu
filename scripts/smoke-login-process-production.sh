#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
TARGET_BASE_URL="${1:-${SMOKE_PRODUCTION_BASE_URL:-https://lawangsewu.pa-semarang.go.id/wa-caraka-admin}}"
TARGET_BASE_URL="${TARGET_BASE_URL%/}"

TMP_HTML="$(mktemp)"
trap 'rm -f "${TMP_HTML}"' EXIT

probe_code="$(curl -k -sS -m 12 -o "${TMP_HTML}" -w '%{http_code}' "${TARGET_BASE_URL}/login" || true)"

if grep -Eqi 'just a moment|cf-browser-verification|__cf_chl|cloudflare' "${TMP_HTML}"; then
  printf 'SMOKE: INCONCLUSIVE production-host (%s) challenge/protection detected\n' "${TARGET_BASE_URL}"
  exit 2
fi

if [[ ! "${probe_code}" =~ ^2[0-9][0-9]$ ]]; then
  printf 'SMOKE: FAIL production-host (%s) code=%s\n' "${TARGET_BASE_URL}" "${probe_code:-none}"
  exit 1
fi

exec env SMOKE_AUTO_SERVE=0 SMOKE_BASE_URL="${TARGET_BASE_URL}" bash "${PROJECT_DIR}/scripts/smoke-login-process-report.sh"