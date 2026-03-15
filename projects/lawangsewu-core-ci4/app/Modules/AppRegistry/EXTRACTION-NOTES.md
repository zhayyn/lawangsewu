# AppRegistry Extraction Notes

Source utama launcher aplikasi saat ini:

- `/var/www/html/lawangsewu/widgets/views/php/public/index.php`
- `/var/www/html/lawangsewu/gateway/bootstrap.php`
- bridge launcher di `AuthGateway`

## Target Ekstraksi

### Pisahkan launcher dari portal

- metadata sibling app
- kategori aplikasi core, sibling, ops, ai, reference
- role minimum untuk melihat launcher
- status SSO ringkas

### Pertahankan perilaku yang sudah disukai

- launcher ke gateway tetap stabil
- launcher ke WA Caraka tetap lewat jalur SSO yang sama
- Portal tetap bisa menampilkan shortcut internal yang sudah familiar

### Jangan campur ke modul lain

- widget publik tetap di WidgetRegistry
- walkthrough tetap di DocsRegistry
- auth session dan signed launcher tetap di AuthGateway

## Rule Final

AppRegistry menangani katalog aplikasi dan launcher metadata, bukan render dashboard portal atau autentikasi sesi.