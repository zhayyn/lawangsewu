#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
WA_DIR="${PROJECT_DIR}/wa-caraka"
ADMIN_DIR="${WA_DIR}/dashboard-ci4-admin"

COURT_CODE=""
BASE_URL=""
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="db_wacaraka"
DB_USER="root"
DB_PASSWORD=""
WA_PORT="8793"
WA_HOST="127.0.0.1"
ADMIN_USER="superadmin"
ADMIN_PASSWORD=""

usage() {
  cat <<'EOF'
Usage:
  bash scripts/install-court-instance.sh --court-code <slug> [options]

Options:
  --court-code <slug>          Required. Example: pa-semarang
  --base-url <url>             CI4 app.baseURL, example: https://domain/wa-caraka-admin/
  --db-host <host>             Default: 127.0.0.1
  --db-port <port>             Default: 3306
  --db-name <name>             Default: db_wacaraka
  --db-user <user>             Default: root
  --db-password <pass>         Default: empty
  --wa-port <port>             Default: 8793
  --wa-host <host>             Default: 127.0.0.1
  --admin-user <username>      Default: superadmin
  --admin-password <password>  Optional. Auto-generated if omitted.
  -h, --help                   Show this help.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --court-code) COURT_CODE="$2"; shift 2 ;;
    --base-url) BASE_URL="$2"; shift 2 ;;
    --db-host) DB_HOST="$2"; shift 2 ;;
    --db-port) DB_PORT="$2"; shift 2 ;;
    --db-name) DB_NAME="$2"; shift 2 ;;
    --db-user) DB_USER="$2"; shift 2 ;;
    --db-password) DB_PASSWORD="$2"; shift 2 ;;
    --wa-port) WA_PORT="$2"; shift 2 ;;
    --wa-host) WA_HOST="$2"; shift 2 ;;
    --admin-user) ADMIN_USER="$2"; shift 2 ;;
    --admin-password) ADMIN_PASSWORD="$2"; shift 2 ;;
    -h|--help) usage; exit 0 ;;
    *)
      echo "Unknown option: $1" >&2
      usage
      exit 1
      ;;
  esac
done

if [[ -z "${COURT_CODE}" ]]; then
  echo "ERROR: --court-code wajib diisi" >&2
  usage
  exit 1
fi

if [[ -z "${ADMIN_PASSWORD}" ]]; then
  ADMIN_PASSWORD="$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 16)"
fi

WA_ENV="${WA_DIR}/.env"
if [[ ! -f "${WA_ENV}" ]]; then
  cp "${WA_DIR}/.env.example" "${WA_ENV}"
fi

CI4_ENV="${ADMIN_DIR}/.env"
if [[ ! -f "${CI4_ENV}" ]]; then
  cp "${ADMIN_DIR}/env" "${CI4_ENV}"
fi

set_kv() {
  local file="$1"
  local key="$2"
  local value="$3"
  if grep -Eq "^${key}=" "${file}"; then
    sed -i -E "s#^${key}=.*#${key}=${value}#" "${file}"
  else
    printf '\n%s=%s\n' "${key}" "${value}" >> "${file}"
  fi
}

set_ci4_kv() {
  local file="$1"
  local key="$2"
  local value="$3"
  local escaped
  escaped="$(printf '%s' "${value}" | sed "s/'/'\\''/g")"

  if grep -Eq "^\s*#?\s*${key}\s*=" "${file}"; then
    sed -i -E "s#^\s*#?\s*${key}\s*=.*#${key} = '${escaped}'#" "${file}"
  else
    printf "\n%s = '%s'\n" "${key}" "${escaped}" >> "${file}"
  fi
}

set_kv "${WA_ENV}" "WA_CARAKA_PORT" "${WA_PORT}"
set_kv "${WA_ENV}" "WA_CARAKA_HOST" "${WA_HOST}"
set_kv "${WA_ENV}" "DB_HOST" "${DB_HOST}"
set_kv "${WA_ENV}" "DB_PORT" "${DB_PORT}"
set_kv "${WA_ENV}" "DB_NAME" "${DB_NAME}"
set_kv "${WA_ENV}" "DB_USER" "${DB_USER}"
set_kv "${WA_ENV}" "DB_PASSWORD" "${DB_PASSWORD}"

set_ci4_kv "${CI4_ENV}" "CI_ENVIRONMENT" "production"
if [[ -n "${BASE_URL}" ]]; then
  set_ci4_kv "${CI4_ENV}" "app.baseURL" "${BASE_URL}"
fi
set_ci4_kv "${CI4_ENV}" "database.default.hostname" "${DB_HOST}"
set_ci4_kv "${CI4_ENV}" "database.default.port" "${DB_PORT}"
set_ci4_kv "${CI4_ENV}" "database.default.database" "${DB_NAME}"
set_ci4_kv "${CI4_ENV}" "database.default.username" "${DB_USER}"
set_ci4_kv "${CI4_ENV}" "database.default.password" "${DB_PASSWORD}"
set_ci4_kv "${CI4_ENV}" "ADMIN_DEFAULT_USERNAME" "${ADMIN_USER}"
set_ci4_kv "${CI4_ENV}" "ADMIN_DEFAULT_PASSWORD" "${ADMIN_PASSWORD}"
set_ci4_kv "${CI4_ENV}" "APP_INSTANCE_CODE" "${COURT_CODE}"

echo "[OK] Bootstrap konfigurasi instance pengadilan selesai"
echo "- court_code      : ${COURT_CODE}"
echo "- wa_env          : ${WA_ENV}"
echo "- ci4_env         : ${CI4_ENV}"
echo "- admin_username  : ${ADMIN_USER}"
echo "- admin_password  : ${ADMIN_PASSWORD}"
echo

echo "Langkah lanjutan di server target:"
echo "1) cd ${ADMIN_DIR} && composer install --no-dev --optimize-autoloader"
echo "2) php spark migrate --all"
echo "3) php spark db:seed AdminUserSeeder"
echo "4) cd ${WA_DIR} && npm ci --omit=dev"
echo "5) bash ${PROJECT_DIR}/scripts/start-wa-caraka.sh && bash ${PROJECT_DIR}/scripts/start-wa-caraka-admin.sh"
