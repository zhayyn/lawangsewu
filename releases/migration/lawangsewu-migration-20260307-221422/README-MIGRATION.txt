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
6. Jalankan healthcheck dan smoke test login/WA/report.
7. Aktifkan cron backup + offsite.
