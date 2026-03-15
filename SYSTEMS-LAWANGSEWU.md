# Systems Lawangsewu

Dokumen ini menjadi peta ringkas sistem yang hidup di dalam root `/var/www/html/lawangsewu`.

## Batas aman

- Perubahan harus dibatasi di dalam root `lawangsewu`.
- Jangan memindahkan atau mengubah aplikasi di luar root ini.
- Untuk service aktif, utamakan perapihan dokumentasi, indeks struktur, dan naming yang tidak mengubah entrypoint runtime.

## Sistem utama

### Widget publik Lawangsewu
- Lokasi implementasi: `widgets/views/`
- Direktori widget publik: `/daftar-widget`
- Dokumentasi publik: `/walkthrough`
- Catatan: route publik tetap di root domain dan implementasi internal diblokir dari akses langsung.

### Gateway portal / SSO
- Lokasi: `gateway/`
- Fungsi: login portal, SSO mapping, dan routing akses admin.
- Catatan: perubahan di sini harus hati-hati karena berdampak ke login lintas layanan.

### Gateway Node
- Lokasi: `gateway-node/`
- Fungsi: service Node pendukung gateway jika dipakai.

### WA Caraka Runtime
- Lokasi: `wa-caraka/`
- Fungsi: runtime WhatsApp API terisolasi.
- Entry runtime: `wa-caraka/server.mjs`
- Dokumentasi utama: `wa-caraka/README.md`

### WA Caraka Admin Wrapper
- Lokasi: `wa-caraka-admin/`
- Fungsi: webroot tipis untuk mem-publish dashboard CI4 admin tanpa menaruh seluruh app di document root.
- Entry web: `wa-caraka-admin/index.php`

### WA Caraka Dashboard CI4
- Lokasi: `wa-caraka/dashboard-ci4-admin/`
- Fungsi: panel admin/operator untuk runtime WA Caraka.
- Catatan: app asli ada di subfolder ini, sedangkan `wa-caraka-admin/` hanya wrapper publik.

### Arsip dan release
- Lokasi: `archive/`, `releases/`
- Fungsi: snapshot/arsip kerja. Hindari perubahan tanpa kebutuhan yang jelas.

### Proyek referensi dan integrasi
- Lokasi: `projects/`, `pasarjohar/`, `Api-Caraka/`
- Fungsi: proyek pendukung, legacy, atau referensi integrasi. Perlakukan sebagai area terpisah dari widget publik aktif.

## Prioritas perapihan profesional

1. Rapikan dokumentasi dan indeks struktur terlebih dahulu.
2. Pisahkan folder berdasarkan fungsi sebelum memindahkan file aktif.
3. Setelah setiap perubahan struktur, verifikasi route/entrypoint penting.
4. Hindari rename folder aplikasi aktif kecuali benar-benar dibutuhkan dan sudah ditracing semua referensinya.
