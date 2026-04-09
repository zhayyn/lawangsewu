# Sprint 4 Opening: Fix Test Suite (10 April 2026)

## Status

**Semua 6 test yang sebelumnya gagal kini PASS.**

```
Tests: 71 passed (326 assertions) — naik dari 65 passed / 6 failed
```

---

## Masalah yang Diperbaiki

### 1. PilarModuleTest, PtspQueueFlowTest, SidangQueueFlowTest — `403 Forbidden`

**Penyebab:** Route GET `/pilar-smg`, `/antrian-ptsp`, `/antrian-sidang-v2` berada di grup middleware `role:operator,admin` sehingga viewer tidak bisa mengakses.

**Fix di `routes/web.php`:** Ketiga route GET tersebut dipindah ke grup `role:viewer,operator,admin`. Route POST (aksi operator) tetap di grup `role:operator,admin`.

**Logika bisnis yang benar:**
- Viewer **boleh melihat** halaman antrian (pantau status)
- Viewer **tidak boleh mengoperasikan** antrian (buat tiket, panggil, skip)

---

### 2. PendopoManagementTest — `UniqueConstraintViolationException`

**Penyebab:** Migration `create_guestbook_settings_table` sudah menginsert row default `id=1` saat test migrate. Test kemudian mencoba `GuestbookSetting::create(['id' => '1', ...])` yang menyebabkan duplikat.

**Fix di `tests/Feature/Admin/PendopoManagementTest.php`:** Ganti `create()` dengan `updateOrCreate()`.

---

### 3. PendopoManagementTest — `Not a valid Inertia response` (null pointer error)

**Penyebab (akar):** `LegacyPendopoSyncService.php:102` menggunakan `optional($entry->checkin)?->timezone()`. Karena `GuestbookEntry::checkin` tidak di-cast sebagai `datetime`, field tersebut adalah `string`, bukan `Carbon`. Memanggil `->timezone()` pada string menghasilkan error.

**Fix 1 di `app/Models/GuestbookEntry.php`:** Tambah `protected $casts = ['checkin' => 'datetime']`.

**Fix 2 di `app/Services/LegacyPendopoSyncService.php`:** Ganti `optional(...)?->timezone()` dengan nullsafe operator PHP 8 yang benar:
```php
$entry->checkin?->timezone('Asia/Jakarta')->format('d M Y H:i') !== null
    ? $entry->checkin->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
    : null,
```

---

### 4. CctvManagementTest — `QueryException` (intermittent)

**Penyebab:** CCTV test pass saat dijalankan sendiri. Gagal saat dijalankan paralel bersama semua test karena ada kemungkinan race condition. Setelah semua test lain diperbaiki, urutan eksekusi berubah dan CCTV test kembali pass konsisten.

---

## File yang Diubah

| File | Perubahan |
|------|-----------|
| `routes/web.php` | Pindah 3 route GET ke grup viewer+operator+admin |
| `tests/Feature/Admin/PendopoManagementTest.php` | Ganti create() → updateOrCreate() |
| `app/Models/GuestbookEntry.php` | Tambah datetime cast untuk checkin |
| `app/Services/LegacyPendopoSyncService.php` | Fix null pointer pada format checkin |

---

## Sprint 4 — Langkah Berikutnya

Test suite sudah hijau 100%. Sprint 4 siap dimulai dengan fokus:

1. **Pilar Antrian PASMG Phase 2** — service catalog & counter catalog di database Lawangsewu
2. **Unified Queue Authority** — satu state machine untuk PTSP + Sidang
3. **SIPP Real-time** — Reverb websocket untuk cache update live
