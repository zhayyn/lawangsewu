# Laporan Final Migrasi WA Caraka ke Server 192.168.88.33

**Tanggal:** 2026-04-26
**Penyusun:** dbprakom (via AI Assistant)
**Status:** ✅ SELESAI & BERHASIL

## 1. Ringkasan Eksekutif
Proses migrasi layanan *messaging* (WhatsApp Caraka) dari server utama (Lawangsewu) ke *Worker Node* terdedikasi telah selesai. Langkah ini diambil untuk mencegah *Out of Memory* (OOM) dan memastikan server utama Lawangsewu tidak terbebani oleh pemrosesan *I/O* pesan massal dari WhatsApp.

## 2. Arsitektur Terdistribusi (Distributed Architecture)

Sistem kini dipecah menjadi dua server yang saling berkomunikasi melalui jaringan LAN privat (Tailscale/Lokal).

### Server 9 (Host Utama Lawangsewu)
- **IP Address:** `192.168.88.9`
- **Peran:** Aplikasi Web Utama, Webhook Penerima, Database Pusat, dan Antrean (Queue).
- **Komponen Kunci:**
  - Laravel 11 (Lawangsewu)
  - MariaDB (Port 3306) — Terikat pada `0.0.0.0`
  - Redis (Port 6379) — Terikat pada `0.0.0.0` dengan `protected-mode no`
- **Keamanan (UFW):** Firewall secara ketat hanya mengizinkan IP `192.168.88.33` untuk mengakses port 3306 dan 6379.

### Server 33 (Worker Engine Terdedikasi)
- **IP Address:** `192.168.88.33`
- **Peran:** Node pekerja berat yang menangani enkripsi, koneksi WebSocket ke server WhatsApp Meta, dan Puppeteer/Chrome runtime.
- **Komponen Kunci:**
  - `wa-runtime` (Node.js) — Berjalan di port **8089**. Mesin yang membuka sesi WhatsApp.
  - `wa-bridge` (Node.js) — Berjalan di port **8790**. Middleware yang menerjemahkan *request* dari Laravel ke `wa-runtime` (dan sebaliknya).
- **Proses Manager:** PM2 (menjamin *auto-restart* saat sistem *crash* atau *reboot*).

## 3. Konfigurasi Lingkungan (Environment)

### Penyesuaian di Server 9 (.env Lawangsewu)
```env
LW_WA_V2_BASE=http://192.168.88.33:8790
LW_WA_V2_TOKEN=lawangsewu2026
```

### Penyesuaian di Server 33 (.env Runtime & Bridge)
Mesin di Server 33 diatur agar "menembak" kembali database yang berada di Server 9.
```env
DBHOST = 192.168.88.9
DBUSER = dbprakom
DBPASS = semakinhebat@26
DBNAME = lawangsewu_core
```

## 4. Pembaruan UI/UX (Tampilan Mesin)
Tampilan mentah (raw UI) dari mesin WhatsApp di Server 33 (port 8089) telah diperbarui secara total.
- **Sebelumnya:** Tampilan *default* "Sinofita" yang membingungkan.
- **Sekarang:** Tampilan antarmuka futuristik (*Glassmorphism*, Mode Gelap, *Particles.js*) dengan penamaan yang tegas: **WA-CARAKA**.
- *Catatan Linguistik:* Kata "Caraka" berasal dari bahasa Sanskerta/Jawa Kuno yang secara harfiah berarti "Utusan" atau "Pembawa Pesan". Tidak ada singkatan khusus untuk istilah ini.

## 5. Rekomendasi Keamanan & Kebersihan Container Server 33
1. **Isolasi Penuh (Single Responsibility):** Server 33 (`192.168.88.33`) dirancang dan dikonfigurasi sebagai *Dedicated Worker Node*. Sangat direkomendasikan agar container/server ini **TIDAK** dicampur dengan aplikasi web, database, atau layanan lain. Biarkan server ini fokus menanggung beban berat dari Puppeteer WhatsApp.
2. **Penghapusan File Sampah:** Karena proses instalasi sebelumnya menggunakan `git clone` atas seluruh repositori Lawangsewu, terdapat banyak file PHP/Laravel yang tidak dibutuhkan di Server 33. File ini harus dihapus untuk menghemat *storage* dan menghindari celah keamanan.

---
*Dokumen ini dibuat secara otomatis pasca-migrasi untuk menjaga konsistensi pengetahuan tim.*
// developed by dbprakom™
