# CI4 Core Module Map Lawangsewu

Dokumen ini menerjemahkan Lawangsewu ke modul-modul CI4 Core yang konkret.

Tujuannya agar migrasi tidak dilakukan per file acak, tetapi per domain fungsi.

## Status Saat Ini

Module map ini sudah valid sebagai peta kerja, tetapi belum berarti semua modul di bawah sudah selesai diimplementasikan pada runtime produksi.

Status yang paling tepat saat ini:

- modul-modul inti CI4 Core sudah terdefinisi jelas
- staging shell untuk beberapa modul utama sudah ada
- sebagian modul sudah punya provider, controller, route contract, dan smoke test di staging
- implementasi produksi penuh dan cutover route masih bertahap

## Prinsip Dasar

CI4 Core adalah rumah utama. Rumah ini tidak menelan semua gedung lain, tetapi mengurus:

- pintu masuk
- identitas
- navigasi
- katalog
- integrasi
- dokumentasi

## Modul CI4 Core yang Direkomendasikan

### 1. `PublicSite`

Menangani:

- landing page `/`
- halaman publik ringan yang benar-benar milik rumah Lawangsewu
- elemen branding dan front-facing page

Sumber migrasi awal:

- `widgets/views/php/public/landing.php`
- sebagian halaman public ringan di `widgets/views/php/public/`
- sebagian halaman statis di `widgets/views/html/public/`

### 2. `Portal`

Menangani:

- `/portal`
- dashboard utama
- daftar aplikasi
- daftar shortcut internal
- card launcher dan favorit

Sumber migrasi awal:

- `widgets/views/php/public/index.php`

### 3. `AuthGateway`

Menangani:

- login/logout portal
- session portal
- return URL normalization
- signed launcher/SSO helpers

Sumber migrasi awal:

- `gateway/bootstrap.php`
- `gateway/login.php`
- `gateway/logout.php`

Catatan:

- ini modul sensitif
- pindahkan per fungsi, bukan langsung rewrite semua sekaligus

### 4. `AppRegistry`

Menangani:

- daftar sibling apps
- metadata aplikasi
- launcher ke WA Caraka dan aplikasi lain
- status akses user per aplikasi

Sumber migrasi awal:

- link dan metadata yang sekarang tersebar di portal dan gateway

### 5. `WidgetRegistry`

Menangani:

- katalog widget
- metadata widget
- canonical URL widget
- compatibility alias untuk widget lama

Sumber migrasi awal:

- `.htaccess` route inventory
- `Walkthrough-DBPrakom/widget-links.html`

### 6. `DocsRegistry`

Menangani:

- dokumentasi publik
- dokumentasi internal yang dibuka dari portal
- walkthrough registry

Sumber migrasi awal:

- `Walkthrough-DBPrakom/`
- route `/walkthrough`

### 7. `PublicApiLite`

Menangani:

- endpoint kecil yang benar-benar milik portal/widget
- bridge ringan yang tidak butuh dipisah jadi service besar

Sumber migrasi awal:

- `widgets/views/php/api/`

Catatan:

- jangan campur endpoint service berat atau runtime-specific ke sini tanpa alasan kuat

### 8. `MonitoringLite`

Menangani:

- health summary ringan
- status layanan yang ditampilkan di portal
- monitor publik ringan

Sumber migrasi awal:

- monitor WA publik
- bridge status ringan
- beberapa card status portal

### 9. `AuditAccess`

Menangani:

- jejak login portal
- jejak launcher aplikasi
- jejak akses modul penting

Catatan:

- tidak perlu menyalin seluruh audit dari sibling app
- cukup audit akses di level rumah utama

## Modul yang Bukan Bagian CI4 Core

### Tetap Sibling App

- WA Caraka runtime
- WA Caraka admin dashboard CI4
- gateway-node service
- service AI yang kelak mungkin dipisah

### Tetap Utility Terpisah Dulu

- CLI/system script di `widgets/views/php/system/`
- folder arsip dan release
- utilitas yang terhubung ke cron dan shell

## Struktur Modul Praktis

Struktur logis yang direkomendasikan di CI4 Core:

- `Modules/PublicSite`
- `Modules/Portal`
- `Modules/AuthGateway`
- `Modules/AppRegistry`
- `Modules/WidgetRegistry`
- `Modules/DocsRegistry`
- `Modules/PublicApiLite`
- `Modules/MonitoringLite`
- `Modules/AuditAccess`

## Mapping Cepat dari Sistem Lama

| Sistem Lama | Modul CI4 Core |
|---|---|
| `gateway/login.php` | `AuthGateway` |
| `gateway/logout.php` | `AuthGateway` |
| `gateway/bootstrap.php` | `AuthGateway` + shared services |
| `widgets/views/php/public/landing.php` | `PublicSite` |
| `widgets/views/php/public/index.php` | `Portal` |
| `Walkthrough-DBPrakom/` | `DocsRegistry` |
| `daftar-widget` | `WidgetRegistry` |
| `widgets/views/php/api/*` | `PublicApiLite` |
| route alias di `.htaccess` | route compatibility layer |

## Aturan Praktik per Modul

### Jika modul hanya bertugas menampilkan dan meluncurkan

Taruh di CI4 Core.

### Jika modul mulai punya workflow bisnis panjang

Jangan paksa masuk CI4 Core. Pertimbangkan jadi Laravel sibling app.

### Jika modul mengelola runtime khusus

Biarkan tetap sibling service/app.

## Rule Final

Migrasi dilakukan per modul, bukan per file acak.

Urutan paling aman:

1. `AuthGateway`
2. `Portal`
3. `PublicSite`
4. `WidgetRegistry`
5. `DocsRegistry`
6. `PublicApiLite`
7. `MonitoringLite`
8. `AuditAccess`

## Catatan Implementasi Praktis

Saat membaca module map ini, bedakan tiga level berikut:

- `sudah dipetakan`: modulnya sudah jelas dan boundary-nya disepakati
- `sudah distaging`: sudah ada shell atau implementasi awal yang bisa diuji
- `sudah produksi`: sudah menjadi jalur runtime utama

Untuk Lawangsewu saat ini, banyak modul sudah sampai level `dipetakan` dan sebagian sudah `distaging`, tetapi belum semuanya `produksi`.