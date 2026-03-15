# Route Family Cutover Matrix

Dokumen ini menerjemahkan hasil `ROUTE-FAMILY-READINESS.php` menjadi keputusan operasional untuk shadow, hold, dan cutover bertahap.

Prinsipnya sederhana:

- route yang sudah lolos kontrak dan audit dasar boleh masuk fase shadow
- route yang masih tergantung sesi aktif atau integrasi luar tidak boleh dipotong langsung
- alias lama tetap diperlakukan sebagai compatibility contract, bukan target cutover utama

## Status Verified Saat Ini

Status di bawah ini mengikuti hasil report readiness staging terbaru.

| Route family | Status | Mode berikut | Bukti saat ini | Blocker utama | Keputusan |
|---|---|---|---|---|---|
| `landing` | `ready-for-shadow` | shadow | `GET /` render lokal, audit `request` ada | belum ada | aman untuk shadow lebih dulu |
| `docs` | `ready-for-shadow` | shadow | `GET /walkthrough` render lokal, audit `request` ada | belum ada | aman untuk shadow lebih dulu |
| `widget-directory` | `ready-for-shadow` | shadow | `GET /daftar-widget` dan `/widget-links` lolos | belum ada | aman untuk shadow lebih dulu |
| `public-widget` | `ready-for-shadow` | shadow | 17 route widget kanonik lolos render lokal | belum ada | kandidat utama gelombang publik |
| `compat-alias` | `ready-for-compat` | compat-only | 13 alias lama redirect ke target kanonik | jangan dijadikan target final | pertahankan sebagai jembatan |
| `app-registry` | `ready-for-shadow` | shadow | halaman registry dan launcher audit `launch-app` sudah ada | belum ada smoke sesi admin spesifik | aman untuk shadow terkontrol |
| `portal` | `full-cutover-review` | review-before-cutover | redirect anon, auth sintetis, dan login live gateway sudah benar | masih perlu keputusan cutover produksi yang terkontrol | boleh masuk review cutover penuh |
| `portal-launch` | `full-cutover-review` | review-before-cutover | auth sintetis tervalidasi dan login live gateway portal sudah lolos | launcher live per target masih perlu spot-check sesuai app tujuan | boleh masuk review cutover penuh |

## Route Family di Luar Scope Cutover Saat Ini

Family berikut tetap diperlakukan sebagai kontrak yang harus dipertahankan, tetapi belum menjadi target cutover staging saat ini:

- `gateway` auth routes
- `publicApiRoutes`
- `integrationContracts`

Alasannya:

- masih bergantung pada runtime aktif
- menyentuh sesi, SSO, atau integrasi sibling app
- membutuhkan smoke dan rollback yang lebih ketat dibanding halaman publik

## Urutan Cutover yang Direkomendasikan

1. `landing`
2. `docs`
3. `widget-directory`
4. `public-widget`
5. `app-registry`
6. `compat-alias` tetap hidup sebagai redirect setelah family kanonik stabil
7. `portal` dan `portal-launch` masuk `full-cutover-review` setelah smoke login aktif nyata lolos
8. cutover penuh tetap dilakukan terpisah dan terkontrol, bukan di langkah yang sama dengan verifikasi login

## Guardrail per Fase

### Fase Shadow Publik

- jangan ubah URL final yang sudah ada di `.htaccess`
- bandingkan body render, status, dan audit staging terhadap runtime aktif
- pertahankan alias lama ke target kanonik

### Fase Compatibility

- alias lama tetap 302 ke route kanonik
- jangan render konten final dari alias lama
- semua dokumentasi publik harus menyebut route kanonik, bukan alias

### Fase Portal Auth

- wajib ada smoke login aktif
- wajib ada bukti audit `launch-portal-item`
- wajib uji return path `/portal`
- wajib uji logout tanpa redirect berantai rapuh
- smoke sintetis boleh dipakai untuk menaikkan readiness staging, tetapi bukan pengganti validasi login live

## Definisi Siap Cutover

Sebuah family baru boleh naik dari `ready-for-shadow` ke kandidat cutover jika syarat berikut terpenuhi:

1. semua route dalam family lolos status yang diharapkan
2. audit event dasar muncul konsisten di staging
3. tidak ada compatibility alias yang putus
4. smoke route tetap hijau setelah perubahan

Untuk family auth-protected, tambah syarat berikut:

1. login aktif tervalidasi
2. launcher event saat sesi aktif tercatat
3. return path dan logout tervalidasi

## Keputusan Praktis Saat Ini

- family publik sekarang sudah cukup matang untuk shadow bertahap
- alias lama sudah cukup matang untuk dipertahankan sebagai lapisan kompatibilitas
- portal sekarang sudah masuk kandidat review cutover penuh karena login live gateway sudah lolos sekali