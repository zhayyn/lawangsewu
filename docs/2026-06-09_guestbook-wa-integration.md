# Integrasi Nomor HP dan Pengiriman WA Otomatis pada Buku Tamu

**Tanggal:** 9 Juni 2026
**Fitur:** Penambahan Nomor HP Buku Tamu & Integrasi Pesan Umpan Balik via WaCaraka

## Ringkasan Eksekutif
Fitur ini bertujuan untuk melengkapi pencatatan data tamu pada aplikasi Portal Lawangsewu dengan menambahkan nomor WhatsApp (HP) sebagai kolom *mandatory* (wajib diisi). Selain untuk dokumentasi internal, nomor ini digunakan untuk memicu pengiriman pesan WhatsApp otomatis kepada tamu yang baru mendaftar, berisi ucapan terima kasih sekaligus meminta umpan balik/kritik & saran terkait layanan Pengadilan Agama Semarang.

## Perubahan Arsitektur & Database
- Dibuat migrasi baru `2026_06_09_030500_add_phone_to_guestbook_entries_table.php` yang menambahkan kolom `phone` dengan tipe data `VARCHAR(20)` pada tabel `guestbook_entries`.
- Model `App\Models\GuestbookEntry` diperbarui dengan memasukkan `phone` ke dalam daftar `$fillable`.

## Perubahan Antarmuka (UI)
- **Form Pendaftaran (`resources/views/guestbook/form.blade.php`)**:
  - Kolom teks `Nomor HP / WhatsApp` (`nomor_hp`) ditambahkan sebelum pemilih instansi.
  - Validasi JavaScript sisi klien (fungsi `bidangWajibTerisi()`) diperbarui untuk memastikan kamera *capture* tidak menyala sebelum nomor WA diisi.
  - Fungsi `FormData.append('nomor_hp')` diintegrasikan dalam *AJAX payload*.
- **Daftar & Detail (`list.blade.php` & `detail.blade.php`)**:
  - Tampilan daftar riwayat tamu (`list.blade.php`) diperbarui untuk memuat informasi tambahan `"HP: [Nomor]"`.
  - Tampilan *drilldown* detail tamu (`detail.blade.php`) memuat kolom/baris tersendiri untuk menampilkan data spesifik "Nomor HP".

## Alur Notifikasi WhatsApp (WaCarakaService)
Modul menggunakan `WaCarakaService` yang merupakan antarmuka standar (standar baru menggantikan *legacy* Sinofita) untuk komunikasi perpesanan (*messaging gateway*) di dalam Portal Lawangsewu.

Pada `GuestbookController::store`, setelah *entry* data dan pengambilan foto berhasil melalui `storeAs()`, sistem akan merangkai *message template* dan memanggil metode *asynchronous*:

```php
$waService = app(\App\Services\WaCarakaService::class);
$waService->queueText($entry->phone, $messageText, 'PA Semarang');
```
Metode `queueText` memastikan form buku tamu dapat segera merespons "Sukses" tanpa harus menunggu proses sinkron pengiriman API ke *backend* WaCaraka, menghindarkan UI dari risiko *timeout* lambat.

## Pengujian (Testing)
- *Automated test* (`tests/Feature/Portal/GuestbookFlowTest.php`) dimodifikasi untuk memasukkan *payload* `nomor_hp`.
- Objek eksternal `WaCarakaService` di-*mock* (menggunakan Mockery) agar `queueText` tidak benar-benar dikirimkan ketika dilakukan `php artisan test`, mencegah tumpukan antrean pada *queue driver* selama tahap CI/CD maupun iterasi *development*.
