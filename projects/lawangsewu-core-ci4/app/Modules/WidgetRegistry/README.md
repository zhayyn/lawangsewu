# WidgetRegistry Module Staging

Modul ini akan memegang katalog widget publik Lawangsewu.

## Scope

- route `/daftar-widget`
- registry widget publik
- kategori widget dan dashboard ringan
- metadata embed/snippet dasar

## Source Lama

- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/widget-links.html`
- `/var/www/html/lawangsewu/ROUTE-INVENTORY-LAWANGSEWU.md`
- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/README.md`

## Boundary Penting

- katalog widget harus jadi registry, bukan HTML statis permanen
- route publik final tetap dipertahankan
- compatibility alias tetap ditangani di route layer, bukan dicampur ke katalog utama

## Target Refactor

1. bekukan daftar widget publik ke provider terstruktur
2. sediakan controller staging untuk route `/daftar-widget`
3. siapkan langkah penggantian sumber HTML statis ke registry runtime

## Status Staging Saat Ini

- `/daftar-widget` sudah dirender dari registry staging
- route widget kanonik bisa dirender lokal dari metadata registry untuk shell staging
- alias lama yang belum dirender langsung bisa tetap diarahkan ke slug kanonik