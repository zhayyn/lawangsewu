#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="/var/www/html/lawangsewu"
OUTPUT_DIR="${PROJECT_DIR}/releases/migration"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
PKG_DIR="${OUTPUT_DIR}/lawangsewu-migration-${TIMESTAMP}"
mkdir -p "${PKG_DIR}"

echo "[1/5] Menjalankan preflight security audit lokal..."
"${PROJECT_DIR}/scripts/security-audit.sh" "${1:-http://127.0.0.1:8792}" || true

echo "[2/5] Membuat git bundle untuk migrasi offline..."
git -C "${PROJECT_DIR}" bundle create "${PKG_DIR}/repo-${TIMESTAMP}.bundle" --all

echo "[3/5] Membuat source snapshot (tanpa secret/runtime)..."
tar \
  --exclude='.git' \
  --exclude='logs' \
   --exclude='releases/migration' \
  --exclude='archive' \
  --exclude='wa-caraka/logs' \
  --exclude='wa-caraka/auth_info_multi*' \
  --exclude='scripts/backup.conf' \
  --exclude='scripts/offsite.conf' \
  --exclude='**/.env' \
  --exclude='**/node_modules' \
  --exclude='**/vendor' \
  -czf "${PKG_DIR}/source-${TIMESTAMP}.tar.gz" \
  -C "${PROJECT_DIR}" .

echo "[4/5] Membuat checksum artefak migrasi..."
(
  cd "${PKG_DIR}"
  sha256sum ./* > SHA256SUMS
)

echo "[5/5] Membuat panduan ringkas migrasi..."
cat > "${PKG_DIR}/README-MIGRATION.txt" <<'EOF'
Langkah migrasi (target server):
1. Clone repo dari GitHub atau gunakan file bundle:
   - git clone <url-repo-github>
   - atau: git clone repo-*.bundle lawangsewu
2. Salin source snapshot bila perlu:
   - tar -xzf source-*.tar.gz -C /var/www/html/lawangsewu
3. Buat file env dari contoh + isi secret lokal:
   - scripts/backup.conf, scripts/offsite.conf, gateway/.env, project .env
4. Install dependency sesuai stack:
   - CI4: composer install
   - Node WA: npm ci (di folder wa-caraka)
5. Jalankan migration/seed CI4 jika dibutuhkan.
6. Jalankan healthcheck dan smoke test:
   - bash scripts/post-deploy-wa-caraka-smoke.sh
   - atau minimal: bash wa-caraka/scripts/smoke-runtime-endpoints.sh dan bash scripts/smoke-login-process-report.sh
7. Aktifkan cron backup + offsite.
EOF

echo "Paket migrasi siap di: ${PKG_DIR}"
ls -la "${PKG_DIR}"
