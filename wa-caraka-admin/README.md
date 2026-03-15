# WA Caraka Admin Wrapper

Folder ini sengaja dibuat tipis agar document root publik tidak langsung menunjuk ke seluruh project CodeIgniter.

## Fungsi

- `index.php`
  - Bootstrap wrapper menuju app CI4 asli di `../wa-caraka/dashboard-ci4-admin/public` melalui bootstrapping project.
- `.htaccess`
  - Rewrite rule untuk semua route admin.

## Prinsip desain

- Folder ini bukan tempat kode bisnis utama.
- Kode aplikasi admin tetap berada di `wa-caraka/dashboard-ci4-admin/`.
- Wrapper ini harus tetap ringan, stabil, dan mudah diaudit.

## Catatan operasional

- URL publik admin: `https://lawangsewu.pa-semarang.go.id/wa-caraka-admin/login`
- Jika dashboard perlu maintenance, verifikasi juga script root-level:
  - `scripts/start-wa-caraka-admin.sh`
  - `scripts/status-wa-caraka-admin.sh`
  - `scripts/stop-wa-caraka-admin.sh`
