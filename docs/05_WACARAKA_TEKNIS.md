# 05 — WaCaraka — Dokumentasi Teknis Lengkap

> **Modul:** WaCaraka (WhatsApp Operator Desk)  
> **Updated:** 2026-05-08  

---

## Ringkasan

WaCaraka adalah modul inti Lawangsewu yang memungkinkan operator menjawab pesan WhatsApp masuk secara terpusat melalui browser. Sistem ini terdiri dari:

1. **Frontend** — `Index.vue` (Vue 3 SPA via Inertia.js)
2. **Backend Laravel** — `WaCarakaController` + service classes
3. **WA Bridge** — Node.js/Baileys server di 192.168.88.33:8790
4. **WebSocket** — Laravel Reverb untuk realtime

---

## Arsitektur Internal

```
Index.vue
│
├── callApi(action, options)     ← fungsi proxy semua request
│       │
│       └─► POST /wa-caraka/api/{action}
│                   │
│           WaCarakaController::proxy()
│                   │
│           match($action) => private methods
│                   │
│           ┌───────┴────────┐
│           │                │
│    WaCarakaService   WaCarakaConversationService
│    (bridge HTTP)     (DB logic)
│           │
│    HTTP → 192.168.88.33:8790
│
└── Echo.js listeners
        │
        └─► Reverb WebSocket (:8080)
```

---

## Daftar Actions API

### Route: `POST /wa-caraka/api/{action}`

| Action | Method | Fungsi |
|---|---|---|
| `health` | GET | Cek status device |
| `qr` | GET | Ambil QR pairing |
| `refresh-qr` | POST | Generate QR baru |
| `restart` | POST | Restart runtime |
| `disconnect` | POST | Putus session WA |
| `reconnect` | POST | Reconnect WA |
| `history` | GET | Riwayat pesan runtime |
| `lid-mappings` | GET | Mapping LID → nomor |
| `sync-contacts` | POST | Sinkron kontak |
| `resolve-contacts` | POST | Resolve info JID |
| `stats` | GET | Statistik umum |
| `message-stats` | GET | Statistik pesan |
| `convo-stats` | GET | Statistik percakapan |
| `operator-stats` | GET | Statistik per operator |
| `logs` | GET | Log pengiriman |
| `inbox` | GET | Data inbox lengkap |
| `conversation` | GET | Pesan satu percakapan |
| `pull-inbox` | POST | Pull dari runtime |
| `send-text` | POST | Kirim teks |
| `reply` | POST | Reply ke percakapan |
| `send-media` | POST | Kirim media |
| `broadcast` | POST | Broadcast ke banyak |
| `tickets` | GET | Daftar tiket |
| `ticket-stats` | GET | Statistik tiket |
| `ticket-reply` | POST | Balas tiket |
| `ticket-send` | POST | Kirim dari tiket |
| `ticket-transfer` | POST | Transfer tiket |
| `ticket-close` | POST | Tutup tiket |
| `request-handover` | POST | Minta handover |
| `approve-handover` | POST | Setuju handover |
| `reject-handover` | POST | Tolak handover |
| `force-handover` | POST | Paksa alih (admin) |
| `close` | POST | Tutup + kirim salam |
| `close-silent` | POST | Tutup tanpa salam |
| `reopen` | POST | Buka kembali |
| `mark-read` | POST | Tandai dibaca |
| `mark-customer` | POST | Beri alias/label |
| `unmark-customer` | POST | Hapus alias/label |
| `delete-message` | POST | Hapus dari DB lokal |
| `unsend-message` | POST | Recall dari WA (≤60 mnt) |
| `clear-conversation` | POST | Hapus sesi percakapan |
| `clear-all-conversations` | POST | Hapus semua (superadmin) |
| `reset-state` | POST | Reset state runtime |
| `get-handover-enabled` | GET | Cek status fitur handover |
| `toggle-handover-enabled` | POST | Toggle fitur handover |
| `toggle-pin` | POST | Pin/unpin percakapan |

---

## Fitur Baru (2026-05-08)

### 1. Recall Pesan (`unsend-message`)

**Endpoint Bridge:** `POST http://192.168.88.33:8790/unsend-message`

**Request:**
```json
{
  "jid": "628123456789@c.us",
  "wa_message_id": "false_628xxx_WAMID..."
}
```

**Flow:**
1. Frontend validasi client-side: `canUnsend(msg)` cek sentAtRaw < 60 menit
2. Tampilkan konfirmasi + sisa waktu recall
3. `POST /wa-caraka/api/unsend-message` dengan `{jid, wa_message_id}`
4. Controller cek permission (harus pengirim asli atau admin)
5. Controller cek direction harus `outbound`
6. Service pre-check DB: jika sudah > 60 menit, tolak sebelum call bridge
7. Call bridge `/unsend-message`
8. Log hasilnya (`Log::info` atau `Log::warning`)
9. Jika sukses, hapus juga dari DB lokal

**Context Menu UI:**
- Tombol "Recall dari WA" hanya tampil untuk pesan outbound yang punya `wa_message_id`
- Disabled + label "(kedaluwarsa)" jika sudah > 60 menit
- Enabled + label "(sisa X mnt)" jika masih dalam window

### 2. Tutup Percakapan Tanpa Salam (`close-silent`)

**Flow:**
- Panggil `conversations->closeConversation()` 
- Mark semua inbound unreplied → `replied_at = now()`
- **Tidak** memanggil bridge, **tidak** mengirim pesan apapun

### 3. Tutup Percakapan dengan Salam (`close`)

**Flow:**
- Sama dengan close-silent
- Tambahan: ambil template dari `Cache::get('wacaraka_closing_template')` 
  - Fallback ke teks default jika belum dikonfigurasi
- Kirim via `$wa->sendText()` menggunakan `app()->terminating()` (fire-and-forget)

### 4. Template Pesan Salam (Admin Configurable)

**Cache key:** `wacaraka_closing_template`  
**Diatur via:** Admin console → tab "Pengaturan" → form "Template Pesan Salam Penutup"  
**Endpoint:** `POST /admin/wa-caraka/api/save-closing-template`  
**Payload:** `{ "template": "..." }`

### 5. Animasi Border Composer

CSS conic-gradient berputar 360° dengan durasi 3 detik:
- Layer animasi: `div.animate-[spin_3s_linear_infinite]` dengan `bg-[conic-gradient(...)]`
- Layer mask: `div.absolute.inset-[1px]` dengan background surface
- Hanya aktif (opacity 100%) saat ada percakapan aktif yang bisa dibalas

---

## Message Object Structure

Field yang dikirim dari backend ke frontend per pesan:

```js
{
  id: Number,           // Primary key DB
  direction: String,    // 'inbound' | 'outbound'
  remoteNumber: String, // JID: 628xxx@c.us / group@g.us / xxx@lid
  text: String,         // Teks pesan
  type: String,         // 'text' | 'image' | 'document' | ...
  status: String,       // 'sent' | 'delivered' | 'read' | 'failed'
  waMessageId: String,  // WA Message ID (untuk recall)
  operator: String,     // Nama/alias pengirim
  repliedAt: String,    // Format: "08 Mei 09:00 WIB"
  sentAt: String,       // Format: "08 Mei 09:00 WIB"
  sentAtRaw: String,    // ISO8601: "2026-05-08T02:00:00.000Z" ← untuk canUnsend()
  metadata: Object,     // Data tambahan (group sender, media info, dll)
  isGroup: Boolean,
  groupName: String,
  senderName: String,
  senderKey: String,
}
```

---

## Conversation Object Structure

```js
{
  conversationId: String,   // wa_personal_628xxx_abc123
  remoteNumber: String,     // 628xxx@c.us
  displayTitle: String,     // Nama / alias / nomor
  status: String,           // 'open' | 'closed'
  ownership: String,        // 'mine' | 'claimed' | 'unclaimed'
  owner: { id, name },
  unrepliedCount: Number,
  lastMessageAt: String,
  customerMark: { label, tone, isPinned, note },
  pendingHandover: Object,
  ownerPresence: String,
}
```

---

## Logging

Semua log menggunakan Laravel `Log` facade dengan prefix `[WaCaraka]`:

```
[WaCaraka] GET failed        ← Request ke bridge gagal
[WaCaraka] POST failed       ← Request ke bridge gagal  
[WaCaraka][Unsend] ...       ← Recall attempt & result
[WaCaraka] Inbox cleared     ← Clear inbox
```

Log tersimpan di `storage/logs/laravel.log`.

---

## Troubleshooting

| Masalah | Kemungkinan Penyebab | Solusi |
|---|---|---|
| Pesan masuk tidak muncul realtime | Reverb mati | `php artisan reverb:start` |
| Kirim pesan gagal | WA Bridge tidak reachable | Cek `curl http://192.168.88.33:8790/health` |
| Statistik semua 0 | Bug `.value` di template Vue | Sudah diperbaiki — pastikan build terbaru |
| Recall pesan gagal | Sudah > 60 menit | Tidak ada solusi, ini limitasi WA |
| Queue stuck | Worker mati | `php artisan queue:work` ulang |
| WebSocket disconnected | Reverb mati | Restart Reverb, cek port 8080 |
