# Migrasi WA Caraka via GitHub ke Server Baru

## Tujuan
Dokumen ini menyiapkan alur migrasi aman: source dari GitHub, secret tetap lokal, dan verifikasi pasca-migrasi.

## 1. Persiapan dari server asal
Jalankan:

```bash
cd /var/www/html/lawangsewu
bash scripts/prepare-github-migration.sh http://127.0.0.1:8792
```

Output ada di `releases/migration/lawangsewu-migration-<timestamp>/`:
- `repo-<timestamp>.bundle`
- `source-<timestamp>.tar.gz`
- `SHA256SUMS`
- `README-MIGRATION.txt`

Alternatif pipeline penuh (backup terenkripsi + verifikasi + migrasi + push GitHub):

```bash
cd /var/www/html/lawangsewu
bash scripts/backup-github-full.sh
```

## 2. Upload kode ke GitHub
1. Buat repository private di GitHub.
2. Tambahkan remote:

```bash
git remote add github <url-repo-private>
```

3. Push branch utama dan tag:

```bash
git push github main --tags
```

## 3. Pull ke server target
```bash
cd /var/www/html
git clone <url-repo-private> lawangsewu
cd lawangsewu
```

## 4. Restore konfigurasi lokal (wajib)
Secret tidak ikut ke GitHub. Siapkan ulang file berikut:
- `scripts/backup.conf`
- `scripts/offsite.conf`
- `gateway/.env`
- `.env` untuk project CI4/Node terkait

## 5. Install dependency
- CI4 admin:

```bash
cd /var/www/html/lawangsewu/wa-caraka/dashboard-ci4-admin
composer install --no-dev --optimize-autoloader
```

- WA runtime:

```bash
cd /var/www/html/lawangsewu/wa-caraka
npm ci --omit=dev
```

## 6. Validasi pasca-migrasi
1. Jalankan healthcheck script dan cek service status.
2. Verifikasi login dashboard, akses WA Console, dan generate report.
3. Jalankan security audit:

```bash
cd /var/www/html/lawangsewu
bash scripts/security-audit.sh http://127.0.0.1:8792
```

## 7. Aktifkan backup dan offsite
- Backup harian terenkripsi:

```bash
bash scripts/setup-backup-cron.sh
```

- Offsite backup (rclone):

```bash
bash scripts/setup-offsite-cron.sh
```

- Verifikasi backup terbaru:

```bash
bash scripts/verify-backup.sh
```

## 8. Bootstrap untuk Pengadilan Lain

Untuk setup instance pengadilan baru secara cepat (env WA + env CI4):

```bash
cd /var/www/html/lawangsewu
bash scripts/install-court-instance.sh --court-code pa-contoh --base-url 'https://contoh.go.id/wa-caraka-admin/'
```
