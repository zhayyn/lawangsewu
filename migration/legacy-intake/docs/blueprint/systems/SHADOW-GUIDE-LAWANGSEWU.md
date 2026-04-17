# Shadow Guide Lawangsewu

Dokumen ini merangkum seluruh shadow changelog agar pembaca tidak perlu membuka beberapa file hanya untuk mengetahui status aktivasi route family.

## Ringkasan Shadow Family

| Family | Dokumen Detail | Status Ringkas |
|---|---|---|
| Landing | `LANDING-SHADOW-CHANGELOG.md` | guardrail siap, shadow landing disiapkan untuk `/` |
| Portal | `PORTAL-SHADOW-CHANGELOG.md` | shadow terkontrol untuk `portal` dan `portal/launch` |
| Widget Directory | `WIDGET-DIRECTORY-SHADOW-CHANGELOG.md` | shadow untuk `daftar-widget` dan alias `widget-links` |
| Public Widget | `PUBLIC-WIDGET-SHADOW-CHANGELOG.md` | shadow family widget publik kanonik yang verdict-nya aman |
| App Registry | `APP-REGISTRY-SHADOW-CHANGELOG.md` | shadow untuk `app-registry` dan `app-registry/launch` |

## Prinsip Umum Semua Shadow Change

- default tetap `off` sampai flag diaktifkan eksplisit
- proxy shadow dipanggil lewat `.htaccess` dan tidak boleh diakses langsung
- rollback dilakukan dengan menonaktifkan flag atau menjalankan script disable terkait
- staging CI4 Core menjadi target eksekusi shadow, bukan langsung mengganti URL publik begitu saja

## Kapan Perlu Membuka Dokumen Detail

- buka changelog detail jika sedang mengaktifkan atau me-roll back satu family tertentu
- untuk kebutuhan status umum, ringkasan di file ini biasanya sudah cukup
- untuk kontrak URL yang harus dijaga, tetap rujuk ke `ROUTE-INVENTORY-LAWANGSEWU.md`