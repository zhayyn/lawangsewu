# AuthGateway Extraction Notes

Target ekstraksi dari gateway legacy ke modul CI4 Core:

## Fungsi yang Harus Dipindah

- `gateway_auth_user()`
- `gateway_is_logged_in()`
- `gateway_normalize_return_path()`
- `gateway_login_url_with_return()`
- `gateway_attempt_login()`
- `gateway_logout()`
- helper launcher dasar yang memang milik portal

## Fungsi yang Harus Dicek Ulang Sebelum Dipindah

- SSO launcher ke WA Caraka
- helper URL lama berbasis `/lawangsewu/gateway/*`
- flash message session
- regenerasi session id pasca login/logout

## Aturan Ekstraksi

1. pindah fungsi kecil lebih dulu
2. uji login dan logout setelah setiap potongan dipindah
3. jangan memutus helper lama sebelum compatibility bridge siap