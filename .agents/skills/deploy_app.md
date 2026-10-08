# 🚀 Skill: deploy_app
# Agent: @devops (Deployment Wizard)
# Trigger: Dipanggil setelah @qa selesai audit

## Instruksi Eksekusi

Ketika skill ini diaktifkan, @devops HARUS mengikuti prosedur deployment Lawangsewu:

---

## Phase 0: PRE-FLIGHT CHECK (Sebelum Deploy)

```bash
# 1. Status git terkini
git status
git log --oneline -3

# 2. Pastikan working tree bersih atau known clean
git diff --stat

# 3. Cek branch yang akan di-deploy
git branch --show-current

# 4. Backup point untuk rollback
git tag deploy-pre-$(date +%Y%m%d-%H%M%S) 2>/dev/null || true
```

---

## Phase 1: DETECT — Identifikasi Scope Perubahan

Sebelum deploy, identifikasi:

```
1. Ada migration baru?          → Perlu php artisan migrate
2. Ada perubahan config?        → Perlu config:clear
3. Ada perubahan route?         → Perlu route:clear
4. Ada perubahan view?          → Perlu view:clear
5. Ada perubahan frontend?      → Perlu npm run build
6. Ada perubahan permission?    → Perlu seed permissions
7. Ada package baru?            → Perlu composer install / npm install
```

### Phase 2: BUILD — Persiapan

#### Backend Dependencies
```bash
# Jika composer.json berubah
composer install --no-dev --optimize-autoloader

# Jika package.json berubah
npm install
```

#### Frontend Build
```bash
# Build production assets
npm run build

# Verifikasi output
ls -la public/build/
```

#### Cache Management
```bash
# Clear semua cache lama
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan event:clear

# Rebuild cache (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Phase 3: MIGRATE — Database

```bash
# Cek status migration
php artisan migrate:status

# Jalankan migration
php artisan migrate --force

# Seed jika ada data baru
php artisan db:seed --class=PilarServiceCatalogSeeder  # jika ada perubahan catalog
php artisan db:seed --class=PermissionSeeder            # jika ada permission baru

# Verifikasi RBAC integrity
php artisan rbac:verify
```

### Phase 4: VERIFY — Post-Deployment Check

#### System Check
```bash
# Framework OK?
php artisan --version

# Routes OK?
php artisan route:list --compact | head -20

# Database OK?
php artisan migrate:status | tail -5

# RBAC OK?
php artisan rbac:verify
```

#### Test Suite
```bash
# Jalankan test
php artisan test

# Baseline aktual: 100+ tests, 1 pre-existing fail (ProfileTest)
# Target: 0 regresi baru
```

#### Service Health
```bash
# Cek apakah server bisa diakses
php artisan serve --port=8000 &
sleep 2
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000
kill %1

# Cek Reverb (WebSocket)
# php artisan reverb:start --port=8080 &
```

### Phase 5: SERVE — Launch

#### Development (Lokal)
```bash
# Option 1: Semua service sekaligus
composer run dev
# → server, queue, logs, vite semua berjalan

# Option 2: Manual
php artisan serve                    # HTTP server
php artisan queue:listen --tries=1   # Queue worker
php artisan reverb:start             # WebSocket
npm run dev                          # Vite HMR
```

#### Production
```bash
# Pastikan Nginx config benar
# Pastikan SSL valid
# Pastikan .env production benar:
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://lawangsewu.pa-semarang.go.id
#   GOOGLE_REDIRECT_URI=https://lawangsewu.pa-semarang.go.id/auth/google/callback

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart Reverb (jika menggunakan supervisor)
sudo supervisorctl restart lawangsewu-reverb

# Restart Queue (jika menggunakan supervisor)
sudo supervisorctl restart lawangsewu-queue
```

---

## Output

Setelah deployment, @devops WAJIB menyajikan laporan:

```markdown
## 🚀 Deployment Report

### Environment
- Framework: Laravel XX.X.X
- PHP: X.X.X
- Node: X.X.X

### Actions Taken
- [x] Dependencies installed
- [x] Frontend built
- [x] Caches cleared
- [x] Migrations executed (X baru)
- [x] Tests passed (XX/XX)
- [x] RBAC verified

### Access
- Local: http://localhost:8000
- Production: https://lawangsewu.pa-semarang.go.id

### Status: ✅ OPERATIONAL
```

---

## 🔙 Rollback Procedure (Jika Deploy Gagal)

Jika deployment gagal atau ada regresi critical, FOLLOW THIS PROCEDURE:

### Step 1: IDENTIFY FAILURE POINT

```bash
# Cek apakah masalah di frontend atau backend
npm run build  # Apakah gagal?
php artisan migrate --force  # Apakah migration error?
php artisan test  # Apakah test fails?

# Cek log untuk error spesifik
tail -50 storage/logs/laravel.log
```

### Step 2: DECIDE ROLLBACK SCOPE

| Scenario | Action |
|----------|--------|
| Frontend build failed | `npm run build` ulang, tidak perlu rollback DB |
| Migration failed | `php artisan migrate:rollback --step=1` |
| Test regression | Git revert, rebuild |
| Full system crash | Restore dari backup |

### Step 3: ROLLBACK CODE

```bash
# Jika perlu revert code changes
# Option 1: Revert specific commit
git revert <bad-commit-hash>

# Option 2: Checkout ke commit terakhir yang good
git log --oneline -10  # Cari commit good
git checkout <good-commit-hash> -- .
git commit -m "revert: rollback to stable state"

# Option 3: Reset ke tag backup (dari Phase 0)
git checkout deploy-pre-YYYYMMDD-HHMMSS  # Tag dari pre-flight
```

### Step 4: ROLLBACK DATABASE (Jika Perlu)

```bash
# HANYA jika migration yang menyebabkan masalah

# Cek migration status
php artisan migrate:status

# Rollback migration terakhir
php artisan migrate:rollback --step=1

# Jika perlu full restore (HARUS punya backup sebelumnya)
# mysql -u root -p lawangsewu < backup-YYYYMMDD-HHMMSS.sql
```

### Step 5: RESTART SERVICES

```bash
# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart Reverb
sudo supervisorctl restart lawangsewu-reverb

# Restart Queue
sudo supervisorctl restart lawangsewu-queue

# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 6: VERIFY RECOVERY

```bash
# Framework OK?
php artisan --version

# Routes OK?
php artisan route:list --compact | head -10

# Test suite?
php artisan test --compact 2>&1 | tail -20

# Health check
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000
```

### Step 7: REPORT INCIDENT

```markdown
## 🔙 ROLLBACK REPORT

### Incident
- Time: [TIMESTAMP]
- Trigger: [What caused the issue]
- Impact: [What was affected]

### Actions Taken
- [x] Identified failure point: [description]
- [x] Rolled back code to: [commit/tag]
- [x] Rolled back DB: [Yes/No - reason]
- [x] Restarted services
- [x] Verified recovery

### Current Status
- Framework: [version] - OK
- Test Suite: [passed/failed] - [count]
- System: [operational/degraded]

### Next Steps
1. [What to do next]
2. [Prevention measures]
```

---

## Constraint Assertions

- ❌ **DILARANG** menjalankan `migrate:fresh` atau `migrate:reset` di production
- ❌ **DILARANG** mengubah `.env` production tanpa approval user
- ❌ **DILARANG** deploy jika test suite punya regression baru
- ✅ **WAJIB** backup database sebelum migration production (`mysqldump`)
- ✅ **WAJIB** clear cache sebelum dan sesudah deployment
- ✅ **WAJIB** report URL akses setelah deployment selesai
- ✅ **WAJIB** restart supervisor: `lawangsewu-reverb` dan `lawangsewu-queue`
- ✅ **WAJIB** buat rollback tag di Phase 0 sebelum deploy

<!-- developed by dbprakom™ -->
