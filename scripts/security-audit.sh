#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_URL="${1:-https://lawangsewu.pa-semarang.go.id}"

FAIL=0

echo "== Security Audit: ${REPO_DIR} =="
echo "Base URL: ${BASE_URL}"
echo

echo "[1/3] Cek tracked file sensitif..."
SENSITIVE_TRACKED="$(git -C "${REPO_DIR}" ls-files | grep -E '(^|/)(\.env|backup\.conf|offsite\.conf|id_rsa|.*\.(pem|key|p12|pfx|sql|sql\.gz))$' || true)"
if [[ -n "${SENSITIVE_TRACKED}" ]]; then
  echo "FAIL: ditemukan file sensitif ter-track:"
  echo "${SENSITIVE_TRACKED}"
  FAIL=1
else
  echo "OK: tidak ada file sensitif ter-track"
fi
echo

echo "[2/3] Cek pola secret berisiko di tracked files..."
PATTERN_HITS="$(git -C "${REPO_DIR}" grep -nEI '(R4h4514@|banjarnegara1|GATEWAY_API_TOKEN\s*=\s*"?[A-Za-z0-9]{24,}|\$password\s*=\s*\x27[^\x27]{6,}\x27)' -- ':!*.min.js' ':!*.min.css' || true)"
PATTERN_HITS="$(printf '%s\n' "${PATTERN_HITS}" | grep -Ev '(DB_PASS=ganti_password_db|TOKEN_KAMU|ganti-dengan-token)' || true)"
if [[ -n "${PATTERN_HITS}" ]]; then
  echo "WARN: ditemukan string yang mirip secret (review manual):"
  echo "${PATTERN_HITS}"
  FAIL=1
else
  echo "OK: tidak ada pola secret berisiko terdeteksi"
fi
echo

echo "[3/3] Cek endpoint sensitif publik..."
check_code() {
  local path="$1"
  local expected="$2"
  local code
  code="$(curl -k -s -o /dev/null -w '%{http_code}' "${BASE_URL}${path}")"
  if [[ "${code}" == "${expected}" ]]; then
    echo "OK: ${path} -> ${code}"
  else
    echo "FAIL: ${path} -> ${code} (expected ${expected})"
    FAIL=1
  fi
}

check_code "/.env" "403"
check_code "/scripts/" "403"
check_code "/projects/" "403"
echo

if [[ "${FAIL}" -eq 0 ]]; then
  echo "HASIL: PASS"
  exit 0
fi

echo "HASIL: FAIL (perlu tindak lanjut)"
exit 1
