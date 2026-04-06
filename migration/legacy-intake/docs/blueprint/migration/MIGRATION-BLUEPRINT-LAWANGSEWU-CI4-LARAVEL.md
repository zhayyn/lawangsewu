# Migration Blueprint Lawangsewu

Dokumen ini menjelaskan arah migrasi yang direkomendasikan:

- Lawangsewu menjadi CI4 Core
- aplikasi bisnis baru berdiri sebagai Laravel sibling app
- fitur AI dipilah tegas antara prompt-only, DB/API-connected, dan RAG-connected

## Dokumen Pendamping

Gunakan dokumen ini sebagai peta utama, lalu buka dokumen pendamping berikut untuk eksekusi teknis:

- `MIGRATION-MATRIX-LAWANGSEWU.md`
- `TONIGHT-EXECUTION-PLAN-LAWANGSEWU.md`
- `ROUTE-INVENTORY-LAWANGSEWU.md`
- `CI4-CORE-MODULE-MAP-LAWANGSEWU.md`
- `AI-CAPABILITY-REGISTER-LAWANGSEWU.md`

## Perumpamaan Sederhana

Bayangkan Lawangsewu seperti sebuah kawasan kantor.

- CI4 Core adalah gerbang utama, lobi, satpam, papan petunjuk, dan resepsionis.
- Laravel app adalah gedung kantor khusus yang berdiri di dalam kawasan itu.
- WA Caraka adalah gedung khusus lain yang sudah berdiri dan tetap dipakai.
- SSO adalah kartu akses yang dipakai untuk masuk ke beberapa gedung.
- Widget publik adalah papan informasi di area depan yang bisa dilihat tanpa masuk ke gedung kantor.

Artinya:

- semua orang masuk dari gerbang yang sama
- tidak semua fungsi harus dipindah ke satu gedung
- gedung besar boleh punya sistem sendiri, asalkan aksesnya tetap lewat gerbang utama

## Keputusan Arsitektur

### 1. CI4 Core untuk Lawangsewu

CI4 Core memegang fungsi berikut:

- landing page
- portal utama
- login/logout portal
- gateway SSO
- katalog aplikasi
- katalog widget publik
- dokumentasi dan walkthrough
- monitoring ringan
- audit akses dasar
- launcher ke aplikasi sibling

### 2. Laravel untuk aplikasi bisnis baru

Laravel dipakai untuk aplikasi yang memiliki ciri berikut:

- punya tabel dan relasi data sendiri
- punya workflow dan approval
- punya menu yang akan terus bertambah
- punya laporan, ekspor, notifikasi, role/policy kompleks
- suatu saat bisa berdiri sebagai sistem mandiri

### 3. WA Caraka tetap sibling app

WA Caraka tetap diperlakukan sebagai aplikasi terpisah yang dihubungkan lewat portal dan SSO.

## Pemilahan Modul

### Masuk ke CI4 Core

- halaman depan Lawangsewu
- dashboard portal
- gateway auth dan logout
- daftar widget
- daftar aplikasi
- halaman publik seperti berita, pengumuman, monitor, embed page
- dokumentasi publik dan internal yang dibuka dari portal
- permission layer dasar untuk membuka aplikasi

### Masuk ke Laravel App

- sistem surat/disposisi yang kompleks
- helpdesk/ticketing internal
- arsip digital dengan approval
- manajemen dokumen internal
- workflow administrasi yang panjang
- aplikasi pelayanan internal yang punya form, riwayat, approval, dan laporan sendiri

### Tetap di luar sebagai sibling app

- WA Caraka runtime
- WA Caraka admin dashboard
- service Node tertentu
- service AI tertentu jika nanti dipisah

## Aturan Praktik yang Harus Dijaga

### Aturan 1. Portal bukan tempat menumpuk semua logika bisnis

Portal hanya menjadi rumah depan, bukan gudang semua mesin.

Praktiknya:

- CI4 Core hanya menyimpan fungsi portal, auth, navigasi, katalog, dan integrasi
- aplikasi besar tidak ditanam sebagai modul campur-aduk di portal

### Aturan 2. Satu gerbang login, banyak gedung

User sebaiknya mengenal satu pintu masuk utama.

Praktiknya:

- login utama terjadi di Lawangsewu Core
- Laravel app menerima trust dari portal melalui SSO atau signed token
- logout tidak mengandalkan redirect berantai yang rapuh

### Aturan 3. Tiap aplikasi punya database dan lifecycle yang jelas

Praktiknya:

- CI4 Core punya data portal sendiri
- Laravel punya data bisnisnya sendiri
- WA Caraka tetap dengan data runtime dan admin sendiri
- jika perlu sinkronisasi, lakukan melalui service contract atau tabel integrasi yang jelas

## Catatan Penting Soal AI

Sebelum memutuskan rollout, training, atau positioning fitur AI, semua fitur harus diberi label kemampuan.

### Tiga label yang wajib dipakai

#### A. Prompt-only

AI hanya menjawab dari instruksi dan model umum.

Ciri-ciri:

- tidak membaca database internal
- tidak memanggil API sistem internal
- tidak mengambil dokumen knowledge base internal saat menjawab

Risiko jika tidak diberi label:

- user mengira AI tahu data terbaru padahal tidak
- pimpinan mengira AI sudah terhubung sistem padahal masih demo

#### B. DB/API-connected

AI atau sistem AI sudah bisa membaca data tertentu dari database atau API.

Ciri-ciri:

- bisa menampilkan status dari data aktual
- bisa query endpoint tertentu
- jawaban bisa bersandar pada data sistem

Risiko jika label ini tidak jelas:

- orang mengira semua jawaban akurat padahal mungkin hanya sebagian fitur yang benar-benar live

#### C. RAG-connected

AI menjawab dengan bantuan knowledge base atau dokumen internal yang diindeks.

Ciri-ciri:

- ada sumber dokumen yang dipakai saat inference
- jawaban bisa merujuk ke basis pengetahuan internal
- update knowledge memengaruhi jawaban

Risiko jika tidak diberi label:

- user tidak tahu apakah jawaban berasal dari dokumen resmi atau hanya dari model umum

## Perumpamaan AI Supaya Mudah Dipahami

Bayangkan petugas informasi.

- Prompt-only: petugas hanya menjawab dari ingatan umum, tanpa melihat map atau arsip.
- DB/API-connected: petugas menjawab sambil membuka komputer internal dan membaca data live.
- RAG-connected: petugas menjawab sambil membuka rak arsip dan buku pedoman yang sudah disusun.

Jadi pertanyaan yang harus selalu dijawab sebelum rollout adalah:

- apakah petugas ini hanya mengandalkan ingatan
- apakah dia sedang membuka komputer internal
- apakah dia sedang membuka arsip resmi

## Praktik Operasional AI yang Direkomendasikan

Setiap fitur AI di Lawangsewu nanti harus punya kartu identitas singkat seperti ini:

- Nama fitur
- Owner
- Status: prompt-only atau DB/API-connected atau RAG-connected
- Sumber data
- Batas jawaban
- Risiko salah tafsir
- Kapan terakhir diverifikasi

Contoh:

- Nama: Mas Satset FAQ Publik
- Status: RAG-connected
- Sumber: dokumen FAQ dan knowledge internal terkurasi
- Tidak boleh dianggap membaca database perkara live

Contoh lain:

- Nama: Ringkasan status layanan WA
- Status: DB/API-connected
- Sumber: API runtime WA Caraka
- Tidak boleh dianggap bisa menjawab kebijakan hukum atau SOP umum di luar data runtime

## Tahapan Implementasi yang Direkomendasikan

### Tahap 1. Bekukan boundary

Tentukan secara tertulis:

- mana fungsi portal
- mana fungsi aplikasi bisnis
- mana fungsi AI

Output tahap ini:

- daftar modul CI4 Core
- daftar kandidat Laravel app
- daftar sibling app yang tetap dipertahankan

### Tahap 2. Bangun CI4 Core yang bersih

Mulai dari fungsi paling inti:

- auth
- portal
- app registry
- widget registry
- launcher
- audit akses

### Tahap 3. Pindahkan fungsi kecil lebih dulu

Pindahkan ke CI4 Core:

- halaman publik ringan
- katalog widget
- dokumentasi portal
- launcher internal

Jangan mulai dari aplikasi besar.

### Tahap 4. Bangun Laravel app sebagai sibling

Laravel baru dipakai ketika domain bisnisnya sudah jelas.

Langkah minimal:

- tentukan nama aplikasi
- tentukan domain bisnis
- tentukan database sendiri
- tentukan relasi ke SSO portal
- tentukan menu masuk dari Lawangsewu Core

### Tahap 5. Terapkan label AI capability

Sebelum fitur AI diumumkan ke user:

- labeli prompt-only atau DB/API-connected atau RAG-connected
- tulis sumber datanya
- tulis batas jawabannya
- verifikasi dengan owner sistem

## Prinsip Pengambilan Keputusan Harian

Jika ada fitur baru, tanya ini:

1. Apakah ini fitur portal atau aplikasi bisnis?
2. Apakah ini cukup kecil untuk masuk CI4 Core?
3. Jika besar, apakah lebih tepat jadi Laravel sibling app?
4. Jika ada AI, apakah prompt-only, DB/API-connected, atau RAG-connected?
5. Apakah user bisa salah paham soal sumber jawaban AI?

## Rekomendasi Final

- Jadikan CI4 sebagai rumah utama Lawangsewu.
- Jadikan Laravel sebagai gedung aplikasi bisnis yang berdiri sendiri.
- Pertahankan sibling app yang sudah sehat.
- Jangan campur semua logika bisnis ke portal.
- Wajib beri label kemampuan AI sebelum rollout atau training.

## Status Migrasi Saat Ini

Jawaban singkat untuk pertanyaan "apakah migrasi sudah selesai?" adalah: **belum selesai**.

Yang sudah ada sekarang adalah fondasi staging yang cukup matang untuk melanjutkan migrasi dengan arah yang jelas, tetapi belum seluruhnya menjadi runtime produksi final.

### 1. Yang Sudah Selesai atau Sudah Terbukti

- boundary arsitektur besar sudah dibekukan: Lawangsewu diarahkan menjadi CI4 Core, Laravel disiapkan sebagai sibling app bisnis, dan WA Caraka tetap sibling app
- dokumen pemandu utama sudah tersedia: blueprint, matrix, route inventory, module map, execution plan, dan AI capability register
- shell staging CI4 Core sudah hidup dan punya runtime minimum
- route inti staging dasar sudah bisa dirender, termasuk landing, walkthrough, daftar widget, app registry, dan portal dengan auth gate
- kontrak route, compatibility alias, dan audit dasar staging sudah mulai tervalidasi
- login live gateway sudah pernah dibuktikan lolos untuk alur portal dasar
- sebagian shadow publik terbatas sudah mulai berjalan, terutama untuk `/walkthrough`

### 2. Yang Masih Staging atau Belum Selesai

- CI4 Core belum menjadi entrypoint produksi utama Lawangsewu
- cutover route publik belum selesai penuh; sebagian masih tahap `shadow`, `review`, atau `compat-only`
- portal dan portal-launch baru sampai tahap `full-cutover-review`, belum dinyatakan cutover produksi final
- widget directory dan family publik lain belum seluruhnya naik ke status final aman produksi
- Laravel sibling app masih berupa arah arsitektur dan boundary keputusan, belum berarti aplikasi bisnis barunya sudah dibangun selesai
- gateway auth routes, public API routes, dan integration contracts masih diperlakukan sebagai area sensitif yang belum dicutover ke runtime baru

### 3. Blocker Sebelum Bisa Disebut Selesai Total

- CI4 Core harus benar-benar mengambil peran sebagai rumah utama produksi, bukan hanya shell staging
- family route publik harus selesai melewati shadow dan review tanpa memutus URL lama
- portal auth flow harus lolos validasi produksi yang konsisten, bukan hanya smoke sintetis atau satu kali verifikasi live
- launcher dan integrasi sibling app harus tervalidasi per target aplikasi, bukan hanya portal secara umum
- keputusan untuk Laravel sibling app harus diterjemahkan menjadi implementasi aplikasi nyata bila domain bisnisnya sudah dipilih
- seluruh fitur AI yang akan diumumkan harus punya label capability yang terverifikasi: `prompt-only`, `DB/API-connected`, atau `RAG-connected`

### 4. Kesimpulan Operasional

Kalau ditanya apakah fondasi migrasi sudah siap dipakai untuk kerja teknis, jawabannya: **ya, sudah cukup siap**.

Kalau ditanya apakah migrasi Lawangsewu sudah selesai penuh, jawabannya: **belum**.

Status yang paling akurat saat ini adalah:

- fondasi arsitektur: sudah siap
- shell CI4 Core staging: sudah berjalan
- cutover produksi penuh: belum selesai
- Laravel sibling app: belum selesai dibangun
- pelabelan capability AI: wajib terus diverifikasi sebelum rollout

## Paket Hasil yang Sudah Siap Dipakai

Jika semua dokumen pendamping di atas disetujui, maka secara praktik fondasi migrasi sudah siap dipakai untuk memulai pekerjaan teknis tanpa berjalan buta.

Urutan pakainya:

1. baca blueprint ini untuk arah besar
2. pakai `MIGRATION-MATRIX-LAWANGSEWU.md` untuk memilah folder dan fungsi
3. pakai `ROUTE-INVENTORY-LAWANGSEWU.md` untuk memastikan URL publik tidak putus
4. pakai `CI4-CORE-MODULE-MAP-LAWANGSEWU.md` untuk memigrasikan per modul, bukan per file acak
5. pakai `AI-CAPABILITY-REGISTER-LAWANGSEWU.md` untuk mencegah salah tafsir fitur AI
6. pakai `TONIGHT-EXECUTION-PLAN-LAWANGSEWU.md` sebagai urutan kerja singkat dan aman