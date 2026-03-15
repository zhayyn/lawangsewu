# Morning Handoff 2026-03-13 08:33 +07

Dokumen ini adalah handoff eksekusi untuk 3 jam setelah checkpoint terakhir.

Checkpoint dibuat pada:

- sekarang: `2026-03-13 05:33:14 +07`
- target lanjut: `2026-03-13 08:33:14 +07`

## Tujuan Slot Berikutnya

Tujuan slot ini bukan migrasi besar baru.

Tujuannya:

1. cek apakah subset family publik sudah layak masuk shadow awal
2. cek apakah login live gateway bisa dibuktikan sekali
3. putuskan apakah portal tetap `shadow-guarded` atau boleh naik ke kandidat cutover penuh

## Jalankan dari Folder Ini

`/var/www/html/lawangsewu/projects/lawangsewu-core-ci4`

## Urutan Eksekusi

### 1. Validasi baseline staging

Jalankan:

```bash
php ROUTE-COVERAGE-REPORT.php
php ROUTE-FAMILY-READINESS.php
php PUBLIC-SHADOW-COMPARISON.php
```

Lanjut hanya jika:

- `ROUTE-COVERAGE-REPORT.php` tetap `missing=0`
- `ROUTE-FAMILY-READINESS.php` tetap menunjukkan `portal` dan `portal-launch` minimal `ready-for-shadow-auth`
- `PUBLIC-SHADOW-COMPARISON.php` tidak menunjukkan perubahan status tak terduga

### 2. Pilih subset shadow publik paling aman

Prioritas pertama:

- `docs`

Prioritas kedua:

- `widget-directory`

Jangan mulai dari:

- `landing`
- route publik yang verdict-nya masih `hold`

### 3. Verifikasi login live gateway satu kali

Tujuan langkah ini hanya satu:

- membuktikan bahwa portal live bisa masuk dengan sesi nyata dan return path tetap sehat

Minimal yang harus diverifikasi:

1. login gateway berhasil
2. akses `/lawangsewu/portal` setelah login berhasil
3. logout kembali normal
4. tidak ada redirect berantai yang rusak

Jika login live gagal lagi:

- jangan naikkan portal ke cutover penuh
- pertahankan status `shadow-guarded`
- catat bahwa blocker tetap pada kredensial live atau integrasi login nyata

### 4. Jika dan hanya jika login live lolos

Maka keputusan boleh naik satu tingkat:

- `portal` dan `portal-launch` berubah dari `ready-for-shadow-auth` menjadi kandidat `full-cutover-review`

Status slot ini:

- login live gateway `lolos`
- portal dan portal-launch sudah layak dianggap `full-cutover-review`

Tetap jangan langsung cutover produksi penuh pada slot yang sama.

### 5. Verifikasi AI capability sebelum ada pengumuman

Sebelum ada rollout, training, atau positioning fitur AI:

- cek lagi apakah fitur AI masih `prompt-only`, sudah `DB/API-connected`, atau sudah `RAG-connected`
- jangan biarkan pihak lain mengira AI sudah tersambung DB/API/RAG kalau belum

## Go / No-Go Cepat

### Go untuk shadow publik awal

Jika terpenuhi:

- `docs` tetap `safe`
- tidak ada perubahan status route menjadi `hold`
- alias lama tetap normal

### No-Go untuk portal full cutover

Jika salah satu terjadi:

- login live gateway gagal
- `/portal` hanya lolos pada smoke sintetis, bukan sesi nyata
- logout live masih meragukan

## Output yang Harus Dicatat Setelah Slot Ini

Minimal tulis ringkas:

1. hasil `ROUTE-COVERAGE-REPORT.php`
2. hasil `ROUTE-FAMILY-READINESS.php`
3. route publik mana yang masih `safe`, `review`, `hold`
4. login live gateway: `lolos` atau `gagal`
5. keputusan akhir: `shadow publik lanjut` atau `tahan dulu`

## Keputusan Default Jika Tidak Yakin

Kalau ada keraguan di slot berikutnya, pakai keputusan default ini:

- lanjutkan hanya `docs`
- tahan `landing`
- tahan `portal` full cutover
- jangan ubah alias lama