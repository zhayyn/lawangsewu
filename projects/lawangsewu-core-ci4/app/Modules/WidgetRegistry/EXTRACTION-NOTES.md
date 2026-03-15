# WidgetRegistry Extraction Notes

Source utama katalog widget saat ini:

- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/widget-links.html`
- `/var/www/html/lawangsewu/ROUTE-INVENTORY-LAWANGSEWU.md`
- `/var/www/html/lawangsewu/Walkthrough-DBPrakom/README.md`

## Target Ekstraksi

### Pisahkan katalog dari HTML statis

- daftar widget publik
- kategori widget dan dashboard ringan
- snippet embed dasar
- summary count untuk halaman katalog

### Pertahankan perilaku yang sudah disukai

- route `/daftar-widget`
- canonical root routes
- halaman publik tetap mudah dibuka dan dibagikan
- route kanonik widget sebaiknya bisa dipetakan bertahap ke shell baru tanpa memutus slug lama

### Jangan campur ke modul lain

- daftar widget jangan disimpan permanen di portal
- compatibility alias tetap di route layer
- monitoring ringan tetap boleh muncul di katalog, tetapi registry tetap jadi sumber data tunggal

## Rule Final

WidgetRegistry menangani daftar widget publik dan metadata katalog, bukan logika auth portal atau route compatibility langsung.

## Status Staging Saat Ini

- metadata widget sudah hidup sebagai registry
- katalog widget sudah dirender dari shell staging
- beberapa route kanonik seperti `/pengumuman-peradilan`, `/dashboard-perkara`, dan `/monitor-persidangan` sudah bisa dirender lokal dari registry