# Laporan Insiden SSO Google

## Ringkasan Eksekutif

Pada tanggal **8 April 2026**, login SSO Google di `https://lawangsewu.pa-semarang.go.id` gagal berulang walaupun OAuth client Google sudah diisi dan akun `dbprakom@gmail.com` dipakai sebagai superadmin. Setelah audit bertahap, akar masalah utama ditemukan pada **layer Cloudflare/WAF** yang memblokir payload OAuth callback klasik sebelum request sempat mencapai Laravel. 

Insiden dinyatakan **selesai** pada **8 April 2026** setelah alur login dipindahkan ke skema **Google Identity Services + ID token verification** di backend, ditambah koreksi konfigurasi **Authorized JavaScript origins** di Google Cloud Console.

## Dampak

- Pengguna tidak dapat login menggunakan Google SSO.
- Login gagal sebelum aplikasi menerima callback otorisasi.
- Superadmin `dbprakom@gmail.com` tidak dapat masuk walaupun telah dikonfigurasi sebagai akun utama.

## Gejala Awal

- Browser menampilkan `403 Forbidden` saat callback Google menuju `/auth/google/callback`.
- Pada percobaan berikutnya, browser menampilkan `Access blocked: Authorization Error` dengan pesan `no registered origin` dan `Error 401: invalid_client`.
- Aplikasi Laravel tidak selalu mencatat callback gagal di `laravel.log`, yang mengindikasikan request terhenti sebelum masuk ke origin.

## Kronologi Investigasi

### 1. Validasi konfigurasi dasar aplikasi

Pemeriksaan awal dilakukan pada:

- `APP_URL`
- `GOOGLE_REDIRECT_URI`
- `SUPERADMIN_EMAIL`
- route `/auth/google`
- route `/auth/google/callback`
- konfigurasi session

Hasil:

- `GOOGLE_REDIRECT_URI` sudah diarahkan ke `https://lawangsewu.pa-semarang.go.id/auth/google/callback`
- `SUPERADMIN_EMAIL` sudah diset ke `dbprakom@gmail.com`
- route callback aktif

Kesimpulan sementara:

- konfigurasi dasar aplikasi bukan satu-satunya penyebab

### 2. Audit log Laravel dan Apache

Pola yang ditemukan:

- redirect ke Google berhasil
- sebagian callback tidak tercatat di `Apache access log`
- callback gagal tertentu juga tidak masuk ke `laravel.log`

Interpretasi:

- request gagal diblokir di depan origin
- masalah tidak murni berasal dari controller Laravel

### 3. Verifikasi redirect URI di Google Cloud Console

Redirect URI produksi dipastikan sesuai:

- `https://lawangsewu.pa-semarang.go.id/auth/google/callback`

Hasil:

- redirect URI memang perlu sinkron
- tetapi setelah sinkron, login masih gagal

Kesimpulan:

- ada faktor tambahan selain redirect URI

### 4. Audit logika backend Google di aplikasi

Beberapa masalah aplikasi juga ditemukan dan dirapikan:

- flow lama terlalu bergantung pada `stateless()`
- callback perlu fallback saat `InvalidStateException`
- validasi akses Google sebelumnya terlalu bias ke akun `@gmail.com`

Perbaikan dilakukan agar sisi aplikasi sehat lebih dulu, namun setelah itu login tetap belum stabil karena masih ada `403` dari edge.

### 5. Eksperimen callback `GET`

Callback Google klasik dengan parameter seperti:

- `scope=email+profile+https://www.googleapis.com/auth/userinfo.profile+...`

menghasilkan:

- `403 Forbidden`

Temuan penting:

- saat request memuat pola parameter scope Google klasik, hasilnya diblokir

### 6. Eksperimen callback `POST` dengan `response_mode=form_post`

Untuk menghindari query string panjang, callback diubah agar Google mengirim `POST` ke callback.

Hasil:

- masih `403 Forbidden`

Kesimpulan:

- masalah bukan sekadar method `GET` versus `POST`
- filter keamanan membaca isi payload, bukan hanya URL

### 7. Reproduksi manual dari server

Request callback dibuat ulang secara manual menggunakan `curl` dengan payload mirip Google.

Hasil:

- payload sederhana bisa masuk ke Laravel
- payload yang berisi pola scope Google klasik diblokir `403`
- bahkan ketika payload serupa dikirim ke path lain seperti `/login`, hasilnya tetap `403`

Kesimpulan final investigasi:

- **Cloudflare/WAF memblokir request berdasarkan isi payload OAuth klasik**
- request tersebut tidak sampai ke aplikasi

### 8. Identifikasi solusi teknis yang benar

Karena OAuth callback klasik terus dipukul oleh WAF, dibuat jalur baru:

- browser login memakai **Google Identity Services**
- Google mengirim **ID token**
- aplikasi menerima token di endpoint baru
- backend memverifikasi token Google secara langsung

Keuntungan:

- format request berubah total
- jalur ini tidak terkena pola blok yang sama

### 9. Temuan tambahan: `no registered origin`

Setelah jalur baru dipasang, Google sempat menolak popup login dengan error:

- `no registered origin`
- `Error 401: invalid_client`

Penyebab:

- domain produksi awalnya dimasukkan ke **Authorized redirect URIs**
- padahal untuk popup Google Identity Services, domain harus ada di **Authorized JavaScript origins**

Setelah diperbaiki menjadi:

- **Authorized JavaScript origins**: `https://lawangsewu.pa-semarang.go.id`
- **Authorized redirect URIs**: `https://lawangsewu.pa-semarang.go.id/auth/google/callback`

login berhasil.

## Akar Masalah

### Akar masalah utama

**Cloudflare/WAF memblokir payload OAuth callback klasik Google sebelum request masuk ke Laravel.**

### Faktor pendukung

- konfigurasi Google Console sempat belum lengkap untuk flow popup modern
- flow backend lama masih menyisakan pola autentikasi yang kurang tahan terhadap kondisi produksi
- validasi akses Google perlu dirapikan agar tidak membatasi akun Google Workspace/non-Gmail

## Perubahan yang Dilakukan

### Backend

Perubahan di aplikasi:

- refactor flow Google login di [GoogleController.php](/var/www/lawangsewu/app/Http/Controllers/Auth/GoogleController.php)
- tambah verifier token di [GoogleIdTokenVerifier.php](/var/www/lawangsewu/app/Services/GoogleIdTokenVerifier.php)
- tambah endpoint `POST /auth/google/credential` di [auth.php](/var/www/lawangsewu/routes/auth.php)
- expose `googleClientId` ke halaman login di [AuthenticatedSessionController.php](/var/www/lawangsewu/app/Http/Controllers/Auth/AuthenticatedSessionController.php)

### Frontend

Perubahan UI/login:

- halaman login memakai tombol Google modern berbasis Google Identity Services di [Login.vue](/var/www/lawangsewu/resources/js/Pages/Auth/Login.vue)
- setelah jalur stabil, tampilan tombol dikembalikan ke gaya elegan sambil tetap memakai flow baru

### Dokumentasi

- panduan setup diperbarui di [09_2026-04-08_tasks-for-user.md](/var/www/lawangsewu/docs/09_2026-04-08_tasks-for-user.md)

## Pengujian yang Dilakukan

Pengujian investigatif:

- cek log Laravel
- cek Apache access log
- cek Apache error log
- uji callback `GET`
- uji callback `POST`
- uji payload callback tiruan menggunakan `curl`
- uji path alternatif untuk membuktikan blok terjadi di edge

Pengujian aplikasi:

- syntax check PHP
- `php artisan route:list --path=auth/google`
- `php artisan test --filter=GoogleOAuthTest`
- `npm run build`
- `php artisan optimize:clear`

Hasil penting:

- endpoint baru `POST /auth/google/credential` menghasilkan respons Laravel `422 JSON` saat diberi token palsu
- ini membuktikan jalur baru lolos dari `403 Forbidden` Cloudflare lama

## Status Akhir

Insiden dinyatakan **resolved**.

Kondisi akhir:

- login Google untuk `dbprakom@gmail.com` berhasil
- tombol login modern aktif di production
- tampilan tombol sudah dikembalikan ke desain elegan
- fallback redirect klasik tetap tersedia jika suatu saat dibutuhkan untuk debugging

## Pencegahan Ke Depan

### Teknis

- pertahankan flow Google Identity Services sebagai jalur utama
- simpan konfigurasi Google Console yang benar di dokumentasi internal
- pantau jika Cloudflare/WAF policy berubah dan mulai memukul request baru

### Operasional

- setiap perubahan OAuth Google harus diverifikasi pada dua bagian berbeda:
  - Authorized JavaScript origins
  - Authorized redirect URIs
- setiap error `403` pada flow SSO harus dibandingkan dengan log origin untuk memastikan apakah request benar-benar mencapai aplikasi

## Analogi Sederhana

Insiden ini mirip tamu resmi dari Google yang datang ke gedung membawa surat izin masuk. Namun satpam di gerbang depan salah curiga pada bentuk surat lama, lalu menolak tamu sebelum tamu sempat sampai ke resepsionis. Solusinya bukan sekadar mengganti nama tamu, tetapi mengganti format surat masuk yang dipakai, lalu memastikan daftar tamu di meja depan memang mencantumkan gedung tujuan yang benar.
