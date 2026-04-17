# Inventory Aplikasi dan Widget Lawangsewu

Dokumen ini dipakai sebagai daftar isi operasional agar komponen yang sudah ada tidak tercecer saat repo terus bertambah.

## Cara Pakai

- mulai dari bagian `Aplikasi utama` untuk melihat rumah besar setiap komponen
- lanjut ke `Widget dan halaman publik` jika yang dicari adalah halaman, embed, statistik, atau landing page
- pakai bagian `Checklist penambahan` setiap kali ada aplikasi atau widget baru agar indeks ini tetap hidup

## Legenda Status dengan Perumpamaan

Bayangkan Lawangsewu ini seperti satu kawasan perkantoran besar.

- `aktif`: seperti gedung yang sudah dipakai kerja harian dan orang keluar-masuk setiap saat
- `staging`: seperti ruang mockup atau lantai uji coba; bentuknya sudah jadi, tetapi belum dipakai penuh untuk tamu umum
- `legacy`: seperti gedung lama yang masih menyuplai layanan penting; tampilannya bisa tua, tetapi kabel dan jalurnya masih dipakai
- `arsip`: seperti gudang dokumen atau bangunan yang disimpan untuk referensi; jangan dibongkar sembarangan, tetapi juga bukan tempat kerja harian
- `wrapper`: seperti lobi depan kecil yang mengantar orang ke gedung utama di belakang
- `helper`: seperti meja alat bantu teknis; bukan layanan utama untuk publik, tetapi penting untuk operator

Untuk AI capability, bayangkan seperti tingkat kecerdasan petugas di gedung itu:

- `prompt-only`: petugas hanya menjawab dari instruksi umum yang dibawa saat itu, tanpa membuka map data internal
- `DB/API-connected`: petugas sudah boleh menelepon atau membuka sistem internal untuk cek data langsung
- `RAG-connected`: petugas bisa mencari arsip, dokumen, atau knowledge base sebelum menjawab
- `hybrid`: petugas menggabungkan instruksi, data langsung, dan knowledge base sekaligus

## Tabel Ringkas Komponen Utama

| Komponen | Status | Ibaratnya | Fungsi inti | Publik/Internal | Catatan AI |
| --- | --- | --- | --- | --- | --- |
| Lawangsewu root | aktif | gedung pusat | rumah utama dokumentasi, launcher, dan integrasi | campuran | bukan engine AI, tetapi pusat orientasi |
| gateway/ | aktif | lobi dan front office | login, launcher, SSO, portal operator | publik terbatas + internal | `mas-satset-ai` di sini masih helper/lab |
| widgets/ | aktif | loket layanan publik | halaman publik, widget, embed, adapter ringan | publik | AI tidak inheren; sebagian hanya tampilan atau bridge |
| wa-caraka/ | aktif | pusat call center otomatis | runtime WhatsApp, health, knowledge, LLM bridge | internal service | capability harus dicek per fitur |
| wa-caraka-admin/ | wrapper | meja resepsionis | pintu masuk aman ke dashboard admin | publik terbatas | bukan engine AI |
| projects/lawangsewu-core-ci4/ | staging | gedung baru yang belum dibuka penuh | calon core portal jangka panjang | internal/staging | AI wajib diberi label capability saat masuk portal |
| gateway/node-service/ | staging | unit eksperimen teknis | service Node tambahan, scraper, worker | internal | bukan pusat AI utama |
| projects/lawangsewu-business-laravel/ | staging | kavling ekspansi | lane aplikasi bisnis masa depan | internal/staging | tergantung implementasi nanti |
| projects/website-pa-semarang/lumpiapasar-base/ | legacy | gedung lama yang kabelnya masih dipakai | app legacy dengan path dan endpoint aktif | campuran/legacy | jangan rename sembarangan |
| archive/, pasarjohar/, Api-Caraka/ | arsip/referensi | gudang dan ruang arsip | referensi, pembanding, snapshot historis | internal pasif | bukan patokan live capability |

## Tabel Ringkas Widget dan Halaman

| Kelompok | Status | Ibaratnya | Fungsi inti | Contoh |
| --- | --- | --- | --- | --- |
| PHP public pages | aktif | loket layanan pengunjung | halaman publik yang langsung dilihat pengguna | `landing.php`, `info-persidangan.php`, `statistik-perkara.php` |
| HTML public helpers | aktif/helper | papan informasi dan alat bantu | embed, helper visual, viewer, layar TV | `biaya-proses-berperkara.html`, `slide_sidang.html`, `server10-data-app.html` |
| PHP API adapters | helper | meja sambungan internal | menjembatani data ke widget atau service lain | `api-server10.php`, `statistik-data.php`, `api-wa-v2.php` |
| Mas Satset Ops | aktif operasional | ruang kontrol | memantau kesehatan runtime dan sinkronisasi | `gateway/mas-satset.php` |
| Mas Satset AI | helper/lab | laboratorium uji | tambah knowledge dan uji jawaban | `gateway/mas-satset-ai.php` |

## Aplikasi Utama

### 1. Lawangsewu root

- status: `aktif`
- lokasi: root repo ini
- fungsi: rumah utama dokumentasi, landing, launcher, gateway, widget, dan integrasi sibling app
- catatan: ini titik orientasi utama, bukan sekadar dump file

### 2. Gateway portal

- status: `aktif`
- lokasi: `gateway/`
- fungsi: login, logout, launcher lintas aplikasi, mapping SSO, dan entrypoint operasional portal
- halaman penting:
  - `index.php`: portal utama dan launcher setelah login
  - `login.php`: pintu masuk auth gateway
  - `dubes-prakom.php`: dashboard operasional Dubes Prakom
  - `mas-satset.php`: dashboard cepat untuk health runtime WA, chat website, kapasitas server, dan sync WordPress knowledge
  - `mas-satset-ai.php`: lab untuk uji chat website dan tambah knowledge FAQ ke `wacaraka_faq`
  - `sso-mapping.php`: peta relasi login portal dengan layanan terkait

### 3. Widget runtime dan halaman publik

- status: `aktif`
- lokasi: `widgets/`
- fungsi: implementasi halaman publik, widget embed, adapter API ringan, helper bridge, dan utility internal
- catatan: ini lapisan praktis yang menggerakkan banyak route publik Lawangsewu

### 4. WA Caraka runtime

- status: `aktif`
- AI capability: verifikasi per fitur; jangan diasumsikan semua jalur sudah `hybrid`
- lokasi: `wa-caraka/`
- fungsi: runtime WhatsApp API, health endpoints, knowledge connector, dan jalur integrasi LLM
- status AI yang perlu selalu diingat:
  - LLM bridge sudah ada
  - capability harus diverifikasi lagi sebelum rollout: masih `prompt-only`, sudah `DB/API-connected`, sudah `RAG-connected`, atau `hybrid`
  - `mas-satset` dan `mas-satset-ai` dipakai untuk observasi dan pengayaan knowledge, bukan asumsi otomatis bahwa AI sudah fully data-connected

### 5. WA Caraka admin wrapper

- status: `wrapper`
- lokasi: `wa-caraka-admin/`
- fungsi: webroot tipis menuju dashboard CI4 admin asli di `wa-caraka/dashboard-ci4-admin/`
- catatan: wrapper ini sengaja tipis agar document root publik tidak menunjuk langsung ke seluruh app CI4 admin

### 6. Lawangsewu Core CI4 staging

- status: `staging`
- lokasi: `projects/lawangsewu-core-ci4/`
- fungsi: staging aman untuk membangun core portal, app registry, widget registry, docs registry, dan boundary modul sebelum cutover final
- catatan: ini kandidat rumah utama jangka panjang, tetapi belum menjadi entrypoint produksi tunggal

### 7. Gateway Node

- status: `staging`
- lokasi: `gateway/node-service/`
- fungsi: workspace Node.js terpisah untuk service tambahan seperti API pengumuman, webhook, atau worker tanpa mengganggu PHP existing
- use case saat ini: agregasi/scraper pengumuman MA dan Badilag

### 8. Laravel sibling app

- status: `staging`
- lokasi: `projects/lawangsewu-business-laravel/`
- fungsi: lane aplikasi bisnis yang lebih besar di masa depan
- catatan: bukan pengganti portal Lawangsewu, tetapi ruang ekspansi workflow bisnis

### 9. Website PA Semarang legacy app

- status: `legacy`
- lokasi: `projects/website-pa-semarang/lumpiapasar-base/`
- fungsi: aplikasi legacy yang masih memegang banyak endpoint, path panjar, dan domain lama yang masih dipakai integrasi
- catatan: perubahan di sini harus sangat hati-hati karena banyak URL, path API, dan perilaku runtime masih aktif

### 10. Area referensi dan arsip pendukung

- status: `arsip`
- `pasarjohar/`: varian/arsip halaman publik dan bahan pembanding
- `Api-Caraka/`: basis library/API WhatsApp terisolasi untuk referensi teknis
- `archive/`: arsip pasif; jangan dibersihkan atau dinormalisasi sembarangan
- `Walkthrough-DBPrakom/`: walkthrough dan bundle publikasi dokumen pendukung

## Widget dan Halaman Publik

### A. Halaman publik PHP utama

- status kelompok: `aktif`
- `widgets/views/php/public/landing.php`: landing page utama Lawangsewu dan pintu masuk portal
- `widgets/views/php/public/info-persidangan.php`: tampilan informasi jadwal persidangan publik
- `widgets/views/php/public/monitor-antrian-sidang.php`: monitor antrian sidang untuk layar publik
- `widgets/views/php/public/antrian-sidang.php`: tampilan antrian sidang publik
- `widgets/views/php/public/pa-semarang-pengumuman.php`: halaman pengumuman peradilan
- `widgets/views/php/public/pa-semarang-pengumuman-embed.php`: varian embed untuk pengumuman
- `widgets/views/php/public/widget-pengumuman-rss.php`: widget feed pengumuman berbasis RSS
- `widgets/views/php/public/statistik-perkara.php`: landing/statistik perkara untuk laporan dan mode TV
- `widgets/views/php/public/statistik-hakim.php`: tampilan statistik per hakim
- `widgets/views/php/public/statistik-ecourt.php`: tampilan statistik e-Court
- `widgets/views/php/public/biaya-radius-ghaib.php`: halaman kalkulator/adapter biaya radius ghaib
- `widgets/views/php/public/tabel-radius-kecamatan.php`: tabel radius kecamatan untuk referensi biaya/perkara
- `widgets/views/php/public/jadwal-persidangan-admin.php`: halaman admin/operasional untuk jadwal persidangan

### B. Halaman HTML publik dan helper

- status kelompok: campuran `aktif` dan `helper`
- `widgets/views/html/public/agenda-kegiatan.html`: halaman agenda kegiatan
- `widgets/views/html/public/berita-pengadilan.html`: halaman berita pengadilan
- `widgets/views/html/public/biaya-proses-berperkara.html`: landing kalkulator biaya perkara melalui iframe legacy
- `widgets/views/html/public/server10-data-app.html`: helper bridge untuk uji endpoint Server 10/panjar legacy
- `widgets/views/html/public/slide_sidang.html`: output slide sidang/TV display
- `widgets/views/html/public/wa-v2-qr-viewer.html`: viewer QR dan status integrasi WA v2
- `widgets/views/html/public/pa-semarang-embed-snippet.html`: snippet embed untuk integrasi web lain

### C. Adapter API widget

- status kelompok: `helper`
- `widgets/views/php/api/api-pengumuman.php`: adapter data pengumuman
- `widgets/views/php/api/api-pengumuman-rss.php`: adapter feed RSS pengumuman
- `widgets/views/php/api/api-server10.php`: bridge terkontrol ke endpoint Server 10/legacy
- `widgets/views/php/api/api-wa-v2.php`: adapter ke runtime WA v2
- `widgets/views/php/api/jadwal-persidangan-api.php`: API data jadwal persidangan
- `widgets/views/php/api/statistik-data.php`: sumber data statistik perkara untuk dashboard/landing statistik

## Halaman Mas Satset

### `gateway/mas-satset.php`

- status: `aktif` untuk operasional
- fokus: dashboard cepat untuk operator
- isi utama: health runtime WA, health chat website, kapasitas server, model Ollama aktif, status sync WordPress knowledge, dan tombol uji cepat
- cocok dipakai saat ingin menjawab pertanyaan: apakah runtime sehat dan apakah chat website masih nyambung

### `gateway/mas-satset-ai.php`

- status: `helper` untuk knowledge dan uji AI
- AI capability: jangan dianggap final; ini lebih mirip laboratorium pengujian daripada loket jawaban resmi penuh
- fokus: lab knowledge dan uji jawaban
- isi utama: form tambah knowledge baru, daftar knowledge `wacaraka_faq`, statistik knowledge aktif/nonaktif, dan pengujian prompt ke chat website
- cocok dipakai saat ingin menambah basis jawaban atau memvalidasi jawaban AI sebelum knowledge dipublish lebih luas

## Artefak Runtime yang Tidak Boleh Jadi Patokan Struktur

- file seperti `error_log` dan `PHP_errors.log` di source tree legacy adalah artefak runtime, bukan bagian struktur aplikasi yang harus dipelihara di Git
- log aktif sebaiknya dipusatkan ke direktori runtime seperti `logs/` atau `writable/logs/`, bukan tinggal di dalam source tree aplikasi

## Checklist Penambahan Aplikasi atau Widget Baru

Saat ada komponen baru, tambahkan minimal informasi berikut ke dokumen ini:

1. nama komponen
2. lokasi folder atau file utamanya
3. fungsi singkatnya untuk apa
4. apakah ini publik, internal, staging, sibling app, atau arsip
5. apakah ada dependency penting ke DB, API, WA runtime, atau LLM
6. apakah capability AI-nya masih prompt-only atau sudah benar-benar data-connected

## Pengingat Penting

- jangan anggap semua yang berlabel AI otomatis sudah `DB/API/RAG-connected`
- cek capability aktual sebelum training, rollout, atau briefing operator
- jika ragu, verifikasi dulu lewat health endpoint, config runtime, dan basis data/connector yang benar-benar aktif