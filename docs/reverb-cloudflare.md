# Panduan Cloudflare Untuk Reverb WebSocket

## Gejala

Console browser menampilkan error seperti:

```text
WebSocket connection to 'wss://lawangsewu.pa-semarang.go.id/app/lawangsewu?...' failed
```

Di server, Reverb dan Apache proxy sudah diuji lokal dan berhasil memberi status `101 Switching Protocols`. Artinya aplikasi dan origin sudah siap; yang perlu disesuaikan adalah jalur Cloudflare untuk koneksi WebSocket.

## Target

- Hostname: `lawangsewu.pa-semarang.go.id`
- Path Reverb: `/app/*` dan `/apps/*`
- Origin internal: Apache meneruskan ke `127.0.0.1:8080`
- Hasil sehat: request WebSocket publik mendapat response `101 Switching Protocols`

## Langkah Dashboard Cloudflare

1. Masuk ke Cloudflare dashboard.
2. Pilih zone/domain `pa-semarang.go.id`.
3. Buka menu `Network`.
4. Pastikan opsi `WebSockets` dalam posisi `On`.
5. Buka `DNS`.
6. Pastikan record `lawangsewu` mengarah ke server Lawangsewu yang benar.
7. Jika ingin tetap memakai proteksi Cloudflare, biarkan status DNS `Proxied` atau orange cloud.
8. Buka `Rules` lalu `Cache Rules`.
9. Buat rule baru bernama `Bypass Reverb WebSocket`.
10. Isi expression:

```text
(http.host eq "lawangsewu.pa-semarang.go.id" and starts_with(http.request.uri.path, "/app")) or
(http.host eq "lawangsewu.pa-semarang.go.id" and starts_with(http.request.uri.path, "/apps"))
```

11. Pada `Cache eligibility`, pilih `Bypass cache`.
12. Simpan dan deploy rule.
13. Purge cache untuk hostname `lawangsewu.pa-semarang.go.id`.

## Jika Masih Gagal

Jika request masih tidak berubah menjadi `101`, cek `Security Events` di Cloudflare. Bila ada challenge/block pada path `/app` atau `/apps`, buat WAF custom rule dengan action `Skip` untuk path tersebut.

Expression WAF:

```text
(http.host eq "lawangsewu.pa-semarang.go.id" and starts_with(http.request.uri.path, "/app")) or
(http.host eq "lawangsewu.pa-semarang.go.id" and starts_with(http.request.uri.path, "/apps"))
```

Skip yang cukup dipilih hanya fitur yang memblokir request berdasarkan log, misalnya managed rules atau rate limiting. Jangan mematikan seluruh proteksi domain kalau tidak diperlukan.

## Opsi Darurat

Jika butuh Reverb langsung hidup saat rule Cloudflare belum selesai, ubah DNS record `lawangsewu` menjadi `DNS only` atau gray cloud. Ini membuat WebSocket langsung ke origin, tetapi proteksi/proxy Cloudflare untuk hostname itu tidak aktif selama mode tersebut.

## Verifikasi

Jalankan dari terminal:

```bash
curl -k -i --http1.1 \
  -H 'Host: lawangsewu.pa-semarang.go.id' \
  -H 'Upgrade: websocket' \
  -H 'Connection: Upgrade' \
  -H 'Sec-WebSocket-Key: SGVsbG8sIHdvcmxkIQ==' \
  -H 'Sec-WebSocket-Version: 13' \
  'https://lawangsewu.pa-semarang.go.id/app/lawangsewu?protocol=7&client=js&version=8.5.0&flash=false'
```

Berhasil jika header response berisi:

```text
HTTP/1.1 101 Switching Protocols
```

Setelah itu buka ulang `/dashboard` dan cek DevTools Console. Error WebSocket untuk Reverb seharusnya hilang saat halaman memang membutuhkan fitur realtime.

## Referensi Resmi

- Cloudflare WebSockets: https://developers.cloudflare.com/network/websockets/
- Cloudflare Cache Rules: https://developers.cloudflare.com/cache/how-to/cache-rules/settings/
- Cloudflare WAF Skip action: https://developers.cloudflare.com/waf/custom-rules/skip/
