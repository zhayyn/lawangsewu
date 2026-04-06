# LAPORAN TEKNIS RESMI
## Migrasi & Sinkronisasi Data SIPP ke Google BigQuery (SIPP-HUB)
### Pengadilan Agama Semarang — Sistem Informasi & Teknologi

---

| | |
|---|---|
| **Nomor Laporan** | LW-SIPPHUB-2026-001 |
| **Tanggal Pelaksanaan** | 18 Maret 2026 |
| **Diajukan oleh** | Tim Teknologi Informasi — Lawangsewu Project |
| **Dikembangkan oleh** | zhayyn™ |
| **Status** | ✅ BERHASIL — Produksi Aktif |

---

## I. RINGKASAN EKSEKUTIF

Pada tanggal **18 Maret 2026**, tim IT Pengadilan Agama Semarang telah berhasil menyelesaikan dan mengoperasikan sistem **SIPP-HUB**: sebuah pipeline otomatis yang menyalin seluruh data dari database SIPP (Sistem Informasi Penelusuran Perkara) ke platform cloud **Google BigQuery** untuk keperluan analitik dan pelaporan.

**Hasil Utama:**

| Indikator | Nilai |
|---|---|
| Total tabel di database SIPP | **476 tabel** |
| Tabel yang dipilih untuk sinkronisasi | **440 tabel** |
| Tabel yang dilewati (skip) | **36 tabel** |
| Total data baris yang tersimpan ke cloud | **2.078.651 baris** |
| Waktu mulai proses | 13:17:59 WIB |
| Waktu selesai proses | 13:24:47 WIB |
| **Durasi total** | **6 menit 48 detik** |
| Status keberhasilan | **100% — 0 error** |

---

## II. LATAR BELAKANG

### 2.1 Apa itu SIPP?

SIPP (Sistem Informasi Penelusuran Perkara) adalah sistem manajemen perkara hukum yang digunakan oleh Pengadilan Agama Semarang. Seluruh data perkara, hakim, jadwal sidang, biaya, dan dokumen tersimpan di sini.

Database SIPP berada di **Server 10** (192.168.88.10) dengan total **476 tabel** dan berisi ratusan ribu hingga jutaan baris data operasional.

### 2.2 Apa itu SIPP-HUB?

SIPP-HUB adalah sistem "jembatan data" yang secara otomatis memindahkan salinan data dari database SIPP ke Google BigQuery — platform analitik cloud milik Google yang memungkinkan pengelolaan, pencarian, dan visualisasi data dalam skala besar.

> **Perumpamaan sederhana:**
> Bayangkan database SIPP seperti **lemari arsip kantor** — semua berkas perkara tersimpan rapi di sana, dan setiap hari ada berkas baru yang masuk. SIPP-HUB berfungsi seperti **petugas fotokopi yang berjaga setiap malam**: ia menyalin semua berkas baru ke **gudang pusat digital** (BigQuery), sehingga atasan, analis, dan petugas lain bisa membaca laporan tanpa harus membuka lemari arsip asli — dan tanpa mengganggu aktivitas kantor sehari-hari.

### 2.3 Mengapa Diperlukan?

1. **Keamanan data asli terjaga** — BigQuery hanya menerima salinan. Database SIPP operasional tidak pernah dimodifikasi.
2. **Analitik skala besar** — BigQuery mampu memproses jutaan baris data dalam hitungan detik, sesuatu yang tidak efisien dilakukan langsung di database operasional.
3. **Dasar pembuatan dashboard/laporan otomatis** — Data di BigQuery bisa dihubungkan ke Looker Studio, Google Sheets, atau sistem lainnya.
4. **Arsip historis** — Data yang sudah tersimpan di cloud aman dari potensi kehilangan data di server lokal.

---

## III. ARSITEKTUR SISTEM

```
┌─────────────────────────────────────────────────────────────────┐
│                     INFRASTRUKTUR LOKAL                         │
│                                                                 │
│  ┌─────────────────────┐       ┌─────────────────────────────┐  │
│  │   SERVER 10         │       │   SERVER 9 (App Server)     │  │
│  │   192.168.88.10     │──────▶│   192.168.88.9              │  │
│  │   MariaDB           │  LAN  │   /var/www/html/lawangsewu  │  │
│  │   Database SIPP     │       │   Python SIPP-HUB Engine    │  │
│  │   476 tabel         │       │                             │  │
│  └─────────────────────┘       └──────────────┬──────────────┘  │
└──────────────────────────────────────────────┃─────────────────┘
                                               ║ HTTPS/TLS
                                               ▼
                              ┌────────────────────────────────┐
                              │       GOOGLE CLOUD PLATFORM    │
                              │   Project: lawang-sewu-490507  │
                              │   ┌──────────────────────┐     │
                              │   │ BigQuery             │     │
                              │   │ Dataset: sipp_dataset│     │
                              │   │ Table:               │     │
                              │   │ server10_ingest_raw  │     │
                              │   └──────────────────────┘     │
                              └────────────────────────────────┘
```

**Komponen Utama:**

| Komponen | Keterangan |
|---|---|
| **Source DB** | MariaDB Server 10 — database `sipp` |
| **Sync Engine** | `scripts/sync-server10-sipp-hub.py` (Python 3.12) |
| **Nightly Runner** | `scripts/run-sync-server10-sipp-hub-nightly.sh` |
| **Destination** | BigQuery `lawang-sewu-490507.sipp_dataset.server10_ingest_raw` |
| **Credentials** | Service Account GCP (file: `secrets/service-account.json`, izin 600) |
| **State Tracker** | `runtime-flags/sipp-hub-sync-state.json` — mencatat batas data terakhir |
| **Lokasi Cloud** | `asia-southeast2` (Jakarta) |

---

## IV. METODOLOGI — CARA KERJA

### 4.1 Mode Sinkronisasi: Incremental (Bertahap)

Sistem **tidak menyalin seluruh data setiap hari** — hanya baris yang **baru atau berubah** sejak sinkronisasi terakhir yang diambil.

> **Perumpamaan:**
> Seperti tukang pos yang tidak mengantarkan seluruh isi kantor pos setiap hari, melainkan hanya surat baru yang belum pernah dikirim. Ia mengingat nomor surat terakhir yang dikirim, dan besok hanya mengambil dari nomor berikutnya.

Setiap tabel memiliki **penanda waktu/urutan** yang digunakan sebagai checkpoint:

| Prioritas | Nama Kolom | Keterangan |
|---|---|---|
| 1 | `diperbaharui_tanggal` | Tanggal diperbarui (format SIPP lokal) |
| 2 | `updated_at` | Tanggal update standar |
| 3 | `update_time` | Waktu update alternatif |
| 4 | `last_update` | Update terakhir |
| 5 | `created_at` | Tanggal dibuat |
| 6 | `id` | ID numerik (jika tidak ada kolom waktu) |

### 4.2 Algoritma Klasifikasi Tabel

Dari **476 total tabel**, sistem secara otomatis mengklasifikasikan setiap tabel:

```
476 tabel SIPP
│
├── DIPROSES (440 tabel) ──────────────────────────────────────────┐
│   ├── Tabel utama/canonical (data operasional aktif)             │
│   └── Versi terbaru dari keluarga tabel yang memiliki duplikat   │
│                                                                   │
└── DILEWATI (36 tabel) ────────────────────────────────────────────┘
    ├── 30 tabel: DUPLIKAT VERSI LAMA (arsip tanggal, misal:        │
    │            perkara_biaya_16122024 → winner: perkara_biaya)    │
    ├── 1 tabel:  sys_audittrail (log operasional 256MB — opsional) │
    ├── 2 tabel:  Varian arsip/copy (misal: perkara2_copy)         │
    └── 3 tabel:  Tidak ada kolom penanda waktu/urutan             │
```

---

## V. HASIL EKSEKUSI — 18 MARET 2026

### 5.1 Ringkasan Hasil

```
╔══════════════════════════════════════════════════════╗
║           HASIL SINKRONISASI SIPP-HUB                ║
║                  18 Maret 2026                        ║
╠══════════════════════════════════════════════════════╣
║  Mulai          : 13:17:59 WIB                        ║
║  Selesai        : 13:24:47 WIB                        ║
║  Durasi         : 6 menit 48 detik                   ║
╠══════════════════════════════════════════════════════╣
║  Tabel dipilih  : 440                                 ║
║  Tabel dilewati : 36                                  ║
║  TOTAL BARIS    : 2.078.651 baris                    ║
╠══════════════════════════════════════════════════════╣
║  Tabel aktif (ada data baru)     : 147 tabel         ║
║  Tabel tidak ada perubahan       : 293 tabel         ║
║  Error / Kegagalan               : 0 (NIHIL)         ║
╚══════════════════════════════════════════════════════╝
```

### 5.2 Top 15 Tabel Berdasarkan Jumlah Baris Tersimpan

| Peringkat | Nama Tabel | Baris Tersimpan | Keterangan |
|---|---|---|---|
| 1 | `perkaraprosesweb` | 431.343 | Proses perkara (web) |
| 2 | `perkarabiayaweb` | 430.179 | Biaya perkara (web) |
| 3 | `perkara_biaya_jurusita` | 123.508 | Biaya perkara juru sita |
| 4 | `hakimweb` | 119.188 | Data hakim (web) |
| 5 | `pihakweb` | 107.335 | Data pihak berperkara (web) |
| 6 | `ref_kelurahan_new` | 83.324 | Referensi kelurahan |
| 7 | `putusanpemberitahuanweb` | 72.942 | Pemberitahuan putusan (web) |
| 8 | `pihak` | 65.919 | Data pihak utama |
| 9 | `perkara_putusan_pemberitahuan_putusan` | 62.972 | Putusan & pemberitahuan |
| 10 | `dirput_dokumen` | 59.869 | Dokumen direktori putusan |
| 11 | `perkara_putusan_pemberitahuan_putusan2` | 58.794 | Putusan pemberitahuan (v2) |
| 12 | `ppweb` | 42.737 | Panel perkara web |
| 13 | `jurusitaweb` | 42.468 | Data juru sita (web) |
| 14 | `sidangpertamaweb` | 39.628 | Sidang pertama (web) |
| 15 | `perkaraputusanweb` | 39.380 | Putusan perkara (web) |
| … | *425 tabel lainnya* | *sisa dari 2.078.651* | — |

### 5.3 Tabel yang Dilewati Beserta Alasan

| Kategori | Jml | Contoh | Alasan Dilewati |
|---|---|---|---|
| **Duplikat versi lama** | 30 | `perkara_biaya_16122024` | Sudah ada `perkara_biaya` sebagai versi aktual. Tabel tanggal = snapshot lama. |
| **Log operasional audit** | 1 | `sys_audittrail` (256MB) | Log internal sistem, bukan data perkara. Dapat diaktifkan jika diperlukan. |
| **Salinan arsip (copy)** | 2 | `perkara2_copy` | Duplikat manual, bukan data aktif. |
| **Tanpa identifier waktu** | 3 | *(tabel config kecil)* | Tidak ada kolom timestamp/ID sehingga tidak bisa dilacak inkrementalnya. |

> **Perumpamaan keputusan skip:**
> Dari 476 berkas di lemari arsip, 36 di antaranya adalah **fotokopi lama** atau **catatan log internal petugas** — tidak perlu digandakan lagi karena sudah ada versi aslinya yang terbaru, atau memang bukan untuk publik. Hanya 440 berkas original yang relevan yang disalin ke gudang pusat.

---

## VI. DETAIL TEKNIS INFRASTRUKTUR

### 6.1 Spesifikasi Server

| Komponen | Detail |
|---|---|
| **App Server** | 192.168.88.9 (Server 9) |
| **Database Server** | 192.168.88.10 (Server 10) |
| **OS** | Linux |
| **Python Runtime** | Python 3.12 (venv: `.venv-sipp-hub`) |
| **Koneksi DB** | MariaDB via `mysql-connector-python` |
| **Koneksi Cloud** | `google-cloud-bigquery` library |

### 6.2 Skema Data di BigQuery

Setiap baris yang tersimpan di BigQuery memiliki 3 kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `ingested_at` | TIMESTAMP REQUIRED | Waktu data disalin ke cloud |
| `source` | STRING REQUIRED | Nama tabel asal (misal: `perkara_biaya`) |
| `payload` | JSON REQUIRED | Isi seluruh baris data dalam format JSON |

### 6.3 Keamanan

- Kredensial GCP tersimpan di `secrets/service-account.json` dengan izin file **600** (hanya bisa dibaca root/owner)
- Tidak ada password atau kunci yang tertanam langsung di kode program
- Seluruh konfigurasi sensitif dibaca dari file `.env` yang tidak di-*commit* ke repositori publik
- Koneksi ke Google Cloud menggunakan enkripsi **HTTPS/TLS**

---

## VII. FILE & SKRIP YANG TERLIBAT

| File | Fungsi |
|---|---|
| `scripts/sync-server10-sipp-hub.py` | Mesin utama sinkronisasi — baca SIPP, kirim ke BigQuery |
| `scripts/run-sync-server10-sipp-hub-nightly.sh` | Runner malam hari (entrypoint untuk cron) |
| `scripts/run-sync-server10-sipp-hub.sh` | Wrapper eksekusi Python dengan aktivasi venv |
| `scripts/plan-server10-sipp-hub-tables.py` | Alat audit/perencanaan tabel (laporan klasifikasi) |
| `.env` | Konfigurasi koneksi (host DB, project GCP, dataset, dll) |
| `secrets/service-account.json` | Kredensial akses Google Cloud |
| `runtime-flags/sipp-hub-sync-state.json` | Checkpoint batas data terakhir per tabel |

---

## VIII. MASALAH YANG DITEMUI & SOLUSI

| # | Masalah | Penyebab | Solusi |
|---|---|---|---|
| 1 | File kredensial tidak valid (bukan JSON) | Output CLI gcloud tercampur masuk ke file | Ekstraksi ulang via Python, tulis ulang clean |
| 2 | Error BigQuery: payload bukan format yang tepat | Python dict dikirim, tapi BigQuery JSON field butuh string | Tambah `json.dumps()` sebelum pengiriman |
| 3 | Error SQL: kata `table` adalah kata kunci SQL | Kolom bernama `table` tidak dikuot dengan backtick | Tambah fungsi `quote_identifier()` untuk semua nama kolom/tabel |
| 4 | Hanya 3 tabel yang tersinkronisasi | Kode lama hardcoded 3 tabel saja | Refaktor total ke mode dinamis: baca otomatis 476 tabel |
| 5 | Tabel duplikat (snapshot tanggal) ikut tersinkronisasi | Belum ada logika klasifikasi | Implementasi `discover_sync_jobs()` dengan regex deteksi duplikat |

---

## IX. SARAN & LANGKAH BERIKUTNYA

### 9.1 Prioritas Tinggi — Automasi Nightly (Perlu Segera Dilakukan)

**Jadwalkan sinkronisasi otomatis setiap malam** agar data di BigQuery selalu terkini tanpa intervensi manual.

Perintah yang perlu ditambahkan ke crontab:
```bash
# Jalankan setiap malam pukul 02:00 WIB
0 2 * * * /usr/bin/flock -n /tmp/sipp-hub-sync.lock /var/www/html/lawangsewu/scripts/run-sync-server10-sipp-hub-nightly.sh >> /var/www/html/lawangsewu/logs/sipp-hub-sync.log 2>&1 # sipp-hub-nightly-bigquery
```

> **Perumpamaan:** Ini seperti menyetel alarm untuk petugas fotokopi tadi — sehingga ia datang dan bekerja sendiri setiap malam tanpa perlu diingatkan setiap hari.

### 9.2 Prioritas Menengah — Dashboard & Visualisasi Data

Hubungkan BigQuery ke **Looker Studio** (gratis, milik Google) untuk membuat dashboard otomatis:
- Statistik perkara per bulan/tahun
- Grafik jenis perkara
- Laporan kinerja hakim & panitera
- Monitoring biaya perkara

> **Perumpamaan:** Setelah semua berkas ada di gudang pusat digital, langkah berikutnya adalah membuat **papan informasi otomatis** yang merangkum isi gudang dalam bentuk grafik dan angka — sehingga atasan bisa melihat ringkasan tanpa perlu membuka setiap berkas satu per satu.

### 9.3 Prioritas Rendah — Aktifkan Log Audit (Opsional)

Untuk kebutuhan forensik dan audit kepatuhan, tabel `sys_audittrail` (256MB, ~237.000 baris log aktivitas sistem) dapat diaktifkan dengan menambahkan ke file `.env`:
```
SIPP_HUB_INCLUDE_AUDITTRAIL=true
```

### 9.4 Pemantauan Berkelanjutan

- **Periksa log harian** di `/var/www/html/lawangsewu/logs/sipp-hub-sync.log` untuk memastikan tidak ada error
- **Verifikasi di BigQuery Console** dengan query:
```sql
SELECT source, COUNT(*) as total_baris,
       MAX(ingested_at) as terakhir_update
FROM `lawang-sewu-490507.sipp_dataset.server10_ingest_raw`
GROUP BY source
ORDER BY total_baris DESC
LIMIT 20;
```
- **Pantau ukuran state file** di `runtime-flags/sipp-hub-sync-state.json` — berisi checkpoint terakhir setiap tabel

---

## X. KESIMPULAN

Sistem **SIPP-HUB** telah berhasil dibangun, diuji, dan dijalankan secara penuh pada tanggal **18 Maret 2026**. Seluruh **440 tabel data perkara SIPP** telah tersalin ke Google BigQuery dengan total **2.078.651 baris data** dalam waktu **kurang dari 7 menit**, tanpa satu pun error.

Sistem ini bersifat:
- **Otomatis** — bisa dijadwalkan berjalan sendiri setiap malam
- **Incremental** — efisien, hanya mengambil data baru, tidak mengulang dari awal
- **Non-invasif** — tidak mengubah database SIPP asli
- **Aman** — kredensial terenkripsi, koneksi TLS
- **Terklasifikasi** — hanya 440 dari 476 tabel yang relevan yang diproses, sisanya dilewati dengan alasan yang jelas

Langkah yang paling mendesak berikutnya adalah **mendaftarkan cron job** agar sinkronisasi berjalan otomatis setiap malam, dan **menghubungkan BigQuery ke Looker Studio** agar data dapat divisualisasikan untuk keperluan pelaporan dan pengambilan keputusan.

---

## XI. LAMPIRAN

### D. Dashboard Preview (Responsif)

Preview visual dashboard yang sudah disiapkan dapat dibuka melalui:

- `docs/sipp-hub-dashboard-preview.html`
- `docs/sipp-hub-dashboard-executive.html`

Data feed untuk chart interaktif disimpan di:

- `docs/data/sipp-dashboard-data.json`

Perintah refresh data dashboard (setelah sinkronisasi SIPP-HUB):

```bash
cd /var/www/html/lawangsewu
export GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/lawangsewu/secrets/service-account.json
python3 scripts/export-sipp-dashboard-data.py
```

SQL paket pembentukan 19 view untuk Looker Studio:

- `docs/sql/sipp-hub-looker-studio-chart-pack.sql`

### A. Konfigurasi Aktif (.env — bagian SIPP-HUB)

```ini
LW_STAT_DB_HOST=192.168.88.10
LW_STAT_DB_USER=admin
LW_STAT_DB_NAME=sipp
SIPP_HUB_MODE=incremental
SIPP_HUB_BATCH_SIZE=300
SIPP_HUB_STATE_FILE=/var/www/html/lawangsewu/runtime-flags/sipp-hub-sync-state.json
SIPP_HUB_LOCATION=asia-southeast2
SIPP_HUB_PROJECT_ID=lawang-sewu-490507
SIPP_HUB_DATASET=sipp_dataset
SIPP_HUB_TABLE=server10_ingest_raw
SIPP_HUB_CREDENTIALS_FILE=/var/www/html/lawangsewu/secrets/service-account.json
```

### B. Log Eksekusi (Cuplikan)

```
[SIPP-HUB] started 2026-03-18 13:17:59
{"ok":true,"mode":"all_tables","selected_tables":440,"skipped_tables":36,"target":{"project":"lawang-sewu-490507","dataset":"sipp_dataset","table":"server10_ingest_raw"}}
{"ok":true,"mode":"incremental","apply":true,"source_table":"bandingdetilweb","inserted_rows":738,...}
{"ok":true,"mode":"incremental","apply":true,"source_table":"perkaraprosesweb","inserted_rows":431343,...}
{"ok":true,"mode":"incremental","apply":true,"source_table":"perkarabiayaweb","inserted_rows":430179,...}
... (440 baris hasil per tabel) ...
{"ok":true,"processed_tables":440,"inserted_rows_total":2078651}
[SIPP-HUB] finished 2026-03-18 13:24:47
```

### C. Glosarium Istilah Teknis

| Istilah | Penjelasan Sederhana |
|---|---|
| **BigQuery** | Gudang data cloud milik Google — mampu menyimpan dan menganalisis miliaran baris data |
| **Pipeline** | Aliran/jalur otomatis pemindahan data dari satu tempat ke tempat lain |
| **Incremental sync** | Sinkronisasi bertahap — hanya data baru yang disalin, bukan semua dari awal |
| **Schema** | Struktur/format tabel: nama kolom dan tipe datanya |
| **Service Account** | "Identitas robot" untuk mengakses layanan Google Cloud secara otomatis |
| **Cron job** | Perintah terjadwal yang berjalan otomatis di waktu tertentu (misal: setiap malam) |
| **MariaDB** | Sistem database yang digunakan di server SIPP |
| **State file** | File catatan checkpoint — mencatat data terakhir yang sudah disinkronisasi per tabel |
| **TLS/HTTPS** | Enkripsi data selama pengiriman — seperti amplop tersegel untuk surat |
| **JSON** | Format data universal, seperti "bahasa" antar sistem komputer |

---

*Dokumen ini dibuat secara otomatis berdasarkan log eksekusi dan kode program SIPP-HUB.*
*Dikembangkan oleh: **zhayyn™** | Proyek: **Lawangsewu — Pengadilan Agama Semarang***
*Tanggal: 18 Maret 2026*
