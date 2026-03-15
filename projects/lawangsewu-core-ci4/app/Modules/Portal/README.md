# Portal Module Staging

Modul ini akan memegang dashboard utama Lawangsewu setelah login.

## Scope

- dashboard `/portal`
- app cards
- widget cards
- quick filter
- favorites
- launcher ke sibling app

## Source Lama

- `/var/www/html/lawangsewu/widgets/views/php/public/index.php`

## Boundary Penting

- ini bukan tempat menaruh logika bisnis besar
- ini hanya rumah depan dan launcher
- data aplikasi sebaiknya diubah menjadi registry, bukan hardcoded terlalu lama

## Target Refactor

1. ekstrak section registry
2. ekstrak card metadata
3. ekstrak favorite behavior
4. ekstrak auth gate ke AuthGateway module

## Status Staging Saat Ini

- section internal sudah dikomposisikan dari `AppRegistry`
- referensi dokumen sudah mulai dikomposisikan dari `DocsRegistry`
- public views dan monitoring sudah mulai dikomposisikan dari `WidgetRegistry`
- controller dan presenter staging sudah aktif, tetapi view runtime produksi belum diganti