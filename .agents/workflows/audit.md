# 🔍 Workflow: /audit
# Audit-Only — Scan & Report Tanpa Rebuild
# Trigger: /audit [SCOPE]

## Deskripsi

Workflow untuk audit kode tanpa melakukan perubahan arsitektur atau rebuild.
Berguna untuk health check berkala, pre-launch security review,
atau setelah merge dari multiple contributors.

---

## Execution Flow

```
┌─────────────────────────────────────────────┐
│  USER: /audit [SCOPE]                       │
│                                             │
│  Scope options:                             │
│  - /audit all          → Full audit         │
│  - /audit security     → Security only      │
│  - /audit tests        → Test suite only    │
│  - /audit rbac         → RBAC integrity     │
│  - /audit [modul]      → Modul spesifik     │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────┐
│  @qa → audit_code (Report Mode)             │
│                                             │
│  1. Scan sesuai scope                       │
│  2. TIDAK melakukan fix otomatis            │
│  3. Hanya mencatat dan melaporkan           │
│  4. Prioritaskan temuan:                    │
│     🔴 Critical — harus fix segera          │
│     🟡 Warning — sebaiknya fix              │
│     🟢 Info — nice to have                  │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────┐
│  📊 AUDIT REPORT                            │
│                                             │
│  ## Health Score: X/100                     │
│                                             │
│  ### 🔴 Critical (X issues)                │
│  - [issue 1]                                │
│  - [issue 2]                                │
│                                             │
│  ### 🟡 Warning (X issues)                 │
│  - [issue 1]                                │
│                                             │
│  ### 🟢 Info (X items)                     │
│  - [item 1]                                 │
│                                             │
│  ### Test Results                           │
│  Passed: XX | Failed: XX | Skipped: XX     │
│                                             │
│  ### Recommendations                        │
│  1. [rekomendasi]                           │
│  2. [rekomendasi]                           │
└─────────────────────────────────────────────┘
```

---

## Scope Details

### `/audit all`
Full audit: syntax + security + logic + tests + performance + RBAC

### `/audit security`
Fokus pada:
- Route tanpa middleware guard
- SQL injection vectors
- XSS di Vue templates (v-html check)
- CSRF exceptions
- Exposed sensitive data
- File upload validation

### `/audit tests`
Fokus pada:
- Jalankan full test suite
- Coverage analysis (modul mana yang belum ada test?)
- Test quality (ada test yang terlalu lemah?)

### `/audit rbac`
Fokus pada:
- `php artisan rbac:verify`
- Cross-check route middleware vs RBAC permissions
- Superadmin bypass verification
- Allowlist integrity

### `/audit [modul]`
Audit modul spesifik: `ptsp`, `sidang`, `chat`, `guestbook`, `cctv`, `admin`

---

## Aturan Audit

- ✅ Boleh: scan, report, recommend
- ✅ Boleh: fix typo/formatting jika ditemukan
- ❌ Tidak boleh: mengubah logic tanpa approval user
- ❌ Tidak boleh: mengubah arsitektur
- ❌ Tidak boleh: menambah/menghapus fitur

Jika ditemukan issue critical → **rekomendasikan /hotfix atau /startcycle**

## Contoh Penggunaan

```
User: /audit security

→ @qa scan seluruh route untuk missing auth middleware
→ @qa cek semua input validation
→ @qa cek SQL injection vectors
→ Report dengan health score dan rekomendasi
```
