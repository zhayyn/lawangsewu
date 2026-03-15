# Portal Extraction Notes

Source utama portal saat ini:

- `/var/www/html/lawangsewu/widgets/views/php/public/index.php`

## Target Ekstraksi

### Data yang perlu dipisah dari view

- daftar section
- daftar app card
- simbol/icon metadata
- kategori/filter metadata
- favorites behavior
- komposisi data lintas registry

### Hal yang harus dipertahankan

- route `/portal`
- auth gate menuju login portal
- launcher ke WA Caraka
- launcher ke dokumentasi dan widget registry

### Hal yang perlu dirapikan saat pindah

- card registry jangan hardcoded permanen di view
- app metadata harus dipindah ke config/registry
- route launcher harus memakai helper AuthGateway, bukan string campur-aduk
- portal sebaiknya menjadi komposer yang membaca `AppRegistry`, `DocsRegistry`, dan `WidgetRegistry`

## Rule Final

Portal adalah rumah depan setelah login, bukan tempat logika bisnis besar.

## Status Staging Saat Ini

- provider portal sudah mulai membaca registry modul lain
- metadata launcher utama tidak lagi harus tinggal permanen di provider portal
- view legacy aktif masih belum disentuh sehingga perubahan tetap aman