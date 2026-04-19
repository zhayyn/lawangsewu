# Runbook Refresh Runtime Deploy Lawangsewu

**Tanggal:** 18 April 2026  
**Tujuan:** Mencegah rute baru terlihat `404` setelah deploy walaupun sudah terdaftar di Laravel.  
**Cakupan:** Apache + PHP-FPM + Laravel cache pada server produksi Lawangsewu.

## Latar Belakang

Pada server ini, runtime web menggunakan PHP-FPM dengan OPCache aktif. Ketika perubahan route atau controller sudah masuk ke repository, proses web dapat tetap memegang state lama apabila OPCache belum di-refresh. Gejalanya biasanya sebagai berikut:

- `php artisan route:list` sudah menampilkan route baru.
- Endpoint lama tetap normal.
- Endpoint baru seperti `/tailscale` atau `/admin/system-monitor` masih `404` dari browser.

Artinya, masalah bukan pada definisi route, melainkan pada runtime web yang belum memuat perubahan terbaru.

## Analogi Singkat

Bayangkan Laravel adalah buku pedoman terbaru, tetapi petugas di loket masih memakai fotokopi pedoman lama. Selama fotokopi lama belum diganti, permintaan yang sebenarnya sudah sah tetap dianggap tidak ada. Refresh runtime berarti mengganti fotokopi lama itu dengan versi terbaru.

## Script Operasional

Script yang disediakan:

- `ops/scripts/refresh_web_runtime.sh`
- `ops/scripts/deploy_full_refresh.sh`

Fungsi script:

- membersihkan cache optimasi Laravel,
- membangun ulang cache produksi,
- me-reload PHP-FPM untuk flush OPCache,
- me-reload Apache,
- memverifikasi endpoint penting sesudah deploy.

`deploy_full_refresh.sh` menambahkan tahapan full deploy sebelum refresh runtime:

- `git fetch` + `git pull --ff-only`
- `composer install --no-dev`
- `npm ci` + `npm run build`
- `php artisan migrate --force`

## Cara Menjalankan

Jalankan sebagai root:

```bash
cd /var/www/lawangsewu
sudo bash ops/scripts/refresh_web_runtime.sh
```

Untuk full deploy end-to-end:

```bash
cd /var/www/lawangsewu
sudo bash ops/scripts/deploy_full_refresh.sh
```

Bila nama service PHP-FPM berbeda, gunakan env override:

```bash
cd /var/www/lawangsewu
sudo PHP_FPM_SERVICE=php8.2-fpm bash ops/scripts/refresh_web_runtime.sh
```

Untuk branch selain `main` pada full deploy:

```bash
cd /var/www/lawangsewu
sudo BRANCH=develop PHP_FPM_SERVICE=php8.2-fpm bash ops/scripts/deploy_full_refresh.sh
```

## Urutan Kerja yang Dilakukan Script

1. `php artisan optimize:clear`
2. `php artisan config:cache`
3. `php artisan route:cache`
4. `php artisan view:cache`
5. `systemctl reload php8.3-fpm`
6. `systemctl reload apache2` atau `apachectl -k graceful`
7. Verifikasi endpoint publik

## Expected Result

Setelah script dijalankan:

- `/health` harus `200`
- `/login` harus `200`
- `/tailscale` harus `302` atau `200`
- `/admin/system-monitor` harus `302` atau `200`

Keterangan:

- `302` berarti endpoint hidup tetapi meminta autentikasi.
- `200` berarti endpoint hidup dan sesi autentikasi sudah valid.
- `404` berarti runtime web masih belum membaca route terbaru.
- `503` berarti ada gangguan service origin, bukan sekadar cache route.

## Checklist Deploy Singkat

Gunakan checklist ini setiap ada perubahan route, middleware, controller, atau konfigurasi:

1. Pull perubahan terbaru ke server.
2. Jalankan migrasi bila ada.
3. Jalankan `sudo bash ops/scripts/refresh_web_runtime.sh`.
4. Uji `health`, `login`, dan minimal satu route baru.
5. Uji akses dari browser untuk user terkait.

## Kapan Runbook Ini Wajib Dipakai

Runbook ini wajib dipakai jika perubahan menyentuh salah satu area berikut:

- file route di `routes/`
- controller HTTP di `app/Http/Controllers/`
- middleware akses
- konfigurasi aplikasi
- halaman admin baru

## Catatan Teknis

Pada saat insiden 18 April 2026, penyebab utama adalah runtime web yang masih memegang state lama walaupun route cache CLI sudah benar. Oleh karena itu, `php artisan route:cache` saja tidak cukup; proses PHP-FPM harus ikut di-reload agar OPCache dibersihkan.
