# 📋 Skill: write_specs
# Agent: @pm (Lead Architect)
# Trigger: Dipanggil oleh workflow /startcycle atau /migrate

## Instruksi Eksekusi

Ketika skill ini diaktifkan, @pm HARUS mengikuti prosedur berikut secara berurutan:

---

### Phase 1: ANALYZE — Parsing Permintaan User

1. **Baca** permintaan user dengan cermat
2. **Baca** `.agents/context.md` untuk memahami arsitektur saat ini
3. **Identifikasi:**
   - Modul mana yang terdampak?
   - Apakah butuh tabel/model baru?
   - Apakah ada perubahan RBAC (role/permission baru)?
   - Apakah ada dependensi ke modul lain? (ripple analysis)
   - Apakah ada breaking change ke fitur existing?

### Phase 2: DRAFT — Penyusunan Spesifikasi

Buat dokumen spec dengan format:

```markdown
# [Judul Fitur]

## Executive Summary
Deskripsi singkat apa yang akan dibangun dan mengapa.

## Dampak Terhadap Arsitektur
- Modul terdampak: [daftar]
- Model baru: [daftar atau Tidak Ada]
- Migration baru: [Ya/Tidak]
- Route baru: [daftar]
- Perubahan RBAC: [daftar atau Tidak Ada]

## Core Requirements
1. [Requirement 1]
2. [Requirement 2]
...

## Data Flow
Diagram alur data (gunakan ASCII atau mermaid)

## Technical Implementation
### Backend
- Controller: [nama dan method]
- Model: [nama dan field]
- Service: [nama dan tanggung jawab]
- Migration: [tabel dan kolom]

### Frontend
- Page: [nama file Vue]
- Component: [komponen baru jika ada]
- Route: [path dan nama]

### Testing
- Test file: [nama]
- Test cases: [daftar skenario]

## Open Questions
Hal yang perlu diklarifikasi oleh user.
```

### Phase 3: ASSERT APPROVAL — Gate Persetujuan

Setelah spec selesai, @pm WAJIB menampilkan pesan:

> **"Tuan Muda, spesifikasi arsitektur telah disiapkan.**
> **Mohon review dokumen di atas.**
> **Berikan persetujuan (YES) atau koreksi sebelum pasukan @engineer bergerak."**

**HALT. Tidak boleh lanjut tanpa persetujuan eksplisit.**

---

## Output

- File: `docs/XX_[YYYY-MM-DD]_[judul-kebab-case].md`
- Nomor urut `XX` mengikuti urutan terakhir di folder `docs/`

## Validation Checklist

Sebelum menyerahkan ke user, pastikan:
- [ ] Tech stack sesuai context.md (tidak ada teknologi asing)
- [ ] Naming convention sesuai context.md
- [ ] Route naming mengikuti pola `lawangsewu.[modul].[aksi]`
- [ ] Middleware stack benar (auth → verified → active → role:xxx)
- [ ] Test cases mencakup happy path DAN error path
- [ ] Tidak ada konflik dengan fitur existing
- [ ] Ripple analysis: cek dampak ke WaCaraka, TDMS, SIPP jika fitur menyentuh data bersama

<!-- developed by dbprakom™ -->
