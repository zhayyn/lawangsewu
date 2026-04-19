# Kontrak Runtime Node.js WA Caraka untuk Lawangsewu

Dokumen ini adalah handoff singkat untuk developer Node.js yang mengelola runtime WhatsApp. Sisi Laravel Lawangsewu sekarang sudah menyiapkan inbox operator, webhook penerimaan pesan, polling fallback, dan broadcast Reverb. Agar semuanya bekerja penuh, runtime Node.js wajib memenuhi kontrak berikut.

## 1. Endpoint yang wajib tersedia

### GET `/messages`

Tujuan:
- dipanggil dashboard operator Lawangsewu sebagai fallback polling inbox
- dipakai saat webhook terlambat, restart runtime, atau operator membuka dashboard setelah beberapa pesan masuk

Header:
- `X-WA-V2-Token: <shared_token>` jika token diaktifkan

Query opsional:
- `since=<ISO8601>` untuk hanya mengembalikan pesan setelah waktu tertentu

Respons minimal yang diterima Laravel:

```json
{
  "messages": [
    {
      "id": "wamid.HBg...",
      "from": "628123456789",
      "to": "628001234567",
      "text": "Halo admin",
      "type": "text",
      "timestamp": "2026-04-15T09:30:00Z"
    }
  ]
}
```

Catatan:
- `id` harus stabil dan unik agar Laravel bisa deduplikasi
- `from` wajib berisi nomor pengirim
- `text` boleh kosong jika tipe pesan bukan teks, tetapi struktur objek tetap konsisten
- jika runtime belum punya pesan, kembalikan array kosong, jangan error

### POST webhook ke Laravel `/wa-caraka/webhook/inbound`

Tujuan:
- push real-time setiap ada pesan masuk baru dari WhatsApp
- Laravel akan menyimpan pesan ke tabel `wa_caraka_messages` lalu membroadcast notifikasi ke dashboard operator via Reverb

Target Laravel:
- `/wa-caraka/webhook/inbound`

Header:
- `Content-Type: application/json`
- `X-WA-V2-Token: <shared_token>`

Payload minimal:

```json
{
  "id": "wamid.HBg...",
  "from": "628123456789",
  "to": "628001234567",
  "text": "Halo admin",
  "type": "text",
  "timestamp": "2026-04-15T09:30:00Z",
  "raw": {}
}
```

Respons sukses dari Laravel:

```json
{
  "ok": true,
  "messageId": 123,
  "stored": true
}
```

## 2. Aturan perilaku runtime

- Setiap pesan masuk baru harus segera dipush ke webhook Laravel.
- Endpoint `GET /messages` harus mengembalikan histori inbox yang sama dengan data webhook, bukan dataset lain.
- Nilai `id` harus sama antara webhook dan `GET /messages` supaya Laravel tidak menggandakan pesan.
- Jika runtime reconnect, endpoint `GET /messages` tetap harus bisa dipakai untuk mengisi gap pesan yang terlewat.

## 3. Dampak di sisi Lawangsewu

Jika kontrak di atas tersedia, maka dashboard operator akan otomatis mendapatkan:
- daftar percakapan masuk
- isi thread per nomor
- tombol balas dari dashboard
- polling fallback inbox
- notifikasi real-time via Reverb saat webhook masuk

Jika `GET /messages` belum tersedia, dashboard masih bisa hidup dari webhook push, tetapi sinkronisasi setelah restart atau setelah dashboard lama tertutup akan kurang stabil.