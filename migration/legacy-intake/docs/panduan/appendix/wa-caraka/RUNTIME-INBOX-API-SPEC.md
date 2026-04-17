# WA Caraka v2 — Node.js Runtime Inbox API Spec

> Dokumen ini adalah spesifikasi endpoint yang perlu ditambahkan ke runtime Node.js (WA Caraka, port 8790).
> Laravel siap menerima data dari semua endpoint ini.

---

## Endpoint yang Sudah Ada (Tidak Perlu Diubah)

| Method | Path | Keterangan |
|---|---|---|
| GET | `/health` | Status device (connected, hasQr) |
| GET | `/qr` | QR code data URL |
| POST | `/send-text` | Kirim pesan teks |
| POST | `/restart` | Restart runtime |
| POST | `/reconnect` | Reconnect session |
| POST | `/disconnect` | Disconnect session |
| GET | `/history` | Event history runtime |
| POST | `/history/clear` | Clear history |

---

## Endpoint Baru yang Perlu Ditambahkan

### 1. `GET /messages` — Pull Inbox

Mengambil daftar pesan masuk (inbound) yang diterima device sejak timestamp tertentu.

**Request:**
```
GET /messages?since=1713200000&limit=50
Headers:
  X-WA-V2-Token: <token>
```

**Response:**
```json
{
  "ok": true,
  "count": 3,
  "messages": [
    {
      "id": "wamid.ABC123",
      "from": "628111222333",
      "to": "628001234567",
      "text": "Assalamualaikum pak, apa jadwal sidang besok?",
      "type": "text",
      "timestamp": 1713200100,
      "pushName": "Ahmad Sulaiman"
    },
    {
      "id": "wamid.DEF456",
      "from": "628555666777",
      "to": "628001234567",
      "text": "Mohon info biaya pendaftaran",
      "type": "text",
      "timestamp": 1713200200,
      "pushName": "Siti Rahayu"
    }
  ]
}
```

**Field wajib per message:**
- `id` — unique message ID dari Baileys (`message.key.id`)
- `from` — nomor pengirim format internasional
- `to` — nomor device kita
- `text` — isi pesan (untuk `type=text`)
- `type` — `text` | `image` | `document` | `audio` | `sticker`
- `timestamp` — Unix timestamp

**Field opsional:**
- `pushName` — nama profil pengirim di WA
- `mediaUrl` — URL media jika type bukan text

---

### 2. `POST /webhook/register` — Register Callback URL

Mendaftarkan URL callback Laravel agar runtime mendorong (push) setiap pesan masuk secara real-time.

**Request:**
```
POST /webhook/register
Headers:
  X-WA-V2-Token: <token>
  Content-Type: application/json

Body:
{
  "url": "https://lawangsewu.pa-semarang.go.id/api/wa-caraka/webhook/inbound",
  "token": "<shared_token_untuk_verifikasi_webhook>",
  "events": ["message.received"]
}
```

**Response:**
```json
{
  "ok": true,
  "registered": true,
  "callbackUrl": "https://lawangsewu.pa-semarang.go.id/api/wa-caraka/webhook/inbound"
}
```

---

### 3. Push Webhook — Kirim ke Laravel saat Pesan Masuk

Setiap kali device menerima pesan baru, runtime melakukan POST ke URL yang terdaftar.

**Laravel siap menerima di:** `POST /api/wa-caraka/webhook/inbound`

**Payload yang dikirim runtime:**
```json
{
  "from": "628111222333",
  "to": "628001234567",
  "text": "Assalamualaikum",
  "type": "text",
  "id": "wamid.ABC123",
  "timestamp": 1713200100,
  "pushName": "Ahmad Sulaiman"
}
```

**Headers yang perlu dikirim:**
```
X-WA-V2-Token: <shared_token>
Content-Type: application/json
```

---

## Implementasi Baileys (Contoh)

### `GET /messages` endpoint

```javascript
import { Router } from 'express';
const router = Router();

// In-memory store (sesuaikan dengan storage yang sudah ada)
const receivedMessages = [];

// Di handler onMessagesUpsert Baileys:
sock.ev.on('messages.upsert', ({ messages, type }) => {
  if (type === 'notify') {
    messages.forEach(msg => {
      if (!msg.key.fromMe) {  // Hanya pesan masuk
        receivedMessages.push({
          id:        msg.key.id,
          from:      msg.key.remoteJid.replace('@s.whatsapp.net', ''),
          to:        sock.user?.id?.replace(':0', '').replace('@s.whatsapp.net', ''),
          text:      msg.message?.conversation
                     || msg.message?.extendedTextMessage?.text
                     || null,
          type:      msg.message?.conversation ? 'text' : 'image', // sesuaikan
          timestamp: msg.messageTimestamp,
          pushName:  msg.pushName || null,
        });

        // Push webhook ke Laravel
        pushWebhook(receivedMessages.at(-1));
      }
    });
  }
});

router.get('/messages', authMiddleware, (req, res) => {
  const since = parseInt(req.query.since || '0', 10);
  const limit = Math.min(parseInt(req.query.limit || '50', 10), 200);

  const filtered = receivedMessages
    .filter(m => m.timestamp > since)
    .slice(-limit);

  res.json({ ok: true, count: filtered.length, messages: filtered });
});

// Push webhook function
async function pushWebhook(message) {
  const webhookUrl = process.env.WEBHOOK_CALLBACK_URL;
  const token = process.env.LW_WA_V2_TOKEN || '';

  if (!webhookUrl) return;

  try {
    await fetch(webhookUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WA-V2-Token': token,
      },
      body: JSON.stringify(message),
    });
  } catch (err) {
    console.error('[WaCaraka] Webhook push failed:', err.message);
  }
}

export default router;
```

### `.env` baru yang diperlukan di Node.js runtime

```env
# Tambahkan ke .env wa-caraka:
WEBHOOK_CALLBACK_URL=https://lawangsewu.pa-semarang.go.id/api/wa-caraka/webhook/inbound
```

---

## Ringkasan

| Prioritas | Endpoint | Dampak |
|---|---|---|
| 🔴 **Kritis** | Push webhook `messages.received` | Pesan masuk real-time ke Laravel |
| 🟡 **Penting** | `GET /messages?since=` | Fallback polling jika webhook gagal |
| 🟢 **Opsional** | `POST /webhook/register` | Dynamic webhook management |
