# Panduan Migrasi Server Baru Lawangsewu

Tanggal: 2026-04-23

## Tujuan

Dokumen ini dibuat sebagai pengingat langkah migrasi `lawangsewu` ke server baru agar tidak ada komponen penting yang tertinggal.

Fokus utama:

- aplikasi `Laravel` di `/var/www/lawangsewu`
- database utama `lawangsewu_core`
- runtime `WA Caraka`
- service `queue` dan `reverb`
- konfigurasi Apache dan permission user `dbprakom` + `www-data`

## Ringkasan Rekomendasi

Untuk project ini, cara paling aman adalah:

- source code lewat `GitHub`
- data dan state penting lewat transfer langsung server-to-server atau `WinSCP`
- database lewat `mysqldump`

Jangan hanya copy 1 folder penuh lalu langsung dijalankan di server baru, karena ada komponen yang tidak ikut aman kalau hanya copy folder.

## Apakah Bisa Pakai WinSCP Saja?

Bisa, tetapi tidak cukup kalau hanya:

- copy `/var/www/lawangsewu`
- copy `/home/dbprakom`

Lalu berharap aplikasi langsung normal di server baru.

Alasannya:

- database MySQL tidak ikut aman hanya dari copy folder project
- service `systemd` perlu dipasang ulang
- Apache config perlu dicek ulang
- permission file dan ACL perlu disesuaikan
- runtime WA punya state tersendiri

## Opsi Migrasi

### Opsi 1: GitHub + Transfer Data Langsung

Ini opsi terbaik dan paling aman.

Pola kerja:

- push source code yang bersih ke `GitHub`
- clone di server baru
- transfer `.env`, file upload, dan state runtime secara manual
- dump dan import database

Kelebihan:

- source lebih rapi
- mudah rollback
- tidak membawa cache atau sampah runtime lama
- cocok untuk deployment ulang di masa depan

### Opsi 2: WinSCP Full Copy

Bisa dipakai untuk percepatan, tetapi harus hati-hati.

Pakai jika:

- ingin memindahkan file project dengan cepat
- kedua server belum siap dengan workflow GitHub

Risiko:

- ikut membawa cache lama
- ikut membawa permission yang tidak cocok
- folder besar seperti `vendor` dan `node_modules` ikut tersalin padahal bisa dibangun ulang
- rawan lupa database dan service

## Yang Perlu Dipindahkan

### Wajib

- `/var/www/lawangsewu`
- file `.env`
- database `lawangsewu_core`
- konfigurasi Apache yang dipakai domain `lawangsewu`
- service:
  - `lawangsewu-queue.service`
  - `lawangsewu-reverb.service`

### Perlu Dicek Jika Dipakai

- `storage/app/`
- `storage/app/public/`
- `storage/app/private/`
- `wa-runtime/baileys_auth_info/`
- file tambahan di `/home/dbprakom` jika ada script pribadi atau SSH config yang memang dibutuhkan

### Tidak Perlu Diutamakan untuk Dicopy Mentah

- `vendor/`
- `node_modules/`
- `bootstrap/cache/*`
- `storage/framework/*`
- `storage/logs/*`
- `wa-runtime/server.log`

Komponen di atas lebih aman dibuat ulang di server baru.

## Tentang `dbprakom`

Perlu dibedakan:

- `dbprakom` bisa berarti user Linux
- `dbprakom` juga bisa dipakai sebagai username database

Untuk migrasi server:

- buat user Linux `dbprakom` juga di server baru
- samakan ownership dan permission yang dibutuhkan
- tidak harus copy seluruh `/home/dbprakom` bila isinya tidak relevan

## Langkah Aman Migrasi

### 1. Siapkan server baru

Minimal siapkan:

- Apache
- PHP sesuai versi aktif project
- Composer
- Node.js + npm
- MySQL atau MariaDB
- systemd
- `setfacl` jika dipakai untuk ACL runtime

### 2. Pindahkan source code

Disarankan:

- clone dari GitHub

Alternatif:

- copy folder project lewat WinSCP

### 3. Pindahkan file konfigurasi rahasia

Copy secara manual:

- `.env`

Jangan commit `.env` ke GitHub.

### 4. Pindahkan database

Di server lama:

```bash
mysqldump -u dbprakom -p lawangsewu_core > lawangsewu_core.sql
```

Pindahkan file dump ke server baru, lalu import:

```bash
mysql -u dbprakom -p lawangsewu_core < lawangsewu_core.sql
```

### 5. Pindahkan data stateful bila ada

Contoh:

- file upload di `storage/app/`
- state WA di `wa-runtime/baileys_auth_info/`

### 6. Install dependency ulang

Di server baru:

```bash
cd /var/www/lawangsewu
composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
```

### 7. Jalankan migrasi dan cache ulang

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Pasang service

Gunakan file di project:

- `ops/systemd/lawangsewu-queue.service`
- `ops/systemd/lawangsewu-reverb.service`

Lalu install:

```bash
sudo bash ops/scripts/install_prod_services.sh
```

### 9. Perbaiki permission runtime

```bash
sudo bash ops/scripts/fix_laravel_runtime_permissions.sh
```

### 10. Verifikasi

Cek:

- halaman `/health`
- halaman `/login`
- queue worker aktif
- reverb aktif
- runtime WA aktif
- koneksi ke `SIPP_DB_HOST` tetap bisa jalan

## Checklist WinSCP

Kalau tetap mau pakai WinSCP, checklist aman:

- copy `/var/www/lawangsewu`
- copy `.env`
- copy `storage/app/` bila ada data penting
- copy `wa-runtime/baileys_auth_info/` bila sesi WA ingin dipertahankan
- jangan andalkan WinSCP untuk database
- jangan lupa Apache config dan systemd
- setelah copy, tetap jalankan install dependency dan cache ulang di server baru

## Checklist Sebelum Cutover

- source code terbaru sudah ada di server baru
- `.env` sudah benar
- database sudah terimport
- Apache vhost sudah sesuai
- permission `dbprakom` dan `www-data` sudah benar
- queue dan reverb aktif
- WA runtime aktif bila dipakai
- endpoint utama lulus pengecekan

## Kesimpulan

Kesimpulan praktis:

- `WinSCP` boleh dipakai untuk memindahkan file
- tetapi jangan dijadikan satu-satunya metode migrasi
- database tetap harus dipindah dengan dump
- setup service, Apache, dan permission tetap harus dikerjakan terpisah

Pilihan terbaik untuk kasus `lawangsewu`:

- `GitHub` untuk source code
- `WinSCP` atau transfer private IP untuk file penting
- `mysqldump` untuk database

