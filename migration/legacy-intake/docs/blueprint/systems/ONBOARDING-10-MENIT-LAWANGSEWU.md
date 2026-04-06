# Onboarding 10 Menit Lawangsewu

Dokumen ini untuk orang baru yang perlu cepat paham kawasan Lawangsewu tanpa tenggelam dalam detail teknis sejak awal.

## Target 10 Menit

Setelah membaca dokumen ini, pembaca idealnya paham:

1. mana area aktif, staging, legacy, dan arsip
2. gateway itu untuk apa
3. widgets itu untuk apa
4. WA Caraka itu untuk apa
5. mengapa app legacy tidak boleh dibongkar gegabah
6. mengapa capability AI harus selalu diverifikasi

## Menit 1 sampai 2: Pahami Kawasannya Dulu

Bayangkan repo ini seperti satu kawasan layanan.

- `gateway/` adalah lobi dan front office
- `widgets/` adalah loket layanan publik
- `wa-caraka/` adalah ruang mesin komunikasi otomatis
- `wa-caraka-admin/` adalah pintu admin
- `projects/lawangsewu-core-ci4/` adalah gedung baru yang sedang disiapkan
- `projects/website-pa-semarang/lumpiapasar-base/` adalah gedung lama yang kabelnya masih dipakai
- `archive/` dan area referensi lain adalah gudang arsip

Kalau sudah paham analogi ini, membaca dokumen lain jadi jauh lebih mudah.

## Menit 3 sampai 4: Kenali Area Aktif

Fokus dulu ke tiga area ini:

### 1. gateway/

- fungsi: login, portal, launcher, mapping akses
- kalau rusak: operator bisa kehilangan jalur masuk

### 2. widgets/

- fungsi: halaman publik, statistik, pengumuman, embed, helper visual
- kalau rusak: dampaknya langsung kelihatan ke publik atau layar operasional

### 3. wa-caraka/

- fungsi: runtime WhatsApp, health endpoint, knowledge, dan LLM bridge
- kalau rusak: komunikasi otomatis dan fitur AI operasional bisa terganggu

## Menit 5 sampai 6: Kenali Area yang Sering Disalahpahami

### projects/lawangsewu-core-ci4/

- ini penting, tetapi belum otomatis berarti live production utama
- anggap sebagai gedung baru yang sedang dipersiapkan

### projects/website-pa-semarang/lumpiapasar-base/

- namanya legacy, tetapi masih penting
- anggap sebagai gedung lama yang pipa dan kabelnya masih tersambung ke banyak layanan
- jadi jangan ubah nama, folder, atau path tanpa tracing referensi dulu

### wa-caraka-admin/

- ini bukan dashboard admin utamanya
- ini hanya pintu depan yang mengantar ke dashboard admin asli

## Menit 7 sampai 8: Pahami AI dengan Bahasa Sederhana

Jangan langsung percaya bahwa fitur AI sudah pintar karena namanya terlihat canggih.

Pakai cara baca ini:

- `prompt-only`: baru seperti petugas yang bicara dari hafalan
- `DB/API-connected`: sudah bisa cek data langsung
- `RAG-connected`: sudah bisa buka knowledge base atau arsip
- `hybrid`: sudah bisa gabungkan semuanya

Aturan penting:

- sebelum rollout, training, atau briefing, cek capability aktualnya
- jangan asumsi dari UI atau nama halaman
- lihat health, config, connector, dan sumber data yang benar-benar aktif

## Menit 9: Dokumen yang Wajib Dikenal

Buka urutan ini kalau ingin lanjut:

1. `README.md`
2. `docs/appendix/systems/SYSTEM-MAP-MANAJERIAL-LAWANGSEWU.md`
3. `docs/appendix/systems/APPS-WIDGETS-INVENTORY-LAWANGSEWU.md`
4. `docs/appendix/systems/EXECUTIVE-COMPONENT-STATUS-LAWANGSEWU.md`
5. `docs/appendix/systems/SYSTEMS-ONEPAGE-LAWANGSEWU.md`

## Menit 10: Tiga Pertanyaan Sebelum Menyentuh Apa Pun

Sebelum mengubah file atau sistem, tanyakan:

1. ini area aktif, staging, legacy, atau arsip?
2. ini jalur publik, operator, atau helper internal?
3. kalau ada AI, dia sungguh terhubung data atau baru sekadar prompt-only?

Kalau tiga pertanyaan itu belum terjawab, berarti belum waktunya mengubah komponen tersebut.