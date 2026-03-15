# Modules Staging Area

Folder ini disiapkan sebagai tempat modul-modul CI4 Core nantinya.

Modul target:

- PublicSite
- Portal
- AuthGateway
- AppRegistry
- WidgetRegistry
- DocsRegistry
- PublicApiLite
- MonitoringLite
- AuditAccess

Status saat ini:

- AuthGateway sudah punya bridge, contract, controller stub, dan smoke script
- Portal sudah punya registry, presenter, controller stub, dan smoke script
- PublicSite sudah punya service, controller stub, dan smoke script
- WidgetRegistry sudah dimulai sebagai registry provider + controller stub
- DocsRegistry sudah dimulai sebagai registry provider + controller stub
- AppRegistry sudah dimulai sebagai registry provider + controller stub
- AuditAccess sudah dimulai sebagai logger JSONL untuk shell staging
- view adapter staging dan render smoke sudah tersedia di `app/Views` dan `app/SMOKE-VIEWS.php`
- layout bersama staging sudah tersedia di `app/Views/layouts/base.php`
- AppRegistry dan Portal sekarang juga punya launch route staging terverifikasi untuk audit interaksi launcher
- Portal sekarang juga punya smoke authenticated sintetis untuk membuktikan alur sesi aktif tanpa menyentuh akun produksi

Untuk keamanan, route produksi belum dipindahkan ke sini.
Yang sedang dibangun adalah boundary, route contract, service/provider/controller layer, dan urutan migrasi yang bisa diuji bertahap.