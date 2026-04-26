# Laporan Stabilisasi Lawangsewu: Console OOM, Queue, dan Reverb

Tanggal: 2026-04-22  
Sistem: Lawangsewu / WA Caraka  
Status: Stabil Sementara, Mode Aman Aktif

## Ringkasan

Server sempat berada dalam kondisi berbahaya setelah proses `php artisan`, `queue:work`, dan `reverb:start` memicu konsumsi resource yang tidak sehat. Gejala yang terlihat:

- `queue` dan `reverb` gagal start atau restart loop.
- `php artisan` command tertentu memakan memori berlebihan.
- `reverb` sempat berjalan tetapi tumbuh sampai beberapa gigabyte RAM.
- server berisiko kembali hang jika service dipaksa hidup dalam mode lama.

Mode aman sekarang yang aktif:

- `lawangsewu-reverb.service` dimatikan.
- `lawangsewu-queue.service` dimatikan.
- `BROADCAST_CONNECTION=log`
- `CACHE_STORE=file`
- `SESSION_DRIVER=file`
- queue diproses dengan cron pendek memakai `flock` dan `--stop-when-empty`
- scheduler diproses dengan cron per menit
- aplikasi web kembali hidup setelah maintenance mode dilepas dan permission session file diperbaiki

## Kondisi Akhir Saat Stabil

Verifikasi akhir menunjukkan:

- `lawangsewu-reverb.service`: `inactive (dead)`
- `lawangsewu-queue.service`: `inactive (dead)`
- cron aktif:
  - `schedule:run`
  - `queue-safe` dengan `flock`
- Lawangsewu web merespons normal lagi dengan redirect `302` ke `/login`
- RAM longgar kembali:
  - `Mem total 31 Gi`
  - `used sekitar 4 Gi`
  - `available sekitar 27 Gi`

Gejala ini menandakan sumber tekanan utama berhasil diputus tanpa menyentuh service lain seperti `wa-lawangsewu`, `wa-bridge`, VM, atau service proyek lain.

## Analogi Sederhana

Masalah ini seperti gedung yang punya tiga mesin:

- jam alarm gedung: `schedule:run`
- conveyor barang: `queue:work`
- radio komunikasi realtime: `reverb:start`

Yang terjadi kemarin:

- radio komunikasi menyala tetapi bocor bensin sangat besar
- conveyor kadang ikut macet karena ruang mesin Laravel console sendiri sedang tidak sehat
- jam alarm tetap berbunyi, tetapi kalau mesin ruang belakang rusak, setiap alarm memicu stress baru

Tindakan saat ini adalah mematikan mesin radio yang paling boros, menghentikan conveyor permanen, lalu menggantinya dengan petugas keliling yang datang sebentar tiap menit, bekerja singkat, lalu pulang.

## Apa yang Dicoba Kemarin

### 1. Hardening systemd untuk queue dan reverb

Dilakukan perubahan pada unit:

- `ops/systemd/lawangsewu-queue.service`
- `ops/systemd/lawangsewu-reverb.service`

Tujuan:

- membatasi memory dan restart loop
- membuat `queue` dan `reverb` lebih disiplin
- mencegah proses liar bertahan terlalu lama

Alasan:

- pada awal investigasi dugaan utama adalah overlap process manager dan daemon jangka panjang yang tidak dibatasi.

### 2. Perbaikan scheduler

File:

- `routes/console.php`

Dilakukan:

- menambahkan `withoutOverlapping()`
- menambahkan `runInBackground()`
- memberi nama event scheduler agar lock lebih jelas

Alasan:

- agar tugas scheduled tidak bertumpuk seperti kejadian OOM sebelumnya.

### 3. Aktivasi queue dan reverb lewat systemd

Percobaan ini menunjukkan:

- kedua service sempat gagal start karena bug scheduler
- setelah bug scheduler diperbaiki, `reverb` bisa hidup tetapi memori naik drastis

Temuan penting:

- `reverb` sempat mencapai sekitar `3.7 GB`, lalu saat diamati lebih jauh mendekati `7 GB RSS`
- ini bukan perilaku normal untuk server realtime yang idle ringan

### 4. Uji memory limit CLI

Temuan:

- PHP CLI server diset `memory_limit = -1`
- ini berbahaya karena saat proses bocor, dia bebas memakan RAM server

Alasan pemeriksaan:

- untuk membedakan apakah masalahnya memang load tinggi wajar atau proses runaway.

### 5. Uji command artisan dengan driver runtime berbeda

Temuan bertahap:

- `php artisan` tertentu tetap bisa OOM bahkan saat `reverb` mati
- `php artisan schedule:list` normal saat dijalankan dengan:
  - `CACHE_STORE=file`
  - `SESSION_DRIVER=file`
- `php artisan queue:work ... --stop-when-empty` juga kembali normal dalam mode ini

Kesimpulan:

- selain kebocoran `reverb`, mode console Laravel juga tidak sehat saat memakai driver `database` untuk `cache/session`
- problem utamanya bukan syntax scheduler lagi, melainkan kombinasi boot console + driver database pada environment ini

### 6. Pemulihan akses web Lawangsewu

Setelah mode console stabil, aplikasi web ternyata masih belum bisa diakses normal. Tahap pemulihan web menemukan dua hal tambahan:

- aplikasi masih berada dalam `maintenance mode`
- setelah mode tersebut dilepas, aplikasi berubah menjadi `500 Internal Server Error`

Temuan detail:

- file `storage/framework/down` masih tertinggal
- setelah `php artisan up`, maintenance file hilang
- error berikutnya berasal dari session file:

```text
file_put_contents(.../storage/framework/sessions/...): Failed to open stream: Permission denied
```

Kesimpulan:

- maintenance mode yang tertinggal menyebabkan `503 Service Unavailable`
- setelah itu, permission pada `storage/framework/sessions` membuat Laravel tidak bisa menyimpan session saat `SESSION_DRIVER=file`
- ini menyebabkan web `500` walaupun aplikasi inti sebenarnya sudah hidup

## Akar Masalah yang Ditemukan

### Akar Masalah 1: Reverb mengalami konsumsi memori abnormal

Gejala:

- `lawangsewu-reverb.service` aktif tetapi memakan RAM dalam ukuran gigabyte
- untuk kondisi server ini, itu terlalu besar

Dampak:

- jika dibiarkan, bisa mendorong server kembali ke tekanan memori tinggi
- risiko login lambat, kill process, swap naik, sampai hang total

### Akar Masalah 2: PHP CLI tidak punya pagar memori

Temuan:

- `memory_limit = -1`

Dampak:

- jika satu proses console Laravel mulai bocor, dia tidak berhenti sendiri
- server harus mengandalkan OOM killer kernel, yang terlalu terlambat

### Akar Masalah 3: Driver database untuk cache/session membuat mode console berat

Temuan:

- saat `CACHE_STORE=database` dan `SESSION_DRIVER=database`, command console tertentu bisa meledak memori
- saat dipindah ke `file`, command console kembali stabil

Kemungkinan penyebab:

- lock scheduler berbasis database
- interaksi cache/session/queue dengan event listener atau query instrumentation
- environment console tidak cocok untuk mode ini pada beban sekarang

### Akar Masalah 4: Ada noise observability/listener yang membuat query path lebih berat

Ditemukan komponen seperti:

- `app/Listeners/DatabaseQueryListener.php`
- `app/Services/DatabaseOptimizationService.php`

Komponen ini belum ditetapkan sebagai akar tunggal, tetapi berpotensi memperberat mode console karena setiap query ikut diproses lagi untuk metrics, tracing, logging, dan optimasi.

### Akar Masalah 5: Residu maintenance mode dan permission runtime web

Temuan:

- ada file maintenance yang tertinggal
- permission direktori runtime `storage/framework/sessions` tidak langsung sesuai untuk user web server saat driver session dipindah ke `file`

Dampak:

- domain `lawangsewu.pa-semarang.go.id` sempat menjawab `503`
- setelah maintenance lepas, aplikasi menjawab `500` karena gagal menulis session

Penyelesaian:

- maintenance mode dilepas
- permission runtime session/cache diperbaiki agar web kembali bisa menulis file runtime

## Kenapa Mode Aman Dipilih

Keputusan mode aman diambil karena target utama bukan “semua fitur realtime hidup”, tetapi:

- server tidak hang lagi
- root login tidak tersumbat lagi
- queue tetap bisa diproses
- scheduler tetap berjalan
- sistem lain tidak ikut terganggu

Karena itu dipilih pendekatan:

- matikan `reverb`
- jangan pakai daemon `queue:work` panjang dulu
- gunakan cron queue pendek dengan `flock`
- gunakan file-based cache/session untuk mode runtime ini

Ini adalah kompromi yang sengaja konservatif.

## Tindakan yang Dilakukan Sekarang

### Perubahan konfigurasi

File `.env`:

- `APP_DEBUG=false`
- `BROADCAST_CONNECTION=log`
- `CACHE_STORE=file`
- `SESSION_DRIVER=file`

### Cron aktif

Scheduler:

```cron
* * * * * cd /var/www/lawangsewu && /usr/bin/php artisan schedule:run >> /var/www/lawangsewu/storage/logs/scheduler.log 2>&1
```

Queue safe:

```cron
* * * * * cd /var/www/lawangsewu && /usr/bin/flock -n /tmp/lawangsewu-queue-safe.lock /usr/bin/php -d memory_limit=256M artisan queue:work database --queue=default --stop-when-empty --sleep=1 --tries=3 --backoff=5 --timeout=120 --max-jobs=50 --max-time=50 --memory=192 --no-interaction >> /var/www/lawangsewu/storage/logs/queue-safe.log 2>&1
```

### Service dimatikan

- `lawangsewu-reverb.service`
- `lawangsewu-queue.service`

### Pemulihan akses web

- `php artisan up` dijalankan untuk menghapus maintenance mode
- permission direktori runtime Laravel diperbaiki agar web server bisa menulis session file
- setelah itu Lawangsewu kembali merespons dan mengarahkan user ke halaman login

## Kenapa Ini Aman Sementara

Model ini aman karena:

- tidak ada daemon panjang yang diam-diam tumbuh memory
- setiap job queue diproses sebentar lalu proses keluar
- jika tidak ada job, worker selesai cepat
- scheduler tetap hidup tetapi ringan
- realtime tidak memaksa server menanggung proses `reverb`

## Apa yang Harus Dilakukan Kedepannya

### Jangka Pendek

1. Biarkan mode aman ini berjalan minimal 24-48 jam.
2. Pantau:
   - `free -h`
   - `storage/logs/scheduler.log`
   - `storage/logs/queue-safe.log`
3. Pastikan tidak ada proses `artisan reverb:start` aktif lagi.
4. Rapikan permission runtime Laravel agar tidak bergantung pada mode darurat.

### Jangka Menengah

1. Audit khusus mengapa `reverb` leak di server ini.
2. Audit listener/query instrumentation yang aktif di mode console.
3. Pertimbangkan memisahkan runtime realtime ke host/VM/container terpisah.
4. Pertimbangkan cache backend yang lebih cocok untuk realtime seperti Redis, tetapi hanya setelah sumber leak jelas.

### Jangka Panjang

1. Pisahkan concern:
   - web request
   - queue worker
   - realtime websocket
2. Tambahkan alert:
   - RAM
   - swap
   - proses `artisan`
   - kesehatan cron
3. Tambahkan health-check operasional untuk memastikan tidak ada service manager ganda.

## Keputusan Operasional Saat Ini

Keputusan resmi sementara:

- **WA Caraka tetap berjalan tanpa realtime Reverb**
- **Queue berjalan dalam mode short-lived cron worker**
- **Scheduler berjalan normal**
- **Web Lawangsewu berjalan kembali**
- **Server diprioritaskan untuk stabilitas, bukan fitur realtime penuh**

## File yang Terkait dalam Penanganan

- `routes/console.php`
- `.env`
- `ops/systemd/lawangsewu-queue.service`
- `ops/systemd/lawangsewu-reverb.service`
- `ops/scripts/install_scheduler_cron.sh`
- `ops/scripts/install_queue_cron_safe.sh`
- `ops/scripts/audit_lawangsewu_processes.sh`
- `docs/oom-prevention-runbook.md`

## Penutup

Masalah ini bukan satu bug tunggal. Ini gabungan beberapa lapisan:

- reverb leak / runaway memory
- PHP CLI tanpa batas memory
- mode console Laravel tidak sehat saat memakai cache/session database pada environment ini
- service panjang terlalu berisiko untuk kondisi server sekarang

Karena itu solusi yang diterapkan juga berlapis. Fokus utama bukan memaksakan semua fitur hidup, tetapi memastikan server Lawangsewu tidak hang lagi dan tidak mengganggu sistem lain.

## Dokumen Pendamping

Untuk penjelasan non-teknis dengan analogi sederhana mengenai fungsi Reverb, alasan dinonaktifkan sementara, dampaknya, dan alternatifnya, lihat:

- [03_2026-04-22_penjelasan-reverb-dan-mode-aman-lawangsewu.md](/var/www/lawangsewu/docs/03_2026-04-22_penjelasan-reverb-dan-mode-aman-lawangsewu.md:1)
