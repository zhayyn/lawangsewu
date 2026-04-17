# 🚀 Workflow: /startcycle
# Full Development Cycle — Spec → Build → Audit → Deploy
# Trigger: /startcycle [IDEA atau DESKRIPSI FITUR]

## Deskripsi

Workflow ini menjalankan rantai komando lengkap untuk mengembangkan fitur baru
atau melakukan perubahan signifikan pada Lawangsewu. Setiap agent dipanggil
secara berurutan dengan gate approval di antara tahapan kritis.

---

## Execution Flow

```
┌─────────────────────────────────────────────┐
│  USER: /startcycle [IDEA]                   │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────┐
│  Phase 1: @pm → write_specs                │
│                                             │
│  1. Baca context.md                         │
│  2. Analisis kebutuhan user                 │
│  3. Buat Technical Specification            │
│  4. Simpan ke docs/                         │
│  5. ⛔ HALT — Minta approval user           │
│                                             │
│  "Tuan Muda, spesifikasi siap. Approve?"   │
└──────────────────┬──────────────────────────┘
                   │
              User: YES
                   │
                   ▼
┌─────────────────────────────────────────────┐
│  Phase 2: @engineer → generate_code         │
│                                             │
│  1. Baca spec yang diapprove                │
│  2. Buat task checklist                     │
│  3. Implementasi: migration → model →       │
│     service → controller → route →          │
│     vue page → test                         │
│  4. Report progress                         │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────┐
│  Phase 3: @qa → audit_code                  │
│                                             │
│  1. Syntax & dependency scan                │
│  2. Logic & spec compliance check           │
│  3. Security audit (auth, injection, CSRF)  │
│  4. Run test suite                          │
│  5. Fix bugs langsung                       │
│  6. Generate audit report                   │
└──────────────────┬──────────────────────────┘
                   │
              Tests pass?
              ├── NO → Fix & retry
              └── YES ↓
                   │
                   ▼
┌─────────────────────────────────────────────┐
│  Phase 4: @devops → deploy_app              │
│                                             │
│  1. Install dependencies                    │
│  2. Build frontend                          │
│  3. Clear & rebuild caches                  │
│  4. Run migrations                          │
│  5. Verify RBAC                             │
│  6. Final test run                          │
│  7. Serve & report URL                      │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────┐
│  📊 FINAL REPORT                            │
│                                             │
│  - Spec: [link ke docs/]                    │
│  - Files changed: [daftar]                  │
│  - Tests: XX passed, 0 regressions          │
│  - Status: ✅ OPERATIONAL                   │
│  - URL: http://localhost:8000               │
└─────────────────────────────────────────────┘
```

---

## Contoh Penggunaan

```
User: /startcycle Tambahkan fitur notifikasi WhatsApp ketika
      antrian dipanggil, kirim pesan ke nomor HP yang terdaftar
      di tiket antrian.

→ @pm menganalisis, cek arsitektur, buat spec
→ User approve
→ @engineer build
→ @qa audit
→ @devops deploy
→ Report
```

## Abort Conditions

Workflow HARUS dihentikan jika:
1. User mengatakan NO/TIDAK di approval gate
2. @qa menemukan issue arsitektural yang butuh redesign
3. Test suite punya >3 regression baru yang tidak bisa di-fix
4. Perubahan membutuhkan tech stack di luar yang di-lock
