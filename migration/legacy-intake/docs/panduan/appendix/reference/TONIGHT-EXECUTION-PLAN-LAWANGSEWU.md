# Tonight Execution Plan Lawangsewu

Dokumen ini memecah pekerjaan menjadi target yang realistis untuk dikerjakan malam ini tanpa merusak sistem aktif.

## Status Dokumen Saat Ini

Sebagian target inti dokumen ini sudah tercapai pada level fondasi:

- boundary arsitektur sudah dibekukan
- pemilahan modul dan matriks migrasi sudah tersedia
- skeleton CI4 Core staging sudah hidup
- sebagian shadow dan verifikasi portal sudah berjalan

Namun dokumen ini masih relevan karena proses migrasinya sendiri **belum selesai penuh**. Jadi isi dokumen ini sekarang dibaca sebagai guardrail eksekusi lanjutan, bukan sekadar rencana malam pertama.

## Target Malam Ini

Target malam ini bukan migrasi penuh.

Target malam ini adalah menyiapkan rel migrasi yang aman:

- boundary final disepakati
- modul dipilah
- urutan migrasi dibekukan
- area yang tidak boleh disentuh ditandai

## Hasil yang Harus Selesai Malam Ini

### 1. Boundary arsitektur final

Harus jelas:

- Lawangsewu = CI4 Core
- Laravel = sibling app untuk domain bisnis baru
- WA Caraka = sibling app tetap

### 2. Matriks folder aktif

Harus jelas:

- mana yang masuk CI4 Core
- mana yang tetap sibling
- mana yang belum boleh dipindah

### 3. Daftar gelombang migrasi

Harus jelas:

- gelombang 1: portal/auth/registry/docs
- gelombang 2: widget publik ringan
- gelombang 3: endpoint kecil dan integrasi lanjutan
- gelombang 4: onboarding Laravel sibling app

### 4. Label AI capability awal

Minimal tiap fitur AI diberi status sementara:

- prompt-only
- DB/API-connected
- RAG-connected
- perlu verifikasi

## Yang Bisa Dikerjakan Malam Ini dengan Aman

### Paket A. Desain boundary

- sahkan blueprint arsitektur
- sahkan migration matrix
- tetapkan naming resmi untuk CI4 Core

### Paket B. Persiapan struktur target

- siapkan nama modul CI4 Core
- siapkan daftar route utama yang nanti dipertahankan
- siapkan daftar compatibility route yang tidak boleh putus

### Paket C. Persiapan Laravel sibling app

- tentukan aplikasi bisnis pertama yang memang layak Laravel
- tentukan apakah butuh DB sendiri
- tentukan apakah perlu SSO langsung dari portal

## Yang Sebaiknya Tidak Dikerjakan Malam Ini

- rewrite total gateway sekaligus
- memindahkan WA Caraka ke dalam portal
- mengubah banyak entrypoint aktif serentak
- memindahkan script system/cron tanpa dependency tracing
- mengubah autentikasi lintas aplikasi tanpa smoke test

## Rencana Kerja Besok Pagi

Jika malam ini selesai, besok pagi mulai dari sini:

1. bentuk skeleton CI4 Core
2. migrasikan auth gateway ke modul baru
3. migrasikan portal dan app registry
4. migrasikan widget registry dan docs registry
5. pasang compatibility routes

## Status Lanjutan yang Paling Akurat

Kalau diringkas terhadap kondisi terbaru:

- target fondasi: sudah cukup tercapai
- target staging CI4 Core: sudah berjalan
- target cutover produksi penuh: belum tercapai
- target Laravel sibling app nyata: belum dimulai sebagai implementasi final
- target label AI capability: harus terus diverifikasi sebelum rollout

## Perumpamaan Praktis

Malam ini bukan malam memindahkan seluruh isi kota.

Malam ini adalah malam menggambar peta jalan, memasang pagar proyek, memberi label gedung, dan menentukan pintu mana yang tetap dibuka besok pagi.

Kalau itu dilakukan dengan benar, migrasi esok hari menjadi terkontrol.
Kalau itu dilewati, migrasi cepat justru berubah jadi bongkar kota tanpa rambu.

## Keputusan Akhir untuk Malam Ini

Kalimat kerja yang harus dipegang:

- bekukan boundary dulu
- migrasikan rumah utama dulu
- jangan pindahkan gedung besar malam ini
- semua fitur AI wajib diberi label kemampuan sebelum diumumkan