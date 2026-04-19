# Laporan Hotfix WA-Caraka — Outbound Messaging & LID Resolution

> **Tanggal:** 19 April 2026  
> **Pelaksana:** SenopaTEA @engineer + @qa  
> **Modul:** WA-Caraka (Gateway WhatsApp Internal)  
> **Severity:** Critical (Outbound messaging 100% gagal)

---

## 1. Ringkasan Eksekutif

Seluruh fitur outbound messaging WA-Caraka (kirim personal, kirim grup, reply pesan masuk) mengalami kegagalan total sejak migrasi WhatsApp ke arsitektur LID (Linked ID). Sesi debugging ini mengidentifikasi dan memperbaiki **5 root cause** yang saling bertumpuk, sehingga outbound messaging kini berfungsi penuh.

**Status Akhir:** SEMUA fitur outbound berhasil diverifikasi via audit otomatis.

---

## 2. Root Cause Analysis

### RC-1: BroadcastException Menghancurkan Alur queueText

| Item | Detail |
|---|---|
| **Gejala** | `queueText()` gagal total, pesan tidak pernah masuk queue |
| **Akar Masalah** | `WaCarakaMessageSynced::dispatch()` mengimplementasi `ShouldBroadcastNow` → Reverb WebSocket mengembalikan HTTP 404 → exception naik ke `queueText()` dan menghentikan seluruh flow |
| **Dampak** | 100% outbound gagal |
| **File Diperbaiki** | `app/Services/WaCarakaService.php` |
| **Solusi** | Membungkus broadcast dalam method `dispatchMessageSynced()` dengan try-catch. Kegagalan broadcast hanya di-log sebagai warning, tidak menghentikan alur utama. |

```php
private function dispatchMessageSynced(WaCarakaMessage $message): void
{
    try {
        WaCarakaMessageSynced::dispatch($message);
    } catch (\Throwable $e) {
        Log::warning('[WaCaraka] Broadcast dispatch failed (non-critical)', [
            'message_id' => $message->id,
            'error'      => $e->getMessage(),
        ]);
    }
}
```

### RC-2: Queue Mismatch — Job Masuk Queue Salah

| Item | Detail |
|---|---|
| **Gejala** | Job `SendWaCarakaOutboundMessage` masuk antrian tapi tidak pernah diproses |
| **Akar Masalah** | Config `wa_caraka.php` default queue = `'wa-caraka'`, tapi queue worker hanya listen `'default'` |
| **Dampak** | Pesan stuck di queue selamanya |
| **File Diperbaiki** | `config/wa_caraka.php` |
| **Solusi** | Menambahkan `'queue' => env('WA_QUEUE', 'default')` sehingga job masuk ke queue yang didengarkan worker. |

### RC-3: Group JID Dirusak oleh phoneNumberFormatter

| Item | Detail |
|---|---|
| **Gejala** | Kirim ke grup selalu gagal — target berubah dari `120363…@g.us` menjadi `120363…@c.us` |
| **Akar Masalah** | `phoneNumberFormatter()` di `wa-lawangsewu/libs/utils.js` menstrip semua suffix dan menambah `@c.us` — termasuk untuk JID grup |
| **Dampak** | 100% outbound grup gagal |
| **File Diperbaiki** | `/home/dbprakom/wa-lawangsewu/libs/utils.js` |
| **Solusi** | Menambahkan early return sebelum normalisasi jika JID berakhiran `@g.us`. |

### RC-4: LID Personal Number Gagal Registration Check

| Item | Detail |
|---|---|
| **Gejala** | Kirim ke kontak personal yang teridentifikasi via LID gagal — `isRegisteredUser` return false |
| **Akar Masalah** | LID-derived number (misal `229583802597421@lid`) bukan MSISDN asli. Ketika dinormalisasi ke `@c.us`, WA API menolaknya sebagai unregistered. |
| **Dampak** | 100% outbound ke kontak LID gagal |
| **File Diperbaiki** | `wa-lawangsewu/libs/utils.js` + `wa-lawangsewu/index.js` |
| **Solusi** | (a) `phoneNumberFormatter` diberi early return untuk `@lid`; (b) `sendMessage` di `index.js` skip `checkRegisteredNumber` jika JID berakhiran `@lid` atau `@g.us`. |

```javascript
// index.js
const isSpecialJid = number.endsWith('@g.us') || number.endsWith('@lid');
if (!isSpecialJid) {
    const isRegistered = await client.isRegisteredUser(number);
    if (!isRegistered) { /* reject */ }
}
```

### RC-5: Reply Menggunakan Target yang Salah

| Item | Detail |
|---|---|
| **Gejala** | Reply dari UI ke kontak LID gagal — dikirim ke `229583…@s.whatsapp.net` yang bukan JID valid |
| **Akar Masalah** | `sendReply()` di controller menggunakan `$convo->remote_number` langsung, yang merupakan identifier turunan LID (bukan @lid dan bukan phone number asli) |
| **Dampak** | Reply ke kontak LID selalu gagal |
| **File Diperbaiki** | `app/Http/Controllers/WaCarakaController.php` |
| **Solusi** | Menambahkan method `resolveReplyTarget()` yang mengambil `fromRaw`/`fromLid` dari metadata inbound terakhir, dan menggunakan `@lid` JID jika tersedia. |

```php
private function resolveReplyTarget(WaCarakaConversation $convo): string
{
    $remote = (string) $convo->remote_number;
    $latestInbound = WaCarakaMessage::where('conversation_id', $convo->conversation_id)
        ->where('direction', 'inbound')
        ->latest('id')
        ->first(['metadata']);

    $lid = data_get($latestInbound?->metadata, 'fromRaw')
        ?? data_get($latestInbound?->metadata, 'fromLid')
        ?? data_get($latestInbound?->metadata, 'raw.fromRaw')
        ?? data_get($latestInbound?->metadata, 'raw.fromLid');

    return (is_string($lid) && str_ends_with($lid, '@lid')) ? $lid : $remote;
}
```

---

## 3. Fix Tambahan: UI Message Disappearing

| Item | Detail |
|---|---|
| **Gejala** | Pesan yang sedang dikirim (status `sending`/`failed`) menghilang dari layar saat refresh otomatis |
| **Akar Masalah** | `refreshConvoMessages()` mengganti seluruh array `conversationMessages` dari server, menghapus temporary messages |
| **File Diperbaiki** | `resources/js/Pages/Lawangsewu/WaCaraka/Index.vue` |
| **Solusi** | Menyimpan temp messages dengan status `sending`/`failed` sebelum refresh, lalu menambahkannya kembali setelah data server dimuat. |

---

## 4. Hasil Audit Fungsional (19 April 2026, 05:34 WIB)

### 4.1 Service Status

| Service | Status |
|---|---|
| `wa-lawangsewu.service` (runtime, port 8089) | **ACTIVE** |
| `wa-bridge.service` (bridge, port 8790) | **ACTIVE** |
| `lawangsewu-queue.service` (queue worker) | **ACTIVE** |
| Reverb WebSocket | Active tapi return 404 (non-critical, dibungkus try-catch) |

### 4.2 Test Kirim Personal (LID Resolve)

```
Konversasi : wa_229583802597421
Remote     : 229583802597421@s.whatsapp.net
Resolved   : 229583802597421@lid (via metadata fromRaw)
Message ID : 447
Status     : ✅ SENT
WA ID      : bridge_1776576850044
```

### 4.3 Test Kirim Grup

```
Konversasi : wa_120363404709652783
Remote     : 120363404709652783@g.us
Message ID : 448
Status     : ✅ SENT
WA ID      : bridge_1776576854927
```

### 4.4 Test Terima Gambar (Inbound)

```
Total media inbound (image/sticker/video) : 4
Contoh image dari chat grup:
  ID         : 138
  Remote     : 120363341206177671@g.us
  Type       : image
  Tanggal    : 2026-04-16 10:42:01
  
Contoh image dari status broadcast:
  ID         : 148
  Remote     : status@broadcast
  Type       : image
  Metadata   : imageMessage dengan mimetype, caption, thumbnail
```

**Status:** ✅ GAMBAR DITERIMA dan tersimpan di database dengan `message_type: image`, caption tersimpan, metadata raw lengkap.

**Catatan Penting:** Gambar hanya tersimpan sebagai metadata (encrypted URL + thumbnail base64). Runtime **belum melakukan `downloadMedia()`** sehingga gambar tidak bisa ditampilkan di UI sebagai preview. Lihat Bagian 6 untuk detail.

### 4.5 Test Reply Flow

```
Method     : resolveReplyTarget() — EXISTS, private
Test msg   : ID 446, status=sent, wa_id=bridge_1776576331030
Flow       : Controller → resolveReplyTarget → @lid → queueText → job → bridge → runtime → SENT
```

**Status:** ✅ REPLY BERHASIL melalui LID resolution otomatis.

### 4.6 Statistik Keseluruhan

| Metrik | Nilai |
|---|---|
| Total Inbound Messages | Aktif menerima |
| Total Outbound Messages | Meningkat (terbaru: id 448) |
| Outbound Sent | Semua test SENT |
| Outbound Failed | 0 (dari test audit ini) |

---

## 5. Daftar File yang Dimodifikasi

| # | File | Perubahan |
|---|---|---|
| 1 | `wa-lawangsewu/libs/utils.js` | Early return untuk `@g.us` dan `@lid` di `phoneNumberFormatter` |
| 2 | `wa-lawangsewu/index.js` | Skip `isRegisteredUser` untuk `@g.us` dan `@lid` (`isSpecialJid`) |
| 3 | `app/Services/WaCarakaService.php` | `dispatchMessageSynced()` try-catch wrapper |
| 4 | `config/wa_caraka.php` | `'queue' => env('WA_QUEUE', 'default')` |
| 5 | `app/Http/Controllers/WaCarakaController.php` | `resolveReplyTarget()` method + `sendReply` menggunakannya |
| 6 | `resources/js/Pages/Lawangsewu/WaCaraka/Index.vue` | Preserve temp messages saat refresh |
| 7 | `wa-bridge/index.js` | POST `/lid-mappings/inject` endpoint |
| 8 | `app/Console/Commands/WaResolveLid.php` | Artisan `wa:resolve-lid` untuk patching retroaktif LID di DB |

---

## 6. Isu yang Belum Terselesaikan & Solusi yang Dibutuhkan

### 6.1 Preview Gambar di UI (Prioritas: Medium)

**Masalah:** Gambar masuk ke DB sebagai `message_type: image` dengan caption, tapi tanpa konten visual yang bisa ditampilkan. Runtime (`wa-lawangsewu`) tidak memanggil `message.downloadMedia()`.

**Solusi yang dibutuhkan (di luar scope hotfix ini):**

1. **Runtime (`sessions.js`):** Tambahkan `await message.downloadMedia()` di handler `client.on('message')` untuk mendapatkan base64 data URL dari gambar.
2. **Bridge (`index.js`):** Teruskan field `media` yang berisi `{ mimetype, data, filename }` ke Laravel.
3. **Laravel (`WaCarakaService::handleInbound`):** Simpan media ke storage (disk atau S3) dan simpan URL di metadata/kolom terpisah.
4. **Vue (`Index.vue`):** Komponen `normalizeMessage` sudah siap menangani `mediaUrl` — tinggal diisi dari data yang benar.

**Estimasi effort:** Fitur baru (bukan hotfix), perlu spec dari @pm.

### 6.2 Reverb WebSocket Return 404 (Prioritas: Low)

**Masalah:** Reverb aktif tapi mengembalikan 404 untuk broadcast. Sudah di-wrap try-catch sehingga tidak mengganggu flow.

**Dampak:** Real-time push ke frontend tidak bekerja. UI mengandalkan polling.

**Solusi:** Investigasi konfigurasi Reverb (route, channel authorization). Tidak urgent karena polling masih berfungsi.

### 6.3 Legacy LID Messages di Database (Prioritas: Low)

**Masalah:** Ada ~13 pesan lama dengan `remote_number` berformat `@lid` yang perlu di-patch ke format `@s.whatsapp.net` atau `@c.us` untuk konsistensi.

**Solusi tersedia:** `php artisan wa:resolve-lid --dry-run` sudah dibuat dan bisa dijalankan kapan saja.

---

## 7. Arsitektur Flow (Pasca-Fix)

```
┌──────────────┐    webhook    ┌──────────────┐    forward    ┌───────────────────┐
│ wa-lawangsewu│──────────────>│  wa-bridge   │─────────────>│  Laravel Webhook  │
│  (runtime)   │  message+LID  │  (adapter)   │  +LID resolve│  handleInbound()  │
│  port 8089   │               │  port 8790   │              │  → DB + broadcast │
└──────┬───────┘               └──────┬───────┘              └───────────────────┘
       │                              │
       │  ┌───────────────────────────┘
       │  │  POST /send-text
       │  │
       ▼  ▼
┌──────────────┐    queueText   ┌───────────────┐   queue job  ┌──────────────────┐
│   WhatsApp   │<───────────────│  wa-bridge    │<─────────────│ SendWaCaraka     │
│   Server     │   sendMessage  │  /send-text   │              │ OutboundMessage  │
└──────────────┘                └───────────────┘              └──────────────────┘
                                                                      ▲
                                                                      │
                                                               ┌──────┴──────────┐
                                                               │ WaCarakaService │
                                                               │ queueText()     │
                                                               │ → resolveTarget │
                                                               │ → dispatch job  │
                                                               └─────────────────┘
```

**LID Resolution Flow:**
```
Inbound: runtime → metadata.fromRaw = "229...@lid" → bridge learns pair → Laravel stores in metadata
Reply:   Controller → resolveReplyTarget() → reads metadata.fromRaw → sends to "229...@lid"
Runtime: receives @lid → skip isRegisteredUser → sendMessage → ✅ DELIVERED
```

---

## 8. Rekomendasi Operasional

1. **Jangan restart runtime tanpa perlu.** Session WhatsApp bisa hilang dan perlu scan QR ulang.
2. **Monitor queue worker.** Pastikan `lawangsewu-queue.service` selalu active. Jika mati, pesan akan stuck di database.
3. **Backup sebelum update `wa-lawangsewu`.** Perubahan di `utils.js`, `index.js`, dan `sessions.js` adalah custom patch — bisa hilang jika package di-update.
4. **Jalankan `wa:resolve-lid`** ketika sudah yakin mapping LID→PN cukup organik untuk membersihkan data lama.

---

*Dokumen ini dibuat oleh SenopaTEA @qa sebagai laporan audit pasca-hotfix.*  
*Terverifikasi: 19 April 2026, 05:34 WIB.*
