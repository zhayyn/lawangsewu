# Monitor Jaringan

## Ringkasan

Monitor Jaringan adalah halaman superadmin untuk melihat kondisi trafik server Lawangsewu. Fitur ini bekerja seperti panel meter listrik: angka diambil dari sistem operasi, diringkas oleh service Laravel, lalu ditampilkan di halaman Inertia agar operator bisa melihat beban jaringan tanpa masuk terminal.

## Akses

- Halaman: `/admin/network-monitor`
- API refresh: `/admin/network-monitor/api`
- Route name: `admin.network-monitor.index` dan `admin.network-monitor.api`
- Middleware: `auth`, `verified`, `active`, `superadmin`, dan `permission:admin.network-monitor`
- Permission key: `admin.network-monitor`
- Menu: grup `Monitoring` dengan label `Monitor Jaringan`

Superadmin selalu lolos permission. Role admin biasa tetap dibatasi oleh middleware `superadmin`, sehingga fitur ini tidak terbuka untuk admin non-superadmin.

## Komponen Kode

- Controller: `app/Http/Controllers/Admin/NetworkMonitorController.php`
- Service: `app/Services/NetworkMonitorService.php`
- Page Vue: `resources/js/Pages/Admin/NetworkMonitor.vue`
- Permission config: `config/features.php`
- Navigasi: `app/Support/LawangsewuPortal.php`
- Test: `tests/Feature/Admin/NetworkMonitorTest.php`

## Data Yang Ditampilkan

- Interface jaringan aktif dari `/proc/net/dev`
- Kecepatan download/upload berdasarkan selisih byte antar snapshot
- Riwayat bandwidth singkat, disimpan di cache
- Ringkasan koneksi TCP dari `/proc/net/tcp` dan `/proc/net/tcp6`
- Status stream CCTV dari endpoint go2rtc atau MediaMTX, dengan fallback ke tabel `cctv_cameras`

## Tampilan Frontend

Halaman menggunakan gaya light-first: latar dan kartu utama dibuat terang, semi-transparan, dan bersih. Warna kuat hanya dipakai pada teks, border, icon, badge, dan progress bar agar layar tidak terasa terlalu gelap saat mode normal.

Saat layout Lawangsewu masuk dark mode, class `dark:` akan mengubah permukaan menjadi gelap dengan warna aksen yang sama. Jadi background gelap hanya aktif ketika user memilih dark mode.

## Konfigurasi CCTV Stream Health

Service akan mencoba endpoint berikut secara berurutan:

1. `CCTV_SERVER_URL`, jika diisi
2. `CCTV_PROXY_URL`, default `http://localhost:1984`

Contoh `.env`:

```env
CCTV_SERVER_URL=http://192.168.88.200:1984
CCTV_PROXY_URL=http://localhost:1984
```

Jika tidak ada proxy CCTV yang menjawab, halaman tetap aman dibuka dan menampilkan kamera aktif dari database sebagai status `configured`.

## Catatan Perbaikan Error

Error klik menu sebelumnya berasal dari pemanggilan helper navigasi yang tidak ada, yaitu `LawangsewuPortal::getNavigation()`. Controller sekarang memakai helper yang benar, `LawangsewuPortal::navGroups()`. API refresh juga dikirim dari backend lewat prop `endpoints.api`, sehingga frontend tidak tergantung URL hardcoded.

## Verifikasi

Perintah yang dipakai untuk memastikan fitur:

```bash
php artisan route:list --name=network-monitor
php artisan ziggy:generate resources/js/ziggy.js
npm run build
php artisan test tests/Feature/Admin/NetworkMonitorTest.php --compact
php artisan optimize:clear
```

## Troubleshooting

- Jika halaman 403, pastikan user benar-benar superadmin (`is_superadmin = 1` atau email sama dengan `SUPERADMIN_EMAIL`).
- Jika data stream CCTV kosong, cek `CCTV_SERVER_URL` atau `CCTV_PROXY_URL`.
- Jika frontend masih membuka asset lama, jalankan `npm run build` lalu clear cache browser/CDN.
- Jika route tidak dikenali di JavaScript, jalankan ulang `php artisan ziggy:generate resources/js/ziggy.js`.
- Jika console browser menampilkan WebSocket Reverb gagal di URL `/app/lawangsewu`, ikuti `docs/reverb-cloudflare.md`.
