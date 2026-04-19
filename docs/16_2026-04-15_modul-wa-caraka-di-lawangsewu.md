# Modul WA Caraka di Lawangsewu

> Tanggal: 15 April 2026  
> Kategori: Modul Baru  
> Status: Aktif parsial

---

## Tujuan Modul

WA Caraka ditambahkan ke Lawangsewu sebagai modul WhatsApp internal untuk:

- memantau status device WhatsApp
- menampilkan QR pairing
- mengirim pesan tunggal
- mengirim broadcast sederhana
- melihat riwayat event dari runtime
- menyimpan log kirim lokal di database Lawangsewu

Modul ini ditempatkan langsung di shell Lawangsewu agar operator tidak perlu pindah ke dashboard terpisah.

---

## Analogi Sederhana

Kalau Lawangsewu itu seperti gedung kantor terpadu, maka WA Caraka adalah **ruang operator komunikasi**.

- Laravel Lawangsewu adalah meja operator dan panel kontrol.
- Runtime WA adalah mesin radio di belakang meja.
- `wa_caraka_logs` adalah buku catatan keluar-masuk pesan.

Jadi panel kontrolnya sudah ada di gedung utama, tetapi mesin radionya tetap harus tersedia dan menyala agar petugas bisa benar-benar menerima dan mengirim pesan.

---

## Komponen Modul

### Backend Laravel

- `app/Http/Controllers/WaCarakaController.php`
  - menampilkan halaman dashboard WA Caraka
  - menjadi proxy API untuk action WA
- `app/Services/WaCarakaService.php`
  - menghubungkan Lawangsewu ke runtime WA
  - melakukan request `health`, `qr`, `send-text`, `history`, dan action kontrol lain
  - menyimpan log lokal ke tabel `wa_caraka_logs`
- `app/Models/WaCarakaLog.php`
  - model log pesan WA lokal
- `config/wa_caraka.php`
  - konfigurasi base URL runtime, token, timeout, limit broadcast, dan logging

### Frontend

- `resources/js/Pages/Lawangsewu/WaCaraka/Index.vue`
  - dashboard operator WA Caraka di dalam layout Lawangsewu
  - menampilkan status koneksi, QR, log aktivitas, history, dan form kirim pesan

### Database

- migration `2026_04_15_115048_create_wa_caraka_logs_table.php`
  - membuat tabel `wa_caraka_logs`

### Testing

- `tests/Feature/Modules/WaCarakaModuleTest.php`
  - menguji akses guest/viewer/operator
  - menguji proxy action
  - menguji pencatatan log lokal
  - menguji broadcast sederhana

---

## Route yang Ditambahkan

Route operator WA Caraka berada di grup middleware:

- `auth`
- `verified`
- `active`
- `role:operator,admin`

Route aktif:

- `GET /wa-caraka`
  - nama route: `lawangsewu.wacaraka.index`
- `GET|POST /wa-caraka/api/{action}`
  - nama route: `lawangsewu.wacaraka.api`

Ini berarti viewer tidak bisa mengakses modul ini, dan guest akan diarahkan ke login.

---

## Action yang Sudah Tersedia

Action yang saat ini didukung lewat proxy Laravel:

- `health`
- `qr`
- `restart`
- `reconnect`
- `disconnect`
- `history`
- `history/clear`
- `stats`
- `logs`
- `send-text`
- `broadcast`

Catatan penting:

- `stats` dan `logs` diambil dari database Lawangsewu
- action lain bergantung pada runtime WA eksternal

---

## Konfigurasi Environment

Variabel environment yang dipakai:

```env
LW_WA_V2_BASE=http://127.0.0.1:8790
LW_WA_V2_TOKEN=
LW_WA_V2_TIMEOUT=20
LW_WA_BROADCAST_LIMIT=50
LW_WA_LOGGING=true
```

Fungsi masing-masing:

- `LW_WA_V2_BASE`
  - alamat runtime WA
- `LW_WA_V2_TOKEN`
  - token akses ke runtime bila runtime mengharuskannya
- `LW_WA_V2_TIMEOUT`
  - timeout request dari Laravel ke runtime
- `LW_WA_BROADCAST_LIMIT`
  - batas maksimal penerima tiap broadcast
- `LW_WA_LOGGING`
  - mengaktifkan atau mematikan log lokal ke tabel `wa_caraka_logs`

---

## Alur Kerja Modul

### Saat halaman dibuka

1. operator membuka `/wa-caraka`
2. controller memuat payload modul dan statistik awal
3. halaman Vue melakukan polling ke action `health`, `qr`, dan `history`
4. hasilnya ditampilkan ke dashboard

### Saat kirim pesan

1. operator mengisi nomor tujuan dan isi pesan
2. frontend mengirim `POST` ke `lawangsewu.wacaraka.api` dengan action `send-text`
3. controller memvalidasi input
4. service meneruskan request ke runtime WA
5. hasil kirim disimpan ke `wa_caraka_logs`
6. dashboard menampilkan status dan memperbarui history

### Saat broadcast

1. operator mengirim daftar nomor dan pesan
2. controller memvalidasi jumlah penerima
3. service mengirim satu per satu ke runtime
4. setiap hasil kirim dicatat ke log lokal

---

## Yang Sudah Normal

- modul sudah masuk ke navigasi Lawangsewu
- akses sudah dilindungi RBAC Lawangsewu
- migration log lokal sudah ada
- dashboard operator sudah tampil di Laravel
- test modul WA Caraka lulus penuh saat audit
- widget publik yang masih menunjuk ke WA lama sudah diarahkan ke `/wa-caraka`

---

## Yang Belum Selesai

Pada audit 15 April 2026 ditemukan beberapa gap:

1. runtime WA tidak tersedia di host kerja saat audit
2. `.env` aktif belum berisi konfigurasi `LW_WA_*`
3. source runtime `server.mjs` tidak ada di repo aktif ini
4. belum ada bukti inbound message sudah masuk ke dashboard Laravel
5. fitur legacy yang lebih luas seperti blast/pengaduan/konsultasi belum terbukti sudah dimigrasikan semua

---

## Kriteria Siap Operasional

Modul ini bisa dianggap siap dipakai menerima pesan WhatsApp jika seluruh kondisi berikut sudah terpenuhi:

1. runtime WA aktif dan merespons `health`
2. QR pairing tampil dan device bisa connected
3. pesan keluar berhasil terkirim dari dashboard
4. pesan masuk dari nomor eksternal benar-benar masuk ke runtime
5. operator bisa melihat bukti event masuk yang relevan
6. SOP restart, reconnect, dan recovery device sudah tersedia

---

## Status Implementasi per 15 April 2026

Status modul saat ini:

- **UI dashboard:** siap
- **SSO/RBAC:** siap
- **proxy Laravel:** siap
- **log lokal:** siap
- **runtime WA aktif:** belum
- **inbound receiving terverifikasi:** belum
- **legacy full cleanup:** belum

Kesimpulan:

**WA Caraka sudah menjadi modul resmi di Lawangsewu, tetapi masih dalam tahap operasional parsial.**
