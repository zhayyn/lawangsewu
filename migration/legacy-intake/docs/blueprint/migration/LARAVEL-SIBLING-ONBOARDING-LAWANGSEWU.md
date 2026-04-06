# Laravel Sibling Onboarding Lawangsewu

Tanggal acuan: 2026-03-15

Dokumen ini mencatat bahwa starter Laravel sibling app untuk fase berikutnya sudah disiapkan secara nyata di:

- `projects/lawangsewu-business-laravel/`

## Status

Yang selesai pada tahap ini:

- skeleton Laravel nyata sudah dibuat
- dependency awal sudah terpasang lewat Composer
- route `/` dipakai sebagai halaman onboarding sibling app
- route `/health` tersedia untuk smoke awal
- env dan config khusus Lawangsewu sudah ditambahkan
- boundary portal versus aplikasi bisnis sudah ditulis di README app
- domain bisnis pertama sudah dipilih: `helpdesk/ticketing internal`
- modul awal helpdesk tickets sudah tersedia di Laravel starter

Yang belum sengaja belum diaktifkan:

- SSO trust produksi
- signed token verification
- database bisnis final
- modul bisnis final
- launcher final dari CI4 Core ke app ini

Catatan runtime saat ini:

- karena `pdo_sqlite` tidak tersedia di PHP CLI server ini dan database MySQL khusus belum bisa diprovision dengan kredensial yang tersedia, modul helpdesk awal memakai mode `staged-file`
- model dan migration database tetap disiapkan untuk fase provisioning database final

## Boundary yang Harus Dijaga

- CI4 Core tetap menjadi portal utama, auth gateway, launcher, registry, dan katalog
- Laravel ini dipakai untuk domain bisnis yang lebih besar dan lebih mandiri
- WA Caraka tetap sibling app terpisah

## Kontrak Minimal untuk Tahap Berikutnya

1. pilih domain bisnis pertama yang memang pantas Laravel
2. tentukan DB sendiri untuk app ini
3. pasang middleware trust dari portal Lawangsewu
4. daftarkan launcher final dari CI4 Core setelah URL, role, dan policy jelas

## Catatan Operasional

Jangan menyebut Laravel sibling app ini sudah selesai produksi.

Status yang akurat adalah: starter Laravel untuk gelombang berikutnya sudah siap, tetapi aplikasi bisnis finalnya belum dipilih dan integrasi portalnya belum di-hardening.