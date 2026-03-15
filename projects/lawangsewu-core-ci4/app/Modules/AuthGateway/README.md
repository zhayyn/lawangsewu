# AuthGateway Module Staging

Modul ini akan menjadi pengganti terstruktur untuk logika auth portal yang saat ini tersebar di folder `gateway/`.

## Scope

- login portal
- logout portal
- current user session
- return-path normalization
- launcher helper ke sibling app
- signed SSO helper yang memang milik portal

## Source Lama

- `/var/www/html/lawangsewu/gateway/bootstrap.php`
- `/var/www/html/lawangsewu/gateway/login.php`
- `/var/www/html/lawangsewu/gateway/logout.php`

## Boundary Penting

- tidak mengelola runtime WA Caraka
- tidak membuat auth utama kedua
- tidak boleh lagi memakai redirect logout rapuh ke sibling app

## Prioritas Ekstraksi

1. `gateway_auth_user()`
2. `gateway_is_logged_in()`
3. `gateway_normalize_return_path()`
4. `gateway_login_url_with_return()`
5. `gateway_attempt_login()`
6. `gateway_logout()`
7. launcher URL helpers yang memang milik rumah utama