# Lawangsewu Gateway (MVP)

Gateway ini jadi pintu gerbang untuk:

- status sistem backup
- daftar koneksi server/git/workflow
- daftar project coding
- deploy project (lokal) via API

## Setup cepat

1. Salin env:

```bash
cp /var/www/html/lawangsewu/gateway/.env.example /var/www/html/lawangsewu/gateway/.env
```

2. Isi token:

```bash
nano /var/www/html/lawangsewu/gateway/.env
```

3. Buka dashboard:

- `/lawangsewu/gateway/index.php`

## Endpoint

- `GET /lawangsewu/gateway/api/status.php`
- `GET /lawangsewu/gateway/api/connections.php`
- `GET /lawangsewu/gateway/api/projects.php`
- `POST /lawangsewu/gateway/api/deploy.php`

Semua endpoint butuh token:

`Authorization: Bearer <TOKEN>`

## Contoh call

```bash
curl -H "Authorization: Bearer TOKEN_KAMU" \
  http://localhost/lawangsewu/gateway/api/status.php
```

```bash
curl -X POST -H "Authorization: Bearer TOKEN_KAMU" \
  -H "Content-Type: application/json" \
  -d '{"project":"contoh-app","target":"contoh-app"}' \
  http://localhost/lawangsewu/gateway/api/deploy.php
```

## Penting

- Default `GATEWAY_ALLOW_COMMANDS=false`, jadi deploy masih dry-run (aman).
- Ubah ke `true` hanya saat kamu siap mengeksekusi deploy via API.
