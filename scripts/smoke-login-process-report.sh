#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
DASH_DIR="${PROJECT_DIR}/wa-caraka/dashboard-ci4-admin"
BASE_URL="${1:-${SMOKE_BASE_URL:-http://127.0.0.1:8788}}"
BASE_URL="${BASE_URL%/}"
REPORT_DIR="${SMOKE_REPORT_DIR:-${PROJECT_DIR}/logs}"
REPORT_FILE="${REPORT_DIR}/smoke-login-process-report-$(date +%Y%m%d-%H%M%S).txt"
AUTO_SERVE="${SMOKE_AUTO_SERVE:-1}"
LOCAL_HOST="${SMOKE_LOCAL_HOST:-127.0.0.1}"
LOCAL_PORT="${SMOKE_LOCAL_PORT:-8788}"

mkdir -p "${REPORT_DIR}"

PASS_COUNT=0
FAIL_COUNT=0
LOCAL_SERVER_PID=""
TMP_FILES=()

log_line() {
  local msg="$1"
  printf '%s\n' "$msg"
  printf '%s\n' "$msg" >> "${REPORT_FILE}"
}

pass() {
  PASS_COUNT=$((PASS_COUNT + 1))
  log_line "[PASS] $*"
}

fail() {
  FAIL_COUNT=$((FAIL_COUNT + 1))
  log_line "[FAIL] $*"
}

mktemp_track() {
  local f
  f="$(mktemp)"
  TMP_FILES+=("$f")
  printf '%s' "$f"
}

cleanup() {
  if [[ -n "${LOCAL_SERVER_PID}" ]] && kill -0 "${LOCAL_SERVER_PID}" >/dev/null 2>&1; then
    kill "${LOCAL_SERVER_PID}" >/dev/null 2>&1 || true
    wait "${LOCAL_SERVER_PID}" >/dev/null 2>&1 || true
  fi

  if [[ ${#TMP_FILES[@]} -gt 0 ]]; then
    rm -f "${TMP_FILES[@]}" >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

extract_csrf() {
  local html="$1"
  local line
  line="$(grep -m1 'type="hidden" name="csrf' "${html}" || true)"
  CSRF_NAME="$(echo "${line}" | sed -n 's/.*name="\([^"]*\)" value="\([^"]*\)".*/\1/p')"
  CSRF_VAL="$(echo "${line}" | sed -n 's/.*name="\([^"]*\)" value="\([^"]*\)".*/\2/p')"
  [[ -n "${CSRF_NAME:-}" && -n "${CSRF_VAL:-}" ]]
}

start_local_server_if_needed() {
  local probe_html
  probe_html="$(mktemp_track)"

  local probe_code
  probe_code="$(curl -k -sS -m 8 -o "${probe_html}" -w '%{http_code}' "${BASE_URL}/login" || true)"

  if [[ "${probe_code}" =~ ^2[0-9][0-9]$ ]] && ! grep -Eqi 'just a moment|cf-browser-verification|__cf_chl|cloudflare' "${probe_html}"; then
    log_line "[INFO] Target aktif: ${BASE_URL}"
    return 0
  fi

  if [[ "${AUTO_SERVE}" != "1" ]]; then
    fail "Target ${BASE_URL} tidak siap (code=${probe_code}) dan SMOKE_AUTO_SERVE=0"
    return 1
  fi

  BASE_URL="http://${LOCAL_HOST}:${LOCAL_PORT}"
  local spark_log
  spark_log="$(mktemp_track)"

  (cd "${DASH_DIR}" && php spark serve --host "${LOCAL_HOST}" --port "${LOCAL_PORT}" >"${spark_log}" 2>&1) &
  LOCAL_SERVER_PID=$!

  local i
  for i in $(seq 1 25); do
    sleep 1
    probe_code="$(curl -k -sS -m 5 -o "${probe_html}" -w '%{http_code}' "${BASE_URL}/login" || true)"
    if [[ "${probe_code}" =~ ^2[0-9][0-9]$ ]]; then
      log_line "[INFO] Local test server aktif: ${BASE_URL}"
      return 0
    fi
  done

  fail "Gagal menyalakan local test server di ${BASE_URL}"
  log_line "[INFO] Spark log: ${spark_log}"
  return 1
}

login_user() {
  local user="$1"
  local passw="$2"
  local cookie="$3"
  local login_html login_hdr
  login_html="$(mktemp_track)"
  login_hdr="$(mktemp_track)"

  curl -k -sS -c "${cookie}" -b "${cookie}" "${BASE_URL}/login" -o "${login_html}" || return 1
  extract_csrf "${login_html}" || return 2

  curl -k -sS -c "${cookie}" -b "${cookie}" -D "${login_hdr}" -o /dev/null \
    -X POST "${BASE_URL}/login" \
    --data-urlencode "${CSRF_NAME}=${CSRF_VAL}" \
    --data-urlencode "username=${user}" \
    --data-urlencode "password=${passw}" || return 3

  local status location
  status="$(awk 'NR==1{print $2}' "${login_hdr}")"
  location="$(awk 'BEGIN{IGNORECASE=1}/^Location:/{print $2}' "${login_hdr}" | tr -d '\r')"
  if [[ ("${status}" == "302" || "${status}" == "303") && "${location}" == *"/dashboard" ]]; then
    return 0
  fi
  return 4
}

get_page_code() {
  local cookie="$1"
  local path="$2"
  local out="$3"
  curl -k -sS -c "${cookie}" -b "${cookie}" -o "${out}" -w '%{http_code}' "${BASE_URL}${path}"
}

check_allow() {
  local cookie="$1"
  local user="$2"
  local path="$3"
  local out code
  out="$(mktemp_track)"
  code="$(get_page_code "${cookie}" "${path}" "${out}")"
  if [[ "${code}" == "200" ]]; then
    pass "${user} allowed ${path} (200)"
  else
    fail "${user} expected allow ${path} but got ${code}"
  fi
}

check_deny_redirect_wa() {
  local cookie="$1"
  local user="$2"
  local path="$3"
  local hdr wa_after status location
  hdr="$(mktemp_track)"
  wa_after="$(mktemp_track)"

  curl -k -sS -c "${cookie}" -b "${cookie}" -D "${hdr}" -o /dev/null "${BASE_URL}${path}" >/dev/null || true
  status="$(awk 'NR==1{print $2}' "${hdr}")"
  location="$(awk 'BEGIN{IGNORECASE=1}/^Location:/{print $2}' "${hdr}" | tr -d '\r')"

  if [[ "${status}" == "302" && "${location}" == *"/wa" ]]; then
    pass "${user} denied ${path} redirected to /wa"
    curl -k -sS -c "${cookie}" -b "${cookie}" "${BASE_URL}/wa" -o "${wa_after}" || true
    if grep -q 'Role Anda tidak memiliki izin untuk aksi ini\.' "${wa_after}"; then
      pass "${user} deny message shown for ${path}"
    else
      fail "${user} deny message missing for ${path}"
    fi
  else
    fail "${user} expected deny redirect for ${path} but got status=${status} location=${location}"
  fi
}

check_role_banner() {
  local cookie="$1"
  local user="$2"
  local expected_upper="$3"
  local out code
  out="$(mktemp_track)"
  code="$(get_page_code "${cookie}" '/wa' "${out}")"

  if [[ "${code}" != "200" ]]; then
    fail "${user} /wa not reachable for role check (code=${code})"
    return
  fi
  if grep -q "Role: ${expected_upper}" "${out}"; then
    pass "${user} role banner shows ${expected_upper}"
  else
    fail "${user} role banner mismatch expected ${expected_upper}"
  fi
}

post_and_assert_toast() {
  local cookie="$1"
  local from_path="$2"
  local post_path="$3"
  local expected_id="$4"
  local expected_text="$5"
  shift 5

  local page hdr after
  page="$(mktemp_track)"
  hdr="$(mktemp_track)"
  after="$(mktemp_track)"

  curl -k -sS -c "${cookie}" -b "${cookie}" "${BASE_URL}${from_path}" -o "${page}" || return 1
  extract_csrf "${page}" || return 2

  local args
  args=( -k -sS -c "${cookie}" -b "${cookie}" -D "${hdr}" -o /dev/null -X POST "${BASE_URL}${post_path}" --data-urlencode "${CSRF_NAME}=${CSRF_VAL}" )
  while [[ "$#" -gt 0 ]]; do
    args+=( --data-urlencode "$1" )
    shift
  done
  curl "${args[@]}" || return 3

  curl -k -sS -c "${cookie}" -b "${cookie}" "${BASE_URL}${from_path}" -o "${after}" || return 4
  if grep -q "id=\"${expected_id}\"" "${after}" && grep -q "${expected_text}" "${after}"; then
    return 0
  fi
  return 5
}

run_matrix_for_user() {
  local user="$1"
  local passw="$2"
  local role="$3"
  local cookie
  cookie="$(mktemp_track)"

  if login_user "${user}" "${passw}" "${cookie}"; then
    pass "${user} login success"
  else
    fail "${user} login failed"
    return
  fi

  check_allow "${cookie}" "${user}" '/dashboard'
  check_allow "${cookie}" "${user}" '/wa'
  check_allow "${cookie}" "${user}" '/messages'

  if [[ "${role}" == 'admin' ]]; then
    check_role_banner "${cookie}" "${user}" 'ADMIN'
    check_allow "${cookie}" "${user}" '/activity'
    check_allow "${cookie}" "${user}" '/devices'
    check_allow "${cookie}" "${user}" '/operator'
    check_deny_redirect_wa "${cookie}" "${user}" '/users'
  elif [[ "${role}" == 'operator' ]]; then
    check_role_banner "${cookie}" "${user}" 'OPERATOR'
    check_allow "${cookie}" "${user}" '/operator'
    check_deny_redirect_wa "${cookie}" "${user}" '/activity'
    check_deny_redirect_wa "${cookie}" "${user}" '/devices'
    check_deny_redirect_wa "${cookie}" "${user}" '/users'
  fi
}

run_ops_report_smoke() {
  local user="$1"
  local passw="$2"
  local cookie wa_html report_out
  cookie="$(mktemp_track)"
  wa_html="$(mktemp_track)"
  report_out="$(mktemp_track)"

  if ! login_user "${user}" "${passw}" "${cookie}"; then
    fail "Ops smoke login gagal (${user})"
    return
  fi

  curl -k -sS -c "${cookie}" -b "${cookie}" "${BASE_URL}/wa" -o "${wa_html}" || {
    fail "Ops smoke gagal load /wa"
    return
  }
  if ! extract_csrf "${wa_html}"; then
    fail 'Ops smoke csrf /wa tidak ditemukan'
    return
  fi

  local run_code
  run_code="$(curl -k -sS -o /dev/null -w '%{http_code}' -b "${cookie}" -c "${cookie}" \
    -X POST "${BASE_URL}/wa/ops/run" \
    --data-urlencode 'task=intent_report_daily' \
    --data-urlencode "${CSRF_NAME}=${CSRF_VAL}" || true)"
  if [[ ! "${run_code}" =~ ^30[23]$ ]]; then
    fail "Ops smoke POST /wa/ops/run gagal (http=${run_code})"
    return
  fi
  pass 'Ops smoke trigger intent_report_daily berhasil'

  local report_code
  report_code="$(curl -k -sS -o "${report_out}" -w '%{http_code}' -b "${cookie}" "${BASE_URL}/wa/ops/file/intent-latest" || true)"
  if [[ "${report_code}" -lt 200 || "${report_code}" -ge 400 ]]; then
    fail "Ops smoke GET /wa/ops/file/intent-latest gagal (http=${report_code})"
    return
  fi
  if grep -q 'Laporan Top Intent Mingguan' "${report_out}"; then
    pass 'Ops smoke konten intent-latest valid'
  else
    fail 'Ops smoke konten intent-latest tidak valid'
  fi
}

run_toast_tests() {
  local cookie

  cookie="$(mktemp_track)"
  if login_user 'loket1' 'Loket@1' "${cookie}"; then
    if post_and_assert_toast "${cookie}" '/operator' '/operator/reply' 'operator_status_toast' 'Nomor dan pesan wajib diisi\.' 'number=' 'message='; then
      pass 'Operator reply validation toast rendered'
    else
      fail 'Operator reply validation toast missing'
    fi
  else
    fail 'Operator toast setup login failed'
  fi

  cookie="$(mktemp_track)"
  if login_user 'loket2' 'Loket@2' "${cookie}"; then
    if post_and_assert_toast "${cookie}" '/wa' '/wa/send' 'wa_action_toast' 'Nomor dan pesan wajib diisi\.' 'number=' 'message=' 'send_mode=plain'; then
      pass 'WA action toast rendered on invalid send'
    else
      fail 'WA action toast missing on invalid send'
    fi
  else
    fail 'WA toast setup login failed'
  fi

  cookie="$(mktemp_track)"
  if login_user 'ketua' 'Semakinhebat@26' "${cookie}"; then
    if post_and_assert_toast "${cookie}" '/devices' '/devices/create' 'device_status_toast' 'Device name dan phone number wajib diisi\.' 'device_name=' 'phone_number='; then
      pass 'Device validation toast rendered'
    else
      fail 'Device validation toast missing'
    fi
  else
    fail 'Device toast setup login failed'
  fi
}

printf '' > "${REPORT_FILE}"
log_line "SMOKE START $(date -Is)"

if ! start_local_server_if_needed; then
  log_line ''
  log_line "TOTAL_PASS=${PASS_COUNT}"
  log_line "TOTAL_FAIL=${FAIL_COUNT}"
  log_line "REPORT_FILE=${REPORT_FILE}"
  exit 1
fi

run_matrix_for_user 'loket1' 'Loket@1' 'operator'
run_matrix_for_user 'loket2' 'Loket@2' 'operator'
run_matrix_for_user 'loket3' 'Loket@3' 'operator'
run_matrix_for_user 'loket0' 'Loket@0' 'operator'
run_matrix_for_user 'superptsp' 'Loket@26' 'operator'
run_matrix_for_user 'ketua' 'Semakinhebat@26' 'admin'
run_matrix_for_user 'wakilketua' 'Semakinhebat@26' 'admin'

run_toast_tests
run_ops_report_smoke 'ketua' 'Semakinhebat@26'

log_line ''
log_line "TOTAL_PASS=${PASS_COUNT}"
log_line "TOTAL_FAIL=${FAIL_COUNT}"
log_line "REPORT_FILE=${REPORT_FILE}"

if [[ "${FAIL_COUNT}" -gt 0 ]]; then
  exit 1
fi

log_line "SMOKE: PASS comprehensive login-role-toast-ops (${BASE_URL})"
