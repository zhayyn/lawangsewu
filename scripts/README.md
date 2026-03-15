# Lawangsewu Root Scripts

Folder ini berisi script operasional tingkat root project Lawangsewu.

## Kelompok script

### WA Caraka
- `start-wa-caraka.sh`
- `status-wa-caraka.sh`
- `stop-wa-caraka.sh`
- `start-wa-caraka-admin.sh`
- `status-wa-caraka-admin.sh`
- `stop-wa-caraka-admin.sh`
- `healthcheck-wa-caraka.sh`
- `post-deploy-wa-caraka-smoke.sh`
- `setup-wa-caraka-smoke-cron.sh`
- `smoke-login-process-production.sh`
- `smoke-login-process-report.sh`
- `install-court-instance.sh`

### Backup dan recovery
- `backup-daily.sh`
- `backup-github-full.sh`
- `verify-backup.sh`
- `push-offsite-backup.sh`
- `restore-drill-monthly.sh`
- `setup-backup-cron.sh`
- `setup-offsite-cron.sh`
- `setup-restore-drill-cron.sh`

### Deployment dan sinkronisasi
- `deploy-lumpiapasar-s9.sh`
- `sync-server10.sh`
- `prepare-github-migration.sh`

### Security dan hardening
- `security-audit.sh`
- `harden-ufw-lawangsewu-safe.sh`
- `harden-ufw-server9.sh`
- `rollback-lawangsewu-secrets.sh`
- `setup-ssh-server10.sh`
- `setup-wireguard-server9.sh`

### Gateway dan utilitas lain
- `healthcheck-gateway.sh`
- `rotate_statistik_log.sh`

## Praktik aman

- Jalankan script root dari direktori `/var/www/html/lawangsewu` kecuali dokumentasi menyebut lain.
- Untuk perubahan yang menyentuh service aktif, lakukan smoke test setelah eksekusi.
- Simpan konfigurasi sensitif di file `.conf` atau `.env`, bukan hardcode di script.
