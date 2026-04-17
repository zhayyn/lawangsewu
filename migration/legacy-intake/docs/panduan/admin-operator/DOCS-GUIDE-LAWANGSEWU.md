# Docs Guide Lawangsewu

Panduan ini merangkum jalur baca dokumentasi root agar pembaca tidak perlu membuka terlalu banyak file untuk memahami status dan struktur proyek.

## Paket Resmi

Jika ingin jalur baca paling aman dan ringkas, mulai dari `OFFICIAL-DOCS-SET-LAWANGSEWU.md`.

Paket resmi sekarang sudah dikonsolidasikan menjadi satu naskah final:

1. `docs/laporan/final/LAPORAN-FINAL-LAWANGSEWU-PKAPP-M-AGUS-HAYYUDIN.pdf`

Jika butuh mengakses dokumen lama sebagai lampiran, gunakan `APPENDIX-INDEX-LAWANGSEWU.md`.

## Jalur Baca 4 Dokumen

Untuk pembacaan paling efisien, cukup baca empat dokumen ini:

1. `docs/appendix/migration/MIGRATION-ONEPAGE-LAWANGSEWU.md`
2. `docs/appendix/systems/ROUTE-INVENTORY-LAWANGSEWU.md`
3. `docs/appendix/systems/SYSTEMS-ONEPAGE-LAWANGSEWU.md`
4. `docs/appendix/security/SECURITY-GUIDE-LAWANGSEWU.md`

Maknanya:

- dokumen 1 memberi ringkasan status migrasi dan batas klaim yang aman
- dokumen 2 membekukan URL dan kontrak route yang harus dijaga
- dokumen 3 menjelaskan komponen utama dan boundary sistem
- dokumen 4 merangkum jalur baca keamanan

Jika butuh versi status formal yang lebih eksplisit, lanjutkan ke `docs/appendix/migration/MIGRATION-STATUS-EXECUTIVE-LAWANGSEWU.md`.

## Dokumen Tambahan Saat Diperlukan

- detail penuh migrasi dan timeline keputusan: `docs/appendix/migration/MIGRATION-REPORT-DETAIL-LAWANGSEWU.md`
- paket resmi dokumen inti: `OFFICIAL-DOCS-SET-LAWANGSEWU.md`
- boundary Laravel sibling app: `docs/appendix/migration/LARAVEL-SIBLING-ONBOARDING-LAWANGSEWU.md`
- klasifikasi capability AI yang aman dikomunikasikan: `docs/appendix/reference/AI-CAPABILITY-REGISTER-LAWANGSEWU.md`
- matriks pemilahan CI4 vs sibling app: `MIGRATION-MATRIX-LAWANGSEWU.md`
- blueprint besar migrasi: `MIGRATION-BLUEPRINT-LAWANGSEWU-CI4-LARAVEL.md`
- ringkasan aktivasi shadow per family: `docs/appendix/systems/SHADOW-GUIDE-LAWANGSEWU.md`
- indeks HTML dokumentasi: `DOCS-INDEX-LAWANGSEWU.html`
- status archive WhatsApp legacy: `archive/whatsapp-legacy-20260306/README.md`
- indeks appendix dan arsip: `APPENDIX-INDEX-LAWANGSEWU.md`

## Dokumen yang Sifatnya Spesifik

Dokumen berikut tidak wajib dibaca semua orang. Buka hanya jika memang relevan dengan tugas:

- `docs/appendix/systems/PORTAL-SHADOW-CHANGELOG.md`
- `docs/appendix/systems/LANDING-SHADOW-CHANGELOG.md`
- `docs/appendix/systems/PUBLIC-WIDGET-SHADOW-CHANGELOG.md`
- `docs/appendix/systems/WIDGET-DIRECTORY-SHADOW-CHANGELOG.md`
- `docs/appendix/systems/APP-REGISTRY-SHADOW-CHANGELOG.md`
- `docs/appendix/security/SECRET-ROTATION.md`
- `docs/appendix/security/SECURITY-HARDENING.md`
- `docs/appendix/security/CLOUDFLARE-CHECKLIST.md`
- `docs/appendix/security/CLOUDFLARE-ACCESS-ADMIN-PLAYBOOK.md`
- `docs/appendix/security/BACKUP-RECOVERY.md`

## Ringkasan Status Saat Ini

- Lawangsewu sudah resmi diposisikan sebagai `CI4 Core` pada level arsitektur
- WA Caraka tetap sibling app
- Laravel dipakai sebagai lane aplikasi bisnis, bukan pengganti portal utama
- target migrasi aktif pada batch ini sudah terpublish
- dirty marker root yang tersisa hanya archive legacy pasif

## Standar Penamaan Dokumen Ringkas

Untuk menjaga root tetap profesional dan mudah dipindai, dokumen ringkas memakai pola berikut:

- `*-ONEPAGE-LAWANGSEWU.md` untuk ringkasan satu halaman
- `*-GUIDE-LAWANGSEWU.md` untuk panduan baca atau indeks topik
- `*-STATUS-*.md` untuk status resmi
- `*-REPORT-DETAIL-*.md` untuk dokumen mendalam

Dokumen lama yang lebih detail tetap dipertahankan jika masih berguna, tetapi pembaca diarahkan dulu ke lapisan ringkas.

Semua dokumen lama di root sekarang sebaiknya dibaca sebagai appendix, bukan sebagai pintu masuk utama.

## Rule Membaca Dokumen dengan Efisien

- mulai dari ringkasan, bukan dari changelog
- buka route inventory sebelum memindah URL atau endpoint
- cek systems one page sebelum menyatukan komponen yang seharusnya tetap terpisah
- cek AI capability register sebelum mengklaim koneksi AI ke DB, API, atau RAG
- buka shadow guide dulu sebelum masuk ke changelog family yang detail
- buka security guide dulu sebelum menyebar ke runbook keamanan yang lebih spesifik