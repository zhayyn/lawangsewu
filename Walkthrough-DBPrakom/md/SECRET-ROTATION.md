# Runbook Rotasi Secret (DB, SMTP, Gateway)

Dokumen ini adalah langkah eksekusi real untuk rotasi secret tanpa downtime berarti.

## Prasyarat

- Akses shell sebagai root/sudo di server
- Akses MySQL/MariaDB admin
- Domain aktif: `lawangsewu.pa-semarang.go.id`

## 0) Siapkan nilai secret baru

Jalankan untuk membuat contoh password/token kuat:

```bash
openssl rand -base64 36
```

Simpan nilai baru di password manager (jangan di chat, jangan di git).

---

## 1) Rotasi credential database Lumpiapasar

### 1.1 Buat user DB baru + grant

Ganti nilai di dalam tanda `<...>` lalu jalankan:

```bash
mysql -u root -p <<'SQL'
CREATE USER IF NOT EXISTS '<DB_USER_BARU>'@'localhost' IDENTIFIED BY '<DB_PASS_BARU>';
GRANT ALL PRIVILEGES ON <DB_NAME_UTAMA>.* TO '<DB_USER_BARU>'@'localhost';
GRANT ALL PRIVILEGES ON <DB_NAME_KEDUA>.* TO '<DB_USER_BARU>'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### 1.2 Update runtime env aplikasi aktif

Edit file:

- `projects/website-pa-semarang/lumpiapasar-s9-active/.env`

Contoh isi minimal:

```dotenv
DB_HOST=localhost
DB_USER=<DB_USER_BARU>
DB_PASS=<DB_PASS_BARU>
DB_NAME=<DB_NAME_UTAMA>
DB_SECONDARY_NAME=<DB_NAME_KEDUA>
```

Set permission aman:

```bash
chown www-data:www-data /var/www/html/lawangsewu/projects/website-pa-semarang/lumpiapasar-s9-active/.env
chmod 640 /var/www/html/lawangsewu/projects/website-pa-semarang/lumpiapasar-s9-active/.env
```

### 1.3 Verifikasi aplikasi

```bash
curl -s -o /dev/null -w 'LUMPIA=%{http_code}\n' http://192.168.88.9/lumpiapasar-s9/
```

Expected: `LUMPIA=200`

### 1.4 Cabut user lama (setelah stabil)

```bash
mysql -u root -p <<'SQL'
DROP USER IF EXISTS '<DB_USER_LAMA>'@'localhost';
FLUSH PRIVILEGES;
SQL
```

---

## 2) Rotasi SMTP credential

Credential SMTP sekarang dibaca dari environment server (`SMTP_USER`, `SMTP_PASS`) pada proses registrasi akun.

### 2.1 Set env SMTP di Apache

Tambah ke VirtualHost/domain aplikasi (atau file env Apache yang kamu pakai):

```apache
SetEnv SMTP_HOST smtp.gmail.com
SetEnv SMTP_USER <SMTP_USER_BARU>
SetEnv SMTP_PASS <SMTP_PASS_BARU>
```

Reload Apache:

```bash
systemctl reload apache2 2>/dev/null || systemctl reload httpd
```

### 2.2 Verifikasi fungsi kirim email

- Uji dari form pendaftaran yang memicu pengiriman email.
- Cek log web server jika gagal.

Setelah valid, nonaktifkan SMTP password lama di provider email.

---

## 3) Rotasi token Gateway API

### 3.1 Buat token baru

```bash
openssl rand -hex 32
```

### 3.2 Update token runtime

Edit file:

- `gateway/.env`

Set:

```dotenv
GATEWAY_API_TOKEN=<TOKEN_BARU>
```

### 3.3 Verifikasi endpoint gateway

```bash
curl -H "Authorization: Bearer <TOKEN_BARU>" \
  https://lawangsewu.pa-semarang.go.id/gateway/api/status.php
```

Expected: JSON status sukses.

Pastikan token lama tidak lagi dipakai oleh client/cron.

---

## 4) Verifikasi keamanan akhir

Jalankan audit otomatis:

```bash
/var/www/html/lawangsewu/scripts/security-audit.sh
```

Expected: `HASIL: PASS`

---

## 5) Rollback cepat (jika ada masalah)

1. Kembalikan nilai lama di `.env` aktif dan `gateway/.env`
2. Reload Apache
3. Verifikasi endpoint `200`
4. Investigasi error log

### Rollback otomatis (disarankan)

Sistem sekarang punya snapshot runtime secrets di folder root-only:

- `/root/lawangsewu-rollback/snapshot-YYYYmmdd-HHMMSS/`

Jalankan rollback otomatis (hanya untuk komponen Lawangsewu):

```bash
sudo /var/www/html/lawangsewu/scripts/rollback-lawangsewu-secrets.sh
```

Atau pilih snapshot tertentu:

```bash
sudo /var/www/html/lawangsewu/scripts/rollback-lawangsewu-secrets.sh /root/lawangsewu-rollback/snapshot-YYYYmmdd-HHMMSS
```

Script ini hanya menyentuh:

- `projects/website-pa-semarang/lumpiapasar-s9-active/.env`
- `gateway/.env`

Script **tidak** mengubah konfigurasi/DB server10 dan tidak menyentuh project lain di server9.

Perintah cek cepat:

```bash
curl -s -o /dev/null -w 'INFO=%{http_code}\n' https://lawangsewu.pa-semarang.go.id/info-persidangan
curl -s -o /dev/null -w 'LUMPIA=%{http_code}\n' http://192.168.88.9/lumpiapasar-s9/
```
