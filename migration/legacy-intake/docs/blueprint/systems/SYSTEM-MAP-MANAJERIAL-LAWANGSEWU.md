# Peta Manajerial Lawangsewu

Dokumen ini dibuat untuk pembaca non-teknis agar cepat paham apa yang sedang berdiri di dalam Lawangsewu, tanpa harus membaca struktur folder satu per satu.

## Gambaran Besar

Bayangkan Lawangsewu seperti satu kawasan layanan besar.

- ada gedung depan untuk menerima orang dan mengarahkan mereka
- ada loket-loket layanan yang melayani kebutuhan publik
- ada pusat mesin dan operator di belakang layar
- ada bangunan lama yang masih menyuplai jalur penting
- ada gedung baru yang sedang dibangun tetapi belum dibuka penuh
- ada gudang arsip yang disimpan untuk referensi, bukan untuk operasional harian

Jadi repo ini bukan satu aplikasi tunggal yang sederhana. Ini lebih mirip kawasan yang terdiri dari beberapa bangunan dengan fungsi berbeda.

## Siapa Melakukan Apa

### 1. Gedung pusat: Lawangsewu root

Ini adalah halaman depan kawasan.

- fungsinya: menjadi rumah utama dokumen, peta sistem, skrip operasional, dan titik orientasi semua komponen
- perumpamaan: seperti kantor pusat yang menyimpan peta kawasan, SOP, dan daftar tenant

### 2. Front office: gateway

Ini adalah pintu masuk resmi untuk operator dan pengguna tertentu.

- fungsinya: login, logout, portal, launcher, dan pemetaan akses layanan
- perumpamaan: seperti lobi utama dan meja resepsionis yang mengarahkan orang ke ruangan yang benar

Halaman penting di sini:

- `mas-satset.php`: ruang kontrol cepat untuk mengecek kesehatan sistem
- `mas-satset-ai.php`: laboratorium kecil untuk menguji jawaban AI dan menambah knowledge

## Loket layanan publik: widgets

Ini adalah bagian yang paling sering terlihat langsung oleh publik.

- fungsinya: halaman persidangan, statistik, pengumuman, embed, dan widget tampilan
- perumpamaan: seperti deretan loket layanan dan papan informasi digital di area depan

Contoh layanan yang masuk kelompok ini:

- informasi persidangan
- monitor antrian sidang
- statistik perkara
- halaman pengumuman
- widget embed untuk website lain

## Mesin belakang layar: WA Caraka

Ini adalah pusat mesin komunikasi otomatis berbasis WhatsApp.

- fungsinya: runtime WhatsApp, health endpoint, knowledge connector, dan jembatan ke LLM
- perumpamaan: seperti call center otomatis di ruang belakang yang bekerja untuk mengirim dan memproses percakapan

Yang penting dipahami:

- tidak semua yang berlabel AI di sini otomatis sudah cerdas penuh
- sebagian masih bisa bersifat `prompt-only`
- sebagian bisa sudah membuka data atau knowledge base
- jadi sebelum rollout, selalu cek dulu capability aktualnya

## Pintu admin: WA Caraka admin wrapper

Ini bukan kantor admin sebenarnya, hanya pintu masuk amannya.

- fungsinya: membuka dashboard admin tanpa menaruh seluruh aplikasi admin langsung di pintu publik
- perumpamaan: seperti meja security yang mengantar orang ke ruang admin di gedung belakang

## Gedung baru yang sedang dibangun: Lawangsewu Core CI4 staging

Ini adalah calon rumah utama jangka panjang, tetapi belum resmi jadi kantor pusat operasional.

- fungsinya: menyiapkan portal inti, registri aplikasi, registri widget, dan boundary modul
- perumpamaan: seperti gedung baru yang struktur, jalur listrik, dan petunjuk ruangannya sudah dibuat, tetapi belum grand opening

## Unit eksperimen teknis: gateway-node

Ini adalah ruang teknis tambahan untuk kebutuhan Node.js.

- fungsinya: service tambahan seperti scraper pengumuman, webhook, atau worker
- perumpamaan: seperti bengkel kecil atau unit riset yang membantu kantor pusat, bukan gedung utama yang dilihat publik

## Kavling ekspansi bisnis: Laravel sibling app

Ini adalah ruang yang disiapkan untuk aplikasi bisnis lebih besar di masa depan.

- fungsinya: lane ekspansi workflow bisnis
- perumpamaan: seperti lahan yang sudah dibeli untuk gedung baru, tetapi belum jadi pusat aktivitas saat ini

## Bangunan lama yang masih menyuplai jalur: app legacy Lumpiapasar

Ini bagian yang paling mudah disalahpahami.

- fungsinya: masih memegang path, endpoint, dan perilaku lama yang ternyata masih dipakai integrasi aktif
- perumpamaan: seperti gedung lama yang kabel telepon, pipa, atau jalur listriknya masih terhubung ke banyak tempat

Maknanya:

- namanya boleh terasa lama
- tampilannya boleh terasa tua
- tetapi tidak boleh dibongkar atau di-rename gegabah, karena banyak layanan lain masih bergantung padanya

## Gudang arsip: archive, pasarjohar, Api-Caraka

Ini bukan pusat operasi harian.

- fungsinya: menyimpan referensi, pembanding, snapshot, dan bahan sejarah teknis
- perumpamaan: seperti gudang arsip, ruang penyimpanan blueprint lama, dan ruang sampel

Maknanya:

- jangan dipakai sebagai acuan utama membaca sistem aktif
- jangan dibersihkan sembarangan
- tetapi tetap penting saat perlu menelusuri asal-usul keputusan teknis

## Cara Membaca Status dengan Bahasa Sederhana

- `aktif`: ini ruangan kerja harian
- `staging`: ini ruangan uji coba yang belum dibuka penuh
- `legacy`: ini bangunan lama yang masih menyuplai kebutuhan penting
- `arsip`: ini gudang referensi
- `wrapper`: ini pintu masuk kecil ke ruangan lain yang lebih besar
- `helper`: ini alat bantu kerja, bukan layanan utama

## Cara Memahami AI dengan Bahasa Sederhana

Bayangkan AI seperti petugas informasi.

- `prompt-only`: petugas menjawab dari hafalan atau catatan singkat yang dibawanya
- `DB/API-connected`: petugas boleh menelepon bagian lain untuk cek data langsung
- `RAG-connected`: petugas boleh membuka lemari arsip atau knowledge base sebelum menjawab
- `hybrid`: petugas menggabungkan hafalan, telepon ke sistem, dan pencarian arsip sekaligus

Karena itu, label AI tidak cukup dilihat dari tampilan halaman. Harus dicek apakah petugas itu benar-benar punya akses ke data atau hanya sedang berbicara dari instruksi umum.

## Kesimpulan Praktis

Kalau disederhanakan:

- `gateway/` adalah lobi dan front office
- `widgets/` adalah loket layanan publik
- `wa-caraka/` adalah mesin komunikasi otomatis di belakang layar
- `wa-caraka-admin/` adalah pintu admin
- `projects/lawangsewu-core-ci4/` adalah gedung baru yang sedang dipersiapkan
- `projects/website-pa-semarang/lumpiapasar-base/` adalah gedung lama yang masih memegang jalur penting
- `archive/` dan kawan-kawan adalah gudang arsip

Jika nanti ada aplikasi baru, cara paling aman adalah menanyakan tiga hal ini lebih dulu:

1. ini gedung utama, ruang uji coba, bangunan lama, atau gudang arsip?
2. ini dipakai publik, operator, atau hanya teknisi?
3. kalau ada AI, dia benar-benar tersambung ke data atau baru sekadar pintar bicara?