# Penjelasan Sederhana: Reverb, Mode Aman, dan Dampaknya ke Lawangsewu

Tanggal: 2026-04-22  
Sistem: Lawangsewu / WA Caraka  
Status: Reverb dinonaktifkan sementara, mode aman aktif

## Ringkasan Singkat

Saat ini Lawangsewu tetap bisa dipakai tanpa Reverb. Yang dimatikan sementara adalah jalur realtime-nya, bukan aplikasi utamanya.

Keputusan ini diambil karena pada server produksi saat ini proses `reverb:start` menunjukkan konsumsi memori yang tidak normal dan berisiko mengulang insiden server hang.

## Reverb Itu Apa

Reverb adalah server WebSocket milik Laravel untuk komunikasi realtime.

Analogi sederhananya:

- request web biasa itu seperti kirim surat
- Reverb itu seperti radio HT yang selalu menyala

Kalau surat dipakai, browser harus minta data lagi secara berkala atau saat pengguna klik refresh. Jika radio HT dipakai, server bisa langsung "bicara" ke browser saat ada perubahan.

## Fungsi Reverb di Lawangsewu

Di codebase Lawangsewu, Reverb dipakai untuk kebutuhan realtime seperti:

- update inbox/chat tanpa refresh penuh
- notifikasi event chat yang baru masuk
- update layar antrean yang harus berubah cepat
- status koneksi realtime di frontend

Jejaknya ada di:

- [config/reverb.php](/var/www/lawangsewu/config/reverb.php:1)
- [config/broadcasting.php](/var/www/lawangsewu/config/broadcasting.php:1)
- [resources/js/echo.js](/var/www/lawangsewu/resources/js/echo.js:1)
- [resources/js/composables/useReverb.js](/var/www/lawangsewu/resources/js/composables/useReverb.js:1)
- [app/Events/WaCarakaMessageReceived.php](/var/www/lawangsewu/app/Events/WaCarakaMessageReceived.php:1)
- [app/Events/WaCarakaConversationUpdated.php](/var/www/lawangsewu/app/Events/WaCarakaConversationUpdated.php:1)
- [app/Events/QueueTicketUpdated.php](/var/www/lawangsewu/app/Events/QueueTicketUpdated.php:1)

Analogi sederhananya:

- halaman chat operator seperti meja customer service
- layar antrean seperti papan panggil di lobby
- Reverb adalah petugas yang berteriak langsung saat ada perubahan

Tanpa Reverb, petugas itu tidak ada. Jadi layar masih bisa diperbarui, tetapi lewat cek berkala atau refresh, bukan lewat teriakan langsung.

## Kenapa Reverb Sementara Dimatikan

Karena pada server ini perilakunya belum aman.

Temuan saat investigasi:

- proses `reverb:start` tumbuh sampai memori ukuran gigabyte
- PHP CLI sempat tidak punya pagar memori yang ketat
- saat service dihidupkan, server kembali rawan kehabisan RAM

Analogi sederhananya:

- Reverb itu seperti mesin radio utama di gedung
- secara teori mesin ini membantu komunikasi jadi cepat
- tetapi di server ini mesinnya seperti bocor bensin
- selama kebocoran belum ditemukan, menyalakan lagi justru membahayakan seluruh gedung

Jadi alasan dimatikan bukan karena fiturnya tidak berguna, tetapi karena stabilitas server lebih penting daripada realtime penuh.

## Apa yang Terpengaruh Saat Reverb Mati

Yang terpengaruh:

- update realtime tidak seinstan sebelumnya
- beberapa halaman akan mengandalkan polling atau refresh
- event broadcast Laravel tidak lagi dikirim lewat jalur WebSocket aktif

Yang tidak terpengaruh:

- website Lawangsewu tetap bisa diakses
- login tetap berjalan
- scheduler tetap berjalan
- queue tetap bisa diproses dengan mode aman
- WA Caraka tetap bisa dipakai untuk alur inti

Analogi sederhananya:

- gedung masih buka
- loket masih bekerja
- arsip masih dicatat
- hanya pengeras suara live yang dimatikan sementara

## Alternatif Pengganti Reverb

Alternatif yang sedang dipakai sekarang:

- `BROADCAST_CONNECTION=log`
- queue aman berbasis cron pendek
- frontend fallback ke polling atau refresh biasa

Alternatif jangka menengah:

- polling berkala untuk chat dan dashboard
- jalankan Reverb di server atau container terpisah
- pakai Redis + Reverb setelah akar masalah memory jelas
- gunakan provider realtime lain jika memang diperlukan

Analogi sederhananya:

- Reverb = panggilan langsung lewat HT
- polling = satpam keliling yang cek papan setiap beberapa saat
- log broadcast = catatan peristiwa tetap ditulis, tetapi tidak diteriakkan langsung ke ruangan

## Kenapa Mode Aman Sekarang Lebih Tepat

Mode aman sekarang dipilih karena target utamanya adalah:

- server tidak hang lagi
- root login tidak macet lagi
- aplikasi utama tetap online
- sistem lain di server tidak ikut terdampak

Karena itu arsitektur sementaranya menjadi:

- `reverb` dimatikan
- `queue` daemon panjang dimatikan
- queue diproses lewat cron pendek dengan `flock` dan `--stop-when-empty`
- scheduler tetap berjalan per menit
- cache dan session runtime memakai `file`

Analogi sederhananya:

- daripada menyalakan satu mesin besar yang rawan meledak
- kita pakai petugas kecil yang datang tiap menit, bekerja singkat, lalu pulang

Cara ini memang tidak se-mewah realtime penuh, tetapi jauh lebih aman untuk produksi saat ini.

## Kenapa Reverb Sebenarnya Tetap Penting

Reverb tetap penting jika Lawangsewu ingin:

- pengalaman chat lebih hidup
- dashboard operator berubah tanpa refresh
- antrean tampil benar-benar realtime
- notifikasi event terasa instan

Dengan kata lain, Reverb itu bukan fitur kosmetik. Ia berguna untuk pengalaman operasional yang cepat.

Namun, penting tidak sama dengan wajib dinyalakan kapan pun. Jika pondasi server belum sehat, fitur penting pun tetap harus ditahan dulu.

## Rekomendasi Ke Depan

Urutan aman yang disarankan:

1. Pertahankan mode aman ini dulu sampai server stabil beberapa hari.
2. Pantau RAM, swap, cron, dan log aplikasi.
3. Audit khusus kenapa `reverb:start` tumbuh abnormal di environment ini.
4. Jika realtime tetap dibutuhkan, pisahkan Reverb ke host atau container tersendiri.
5. Setelah ada hasil audit yang jelas, baru putuskan apakah Reverb diaktifkan lagi.

## Kesimpulan Sederhana

Kalau dianalogikan sebagai gedung layanan:

- Lawangsewu adalah gedungnya
- operator dan halaman web adalah petugas loket
- queue adalah alur antrean kerja
- Reverb adalah pengeras suara realtime

Saat pengeras suara itu sehat, semua orang dapat info lebih cepat. Tetapi saat pengeras suaranya korslet dan menyedot listrik gedung, keputusan yang benar adalah mematikannya dulu agar gedung tetap buka.

Itulah alasan Reverb saat ini belum dinyalakan lagi:

- bukan karena tidak berguna
- tetapi karena risikonya pada server ini masih lebih besar daripada manfaatnya

Dokumen insiden teknis lengkap tetap ada di:

- [02_2026-04-22_lawangsewu-stabilisasi-console-oom-dan-reverb.md](/var/www/lawangsewu/docs/02_2026-04-22_lawangsewu-stabilisasi-console-oom-dan-reverb.md:1)
