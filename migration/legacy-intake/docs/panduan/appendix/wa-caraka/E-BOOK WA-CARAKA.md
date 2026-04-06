# E-BOOK WA-CARAKA

Manual book terpadu operasional WA Caraka (dashboard admin + runtime WA + backup + migrasi).

Versi dokumen: 2026-03-08
Lokasi proyek: `/var/www/html/lawangsewu`

## Daftar Isi

1. Ringkasan Arsitektur
2. Prasyarat Server
3. Struktur Komponen Utama
4. Menjalankan Service
5. Manual Penggunaan Dashboard
6. Operator Inbox (Human Reply)
7. Smoke Test End-to-End
8. Backup Harian Terenkripsi
9. Restore Drill Bulanan + Alert (Webhook/WA)
10. Full Backup ke GitHub (Source + Artefak)
11. Migrasi ke Server Baru
12. Instalasi untuk Pengadilan Lain
13. Troubleshooting Cepat
14. Checklist Operasional Harian/Mingguan/Bulanan

---

## 1) Ringkasan Arsitektur

WA Caraka terdiri dari:

- Runtime WA API (Node): `wa-caraka/server.mjs`
- Dashboard Admin (CodeIgniter 4): `wa-caraka/dashboard-ci4-admin`
- Script operasional: `scripts/`
- Data/log runtime: `logs/`
- Artefak rilis/migrasi: `releases/`

Fungsi utama:

- Kelola WA Console dari dashboard web.
- Kirim message/manual ops/report dari admin.
- Backup terenkripsi + verifikasi restore.
- Migrasi lintas server/pengadilan dengan pipeline standar.

---

## 2) Prasyarat Server

Pastikan tersedia:

- Linux server
- Git
- Node.js + npm
- PHP + Composer
- MySQL/MariaDB
- `curl`, `openssl`, `tar`, `flock`

Direktori kerja standar:

```bash
cd /var/www/html/lawangsewu
```

---

## 3) Struktur Komponen Utama

- Runtime WA:
  - `wa-caraka/.env`
  - `wa-caraka/server.mjs`
- Dashboard CI4:
  - `wa-caraka/dashboard-ci4-admin/.env`
  - `wa-caraka/dashboard-ci4-admin/app/*`
- Script penting:
  - `scripts/start-wa-caraka.sh`
  - `scripts/start-wa-caraka-admin.sh`
  - `scripts/backup-daily.sh`
  - `scripts/verify-backup.sh`
  - `scripts/restore-drill-monthly.sh`
  - `scripts/backup-github-full.sh`
  - `scripts/install-court-instance.sh`

---

## 4) Menjalankan Service

### 4.1 Start WA Runtime

```bash
cd /var/www/html/lawangsewu
bash scripts/start-wa-caraka.sh
bash scripts/status-wa-caraka.sh
```

### 4.2 Start Dashboard Admin

```bash
cd /var/www/html/lawangsewu
bash scripts/start-wa-caraka-admin.sh
bash scripts/status-wa-caraka-admin.sh
```

### 4.3 Stop Service

```bash
cd /var/www/html/lawangsewu
bash scripts/stop-wa-caraka.sh
bash scripts/stop-wa-caraka-admin.sh
```

---

## 5) Manual Penggunaan Dashboard

File rujukan UI lengkap:
`wa-caraka/dashboard-ci4-admin/README-OPERATOR-WA-CONSOLE.md`

Alur operator utama:

1. Login dashboard.
2. Buka menu WA Console.
3. Cek `Runtime Observability` (PID, uptime, status).
4. Lakukan perubahan konfigurasi.
5. Gunakan `Simpan + Restart WA Caraka` bila perlu aktivasi langsung.
6. Cek `Activity Log` untuk audit perubahan.
7. Cek report intent mingguan jika update knowledge dibutuhkan.

Kebijakan user operator:

- Maksimal operator aktif dibatasi 5 user.
- Batas ini diberlakukan saat membuat operator baru dan saat aktivasi ulang user operator.

---

## 6) Operator Inbox (Human Reply)

Fitur baru untuk role `superadmin`, `admin`, dan `operator`:

- Route dashboard operator: `/operator`
- Tujuan: balas chat masuk secara human (manual), cepat, dan fokus ke percakapan terbaru.

Komponen halaman:

- Panel kontak masuk terbaru (50 kontak).
- Form balas human:
  - `Nomor Tujuan`
  - `Pesan Balasan`
  - `Device Token` opsional (wajib diisi jika runtime mengaktifkan `WA_CARAKA_REQUIRE_DEVICE_TOKEN=true`).
- Riwayat chat masuk 200 terbaru + tombol `Balas` untuk auto-isi nomor.

Alur pakai:

1. Buka menu `Operator Inbox` dari Dashboard.
2. Pilih kontak dari panel kiri atau klik tombol `Balas` pada tabel.
3. Tulis balasan manual di form.
4. Klik `Kirim Balasan Human`.

Endpoint backend yang dipakai:

- POST `/operator/reply` di dashboard CI4.
- Diteruskan ke runtime WA Caraka endpoint `/send-text`.

---

## 7) Smoke Test End-to-End

Untuk test login -> proses WA -> report:

```bash
cd /var/www/html/lawangsewu
bash scripts/smoke-login-process-report.sh http://127.0.0.1:8792
```

Mode trusted internal (untuk pengujian lokal deterministik):

```bash
SMOKE_TRUSTED_INTERNAL=1 SMOKE_LOCAL_BASE_URL='http://127.0.0.1:8792/' \
bash scripts/smoke-login-process-report.sh
```

---

## 8) Backup Harian Terenkripsi

### 7.1 Siapkan konfigurasi backup

```bash
cd /var/www/html/lawangsewu
cp -n scripts/backup.conf.example scripts/backup.conf
```

Isi minimal `scripts/backup.conf`:

- `BACKUP_PASSPHRASE` (WAJIB)
- opsional DB config (`DB_HOST`, `DB_NAME`, dll)

### 7.2 Jalankan backup

```bash
cd /var/www/html/lawangsewu
bash scripts/backup-daily.sh
```

### 7.3 Verifikasi backup dapat dipulihkan

```bash
cd /var/www/html/lawangsewu
bash scripts/verify-backup.sh
```

---

## 9) Restore Drill Bulanan + Alert (Webhook/WA)

### 8.1 Jalankan restore drill manual

```bash
cd /var/www/html/lawangsewu
bash scripts/restore-drill-monthly.sh
```

### 8.2 Pasang cron restore drill bulanan

```bash
cd /var/www/html/lawangsewu
bash scripts/setup-restore-drill-cron.sh
```

Default cron: `30 3 1 * *`.

### 8.3 Konfigurasi alert permanen

```bash
cd /var/www/html/lawangsewu
cp -n scripts/restore-drill.conf.example scripts/restore-drill.conf
```

Contoh isi `scripts/restore-drill.conf`:

```bash
RESTORE_DRILL_ALERT_WEBHOOK_URL='https://hooks.example.internal/restore-alert'
RESTORE_DRILL_ALERT_COMMAND='logger -t restore-drill "[$ALERT_LEVEL][$ALERT_HOST] $ALERT_MESSAGE"'

# WA alert ke superadmin
RESTORE_DRILL_ALERT_WA_SUPERADMIN_NUMBERS='6281317361689,628123456789'
RESTORE_DRILL_ALERT_WA_MODE='error'   # error | all | off
RESTORE_DRILL_ALERT_WA_API_URL='http://127.0.0.1:8793/send-text'
# RESTORE_DRILL_ALERT_WA_TOKEN=''      # isi jika WA_CARAKA_REQUIRE_DEVICE_TOKEN=true
```

### 8.4 Cara isi nomor WA superadmin

Aturan pengisian `RESTORE_DRILL_ALERT_WA_SUPERADMIN_NUMBERS`:

- Pisahkan dengan koma.
- Gunakan format angka internasional Indonesia: `62xxxxxxxxxx`.
- Jangan pakai spasi, tanda plus, atau karakter lain.

Contoh benar:

```bash
RESTORE_DRILL_ALERT_WA_SUPERADMIN_NUMBERS='6281317361689,628123456789'
```

Contoh tidak dianjurkan:

- `+6281317361689`
- `081317361689`
- `6281317361689, 628123456789` (ada spasi)

### 8.5 Verifikasi cepat alert command

```bash
ALERT_LEVEL='test' ALERT_HOST='localhost' ALERT_MESSAGE='restore-drill alert test' \
bash -lc "source /var/www/html/lawangsewu/scripts/restore-drill.conf; eval \"\$RESTORE_DRILL_ALERT_COMMAND\""
```

---

## 10) Full Backup ke GitHub (Source + Artefak)

Pipeline ini menjalankan:

1. Security audit (opsional)
2. Backup terenkripsi
3. Verifikasi restore
4. Pembuatan paket migrasi
5. Push source ke GitHub
6. Opsional push artefak backup terenkripsi ke repo private

### 9.1 Siapkan config

```bash
cd /var/www/html/lawangsewu
cp -n scripts/github-backup.conf.example scripts/github-backup.conf
```

Isi sesuai kebutuhan, contoh:

```bash
SOURCE_GITHUB_REMOTE='github'
SOURCE_GITHUB_BRANCH='main'
PUSH_SOURCE_REPO=1

PUSH_ARTIFACTS_GITHUB=1
BACKUP_GITHUB_ARTIFACT_REPO_URL='git@github.com:org/backup-wa-caraka.git'
BACKUP_GITHUB_ARTIFACT_BRANCH='main'
BACKUP_GITHUB_ARTIFACT_SUBDIR='lawangsewu'

AUDIT_BASE_URL='http://127.0.0.1:8792'
RUN_SECURITY_AUDIT=1
```

### 9.2 Jalankan pipeline

```bash
cd /var/www/html/lawangsewu
bash scripts/backup-github-full.sh
```

---

## 11) Migrasi ke Server Baru

Dokumen rujukan detail:
`MIGRASI-GITHUB-SERVER.md`

Ringkas migrasi:

1. Push source repo private ke GitHub.
2. Clone di server target.
3. Install dependency (CI4 + Node).
4. Restore file konfigurasi lokal (`.env`, `backup.conf`, `offsite.conf`).
5. Jalankan migrate/seed.
6. Jalankan smoke test + security audit.

---

## 12) Instalasi untuk Pengadilan Lain

Gunakan bootstrap script:

```bash
cd /var/www/html/lawangsewu
bash scripts/install-court-instance.sh \
  --court-code pa-contoh \
  --base-url 'https://contoh.go.id/wa-caraka-admin/' \
  --db-host 127.0.0.1 --db-port 3306 \
  --db-name db_wacaraka --db-user root --db-password 'ganti-password'
```

Hasil script:

- Menyiapkan/menulis `wa-caraka/.env`
- Menyiapkan/menulis `wa-caraka/dashboard-ci4-admin/.env`
- Mengatur `ADMIN_DEFAULT_USERNAME` dan `ADMIN_DEFAULT_PASSWORD`
- Menampilkan langkah lanjutan install dependency + start service

---

## 13) Troubleshooting Cepat

### 12.1 WA belum connected

- Cek endpoint health:

```bash
curl http://127.0.0.1:8793/health
```

- Cek QR endpoint:

```bash
curl -I http://127.0.0.1:8793/qr/image
```

### 12.2 Dashboard tidak bisa login

- Cek service CI4 aktif di port admin.
- Cek lockout login pada policy keamanan.
- Jalankan smoke test untuk diagnosis cepat.

### 12.3 Backup gagal

- Pastikan `BACKUP_PASSPHRASE` di `scripts/backup.conf` terisi.
- Pastikan folder `BACKUP_ROOT` writable.
- Jalankan `bash scripts/verify-backup.sh` untuk validasi hasil backup.

### 12.4 Alert WA tidak terkirim

- Cek `RESTORE_DRILL_ALERT_WA_SUPERADMIN_NUMBERS` format.
- Cek WA runtime connect (`/health`).
- Jika token wajib, isi `RESTORE_DRILL_ALERT_WA_TOKEN`.
- Uji manual endpoint send-text:

```bash
curl -sS -X POST http://127.0.0.1:8793/send-text \
  -H 'Content-Type: application/json' \
  -d '{"to":"6281317361689","text":"tes notifikasi"}'
```

---

## 14) Checklist Operasional

### Harian

- Cek status WA runtime dan dashboard.
- Cek log error utama.
- Jalankan backup harian (jika belum via cron).

### Mingguan

- Jalankan report intent mingguan.
- Evaluasi knowledge miss/error.
- Review activity log perubahan kritis.

### Bulanan

- Jalankan restore drill.
- Pastikan alert (syslog/webhook/WA) terkirim.
- Review paket migrasi terbaru dan checksum.

---

## Lampiran Perintah Penting

```bash
# Status service
bash scripts/status-wa-caraka.sh
bash scripts/status-wa-caraka-admin.sh

# Backup + verify
bash scripts/backup-daily.sh
bash scripts/verify-backup.sh

# Restore drill + setup cron
bash scripts/restore-drill-monthly.sh
bash scripts/setup-restore-drill-cron.sh

# Full backup pipeline github
bash scripts/backup-github-full.sh

# Bootstrap court instance
bash scripts/install-court-instance.sh --help
```

Selesai.
