# AppRegistry Module Staging

Modul ini akan memegang katalog aplikasi dan launcher sibling app Lawangsewu.

## Scope

- metadata aplikasi internal
- launcher ke gateway dan sibling app
- status akses minimum per user role
- shortcut aplikasi yang dipakai portal

## Source Lama

- `/var/www/html/lawangsewu/widgets/views/php/public/index.php`
- `/var/www/html/lawangsewu/gateway/bootstrap.php`
- launcher URL yang sekarang di-bridge lewat `AuthGateway`

## Boundary Penting

- Portal tidak menyimpan launcher aplikasi permanen
- resolver URL tetap memakai AuthGateway bridge selama masa transisi
- registry aplikasi dipisah dari widget dan docs

## Target Refactor

1. bekukan daftar aplikasi ke provider terstruktur
2. sediakan controller staging untuk katalog aplikasi
3. siapkan langkah agar Portal mengonsumsi registry ini, bukan daftar hardcoded