# lawangsewu

Project for lawangsewu

## Deploy lumpiapasar-s9

Gunakan script berikut untuk sinkronisasi source ke folder active:

- Cek perubahan (aman): `./scripts/deploy-lumpiapasar-s9.sh`
- Eksekusi deploy: `./scripts/deploy-lumpiapasar-s9.sh --apply`

## Struktur endpoint Lawangsewu

- URL publik tetap sama (contoh: `/info-persidangan`, `/monitor-antrian-sidang`)
- Implementasi file sekarang dipusatkan di `widgets/views/`
- Akses URL langsung ke `widgets/views/*` diblokir (`403`)
- Untuk eksekusi CLI generator: `php widgets/views/generate_slide.php`

<!-- autocommit test -->
<!-- autocommit test 2 -->
<!-- autocommit test 3 -->
