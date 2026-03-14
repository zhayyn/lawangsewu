# Migration Matrix Lawangsewu

Dokumen ini memetakan folder dan fungsi yang sudah ada di repo Lawangsewu ke target arsitektur yang disepakati:

- CI4 Core sebagai rumah utama Lawangsewu
- Laravel sebagai sibling app untuk domain bisnis baru
- sibling apps yang tetap dipertahankan apa adanya

## Ringkasan Cepat

### Status Saat Ini

Matriks ini sudah bisa dipakai sebagai dasar kerja teknis, tetapi migrasi keseluruhan **belum selesai**.

Status praktiknya saat ini:

- boundary CI4 Core, Laravel sibling app, dan sibling app tetap sudah cukup jelas
- area inti portal dan halaman publik ringan sudah punya arah migrasi yang konsisten
- WA Caraka tetap di luar scope pemindahan ke CI4 Core
- perpindahan nyata masih bertahap; belum semua route dan entrypoint aktif dipotong ke runtime baru

### Masuk ke CI4 Core

- landing, portal, auth gateway, katalog widget, katalog aplikasi, dokumentasi portal, launcher, dan monitoring ringan

### Tetap sibling app

- WA Caraka runtime
- WA Caraka admin CI4
- wrapper publik WA Caraka
- service Node tertentu

### Kandidat Laravel

- belum ada folder aktif yang wajib dipindah malam ini
- Laravel dipakai nanti untuk aplikasi bisnis baru yang punya workflow sendiri

## Matriks Pemilahan

| Lokasi Saat Ini | Fungsi Saat Ini | Target | Keputusan | Catatan |
|---|---|---|---|---|
| `gateway/` | auth portal, SSO, launcher, utilitas akses | CI4 Core | dipindah bertahap | ini inti rumah Lawangsewu |
| `widgets/views/php/public/` | halaman publik, widget page, embed page | CI4 Core | dipindah bertahap | cocok jadi controller/view CI4 |
| `widgets/views/html/public/` | aset/halaman publik statis | CI4 Core | dipindah bertahap | cocok jadi public assets atau views |
| `widgets/views/php/api/` | endpoint PHP ringan | CI4 Core | dipilah | endpoint kecil masuk CI4, endpoint khusus tetap terpisah jika perlu |
| `widgets/views/php/system/` | utility/system scripts | tetap terpisah dulu | jangan dipindah malam ini | butuh tracing CLI dan cron |
| `widgets/views/data/` | data file internal widget | tetap terpisah dulu | audit dulu | pindah setelah mapping dependency jelas |
| `widgets/views/config/` | konfigurasi widget | CI4 Core atau config shared | audit dulu | jangan dipindah tanpa cek referensi |
| `Walkthrough-DBPrakom/` | dokumentasi | CI4 Core | diintegrasikan lewat document module | konten tetap, delivery masuk portal |
| `wa-caraka/` | runtime WA, docs, node process, admin source | sibling app | tetap | jangan dijadikan modul portal |
| `wa-caraka-admin/` | wrapper publik webroot | sibling wrapper | tetap | tetap sebagai entrypoint publik ke WA admin |
| `wa-caraka/dashboard-ci4-admin/` | dashboard admin/operator CI4 | sibling app | tetap | integrasi via SSO dari CI4 Core |
| `gateway-node/` | service Node pendukung | sibling service | tetap | integrasikan lewat contract/API |
| `Api-Caraka/` | proyek pendukung/integrasi | tetap terpisah | audit kemudian | jangan ikut migrasi malam ini |
| `archive/` | arsip/snapshot | no move | tetap | bukan target migrasi |
| `releases/` | release artifacts | no move | tetap | bukan target migrasi |
| `projects/` | proyek referensi/integrasi | no move | tetap | di luar scope inti malam ini |

## Yang Harus Masuk Gelombang 1 CI4 Core

Gelombang 1 adalah bagian yang paling dekat dengan fungsi rumah utama.

- landing page Lawangsewu
- portal utama
- auth gateway
- logout/login portal
- app registry
- widget registry
- dokumentasi portal
- launcher ke WA Caraka dan layanan lain

## Yang Jangan Dipindah Malam Ini

- WA Caraka runtime
- dashboard CI4 WA Caraka
- script system yang dipakai CLI/cron
- service Node
- folder arsip dan release

Alasannya sederhana: bagian-bagian ini punya entrypoint, runtime, dan blast radius sendiri.

## Laravel Nanti Dipakai untuk Apa

Laravel jangan diisi fungsi portal. Laravel dipakai untuk sistem baru yang punya domain bisnis sendiri.

Contoh yang layak jadi Laravel sibling app:

- sistem surat/disposisi
- helpdesk/ticketing
- arsip/approval dokumen
- sistem pelayanan internal yang punya workflow panjang
- aplikasi administrasi internal dengan form, approval, reporting, dan notifikasi

## Aturan Boundary yang Harus Dipatuhi

### CI4 Core boleh melakukan ini

- autentikasi utama
- menampilkan katalog aplikasi
- menampilkan katalog widget
- membuka aplikasi sibling via launcher/SSO
- menyimpan audit akses portal

### CI4 Core jangan melakukan ini

- menampung semua logika bisnis besar
- mengambil alih runtime WA Caraka
- menanam aplikasi besar menjadi modul campur-aduk

### Laravel sibling app boleh melakukan ini

- punya database sendiri
- punya workflow, approval, report, notification, queue, policy sendiri
- menerima login trust dari portal Lawangsewu

### Laravel sibling app jangan melakukan ini

- menjadi pengganti portal utama
- membuat login utama kedua yang memutus identitas portal

## Klasifikasi AI untuk Repo Ini

Ini draft awal cara mengelompokkan fitur AI berdasarkan kenyataan sistem sekarang.

| Fitur/Area | Status Awal | Alasan |
|---|---|---|
| `gateway/mas-satset-ai.php` | perlu verifikasi | jangan diasumsikan live DB/RAG sebelum dicek koneksi nyata |
| WA Caraka LLM config dan test | DB/API-connected parsial | sudah terhubung ke sistem admin, tetapi tidak otomatis berarti semua jawaban berbasis RAG |
| FAQ atau knowledge-based response | perlu verifikasi | harus dibedakan apakah hanya prompt atau benar-benar memakai basis knowledge |
| status runtime WA | DB/API-connected | karena sumbernya runtime/service data |

## Keputusan Praktis Malam Ini

Malam ini yang realistis dan aman adalah:

- bekukan boundary
- sepakati matriks ini
- siapkan target CI4 Core module list
- jangan memulai rewrite besar sekaligus

Jika ini disetujui, besok migrasi teknis bisa dimulai dari area paling aman: auth, portal, registry, dan halaman publik ringan.

## Catatan Status Operasional

Dokumen ini tidak boleh dibaca seolah-olah semua folder di atas sudah selesai dipindah.

Cara baca yang benar:

- `dipindah bertahap` berarti arah targetnya sudah diputuskan, tetapi implementasinya masih berjalan
- `tetap` berarti sengaja dipertahankan sebagai sibling app atau sibling service
- `audit dulu` berarti belum aman dipindah sebelum dependency dan blast radius-nya jelas
- Laravel di dokumen ini masih boundary arsitektur, belum berarti sibling app bisnisnya sudah dibangun final