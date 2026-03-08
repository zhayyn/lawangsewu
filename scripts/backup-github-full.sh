#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
SCRIPT_DIR="${PROJECT_DIR}/scripts"
CONF_FILE="${SCRIPT_DIR}/github-backup.conf"
BACKUP_CONF_FILE="${SCRIPT_DIR}/backup.conf"

if [[ -f "${CONF_FILE}" ]]; then
  # shellcheck source=/dev/null
  source "${CONF_FILE}"
fi

SOURCE_GITHUB_REMOTE="${SOURCE_GITHUB_REMOTE:-github}"
SOURCE_GITHUB_BRANCH="${SOURCE_GITHUB_BRANCH:-main}"
PUSH_SOURCE_REPO="${PUSH_SOURCE_REPO:-1}"

PUSH_ARTIFACTS_GITHUB="${PUSH_ARTIFACTS_GITHUB:-0}"
BACKUP_GITHUB_ARTIFACT_REPO_URL="${BACKUP_GITHUB_ARTIFACT_REPO_URL:-}"
BACKUP_GITHUB_ARTIFACT_BRANCH="${BACKUP_GITHUB_ARTIFACT_BRANCH:-main}"
BACKUP_GITHUB_ARTIFACT_SUBDIR="${BACKUP_GITHUB_ARTIFACT_SUBDIR:-lawangsewu}"

AUDIT_BASE_URL="${AUDIT_BASE_URL:-http://127.0.0.1:8792}"
RUN_SECURITY_AUDIT="${RUN_SECURITY_AUDIT:-1}"

BACKUP_ROOT="/var/backups/lawangsewu"
if [[ -f "${BACKUP_CONF_FILE}" ]]; then
  # shellcheck source=/dev/null
  source "${BACKUP_CONF_FILE}"
fi
BACKUP_ROOT="${BACKUP_ROOT:-/var/backups/lawangsewu}"

log() {
  printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

latest_migration_dir() {
  ls -1dt "${PROJECT_DIR}"/releases/migration/lawangsewu-migration-* 2>/dev/null | head -n1 || true
}

latest_encrypted_backup() {
  ls -1t "${BACKUP_ROOT}"/lawangsewu_*.tar.enc 2>/dev/null | head -n1 || true
}

commit_if_needed() {
  local repo_dir="$1"
  local message="$2"
  if [[ -n "$(git -C "${repo_dir}" status --porcelain)" ]]; then
    git -C "${repo_dir}" add .
    git -C "${repo_dir}" commit -m "${message}"
    return 0
  fi
  return 1
}

log "Mulai full backup pipeline ke GitHub"

if [[ "${RUN_SECURITY_AUDIT}" == "1" ]]; then
  log "[1/7] Menjalankan security audit"
  "${SCRIPT_DIR}/security-audit.sh" "${AUDIT_BASE_URL}" || true
fi

log "[2/7] Menjalankan backup terenkripsi"
"${SCRIPT_DIR}/backup-daily.sh"

log "[3/7] Verifikasi backup terenkripsi"
"${SCRIPT_DIR}/verify-backup.sh"

LATEST_BACKUP="$(latest_encrypted_backup)"
[[ -n "${LATEST_BACKUP}" ]] || fail "Backup terenkripsi terbaru tidak ditemukan di ${BACKUP_ROOT}"

log "[4/7] Menyiapkan paket migrasi instalasi"
BEFORE_MIGRATION="$(latest_migration_dir)"
"${SCRIPT_DIR}/prepare-github-migration.sh" "${AUDIT_BASE_URL}"
AFTER_MIGRATION="$(latest_migration_dir)"

if [[ -z "${AFTER_MIGRATION}" || "${AFTER_MIGRATION}" == "${BEFORE_MIGRATION}" ]]; then
  fail "Paket migrasi baru tidak terdeteksi"
fi

if [[ "${PUSH_SOURCE_REPO}" == "1" ]]; then
  log "[5/7] Push source repository ke remote ${SOURCE_GITHUB_REMOTE}"
  git -C "${PROJECT_DIR}" push "${SOURCE_GITHUB_REMOTE}" "${SOURCE_GITHUB_BRANCH}" --tags
else
  log "[5/7] Skip push source repository"
fi

if [[ "${PUSH_ARTIFACTS_GITHUB}" == "1" ]]; then
  [[ -n "${BACKUP_GITHUB_ARTIFACT_REPO_URL}" ]] || fail "BACKUP_GITHUB_ARTIFACT_REPO_URL wajib diisi saat PUSH_ARTIFACTS_GITHUB=1"

  log "[6/7] Push artefak backup terenkripsi ke repo artifact"
  TMP_DIR="$(mktemp -d)"
  trap 'rm -rf "${TMP_DIR}"' EXIT

  git clone --depth 1 --branch "${BACKUP_GITHUB_ARTIFACT_BRANCH}" "${BACKUP_GITHUB_ARTIFACT_REPO_URL}" "${TMP_DIR}/artifact-repo"

  ART_DIR="${TMP_DIR}/artifact-repo/${BACKUP_GITHUB_ARTIFACT_SUBDIR}/$(date +%Y%m%d-%H%M%S)"
  mkdir -p "${ART_DIR}"

  cp "${LATEST_BACKUP}" "${ART_DIR}/"
  sha256sum "${LATEST_BACKUP}" > "${ART_DIR}/SHA256SUMS"

  tar -czf "${ART_DIR}/migration-package.tar.gz" -C "${AFTER_MIGRATION}" .
  sha256sum "${ART_DIR}/migration-package.tar.gz" >> "${ART_DIR}/SHA256SUMS"

  {
    echo "# Backup Manifest"
    echo
    echo "- generated_at: $(date -Iseconds)"
    echo "- host: $(hostname -f 2>/dev/null || hostname)"
    echo "- source_commit: $(git -C "${PROJECT_DIR}" rev-parse HEAD)"
    echo "- encrypted_backup: $(basename "${LATEST_BACKUP}")"
    echo "- migration_package: migration-package.tar.gz"
  } > "${ART_DIR}/MANIFEST.md"

  if commit_if_needed "${TMP_DIR}/artifact-repo" "backup: ${BACKUP_GITHUB_ARTIFACT_SUBDIR} $(date +%Y-%m-%d_%H:%M:%S)"; then
    git -C "${TMP_DIR}/artifact-repo" push origin "${BACKUP_GITHUB_ARTIFACT_BRANCH}"
  else
    log "Tidak ada perubahan artefak untuk di-push"
  fi

  trap - EXIT
  rm -rf "${TMP_DIR}"
else
  log "[6/7] Skip push artefak backup ke GitHub (PUSH_ARTIFACTS_GITHUB=0)"
fi

log "[7/7] Selesai"
log "Backup terenkripsi terbaru : ${LATEST_BACKUP}"
log "Paket migrasi terbaru      : ${AFTER_MIGRATION}"
