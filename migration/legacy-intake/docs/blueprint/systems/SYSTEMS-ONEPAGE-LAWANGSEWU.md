# Systems One Page Lawangsewu

Ringkasan ini dibuat agar pembaca bisa memahami peta komponen Lawangsewu tanpa harus membuka dokumen sistem yang lebih panjang.

## Gambaran Singkat

- Lawangsewu adalah rumah utama dengan posisi arsitektur sebagai `CI4 Core`
- WA Caraka tetap sibling app terpisah
- Laravel dipakai untuk lane aplikasi bisnis yang lebih besar
- archive dan release diperlakukan sebagai area pasif, bukan target perubahan sembarangan

## Komponen Utama

### 1. Rumah utama Lawangsewu

- lokasi utama: root repo ini
- fungsi: landing, portal, gateway auth, launcher, registry, dokumentasi, dan widget publik

### 2. Gateway portal

- lokasi: `gateway/`
- fungsi: login, logout, mapping SSO, dan launcher lintas aplikasi
- catatan: perubahan di sini sensitif karena berdampak ke akses pengguna

### 3. Widget dan endpoint publik

- lokasi implementasi: `widgets/views/`
- fungsi: halaman publik, API ringan, utility internal, data, dan config widget
- catatan: route publik tetap dipertahankan di root domain, sedangkan implementasi internal tidak boleh dibuka langsung

### 4. WA Caraka sibling app

- runtime: `wa-caraka/`
- wrapper webroot: `wa-caraka-admin/`
- dashboard CI4 admin: `wa-caraka/dashboard-ci4-admin/`
- fungsi: runtime WhatsApp API dan konsol operator/admin

### 5. Laravel sibling app

- lokasi: `projects/lawangsewu-business-laravel/`
- fungsi saat ini: starter lane bisnis dengan domain awal `helpdesk/ticketing internal`
- catatan: ini bukan pengganti portal utama

### 6. Area pasif dan referensi

- `archive/`, `releases/`, `projects/`, `pasarjohar/`, `Api-Caraka/`
- perlakukan sebagai area arsip, referensi, atau integrasi terpisah kecuali ada keputusan eksplisit untuk mengubahnya

## Boundary yang Harus Dijaga

- jangan campur runtime WA Caraka ke dalam CI4 Core
- jangan perlakukan Laravel sebagai pengganti portal utama
- jangan ubah route publik tanpa mengecek route inventory dan compatibility alias
- jangan normalisasi archive pasif tanpa keputusan eksplisit

## Jika Mau Baca Lanjutan

- inventaris aplikasi dan widget: `APPS-WIDGETS-INVENTORY-LAWANGSEWU.md`
- peta bahasa manajerial: `SYSTEM-MAP-MANAJERIAL-LAWANGSEWU.md`
- dashboard status eksekutif: `EXECUTIVE-COMPONENT-STATUS-LAWANGSEWU.md`
- onboarding 10 menit: `ONBOARDING-10-MENIT-LAWANGSEWU.md`
- versi sistem yang lebih detail: `SYSTEMS-LAWANGSEWU.md`
- kontrak route: `ROUTE-INVENTORY-LAWANGSEWU.md`
- status migrasi satu halaman: `MIGRATION-ONEPAGE-LAWANGSEWU.md`
