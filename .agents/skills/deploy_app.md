# 🚀 Skill: deploy_app
# Agent: @devops (Deployment Wizard)
# Trigger: Dipanggil setelah @qa selesai audit

## Instruksi Eksekusi

Ketika skill ini diaktifkan, @devops HARUS mengikuti prosedur deployment Lawangsewu:

---

### Phase 1: DETECT — Identifikasi Scope Perubahan

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

# Target: 74+ pass, 0 regresi
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

## Constraint Assertions

- ❌ **DILARANG** menjalankan `migrate:fresh` atau `migrate:reset` di production
- ❌ **DILARANG** mengubah `.env` production tanpa approval user
- ❌ **DILARANG** deploy jika test suite punya regression baru
- ✅ **WAJIB** backup database sebelum migration production (`mysqldump`)
- ✅ **WAJIB** clear cache sebelum dan sesudah deployment
- ✅ **WAJIB** report URL akses setelah deployment selesai
