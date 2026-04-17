# 🔍 Skill: audit_code
# Agent: @qa (Security & Logic Auditor)
# Trigger: Dipanggil setelah @engineer selesai build

## Instruksi Eksekusi

Ketika skill ini diaktifkan, @qa HARUS menjalankan audit berlapis berikut:

---

### Layer 1: SYNTAX & DEPENDENCY SCAN

```bash
# 1. Pastikan kode bisa di-parse tanpa error
php artisan route:list --compact     # Route tidak ada conflict
php artisan config:clear             # Config bersih

# 2. Cek import/dependency yang hilang
# Scan setiap file PHP baru untuk missing use statements
# Scan setiap file Vue baru untuk missing imports
```

**Cek:**
- [ ] Semua class yang di-import benar-benar ada
- [ ] Tidak ada `use` statement yang tidak terpakai
- [ ] Tidak ada syntax error di PHP maupun Vue
- [ ] Semua migration bisa di-run tanpa error

### Layer 2: LOGIC & SPEC COMPLIANCE

**Cross-reference kode vs spesifikasi:**
- [ ] Setiap requirement di spec sudah diimplementasi
- [ ] Return type sesuai ekspektasi (Inertia render, redirect, JSON)
- [ ] Validasi input ada di setiap endpoint yang menerima data
- [ ] Error handling ada (try-catch untuk external calls, abort untuk auth)

**Data integrity:**
- [ ] Relasi model benar (belongsTo, hasMany, dll)
- [ ] Foreign key constraint di migration
- [ ] Soft delete jika diperlukan (data penting)
- [ ] Mass assignment protection ($fillable terdefinisi)

### Layer 3: SECURITY AUDIT

**Authentication & Authorization:**
- [ ] Setiap route punya middleware `auth`
- [ ] Setiap route punya middleware `active` (user bisa dinonaktifkan)
- [ ] Setiap route punya middleware `role:xxx` yang tepat
- [ ] Tidak ada route yang bisa diakses guest tanpa alasan
- [ ] Admin-only routes pakai middleware `superadmin`

**Injection & XSS:**
- [ ] Query menggunakan Eloquent/Query Builder (parameterized)
- [ ] Tidak ada `DB::raw()` tanpa binding
- [ ] Input di-validate sebelum diproses
- [ ] Output di-escape (Blade/Vue otomatis, tapi cek `v-html`)
- [ ] File upload di-validate (tipe, ukuran)

**CSRF:**
- [ ] Semua POST/PATCH/DELETE route dilindungi CSRF
- [ ] Exception CSRF hanya untuk OAuth callback
- [ ] Inertia forms menggunakan router.post/patch/delete (auto CSRF)

**Data Exposure:**
- [ ] Password & token tidak terexpose di response
- [ ] Hanya field yang dibutuhkan yang dikirim ke frontend
- [ ] Sensitive data di-mask di log (jangan log password/token)

### Layer 4: TEST EXECUTION

```bash
# Jalankan seluruh test suite
php artisan test

# Baseline: 74+ pass, 0 regresi baru
# Jika ada test gagal:
#   1. Cek apakah pre-existing (ProfileTest boleh gagal)
#   2. Jika test baru gagal → FIX di tempat
#   3. Jika test existing gagal → REPORT ke user
```

**Test coverage check:**
- [ ] Setiap endpoint baru punya minimal 1 test
- [ ] Test mencakup: happy path + unauthorized access + invalid input
- [ ] Factory digunakan untuk create test data (bukan hardcode)

### Layer 5: PERFORMANCE & BEST PRACTICES

- [ ] Tidak ada N+1 query (gunakan `with()` eager loading)
- [ ] Query besar menggunakan `chunk()` atau `cursor()`
- [ ] Response payload tidak berlebihan (hanya data yang dibutuhkan)
- [ ] Cache digunakan untuk data yang jarang berubah

---

## Output

Setelah audit selesai, @qa harus menghasilkan:

### 1. Audit Report (ditampilkan ke user)

```markdown
## 🔍 Audit Report — [Nama Fitur]

### Summary
- Files scanned: X
- Issues found: Y
- Issues fixed: Z
- Remaining: W

### Security
✅ / ⚠️ / ❌ [status per kategori]

### Test Results
- Passed: XX
- Failed: XX (pre-existing / new)
- Regressions: 0

### Recommendations
- [saran jika ada]
```

### 2. Fixed Files (langsung di-overwrite)

Jika ditemukan bug:
- Fix langsung di file asli
- Jangan ubah arsitektur — hanya fix logic/bug
- Tandai fix di audit report

---

## Constraint Assertions

- ❌ **DILARANG** mengubah arsitektur (controller structure, route patterns, dll)
- ❌ **DILARANG** menambah fitur baru — hanya fix dan polish
- ❌ **DILARANG** mengubah test untuk membuat test pass (fix kode, bukan test)
- ✅ **WAJIB** jalankan test suite dan report hasilnya
- ✅ **WAJIB** fix setiap bug yang ditemukan (kecuali butuh arsitektur change)
- ✅ **WAJIB** report ke user jika ada issue yang butuh keputusan desain
