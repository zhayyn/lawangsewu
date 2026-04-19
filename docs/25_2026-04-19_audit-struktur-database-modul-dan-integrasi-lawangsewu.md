# Audit Struktur Database, Modul, dan Integrasi Lawangsewu

Tanggal: 19 April 2026  
Status: Baseline audit teknis  
Ruang lingkup: database, tabel, modul aplikasi, integrasi internal-eksternal, dan penilaian kesiapan arsitektur

## 1. Ringkasan Eksekutif

Secara garis besar, Lawangsewu sudah berbentuk sebagai portal operasional internal yang menggabungkan beberapa domain kerja PA Semarang dalam satu aplikasi Laravel 11: dashboard internal, guestbook, antrian PTSP, antrian sidang, chat internal, WA Caraka, CCTV, SIPP Hub, Tailscale dashboard, serta lapisan widget publik dan API kompatibilitas legacy.

Struktur ini sudah cukup matang untuk operasi harian, tetapi belum sepenuhnya rapi sebagai arsitektur target akhir. Masih ada jejak migrasi dari sistem lama, beberapa tabel legacy yang tampak belum dibersihkan, sebagian frontend masih hybrid Blade plus Inertia/Vue, dan ada satu-dua mismatch antara dokumentasi dan wiring runtime aktual.

Kesimpulan singkat:

- Untuk kebutuhan operasional: sudah layak dan berjalan.
- Untuk konsistensi arsitektur jangka panjang: belum sepenuhnya selesai dirapikan.
- Untuk dokumentasi dan governance teknis: perlu satu sumber kebenaran yang lebih ketat.

## 2. Analogi Arsitektur

Analogi paling mudah: Lawangsewu saat ini seperti sebuah gedung kantor pusat yang sudah dihuni banyak unit kerja.

- Database `lawangsewu_core` adalah ruang arsip utama gedung.
- Database `sipp` adalah gedung tetangga yang dihubungkan koridor khusus untuk data perkara.
- Laravel adalah resepsionis, operator, dan sistem administrasi utama gedung.
- Reverb adalah interkom internal gedung.
- Queue worker adalah staf back office yang mengerjakan pekerjaan tertunda.
- `wa-bridge` adalah penerjemah antara sistem kantor dan dunia WhatsApp.
- `wa-lawangsewu` adalah sopir lapangan yang benar-benar masuk ke jaringan WhatsApp.
- Tailscale adalah jalan dinas privat menuju jaringan lokal kantor.

Artinya, sistem ini bukan lagi aplikasi tunggal sederhana. Ini sudah menjadi ekosistem kecil. Kelebihannya adalah banyak kebutuhan bisa dipusatkan. Konsekuensinya, disiplin struktur, dokumentasi, dan kontrak integrasi harus lebih ketat.

## 3. Database yang Digunakan

Berdasarkan `.env` aktif:

- Database utama aplikasi: `lawangsewu_core`
- Database pendukung integrasi perkara: `sipp`

Validasi live database melalui bootstrap Laravel menunjukkan:

- database aktif: `lawangsewu_core`
- jumlah tabel live saat audit: `42`

Konfigurasi runtime utama aplikasi:

- Session driver: `database`
- Queue connection: `database`
- Broadcast connection: `reverb`
- Reverb port: `8080`
- Reverb scheme: `http`

Implikasi teknis:

- aplikasi inti bergantung kuat pada database utama, bukan hanya untuk data bisnis tetapi juga untuk session, cache, dan queue
- sistem ini cocok untuk deployment tunggal atau kecil, tetapi akan butuh perapihan tambahan jika nanti dipisah ke beberapa node aplikasi

## 4. Struktur Tabel Lawangsewu

Berikut inventaris tabel yang dapat dibuktikan dari migration dan model.

### 4.1. Core Laravel dan fondasi aplikasi

- `users`: akun inti, role, status aktif, atribut Google, superadmin, alias, NIP, avatar.
- `password_reset_tokens`: token reset password.
- `sessions`: session login berbasis database.
- `migrations`: catatan migration yang sudah dijalankan.
- `cache`: cache aplikasi berbasis database.
- `cache_locks`: lock untuk cache/database concurrency.
- `jobs`: antrean pekerjaan asinkron.
- `job_batches`: batch job Laravel.
- `failed_jobs`: log job gagal.

Catatan:

- Ini fondasi yang sehat untuk aplikasi internal yang ingin audit trail dan reliabilitas sederhana tanpa Redis.

### 4.2. OAuth dan SSO

- `oauth_auth_codes`
- `oauth_access_tokens`
- `oauth_refresh_tokens`
- `oauth_clients`
- `oauth_device_codes`
- `google_access_allowlist`: allowlist email/domain/akun untuk Google access.

Peran kelompok ini:

- menjaga kanal autentikasi modern
- mendukung SSO atau integrasi akses berbasis Google

### 4.3. Permission, role, audit, dan keamanan akses

- `permissions`: daftar permission granular.
- `role_permissions`: pemetaan role ke permission.
- `feature_permissions`: override per fitur untuk role atau user tertentu.
- `login_histories`: jejak login.
- `permission_audit_logs`: jejak perubahan permission.

Catatan penting:

- Struktur ini sudah menunjukkan arah RBAC yang serius.
- Tetapi `feature_permissions.role_id` tidak menunjuk ke tabel `roles`, karena role utama aplikasi masih berbentuk string di `users.role` dan `role_permissions.role`.
- Ini berarti lapisan permission belum sepenuhnya konsisten secara model data.

### 4.4. Komunikasi internal dan monitoring

- `chat_aliases`: alias operator/chat identity.
- `chat_messages`: pesan chat internal yang aktif dipakai.
- `messages`: tabel pesan generik legacy atau eksperimen lama.
- `cctv_cameras`: daftar dan konfigurasi kamera CCTV.

Catatan penting:

- `chat_messages` jelas dipakai.
- `messages` tampak sebagai tabel general-purpose lama yang belum terlihat menjadi model utama dalam arsitektur sekarang. Ini kandidat audit dan cleanup.

### 4.5. Guestbook dan pendopo

- `guestbook_entries`: data buku tamu.
- `guestbook_settings`: konfigurasi modul buku tamu.

Status arsitektur:

- Modul Pendopo lama sudah dikonsolidasikan ke Buku Tamu.
- Route lama `satellite/pendopo` sekarang diarahkan ke guestbook.
- Ini arah yang benar, tetapi cleanup legacy-nya belum sepenuhnya selesai di seluruh kodebase.

### 4.6. Queue dan layanan operasional

- `ptsp_queue_tickets`: tiket antrian PTSP model lama.
- `sidang_queue_tickets`: tiket antrian sidang model lama.
- `service_groups`: grup layanan.
- `queue_services`: definisi layanan antrian.
- `service_counters`: loket/meja/counter layanan.
- `queue_tickets`: tiket antrian model inti baru.

Relasi utama:

- `service_groups` -> `queue_services` -> `service_counters` -> `queue_tickets`

Catatan penting:

- Ada dua generasi sistem antrian yang hidup berdampingan: model lama (`ptsp_queue_tickets`, `sidang_queue_tickets`) dan model inti baru (`queue_tickets` dan kawan-kawan).
- Secara bisnis ini mungkin masih aman, tetapi secara arsitektur ini tanda bahwa migrasi belum ditutup penuh.

### 4.7. SIPP Hub dan cache perkara

- `sipp_caches`: cache data dari sistem SIPP.

Peran:

- menjadi buffer antara Lawangsewu dan database `sipp`
- mengurangi ketergantungan penuh pada query langsung ke sistem perkara

Ini adalah pola yang benar dan aman untuk integrasi ke sistem eksternal.

### 4.8. WA Caraka

- `wa_caraka_logs`
- `wa_caraka_messages`
- `wa_caraka_conversations`
- `wa_caraka_handovers`
- `wa_caraka_menus`
- `wa_caraka_sessions`
- `wa_caraka_tickets`
- `wa_caraka_conversation_marks`
- `wa_session`

Peran kelompok ini:

- `wa_caraka_conversations`: kepala percakapan per nomor atau per chat.
- `wa_caraka_messages`: isi pesan, inbound/outbound, status kirim.
- `wa_caraka_handovers`: perpindahan kepemilikan percakapan antar operator.
- `wa_caraka_menus`: konfigurasi bot/menu otomatis.
- `wa_caraka_sessions`: state percakapan atau state bot.
- `wa_caraka_tickets`: tiket layanan yang lahir dari percakapan WA.
- `wa_caraka_conversation_marks`: penandaan khusus oleh operator.
- `wa_caraka_logs`: log lama atau pelengkap audit.
- `wa_session`: tabel sesi WhatsApp yang ada di database live, tetapi belum tampak sebagai domain model Laravel utama saat audit ini.

Catatan penting:

- Ini salah satu domain paling matang dan paling kompleks di aplikasi.
- Namun tetap ada indikasi overlap antara `wa_caraka_logs` dan `wa_caraka_messages`, sehingga perlu ditegaskan mana yang canonical.

## 5. Relasi Konseptual Antar Domain

Secara praktis, relasi antar bagian sistem bisa dibaca begini:

- `users` adalah pusat identitas operator dan admin.
- `users` terhubung ke login history, permission audit, chat alias, ownership WA Caraka, dan operasional queue.
- `guestbook_entries`, `queue_tickets`, `chat_messages`, dan `wa_caraka_messages` adalah domain-domain operasional yang dipakai harian.
- `sipp_caches` adalah jembatan data perkara.
- `cctv_cameras` dan Tailscale dashboard adalah domain infrastruktur/monitoring.

Secara desain, ini sudah mengarah ke pola portal operasional terpadu.

## 6. Seluruh Modul Aplikasi yang Terlihat Aktif

Berikut modul yang benar-benar terlihat dari route, controller, page, model, dan dokumen.

### 6.1. Modul publik dan kompatibilitas legacy

- health check `/health`
- daftar widget dan widget links
- pengumuman RSS dan pengumuman alias
- statistik perkara/ecourt/hakim
- info persidangan dan monitor persidangan
- bridge server10
- WA V2 compatibility endpoint

Modul ini terutama dipegang `WidgetCompatController`.

Kesimpulan:

- ini bukan modul bisnis utama internal, tetapi penting untuk backward compatibility dan publikasi data

### 6.2. Modul akses dan identitas

- login, register, reset password, verify email
- pending access
- Google auth
- profile edit

Kesimpulan:

- fondasi autentikasi sudah modern dan cukup lengkap

### 6.3. Modul portal internal Lawangsewu

- dashboard internal
- CCTV
- chat internal
- Pilar
- SIPP Hub

### 6.4. Modul operasional layanan

- Buku Tamu
- Kelola Buku Tamu
- Detail dan cetak kartu tamu
- Laporan buku tamu
- Antrian PTSP
- Antrian Sidang

### 6.5. Modul WA Caraka

- dashboard operator WA Caraka
- API proxy ke bridge/runtime
- webhook inbound WA Caraka
- admin WA Caraka

### 6.6. Modul admin dan superadmin

- user access management
- allowlist management
- system monitor
- laporan admin
- CCTV manager
- WA Caraka manager
- Tailscale dashboard

## 7. Komponen yang Terhubung di Luar Repo

Bukti runtime aktif di luar workspace ditemukan di `/home/dbprakom`.

### 7.1. Komponen yang benar-benar terhubung

#### A. `/home/dbprakom/wa-lawangsewu`

Peran:

- runtime Node.js WhatsApp utama
- berbasis `express`, `socket.io`, `puppeteer`, `whatsapp-web.js`
- dijalankan oleh unit `wa-lawangsewu.service`

Karakter:

- ini adalah mesin yang benar-benar berbicara ke WhatsApp
- dari dokumen hotfix, runtime ini berjalan pada port `8089`

#### B. `/home/dbprakom/wa-bridge`

Peran:

- adapter antara Laravel dan runtime WA lama/aktif
- berbasis `express`, `axios`, `socket.io-client`
- dijalankan oleh unit `wa-bridge.service`

Karakter:

- ini adalah penerjemah protokol antara Lawangsewu dan runtime WA
- dari dokumen hotfix, bridge ini berjalan pada port `8790`

#### C. Queue worker Laravel

Lokasi unit file:

- `ops/systemd/lawangsewu-queue.service`

Peran:

- menjalankan `php artisan queue:work`
- memproses job database queue seperti outbound WA dan proses asinkron lain

#### D. Reverb server Laravel

Lokasi unit file:

- `ops/systemd/lawangsewu-reverb.service`

Peran:

- websocket internal realtime
- berjalan di port `8080` via HTTP

#### E. Database `sipp`

Peran:

- database eksternal yang menjadi sumber statistik dan data perkara
- dihubungkan melalui konfigurasi `SIPP_DB_*`

#### F. Jaringan Tailscale

Peran:

- membuka jalur ke subnet lokal `192.168.88.0/24`
- dipakai untuk memonitor dan menjangkau server lokal PA Semarang

### 7.2. Komponen yang ada tetapi belum terbukti menjadi wiring aktif utama

Di `/home/dbprakom` juga ada:

- `DashboardController.php`
- `TailscaleService.php`
- `lawangsewu-security-docs/`

Berdasarkan bukti route, service file, package manifest, dan dokumentasi operasional, ketiganya belum terlihat sebagai runtime utama yang langsung tersambung ke alur aplikasi saat ini.

### 7.3. Catatan tentang `wa-runtime/` di repo

Folder `wa-runtime/` ada di workspace dan memakai `@whiskeysockets/baileys`, tetapi bukti operasional yang lebih kuat menunjukkan runtime aktif sekarang justru berada di `/home/dbprakom/wa-lawangsewu` dan `/home/dbprakom/wa-bridge`.

Artinya:

- `wa-runtime/` kemungkinan adalah eksperimen, kandidat migrasi, atau runtime alternatif
- source of truth operasional saat ini belum sepenuhnya dipusatkan di workspace utama

## 8. Alur Koneksi Sistem

Gambaran koneksi saat ini:

1. Browser operator masuk ke Laravel Lawangsewu.
2. Laravel membaca dan menulis ke `lawangsewu_core`.
3. Untuk data perkara/statistik tertentu, Laravel mengambil atau menyinkronkan dari `sipp`.
4. Untuk realtime UI, Laravel memakai Reverb pada `8080`.
5. Untuk pekerjaan berat dan asynchronous, Laravel menulis job ke tabel queue lalu diproses queue worker.
6. Untuk WhatsApp outbound/inbound:
   - Laravel -> `wa-bridge`
   - `wa-bridge` -> `wa-lawangsewu`
   - `wa-lawangsewu` -> WhatsApp network
   - inbound kembali masuk ke Laravel melalui webhook `POST /api/wa-caraka/webhook/inbound`
7. Untuk jaringan lokal kantor, Lawangsewu memakai Tailscale sebagai koridor ke subnet lokal.

## 9. Apa yang Sudah Oke

Berikut bagian yang menurut audit ini sudah berada di jalur yang benar.

### 9.1. Pemisahan domain bisnis sudah jelas

Guestbook, queue, WA Caraka, chat, CCTV, SIPP Hub, dan Tailscale sudah punya batas domain yang cukup jelas. Ini bagus.

### 9.2. RBAC dan audit sudah serius

Adanya `permissions`, `role_permissions`, `feature_permissions`, `login_histories`, dan `permission_audit_logs` menunjukkan sistem ini tidak lagi sekadar portal biasa.

### 9.3. WA Caraka sudah menjadi subsystem sungguhan

Bukan sekadar kirim pesan, tetapi sudah ada conversation ownership, handover, ticketing, marks, menu, dan sesi bot. Secara kapabilitas, ini kuat.

### 9.4. Integrasi eksternal dipisah dari monolith

Runtime WA aktif dikeluarkan ke Node.js terpisah. Ini tepat karena lifecycle WhatsApp Web memang tidak ideal bila dipaksa penuh di PHP.

### 9.5. SIPP diposisikan sebagai integrasi, bukan dicampur ke database utama

Ini keputusan yang benar secara boundary system.

### 9.6. Tailscale menggantikan kompleksitas VPN manual

Untuk kebutuhan operasional PA Semarang, ini sudah pragmatis dan sesuai.

## 10. Apa yang Masih Perlu Diperbaiki

### 10.1. Konsistensi skema permission

Masalah:

- `feature_permissions.role_id` tidak konsisten dengan fakta bahwa role utama masih string-based

Perlu:

- pilih salah satu model: full string-role atau tabel `roles` sungguhan

### 10.2. Cleanup tabel legacy atau ambigu

Kandidat kuat:

- `messages`
- `wa_session` bila ternyata hanya artefak runtime lama dan bukan bagian kontrak aplikasi aktif
- `wa_caraka_logs` bila ternyata fungsinya sudah digantikan `wa_caraka_messages`
- `ptsp_queue_tickets` dan `sidang_queue_tickets` bila migrasi ke `queue_tickets` sudah final

### 10.3. Source of truth runtime WA belum ideal

Masalah:

- workspace utama punya `wa-runtime/`, tetapi runtime aktif justru ada di `/home/dbprakom`

Risiko:

- developer bisa memperbaiki folder yang salah
- deployment dan debugging jadi membingungkan

### 10.4. Dokumentasi kontrak WA masih ada mismatch

Dokumen kontrak lama menyebut webhook `/wa-caraka/webhook/inbound`, padahal route aktif aplikasi berada di `/api/wa-caraka/webhook/inbound`.

Ini harus diseragamkan karena salah satu karakter bug operasional paling mahal adalah dokumentasi benar secara niat tetapi salah secara path.

### 10.5. Frontend masih hybrid

Saat ini sistem memakai kombinasi Blade, PHP public views, Inertia, dan Vue.

Ini sah untuk fase transisi, tetapi mahal untuk maintenance jika dibiarkan terlalu lama.

### 10.6. Media inbound WA belum end-to-end lengkap

Dokumen hotfix menunjukkan gambar masuk baru tersimpan sebagai metadata dan belum sepenuhnya dipersist ke storage untuk preview UI.

### 10.7. Queue, session, cache masih sangat database-centric

Ini bagus untuk kesederhanaan, tetapi jika trafik naik atau job WA makin banyak, database utama bisa menjadi terlalu sibuk.

## 11. Apakah Sudah Sesuai dengan yang Seharusnya?

Jawaban jujurnya: sebagian besar ya, tetapi belum 100 persen sesuai bentuk ideal akhirnya.

Penilaian saya:

- Sesuai untuk tahap operasional sekarang: ya.
- Sesuai untuk arsitektur target jangka panjang: belum penuh.

Kalau diibaratkan, gedungnya sudah dipakai dan cukup fungsional, tetapi masih ada ruangan lama yang belum direnovasi, kabel lama yang belum dicabut, dan beberapa papan petunjuk yang belum diseragamkan.

Secara khusus:

- Domain aplikasi: sudah sesuai.
- Integrasi antar sistem: cukup sesuai.
- Database boundary: cukup sesuai.
- Konsistensi data model: belum sepenuhnya sesuai.
- Kerapian governance teknis: belum sepenuhnya sesuai.

## 12. Prioritas Tindak Lanjut yang Disarankan

### Prioritas 1 - Source of truth arsitektur

- Tetapkan runtime WA aktif resmi: apakah `/home/dbprakom/wa-lawangsewu` dan `/home/dbprakom/wa-bridge` tetap dipertahankan, atau dipindahkan agar satu sumber dengan repo.
- Buat satu runbook tunggal untuk path, port, service, dan webhook.

### Prioritas 2 - Rapikan model permission

- Putuskan apakah role akan tetap string-based atau dipindah ke tabel `roles` yang normal.
- Sesuaikan `feature_permissions` agar tidak setengah jalan.

### Prioritas 3 - Audit tabel legacy

- pastikan penggunaan nyata untuk `messages`
- pastikan status `wa_caraka_logs`
- pastikan roadmap migrasi antrian lama ke `queue_tickets`

### Prioritas 4 - Seragamkan dokumentasi kontrak integrasi

- betulkan semua dokumen yang masih menyebut path webhook lama
- jadikan path aktif `/api/wa-caraka/webhook/inbound` sebagai kontrak resmi

### Prioritas 5 - Lengkapi media pipeline WA Caraka

- download media di runtime
- forward media lewat bridge
- simpan ke storage Laravel
- tampilkan preview real di UI

### Prioritas 6 - Kurangi hybrid frontend jangka panjang

- tentukan mana yang tetap Blade/public PHP
- tentukan mana yang dimigrasikan penuh ke Inertia/Vue
- hindari kondisi setengah-setengah permanen

## 13. Putusan Audit

Verdict akhir:

- Lawangsewu sudah merupakan platform operasional yang nyata, bukan sekadar prototype.
- Fondasi intinya sudah benar.
- Integrasi terpenting sudah hidup.
- Tetapi masih ada hutang arsitektur yang harus ditutup agar sistem ini benar-benar rapi, mudah dirawat, dan tidak bergantung pada pengetahuan tribal.

Kalimat paling tepat untuk kondisi sekarang:

"Sistemnya sudah jalan dan cukup kuat, tetapi belum selesai dibereskan."

## 14. Dokumen Turunan Audit Ini

Untuk pendalaman terstruktur, baca dokumen lanjutan berikut:

- ERD ringkas database: `docs/26_2026-04-19_erd-ringkas-database-lawangsewu.md`
- daftar cleanup teknis: `docs/27_2026-04-19_daftar-cleanup-teknis-dan-tindak-lanjut-lawangsewu.md`
- audit khusus WA Caraka: `docs/28_2026-04-19_audit-khusus-wa-caraka-end-to-end.md`
- penjelasan integrasi eksternal: `docs/29_2026-04-19_laporan-penjelasan-integrasi-eksternal-lawangsewu.md`
