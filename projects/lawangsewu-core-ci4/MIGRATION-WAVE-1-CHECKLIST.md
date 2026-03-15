# Migration Wave 1 Checklist

Gelombang 1 menyentuh tiga modul inti rumah utama Lawangsewu:

- AuthGateway
- Portal
- PublicSite

Tujuannya bukan mengganti seluruh sistem, tetapi menyiapkan tulang punggung rumah utama Lawangsewu dengan boundary yang sudah bisa diuji di staging.

## Sasaran Gelombang 1

### AuthGateway

- petakan semua helper dari `gateway/bootstrap.php`
- pisahkan fungsi session, login, logout, return-path, launcher URL, dan auth user state
- hindari ketergantungan logout ke aplikasi sibling
- staging bridge sudah tersedia dan lolos smoke test

### Portal

- ekstrak komposisi dashboard utama dari `widgets/views/php/public/index.php`
- bekukan daftar aplikasi dan kategori menjadi registry data
- pertahankan perilaku redirect ke login jika sesi tidak aktif
- staging registry, presenter, dan controller stub sudah tersedia

### PublicSite

- ekstrak landing page dari `widgets/views/php/public/landing.php`
- pertahankan inline login panel behavior
- pertahankan branding dan visual layer sebagai front-facing shell
- staging landing service dan controller stub sudah tersedia

## Checklist Teknis

### Sebelum Pindah Kode

- route contract gelombang 1 dibekukan
- source file asal dipetakan
- dependency helper antar file dicatat
- sibling app contract tidak diubah

### Saat Pindah Kode

- pindah per fungsi, bukan per halaman utuh sekaligus
- jangan putuskan route publik existing
- jangan menghapus file lama sebelum compatibility layer siap

### Setelah Pindah Kode

- validasi login portal
- validasi logout portal
- validasi return path `/portal`
- validasi launcher ke WA Caraka
- validasi landing tetap bisa membuka panel login
- validasi smoke script untuk semua boundary staging

## Source Map Gelombang 1

| Modul | Source aktif sekarang |
|---|---|
| AuthGateway | `gateway/bootstrap.php`, `gateway/login.php`, `gateway/logout.php` |
| Portal | `widgets/views/php/public/index.php` |
| PublicSite | `widgets/views/php/public/landing.php` |

## Status Staging Saat Ini

- AuthGateway: interface, legacy bridge, controller stub, smoke test
- Portal: registry provider, presenter/view-model builder, controller stub, smoke test
- PublicSite: landing service, controller stub, smoke test
- WidgetRegistry: sudah dimulai sebagai gelombang berikutnya karena boundary wave 1 sudah cukup stabil di staging

## Rule Final

Jika gelombang 1 belum stabil di runtime nyata, jangan lanjut ke pemindahan route produksi atau endpoint API.