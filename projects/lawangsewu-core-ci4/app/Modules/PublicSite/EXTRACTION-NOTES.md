# PublicSite Extraction Notes

Source utama public site saat ini:

- `/var/www/html/lawangsewu/widgets/views/php/public/landing.php`

## Target Ekstraksi

### Pisahkan logic dari visual

- login inline handling
- portal URL resolution
- landing URL resolution
- auth state read

### Pertahankan perilaku yang sudah disukai

- route `/`
- landing satu layar tanpa scroll
- panel login inline
- centering title dan visual branding

### Jangan campur ke modul lain

- visual landing jangan dicampur ke portal
- auth processing jangan tinggal di file view permanen

## Rule Final

PublicSite menangani wajah depan Lawangsewu dan mengarahkan user ke rumah utama, bukan menampung logika sistem internal.