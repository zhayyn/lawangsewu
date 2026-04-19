# Laporan Insiden 404 Route Baru pada Runtime Web

**Tanggal insiden:** 18 April 2026  
**Sistem terdampak:** Lawangsewu production  
**Dampak utama:** Endpoint baru seperti `/tailscale` dan `/admin/system-monitor` mengembalikan 404 walau route sudah terdaftar.  

## Ringkasan Eksekutif

Insiden terjadi karena proses web runtime (Apache + PHP-FPM) memegang state route lama melalui OPCache, sementara perubahan route terbaru sudah masuk di source code dan terlihat dari `php artisan route:list`.

Dengan kata lain, sisi aplikasi sudah benar, tetapi petugas yang melayani request masih membaca "peta jalan lama".

## Gejala

- `php artisan route:list` menampilkan route baru dengan benar.
- Endpoint lama tetap dapat diakses normal.
- Endpoint baru menghasilkan 404 dari browser.
- Setelah refresh runtime PHP-FPM, endpoint baru kembali normal (302/200).

## Akar Masalah

- Runtime web menggunakan PHP-FPM (`sapi: fpm-fcgi`).
- OPCache aktif dengan validasi timestamp nonaktif (`opcache.validate_timestamps=0`).
- Akibat konfigurasi tersebut, perubahan route/controller tidak otomatis dimuat oleh worker web sampai runtime di-reload atau OPCache di-reset.

## Kronologi Ringkas

1. Route baru ditambahkan dan tervalidasi di CLI.
2. Browser masih menerima 404 pada endpoint baru.
3. Audit menegaskan mismatch antara runtime CLI dan runtime web.
4. Dilakukan refresh runtime (flush OPCache + reload service).
5. Endpoint terdampak berubah dari 404 menjadi 302/200 sesuai autentikasi.

## Dampak Bisnis

- Fitur baru dianggap gagal oleh pengguna walau sebenarnya sudah ter-deploy.
- Waktu troubleshooting bertambah karena gejala tampak seperti bug route.
- Potensi penundaan verifikasi UAT dan serah-terima sprint.

## Mitigasi yang Diterapkan

- Menjalankan prosedur refresh runtime setelah deploy:
  - `php artisan optimize:clear`
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
  - `systemctl reload php8.3-fpm`
  - `systemctl reload apache2`
- Menambahkan script operasional:
  - `ops/scripts/refresh_web_runtime.sh`
  - `ops/scripts/deploy_full_refresh.sh`

## Pencegahan Permanen

1. Terapkan runbook refresh runtime pada setiap deploy yang menyentuh route/controller/middleware.
2. Wajibkan verifikasi endpoint kritikal sesudah deploy.
3. Pertimbangkan kebijakan OPCache yang lebih adaptif jika kebutuhan hot-reload lebih tinggi.

## Status Pasca Perbaikan

- `/tailscale` kembali memberikan 302/200 sesuai status login.
- `/admin/system-monitor` kembali memberikan 302/200 sesuai status login.
- Tidak ditemukan error baru yang terkait route resolution.
