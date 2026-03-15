# Widgets Views Structure

Struktur internal `widgets/views` dirapikan berdasarkan tipe dan fungsi agar lebih profesional dan mudah dipelihara.

## Folder

- `html/public/`
  - Seluruh halaman HTML publik yang dirender langsung oleh rewrite Apache.
- `php/public/`
  - Seluruh halaman PHP publik yang menjadi target route final widget.
- `php/api/`
  - Endpoint API internal/publik untuk data widget.
- `php/system/`
  - Helper, controller, dan script CLI seperti generator slide.
- `data/`
  - File data pendukung seperti JSON source.
- `config/`
  - Contoh file environment dan konfigurasi lokal.
- `assets/`
  - Asset statis bersama.

## Catatan operasional

- URL publik tetap memakai route root seperti `/berita-pengadilan`, `/pengumuman-peradilan`, `/radius-ghaib`, dan lain-lain.
- Jangan akses file lewat URL `widgets/views/*` karena tetap diblokir oleh Apache.
- Script generator slide dijalankan lewat:
  - `php widgets/views/php/system/generate_slide.php`
- Direktori link widget final dan snippet iframe tersedia di:
  - `/daftar-widget`
