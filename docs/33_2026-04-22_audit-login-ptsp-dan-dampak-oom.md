# Audit Pasca-OOM: Login PTSP Gagal dan Error Turunan di Lawangsewu

Tanggal: 2026-04-22  
Sistem: Lawangsewu / WA Caraka  
Status: Penyebab teridentifikasi, perbaikan inti sudah diterapkan

## Ringkasan Singkat

Setelah insiden OOM, muncul kesan bahwa banyak bagian Lawangsewu ikut rusak. Setelah diaudit, masalahnya ternyata bukan satu bug tunggal, tetapi gabungan beberapa lapisan:

- akun operator PTSP yang diharapkan ternyata belum ada di database aktif
- form login hanya menerima email, bukan alias pendek seperti `ptsp1`
- database aktif berisi user demo yang tidak cocok untuk operasional nyata
- permission runtime Laravel sempat tidak konsisten antara proses web dan proses console
- ada sisa error historis dari maintenance mode, session file, scheduler, dan reverb

Analogi sederhananya:

- server kemarin seperti gedung yang sempat kebakaran ruang mesin
- setelah api padam, ternyata ada pintu yang macet, daftar petugas yang hilang, dan sebagian ruang masih memakai label dummy

Jadi yang terlihat "banyak error" itu sebagian adalah efek berantai dari insiden utama, bukan karena semua modul rusak total.

## Temuan Utama Audit Login PTSP

### 1. Akun `ptsp1`, `ptsp2`, `ptsp3` memang tidak ada di database aktif

Audit ke tabel `users` menunjukkan akun berikut sebelumnya tidak ada:

- `ptsp1@pa-semarang.go.id`
- `ptsp2@pa-semarang.go.id`
- `ptsp3@pa-semarang.go.id`

Artinya login gagal bukan semata karena password salah, tetapi karena user record-nya memang belum ada atau belum pernah dibuat di database yang sedang dipakai aplikasi.

### 2. Form login hanya menerima email

Sebelum diperbaiki, backend login di [app/Http/Requests/Auth/LoginRequest.php](/var/www/lawangsewu/app/Http/Requests/Auth/LoginRequest.php:1) mewajibkan field `email` berformat email penuh.

Akibatnya:

- `ptsp1` ditolak
- `ptsp2` ditolak
- `ptsp3` ditolak

padahal di lapangan operator cenderung mengetik identitas pendek seperti nama loket atau kode operator.

### 3. Alias user operator yang terseed tidak identik dengan kebiasaan operator

Seeder operator memakai alias:

- `ptsp-1`
- `ptsp-2`
- `ptsp-3`

Sedangkan operator cenderung mengetik:

- `ptsp1`
- `ptsp2`
- `ptsp3`

Ini membuat login tetap rawan gagal meskipun akun sudah ada, jika backend hanya mencari alias persis.

## Temuan Audit Data Produksi

### 4. Database aktif berisi user demo/factory

Audit `users` menunjukkan adanya akun contoh seperti:

- `stroman.quinten@example.net`
- `connelly.madyson@example.com`
- `mherzog@example.org`

Ini adalah sinyal kuat bahwa database aktif pernah terisi data dummy/demo, bukan hanya data operasional nyata.

Analogi sederhananya:

- buku absensi petugas di meja depan bercampur dengan daftar figuran latihan

Saat operator sungguhan datang, daftar yang dipakai ternyata bukan daftar kerja final.

### 5. `DatabaseSeeder` masih memanggil `ChatDemoSeeder`

Di [database/seeders/DatabaseSeeder.php](/var/www/lawangsewu/database/seeders/DatabaseSeeder.php:1), seeder umum sebelumnya masih memanggil `ChatDemoSeeder`.

Ini berbahaya bila seseorang menjalankan `php artisan db:seed` di environment produksi, karena:

- user demo bisa ikut masuk
- data contoh chat bisa ikut masuk
- operator nyata justru tidak otomatis tersusun sesuai kebutuhan operasional

Perbaikan yang sudah diterapkan:

- `ChatDemoSeeder` sekarang hanya dijalankan di environment `local` dan `testing`

## Temuan Audit Error Pasca-OOM

### 6. Error scheduler sempat membuat hampir semua command `artisan` ikut gagal

Sempat terjadi error:

```text
Non-static method Illuminate\Console\Scheduling\Schedule::command() cannot be called statically
```

Dampaknya:

- `queue:work` ikut gagal start
- `reverb:start` ikut gagal start
- `schedule:run` ikut jatuh

Ini membuat gejalanya terlihat seperti "Laravel rusak semua", padahal satu titik kerusakan di bootstrap console bisa menjalar ke banyak command.

### 7. Session file permission sempat membuat web hidup tetapi login gagal

Sempat terjadi error:

```text
file_put_contents(.../storage/framework/sessions/...): Failed to open stream: Permission denied
```

Dampaknya:

- halaman bisa terbuka
- tetapi session gagal disimpan
- login dan alur stateful lain menjadi tidak stabil

Analogi sederhananya:

- tamu bisa masuk lobby
- tetapi petugas resepsionis tidak bisa menulis kartu tamu

Jadi proses masuknya terlihat ada, tetapi administrasinya gagal.

### 8. Runtime cache permission masih berpotensi bentrok antara `dbprakom` dan `www-data`

Setelah perbaikan awal, audit masih menunjukkan sebagian direktori cache lama dibuat oleh `www-data` dengan mode yang tidak otomatis ramah untuk `dbprakom`.

Ini menjelaskan kenapa:

- web bisa berjalan
- tetapi command `artisan` tertentu masih bisa mengeluh permission

Perbaikan yang sudah diterapkan:

- script [ops/scripts/fix_laravel_runtime_permissions.sh](/var/www/lawangsewu/ops/scripts/fix_laravel_runtime_permissions.sh:1) diperbarui
- script sekarang tidak hanya `chown/chmod`, tetapi juga menambahkan default ACL untuk `dbprakom` dan `www-data`

## Tindakan yang Sudah Dilakukan

### Perbaikan login

Sudah diterapkan:

- login sekarang menerima `email atau alias`
- jika user mengetik `ptsp1`, sistem juga bisa mencocokkan local-part email `ptsp1@...`
- form login sekarang menjelaskan bahwa operator boleh memakai email lengkap atau alias

File terkait:

- [app/Http/Requests/Auth/LoginRequest.php](/var/www/lawangsewu/app/Http/Requests/Auth/LoginRequest.php:1)
- [resources/js/Pages/Auth/Login.vue](/var/www/lawangsewu/resources/js/Pages/Auth/Login.vue:1)
- [tests/Feature/Auth/AuthenticationTest.php](/var/www/lawangsewu/tests/Feature/Auth/AuthenticationTest.php:1)

### Pembuatan ulang akun operator PTSP

Seeder operator sudah dijalankan ke database aktif, sehingga akun berikut sekarang ada dan aktif:

- `ptsp1@pa-semarang.go.id`
- `ptsp2@pa-semarang.go.id`
- `ptsp3@pa-semarang.go.id`

Catatan operasional:

- password default dari seeder harus dianggap sementara dan wajib diganti

### Pagar seeder produksi

Perbaikan:

- `ChatDemoSeeder` tidak lagi ikut jalan pada `production`

Ini penting agar `db:seed` di produksi tidak lagi mencampur data demo ke database aktif.

## Kesimpulan Audit

Kalau disederhanakan:

- OOM kemarin adalah ledakan utama di ruang mesin
- setelah itu, kita menemukan daftar petugas belum lengkap
- pintu login terlalu kaku karena hanya mau menerima email
- lemari arsip runtime sempat tidak bisa ditulis
- dan di gudang data ternyata masih ada barang demo bercampur dengan barang operasional

Jadi benar bahwa "setelah OOM banyak yang error", tetapi setelah ditelusuri, error-error itu bisa dikelompokkan menjadi:

1. error infrastruktur runtime
2. error bootstrap console Laravel
3. data operator yang belum lengkap
4. konfigurasi login yang terlalu sempit
5. kontaminasi data demo di database aktif

## Rekomendasi Berikutnya

1. Uji login nyata dengan:
   - `ptsp1`
   - `ptsp2`
   - `ptsp3`
2. Ganti password default akun operator secepatnya.
3. Audit tabel `users` dan putuskan akun demo mana yang harus dibersihkan.
4. Jalankan ulang script permission runtime yang sudah diperbarui sebagai `root`.
5. Hindari menjalankan `db:seed` umum di produksi tanpa memastikan isi `DatabaseSeeder` aman.

## Dokumen Terkait

- [02_2026-04-22_lawangsewu-stabilisasi-console-oom-dan-reverb.md](/var/www/lawangsewu/docs/02_2026-04-22_lawangsewu-stabilisasi-console-oom-dan-reverb.md:1)
- [03_2026-04-22_penjelasan-reverb-dan-mode-aman-lawangsewu.md](/var/www/lawangsewu/docs/03_2026-04-22_penjelasan-reverb-dan-mode-aman-lawangsewu.md:1)
