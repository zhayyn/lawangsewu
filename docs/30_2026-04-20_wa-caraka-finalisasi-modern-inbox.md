# Finalisasi WA Caraka Modern Inbox

## Executive Summary
WA Caraka akan difinalkan menjadi operator inbox yang terasa seperti WhatsApp Web, namun dengan visual yang lebih modern, futuristik, dan elegan. Perubahan fokus pada finalisasi pengalaman inbox, penguatan dukungan attachment dua arah, perapihan metadata media, dan polishing antarmuka agar tetap ringan serta smooth dipakai operator.

## Dampak Terhadap Arsitektur
- Modul terdampak: `WA Caraka`, `Realtime/Event`, `wa-runtime`
- Model baru: Tidak ada
- Migration baru: Tidak ada
- Route baru:
  - `lawangsewu.wacaraka.media`
- Perubahan RBAC: Tidak ada

## Core Requirements
1. Operator dapat menerima dan menampilkan gambar, sticker, video, audio, dokumen, dan file lain secara konsisten di inbox WA Caraka.
2. Operator dapat mengirim gambar, sticker, video, audio, dokumen, dan file umum dari composer percakapan.
3. Payload media besar harus tetap bisa diunduh/ditampilkan melalui proxy media tanpa memaksa inline base64.
4. Inbox harus terasa seperti WhatsApp Web:
   - list percakapan cepat dibaca
   - thread jelas
   - composer ringkas
   - status pesan mudah dikenali
5. Visual perlu modern dan elegan tanpa membuat aplikasi berat atau animasi berlebihan.
6. Perubahan wajib tetap kompatibel dengan stack Lawangsewu saat ini dan tidak mengubah arsitektur inti.

## Data Flow
```text
WA Runtime (Baileys)
   -> parse inbound message/media
   -> webhook /api/wa-caraka/webhook/inbound
   -> WaCarakaService::handleInbound
   -> wa_caraka_messages + wa_caraka_conversations
   -> Reverb event
   -> Vue Inbox / Thread render

Operator Composer
   -> pilih file / ketik pesan
   -> POST /wa-caraka/api/send-media atau /reply
   -> WaCarakaController
   -> WaCarakaService::sendMedia / queueText
   -> wa-runtime /send-media atau /send-text
   -> store outbound message
   -> broadcast synced message
   -> thread update real-time

Large media
   -> wa-runtime simpan sementara tokenized media
   -> Laravel proxy /wa-caraka/media/{token}
   -> browser download / preview
```

## Technical Implementation
### Backend
- Controller: `WaCarakaController`
  - perlu finalisasi validasi `send-media`
  - dukung `sticker`
  - jaga reply permission dan state read
- Service: `WaCarakaService`
  - normalisasi metadata media inbound/outbound
  - persist URL proxy / media descriptor agar frontend konsisten
- Webhook: `WaCarakaWebhookController`
  - pertahankan kompatibilitas payload inbound runtime

### Runtime
- File: `wa-runtime/server.mjs`
- Tambah dukungan `sticker` pada endpoint `/send-media`
- Rapikan parsing media inbound agar image, sticker, video, audio, document, dan file besar punya metadata yang seragam

### Frontend
- Page: `resources/js/Pages/Lawangsewu/WaCaraka/Index.vue`
- Tambahkan dukungan file umum di composer
- Render khusus untuk:
  - gambar
  - sticker
  - video
  - audio
  - dokumen / file
- Poles visual inbox dan thread agar lebih dekat ke WhatsApp Web modern
- Tambahkan filter/search ringan untuk membantu navigasi inbox tanpa biaya render berat

### Testing
- Test file: `tests/Feature/Modules/WaCarakaModuleTest.php`
- Test cases:
  - kirim media dokumen tersimpan di DB
  - kirim sticker lewat endpoint media
  - inbound media token membentuk URL proxy
  - UI/backend tetap aman untuk request invalid

## Open Questions
- Tidak ada blocker arsitektural. Eksekusi dilanjutkan berdasarkan mandat user untuk finalisasi penuh WA Caraka.
