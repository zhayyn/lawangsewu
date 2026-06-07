# Integrasi CCTV Relay Lawangsewu

## Ringkasan

Halaman CCTV Lawangsewu sekarang memakai relay MediaMTX sebagai sumber utama dan ACO Badilag sebagai cadangan. Polanya sederhana:

1. Grid preview memuat stream `SD` yang ringan.
2. Saat kamera dibuka penuh, sistem berpindah ke stream `HD`.
3. Jika relay utama gagal dimuat, halaman otomatis mencoba fallback ACO.

## Sumber Stream

- Relay utama browser: `CCTV_RELAY_HLS_BASE_URL`, default `/cctv`
- Relay internal MediaMTX HLS: `http://192.168.88.200:8888`
- Preview grid: endpoint `*-sd`
- Detail / fullscreen: endpoint `*-hd`
- Backup: `fallback_src`, atau `iframe_src` lama dari ACO Badilag

Contoh:

```env
CCTV_RELAY_HLS_BASE_URL=/cctv
```

Catatan penting: frontend tidak lagi memuat `http://192.168.88.200:8889/...` secara langsung. URL lama dari database tetap dinormalisasi oleh `LawangsewuPortal::transformCamera()` menjadi `/cctv/{stream}/index.m3u8`, sehingga browser tidak memunculkan Mixed Content saat halaman dibuka melalui HTTPS.

## Proxy HTTPS HLS

Server Lawangsewu saat ini dilayani oleh Apache untuk HTTPS. Nginx tidak aktif di mesin ini, sehingga proxy HLS dipasang di `public/.htaccess` karena vhost Apache mengizinkan `AllowOverride All`.

Endpoint publik HLS:

```text
https://lawangsewu.pa-semarang.go.id/cctv/{nama-stream}/index.m3u8
```

Endpoint internal MediaMTX:

```text
http://192.168.88.200:8888/{nama-stream}/index.m3u8
```

Rule `.htaccess` menambahkan query `cookieCheck=1` saat meneruskan request ke MediaMTX. Ini diperlukan agar MediaMTX tidak membalas 302 ke path internal tanpa prefix `/cctv/`.

File uji publik:

```text
https://lawangsewu.pa-semarang.go.id/test-cctv.html
```

File uji tersebut memakai `hls.js`, memuat `kepaniteraan-sd` untuk preview, lalu otomatis mengganti source ke `kepaniteraan-hd` saat fullscreen dan kembali ke SD saat keluar fullscreen.

## Struktur Database

Kolom baru di `cctv_cameras`:

- `primary_sd_src`: stream ringan untuk grid banyak kamera.
- `primary_hd_src`: stream HD untuk detail atau fullscreen.
- `fallback_src`: backup, umumnya link ACO Badilag.
- `stream_provider`: penanda sumber, misalnya `mediamtx-relay`.

Kolom lama `iframe_src` tetap dipertahankan agar data lama dan admin panel tetap kompatibel.

## Alur Frontend

1. Grid memuat `previewSrc`, yaitu stream SD HLS `/cctv/{nama-sd}/index.m3u8`.
2. Saat kamera diklik, modal expanded memuat `fullSrc`, yaitu stream HD HLS `/cctv/{nama-hd}/index.m3u8`.
3. Komponen `CctvStreamFrame` memakai `hls.js` dari bundle lokal Vite, bukan CDN, agar lebih aman terhadap CSP.
4. Jika primary tidak selesai load sampai timeout, source pindah ke `fallbackSrc`.
5. Tombol retry mengembalikan percobaan ke primary.

## Pemetaan Kamera

Seeder `CctvCameraSeeder` memasang 24 kamera dari NVR #1 dan NVR #2:

- NVR #1: 16 kamera area pelayanan, persidangan, publik, dan internal.
- NVR #2: 8 kamera ruang pimpinan dan internal.

Seeder memakai key `cam-01` sampai `cam-24` supaya data lama tidak dobel saat di-seed ulang.

Perintah update data:

```bash
php artisan migrate
php artisan db:seed --class=CctvCameraSeeder
npm run build
php artisan optimize:clear
```

## Catatan Operasional

- MediaMTX `sourceOnDemand: true` membuat NVR hanya ditarik saat player meminta stream.
- Grid Lawangsewu tetap memakai stream SD agar halaman ringan saat banyak kamera tampil sekaligus.
- Jika relay internal tidak terlihat dari jaringan pengguna, fallback ACO tetap tersedia, tetapi kualitas dan stabilitas mengikuti layanan ACO.
- Jika akses root tersedia, konfigurasi idealnya dipindahkan dari `.htaccess` ke vhost HTTPS Apache `/etc/apache2/sites-available/lawangsewu-ssl.conf` memakai `ProxyPass /cctv/ http://192.168.88.200:8888/`.
