# Daftar Cleanup Teknis dan Tindak Lanjut Lawangsewu

Tanggal: 19 April 2026  
Tujuan: menjadikan hutang teknis Lawangsewu lebih konkret, terurut, dan bisa dieksekusi sprint per sprint

## 1. Ringkasan Prioritas

Prioritas paling penting saat ini bukan menambah modul baru, tetapi menutup celah konsistensi antara:

- struktur database
- runtime integrasi
- dokumentasi operasional
- source of truth kode aktif

## 2. Daftar Cleanup Prioritas Tinggi

### 2.1. Samakan source of truth runtime WhatsApp

Masalah:

- repo utama punya `wa-runtime/`
- runtime aktif justru berada di `/home/dbprakom/wa-lawangsewu` dan `/home/dbprakom/wa-bridge`

Dampak:

- developer rawan mengubah folder yang salah
- debugging dan deployment membingungkan

Yang perlu dilakukan:

1. putuskan runtime resmi yang aktif
2. tandai folder alternatif sebagai `deprecated` atau `experimental`
3. tulis runbook tunggal untuk path, port, service, dan webhook

Status:

- belum beres

### 2.2. Rapikan kontrak webhook WA Caraka

Masalah:

- dokumentasi lama masih menyebut `/wa-caraka/webhook/inbound`
- route aktif aplikasi adalah `/api/wa-caraka/webhook/inbound`

Dampak:

- integrasi eksternal rawan 404

Yang perlu dilakukan:

1. audit semua dokumen `docs` yang menyebut endpoint webhook
2. pastikan bridge dan runtime memakai path yang sama
3. tetapkan satu kontrak resmi

Status:

- sebagian sudah diketahui, belum diseragamkan penuh

### 2.3. Audit tabel ambigu atau legacy

Tabel target:

- `messages`
- `wa_session`
- `wa_caraka_logs`
- `ptsp_queue_tickets`
- `sidang_queue_tickets`

Yang perlu dilakukan:

1. cari apakah masih dipakai oleh controller, service, job, atau runtime
2. kalau tidak dipakai, tandai untuk deprecate
3. jika masih dipakai diam-diam, dokumentasikan fungsi nyatanya

Status:

- belum selesai

### 2.4. Selesaikan model permission

Masalah:

- `users.role` dan `role_permissions.role` memakai string
- `feature_permissions` memakai `role_id`

Yang perlu dilakukan:

1. pilih satu model final
2. kalau mau pakai integer role, buat tabel `roles`
3. kalau tetap string-role, ubah `feature_permissions` agar konsisten

Status:

- belum konsisten

## 3. Daftar Cleanup Prioritas Menengah

### 3.1. Tutup migrasi antrian lama ke antrian baru

Masalah:

- `queue_tickets` sudah lebih matang
- tabel antrian lama masih hidup

Yang perlu dilakukan:

1. tetapkan apakah PTSP dan sidang sudah 100 persen di model baru
2. jika ya, siapkan migrasi data atau archive strategy
3. hapus ketergantungan kode terhadap tabel lama

### 3.2. Lengkapi media pipeline WA Caraka

Masalah:

- inbound media belum menjadi preview visual end-to-end di UI

Yang perlu dilakukan:

1. runtime download media
2. bridge forward media payload
3. Laravel simpan ke storage
4. frontend tampilkan preview

### 3.3. Evaluasi database-centric runtime support

Masalah:

- session, queue, dan cache semua menumpuk di database utama

Yang perlu dilakukan:

1. ukur beban query session dan jobs
2. tentukan apakah perlu pindah sebagian ke Redis
3. lakukan hanya jika memang trafik dan concurrency menuntut

### 3.4. Rapikan hybrid frontend

Masalah:

- ada Blade, PHP public legacy, Inertia, dan Vue hidup berdampingan

Yang perlu dilakukan:

1. kelompokkan mana yang akan tetap legacy
2. kelompokkan mana yang akan dimigrasi penuh ke Inertia/Vue
3. buat roadmap bertahap agar tidak hybrid selamanya tanpa arah

## 4. Daftar Cleanup Prioritas Rendah tapi Penting

### 4.1. Dokumentasi arsitektur tunggal

Yang perlu dilakukan:

1. jadikan satu dokumen sebagai index arsitektur
2. tautkan ke ERD, integrasi eksternal, dan audit WA Caraka

### 4.2. Audit naming convention

Masalah:

- ada nama `wa_session` versus `wa_caraka_sessions`
- ada `messages` versus `chat_messages`

Yang perlu dilakukan:

1. definisikan aturan penamaan domain table
2. catat pengecualian jika tidak bisa diubah sekarang

### 4.3. Validasi stale docs

Yang perlu dilakukan:

1. periksa dokumen lama yang sudah tertinggal setelah perubahan arsitektur
2. beri label `historical`, `active`, atau `deprecated`

## 5. Rencana Eksekusi Praktis

### Sprint A - Konsistensi integrasi

Fokus:

- source of truth runtime WA
- webhook contract
- runbook service dan port

Output:

- tidak ada lagi kebingungan folder aktif dan endpoint aktif

### Sprint B - Konsistensi database

Fokus:

- audit tabel ambigu
- putusan role model
- audit queue lama versus baru

Output:

- skema data lebih rapi dan mudah dipahami

### Sprint C - Penyempurnaan fitur

Fokus:

- media pipeline WA Caraka
- cleanup hybrid frontend
- evaluasi cache/queue runtime support

Output:

- pengalaman operator lebih lengkap dan maintainability lebih baik

## 6. Apa yang Sudah Baik dan Tidak Perlu Dirombak Sembarangan

- pemisahan domain guestbook, queue, WA Caraka, chat, CCTV, dan SIPP sudah baik
- penggunaan queue worker dan bridge terpisah untuk WhatsApp adalah keputusan yang benar
- Tailscale sebagai jalur privat untuk jaringan lokal juga sudah tepat
- RBAC dan audit trail sudah cukup kuat secara fondasi

Artinya, cleanup yang disarankan bukan pembongkaran total. Ini lebih ke merapikan sambungan, membuang sisa kabel lama, dan menyamakan papan petunjuk.

## 7. Putusan Akhir

Kalau hanya satu kalimat:

Lawangsewu tidak butuh dibangun ulang, tetapi butuh dibereskan dengan disiplin.
