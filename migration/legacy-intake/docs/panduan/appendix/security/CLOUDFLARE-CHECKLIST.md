# Checklist Rule Cloudflare Wajib (Lawangsewu)

Dokumen ini jadi checklist agar kasus `Managed Challenge / 403` pada halaman monitor tidak terulang.

## 0) WA Caraka Admin juga wajib dibebaskan dari challenge

Saat ini path berikut juga terdeteksi kena `Managed Challenge`:

- `https://lawangsewu.pa-semarang.go.id/wa-caraka-admin/login`
- `https://lawangsewu.pa-semarang.go.id/wa-caraka`

Gunakan pola rule yang sama seperti halaman monitor, tetapi untuk path WA Caraka:

- Hostname equals `lawangsewu.pa-semarang.go.id`
- URI Path starts with `/wa-caraka-admin`

Action: `Skip`

Komponen yang di-skip:

- All remaining custom rules
- All managed rules
- All rate limiting rules
- All Super Bot Fight Mode rules
- Browser Integrity Check

Jika halaman runtime `/wa-caraka` juga masih ditantang, buat rule kedua:

- Hostname equals `lawangsewu.pa-semarang.go.id`
- URI Path starts with `/wa-caraka`

Action: `Skip`

Setelah itu cek lagi di `Security -> Events` bahwa action untuk kedua path ini tidak lagi `Managed Challenge`.

## 1) Rule WAF custom (Skip) untuk endpoint monitor

Buat **Custom Rule** dengan kriteria:

- Hostname equals `lawangsewu.pa-semarang.go.id`
- URI Path starts with `/info-persidangan`

Action: `Skip`

Komponen yang di-skip (centang):

- All remaining custom rules
- All managed rules
- All rate limiting rules
- All Super Bot Fight Mode rules
- Browser Integrity Check

Prioritas: taruh rule ini di urutan paling atas.

## 2) Configuration Rule untuk Security Level (jika challenge masih muncul)

Karena sumber challenge sebelumnya dari service `Security level` (rule id `iuam`), maka perlu override:

- Hostname equals `lawangsewu.pa-semarang.go.id`
- URI Path starts with `/info-persidangan`

Set konfigurasi:

- Security Level: `Essentially Off`
- Browser Integrity Check: `Off` (jika tersedia)
- Under Attack Mode: `Off` (jika tersedia)

Jika slot Configuration Rules penuh, gunakan rule nonaktif yang ada lalu ubah match dan setting untuk path ini.

## 3) Cek Security Events setiap ada error

Di Cloudflare `Security -> Events`, filter:

- Host: `lawangsewu.pa-semarang.go.id`
- Path: `/info-persidangan`
- (opsional) Ray ID dari header response

Pastikan action tidak lagi `Managed Challenge` untuk path tersebut.

## 4) URL kanonik (tanpa .php)

Server sudah dikonfigurasi agar URL `.php` di-redirect ke URL tanpa `.php` (301).

Gunakan URL utama:

- `https://lawangsewu.pa-semarang.go.id/info-persidangan`

## 5) Uji cepat setelah perubahan

Jalankan dari server:

```bash
curl -I https://lawangsewu.pa-semarang.go.id/info-persidangan
curl -I https://lawangsewu.pa-semarang.go.id/info-persidangan.php
```

Expected:

- URL tanpa `.php` -> `200`
- URL dengan `.php` -> `301` ke URL tanpa `.php`
- Tidak ada header `cf-mitigated: challenge`
