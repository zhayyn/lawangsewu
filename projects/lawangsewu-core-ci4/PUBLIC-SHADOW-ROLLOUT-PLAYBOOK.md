# Public Shadow Rollout Playbook

Dokumen ini adalah panduan eksekusi untuk family publik yang sudah `ready-for-shadow`.

Targetnya bukan cutover penuh sekaligus.
Targetnya adalah membandingkan, memutuskan, lalu memindahkan family publik satu lapis demi satu lapis dengan rollback yang jelas.

## Family yang Masuk Scope Playbook Ini

- `landing`
- `docs`
- `widget-directory`
- `public-widget`
- `compat-alias` sebagai lapisan kompatibilitas setelah family kanonik stabil

Family berikut tidak masuk playbook ini:

- `portal`
- `portal-launch`
- `gateway`
- `publicApiRoutes`
- `integrationContracts`

## Artefak yang Harus Dicek Sebelum Mulai

1. `php ROUTE-COVERAGE-REPORT.php`
2. `php ROUTE-FAMILY-READINESS.php`
3. `php PUBLIC-SHADOW-COMPARISON.php`
4. `ROUTE-FAMILY-CUTOVER-MATRIX.md`

## Aturan Baca Verdict di Public Shadow Comparison

- `safe`: status live dan staging sama, delta HTML relatif kecil, aman untuk shadow awal
- `review`: status sama tetapi delta cukup besar, perlu review visual/fungsi sebelum maju
- `hold`: status berbeda atau delta sangat besar, jangan dipakai untuk shadow dulu

## Urutan Rollout yang Direkomendasikan

1. `docs`
2. `widget-directory`
3. `public-widget` yang verdict-nya `safe`
4. `landing`
5. `public-widget` yang masih `review`
6. `compat-alias` tetap dibiarkan 302 ke target kanonik

Alasan urutan ini:

- `docs` dan `widget-directory` paling mudah diverifikasi
- widget publik sudah lolos kontrak, tetapi tidak semua delta HTML kecil
- `landing` sengaja tidak paling awal karena perbedaan visualnya paling besar terhadap runtime aktif

## Prosedur Shadow per Family

### 1. Bekukan baseline

- jalankan `php PUBLIC-SHADOW-COMPARISON.php`
- simpan hasil verdict terakhir
- pastikan tidak ada route dengan status `hold` di family yang mau dipindah

### 2. Pilih family kecil dulu

- mulai dari `docs`
- lanjut ke `widget-directory`
- baru pindah ke subset `public-widget`

### 3. Lakukan shadow, bukan cutover besar

- jangan ganti semua rewrite sekaligus
- pindahkan satu family per iterasi
- setelah tiap iterasi, ulangi smoke route dan comparison

### 4. Pertahankan alias

- alias lama tetap 302 ke route kanonik
- jangan buka dua sumber render final dari route alias

### 5. Siapkan rollback sederhana

- rollback berarti mengembalikan family itu ke runtime aktif sebelumnya
- jangan gabung banyak family dalam satu langkah rollback

## Kriteria Naik dari Shadow ke Kandidat Cutover

Sebuah family publik baru boleh dipertimbangkan untuk cutover lebih permanen jika:

1. `ROUTE-COVERAGE-REPORT.php` tetap `missing=0`
2. `ROUTE-FAMILY-READINESS.php` tetap menunjukkan `ready-for-shadow`
3. `PUBLIC-SHADOW-COMPARISON.php` tidak menunjukkan `hold` pada family tersebut
4. perbedaan visual yang tersisa memang disengaja, bukan bug fungsional

## Hold Rules

Jangan lanjutkan shadow untuk route atau family jika salah satu kondisi ini muncul:

- status live dan staging berbeda
- route yang sebelumnya 200 berubah menjadi redirect atau sebaliknya
- alias lama tidak lagi menuju target kanonik
- perbedaan HTML besar ternyata menutupi konten penting yang hilang

## Keputusan Praktis Saat Ini

- `docs` sudah aktif sebagai shadow family pertama melalui `/walkthrough`
- `widget-directory` sudah membaik dari `hold` ke `review`, tetapi belum boleh dianggap `safe`
- family `public-widget` perlu dipilah lagi antara `safe` dan `review`
- `landing` harus dianggap family publik khusus karena delta HTML-nya besar walau status sama