# Lawangsewu Core CI4 Staging

Folder ini adalah staging area aman untuk membangun Lawangsewu Core berbasis CI4 tanpa mengganggu runtime aktif di root `/var/www/html/lawangsewu`.

Status saat ini:

- belum menjadi entrypoint produksi
- dipakai untuk membekukan boundary modul
- dipakai untuk menyiapkan kontrak route
- dipakai untuk migrasi bertahap per modul
- sudah punya shell runtime staging dengan `public/index.php`, route config, renderer, dan writable layout dasar

## Tujuan

Lawangsewu Core CI4 akan menjadi rumah utama untuk:

- landing page
- portal utama
- auth gateway
- app registry
- widget registry
- docs registry
- monitoring ringan
- audit akses portal

## Runtime Staging yang Sudah Ada

Struktur runtime minimum yang sekarang sudah tersedia:

- `app/Config/App.php`
- `app/Config/Paths.php`
- `app/Config/Routes.php`
- `app/Controllers/StagingKernel.php`
- `public/index.php`
- `writable/`
- `app/Views/layouts/base.php`

Smoke route yang sudah lolos:

- `GET /`
- `GET /walkthrough`
- `GET /daftar-widget`
- `GET /app-registry`
- `GET /portal` mengembalikan redirect ke login saat sesi belum aktif
- `php ROUTE-COVERAGE-REPORT.php` untuk menghitung coverage route in-scope terhadap kontrak `config/route-contracts.json`
- `php ROUTE-FAMILY-READINESS.php` untuk membaca readiness per family route sebelum shadow atau cutover bertahap
- `php AUTHENTICATED-PORTAL-SMOKE.php` untuk membuktikan alur portal dengan sesi sintetis staging tanpa memakai kredensial live
- `php PUBLIC-SHADOW-COMPARISON.php` untuk membandingkan status dan panjang respons route publik aktif vs shell staging
- `PUBLIC-SHADOW-ROLLOUT-PLAYBOOK.md` untuk urutan shadow publik, interpretasi verdict, dan guardrail rollback
- `WALKTHROUGH-SHADOW-CHANGELOG.md` untuk detail perubahan shadow terbatas pertama di route `/walkthrough`
- `ROUTE-FAMILY-CUTOVER-MATRIX.md` untuk keputusan urutan shadow, compat, hold, dan guardrail cutover

Lapisan kompatibilitas staging yang sudah aktif:

- prefix `/lawangsewu` dinormalisasi aman di shell staging
- alias lokal `/widget-links` diarahkan ke `/daftar-widget`
- alias kontrak lama seperti `/pengumuman-rss-widget` dan `/statistik-perkara` sekarang bisa mengembalikan redirect kanonik di staging
- audit request staging ditulis ke `writable/logs/audit-access.jsonl`
- audit launcher staging sekarang juga menangkap event `launch-app`, `launch-portal-item`, `launch-auth-required`, dan `launch-denied`

Perapian render yang sudah aktif:

- view utama memakai layout bersama di `app/Views/layouts/base.php`
- shell staging sekarang punya navigasi dan presentasi yang konsisten antar modul
- smoke render menunjukkan output HTML stabil untuk landing, docs, widget, app registry, dan portal template

Route publik wave berikut yang sekarang sudah bisa dirender lokal dari shell staging:

- `/pengumuman-peradilan`
- `/dashboard-perkara`
- `/monitor-persidangan`

Pengukuran readiness yang sekarang tersedia:

- coverage report memeriksa `coreRoutes`, `publicWidgetRoutes`, dan `legacyAliases`
- route auth, public API, dan integration contract tetap dicatat sebagai kontrak luar-scope staging saat ini
- smoke route juga memverifikasi launch flow staging untuk `app-registry` dan `portal`
- smoke authenticated portal sekarang membuktikan `portal` dan `portal-launch` dengan sesi sintetis staging
- family readiness report sekarang bisa membedakan bukti anon dan bukti auth sintetis untuk family portal
- `/walkthrough` sekarang sudah aktif sebagai shadow terbatas pertama dengan toggle flag yang bisa di-rollback cepat
- adapter staging `/daftar-widget` sudah dirapikan dan verdict comparison naik dari `hold` ke `review`

## Bukan Tugas Folder Ini

Folder ini tidak mengambil alih langsung:

- WA Caraka runtime
- WA Caraka admin dashboard
- service Node terpisah
- aplikasi Laravel sibling app yang akan dibangun kemudian

## Aturan Kerja

1. Semua pekerjaan di sini harus non-disruptive ke route aktif.
2. URL publik existing dianggap kontrak yang harus dijaga.
3. Perpindahan dilakukan per modul, bukan per file acak.
4. Semua fitur AI yang kelak tampil di portal wajib punya label capability.

## Dokumen Rujukan

- `/var/www/html/lawangsewu/MIGRATION-BLUEPRINT-LAWANGSEWU-CI4-LARAVEL.md`
- `/var/www/html/lawangsewu/MIGRATION-MATRIX-LAWANGSEWU.md`
- `/var/www/html/lawangsewu/ROUTE-INVENTORY-LAWANGSEWU.md`
- `/var/www/html/lawangsewu/CI4-CORE-MODULE-MAP-LAWANGSEWU.md`
- `/var/www/html/lawangsewu/AI-CAPABILITY-REGISTER-LAWANGSEWU.md`