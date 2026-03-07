# Server10 App (Arsip)

Folder ini dipertahankan sebagai backup awal, dan sekarang diarsipkan di `pasarjohar/server10/`.

Versi aktif aplikasi sekarang ada di folder `/var/www/html/tugumuda`.

Jika membuka `server10/index.php`, akan diarahkan ke `/tugumuda/`.

## Struktur arsip

- `index.php` : redirect ke aplikasi aktif
- `archive/config.php` : konfigurasi lama
- `archive/proxy.php` : endpoint proxy lama

## Catatan

- Tidak ada file yang dihapus dari arsip, hanya dipindah ke subfolder `archive`.
- Gunakan folder `tugumuda` untuk pengembangan dan operasional.
