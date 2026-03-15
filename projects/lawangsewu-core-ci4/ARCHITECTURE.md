# Architecture Staging Notes

## Posisi Lawangsewu Core

Lawangsewu Core adalah shell platform.

Fungsi utamanya:

- menjadi gerbang utama
- mengelola identitas dan sesi portal
- menampilkan katalog aplikasi dan widget
- meluncurkan sibling apps melalui launcher/SSO

## Relasi dengan Sistem Lain

### WA Caraka

- tetap sibling app
- dibuka dari portal melalui launcher/SSO
- tidak dipindah ke dalam Core

### Laravel App Baru

- dibangun sebagai sibling app
- dipakai untuk domain bisnis yang kompleks
- menerima trust login dari portal Lawangsewu

### Widget dan Halaman Publik

- dipindah bertahap ke Core
- route lama tetap dipertahankan melalui compatibility layer

## Modul CI4 Core

- `PublicSite`
- `Portal`
- `AuthGateway`
- `AppRegistry`
- `WidgetRegistry`
- `DocsRegistry`
- `PublicApiLite`
- `MonitoringLite`
- `AuditAccess`

## Gelombang Migrasi

### Gelombang 1

- `AuthGateway`
- `Portal`
- `PublicSite`

### Gelombang 2

- `WidgetRegistry`
- `DocsRegistry`
- halaman publik ringan

### Gelombang 3

- `PublicApiLite`
- `MonitoringLite`
- compatibility routes penuh

### Gelombang 4

- onboarding Laravel sibling app
- integrasi permission dan launcher lanjut

## Status Implementasi Staging Saat Ini

- boundary modul utama sudah dipisah ke service/provider/controller layer
- registry `AppRegistry`, `WidgetRegistry`, dan `DocsRegistry` sudah aktif di staging
- `Portal` sudah mulai menjadi komposer yang membaca registry lain
- view adapter staging sudah tersedia di `app/Views`
- shell runtime minimum sudah tersedia lewat `public/index.php` dan `StagingKernel`
- compatibility handling dasar sudah aktif untuk prefix `/lawangsewu` dan alias staging tertentu
- compatibility contract sekarang juga bisa mengembalikan redirect kanonik untuk alias lama yang targetnya belum dirender lokal
- audit akses minimum sudah mulai ditulis ke `writable/logs` staging
- layout bersama sekarang sudah dipakai lintas halaman staging utama
- audit sekarang juga mengelompokkan request ke keluarga route seperti `landing`, `docs`, `widget-directory`, `public-widget`, dan `portal`
- audit launcher staging sekarang juga membedakan `app-launch` dan `portal-launch`
- readiness route family sekarang bisa dihitung dari gabungan kontrak route dan audit event launcher

Artinya, langkah berikutnya bukan lagi menyusun boundary dari nol, tetapi mengeraskan shell ini menuju struktur CI4 penuh dan mengganti adapter lama secara bertahap.