# 🔧 Workflow: /hotfix
# Quick Fix — Langsung Build & Verify, Tanpa Full Cycle
# Trigger: /hotfix [BUG DESCRIPTION]

## Deskripsi

Workflow untuk perbaikan cepat tanpa melalui full cycle spec-approval.
Digunakan untuk bug fix, typo, styling issue, atau perbaikan minor
yang tidak mengubah arsitektur.

---

## Execution Flow

```
┌─────────────────────────────────────────────┐
│  USER: /hotfix [BUG DESCRIPTION]           │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────┐
│  @engineer (Quick Fix Mode)                 │
│                                             │
│  1. Identifikasi root cause                 │
│  2. Fix langsung di tempat                  │
│  3. TIDAK membuat spec terpisah             │
│  4. TIDAK mengubah arsitektur               │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────┐
│  @qa (Quick Verify Mode)                    │
│                                             │
│  1. Jalankan test suite                     │
│  2. Verifikasi fix tidak bikin regresi      │
│  3. Report                                  │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────┐
│  📊 HOTFIX REPORT                           │
│  - Bug: [deskripsi]                         │
│  - Root cause: [penjelasan]                 │
│  - Fix: [file & perubahan]                  │
│  - Tests: XX passed, 0 regressions          │
└─────────────────────────────────────────────┘
```

---

## Aturan Hotfix

- ✅ Boleh: fix bug, typo, styling, missing validation
- ✅ Boleh: tambah test untuk bug yang ditemukan
- ❌ Tidak boleh: tambah fitur baru
- ❌ Tidak boleh: ubah database schema
- ❌ Tidak boleh: ubah route structure
- ❌ Tidak boleh: install package baru

Jika fix membutuhkan perubahan arsitektur → **eskalasi ke /startcycle**

## Contoh Penggunaan

```
User: /hotfix Toast login tidak muncul setelah redirect dari Google OAuth

→ @engineer cari root cause, fix redirect flow
→ @qa run tests, verifikasi 0 regresi
→ Report
```
