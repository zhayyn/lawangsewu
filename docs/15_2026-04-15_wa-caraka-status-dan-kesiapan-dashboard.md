# WA Caraka di Lawangsewu: Status Migrasi, Audit, dan Kesiapan Dashboard

> Tanggal: 15 April 2026  
> Kategori: Audit Modul & Kesiapan Operasional  
> Status: Parsial aktif, belum layak dinyatakan migrasi penuh selesai

---

## Ringkasan Singkat

WA Caraka **sudah berhasil masuk ke Lawangsewu Laravel sebagai modul resmi**, tetapi **belum selesai 100% menggantikan seluruh legacy WA Caraka**.

Yang sudah ada sekarang adalah:

- dashboard operator di Lawangsewu
- proteksi login via SSO/RBAC Lawangsewu
- proxy API ke runtime WA
- pengiriman pesan tunggal
- broadcast sederhana
- logging lokal ke tabel `wa_caraka_logs`
- test Laravel untuk akses, proxy, logging, dan broadcast

Yang **belum tuntas** adalah:

- runtime WA lokal belum aktif saat audit
- referensi live ke legacy masih tersisa di widget publik sebelum audit ini dirapikan
- belum ada bukti migrasi penuh fitur legacy yang lebih luas seperti blast admin lama, pengaduan, dan konsultasi
- belum ada bukti alur inbound message yang benar-benar sudah diterima dashboard Laravel

---

## Analogi Sederhana

Bayangkan WA Caraka itu seperti **loket layanan baru** di gedung Lawangsewu.

- Ruang loketnya sudah jadi.
- Petugasnya sudah punya kartu akses resmi.
- Meja, kursi, buku log, dan tombol panggil sudah tersedia.

Tetapi saat dicek hari ini:

- kabel ke mesin utamanya belum menyala
- sebagian papan penunjuk di gedung masih mengarah ke loket lama
- beberapa layanan lama masih belum dipindah ke loket baru

Jadi, **gedungnya sudah siap sebagian**, tetapi **operasional penuh belum bisa disebut selesai**.

---

## Hasil Audit 15 April 2026

### Yang tervalidasi normal

- Route Laravel modul WA Caraka aktif:
  - `lawangsewu.wacaraka.index`
  - `lawangsewu.wacaraka.api`
- Migration tabel `wa_caraka_logs` sudah `Ran`
- Test khusus WA Caraka lulus penuh: `11/11`
- Test suite aplikasi secara umum lulus `85` test
- Tidak ada error statis pada controller, service, halaman Vue, dan test WA Caraka

### Temuan penting

1. **Runtime WA lokal tidak aktif**
   - Audit `curl http://127.0.0.1:8790/health` gagal konek
   - Artinya dashboard Laravel ada, tetapi mesin WhatsApp yang menjadi backend pengiriman/penerimaan belum hidup

2. **Migrasi belum bisa disebut penuh**
   - Arsip legacy masih menunjukkan cakupan lama yang lebih besar: `server.mjs`, `db_wacaraka`, `dashboard-ci4-admin`, blast, pengaduan, konsultasi
   - Implementasi Laravel saat ini baru mencakup console inti dan logging lokal

3. **Ada residu legacy pada jalur live**
   - Widget publik sebelumnya masih menaut ke dashboard WA lama
   - Pada audit ini, tautan live tersebut dirapikan agar mengarah ke modul Laravel `/wa-caraka`

4. **Ada satu regresi aplikasi, tetapi bukan di WA Caraka**
   - `Tests\Feature\ProfileTest > profile information can be updated` gagal
   - Penyebabnya berkaitan dengan aturan nama resmi profil, bukan modul WA Caraka

---

## Progress Saat Ini

### Estimasi progress migrasi WA Caraka ke Lawangsewu: `70%`

Pembacaan progress ini didasarkan pada pembagian berikut:

- `15%` SSO dan RBAC sudah menyatu dengan Lawangsewu
- `15%` route, controller, service, dan page Laravel sudah ada
- `10%` migration log dan model lokal sudah aktif
- `10%` test modul khusus sudah lulus
- `10%` broadcast dasar dan send text dasar sudah tersedia
- `10%` cleanup link live dari legacy sudah dirapikan

Sisa `30%` yang belum:

- `10%` runtime WA harus benar-benar hidup dan stabil
- `10%` penerimaan pesan masuk ke dashboard/operator harus terbukti berjalan
- `10%` fitur legacy yang belum dipindah atau dipensiunkan harus diputuskan dan dituntaskan

---

## Kurang Apa Lagi Agar Dashboard Siap Dipakai Menerima Pesan WhatsApp

Berikut daftar minimum agar dashboard bisa dianggap siap operasional:

1. **Nyalakan runtime WA**
   - Endpoint `LW_WA_V2_BASE` harus merespons normal
   - `health`, `qr`, `history`, `send-text` harus hidup di server nyata

2. **Validasi pairing device**
   - QR harus muncul
   - device harus bisa benar-benar tersambung
   - status harus berubah menjadi connected

3. **Uji end-to-end inbound message**
   - kirim pesan dari nomor luar ke device WA
   - pastikan pesan masuk tercatat di runtime
   - pastikan operator bisa melihat status/riwayat yang relevan di dashboard

4. **Tentukan nasib fitur legacy yang belum termigrasi**
   - blast lama
   - pengaduan
   - konsultasi
   - dokumentasi admin CI4 lama

5. **Bersihkan legacy setelah poin 1-4 selesai**
   - hapus jalur live yang masih menunjuk ke sistem lama
   - arsipkan atau hapus folder/fungsi lama hanya setelah dipastikan tidak dipakai lagi

6. **Tambahkan SOP operasional**
   - siapa yang scan QR
   - bagaimana restart runtime
   - bagaimana recovery jika device logout
   - bagaimana memantau pesan gagal

---

## Keputusan Audit

### Status akhir

**Belum bisa dinyatakan selesai migrasi penuh.**

### Status yang lebih tepat

**Sudah masuk fase operasional parsial di Laravel, tetapi belum siap pembersihan total legacy.**

---

## Rekomendasi Langkah Berikutnya

1. Hidupkan runtime WA pada host target dan ulangi audit health/QR/send/history.
2. Lakukan uji pesan masuk nyata dari nomor WhatsApp eksternal.
3. Putuskan apakah fitur legacy `blast/pengaduan/konsultasi` akan dimigrasikan ke Laravel atau dipensiunkan resmi.
4. Setelah poin 1-3 beres, baru hapus sisa legacy WA Caraka secara fisik.
