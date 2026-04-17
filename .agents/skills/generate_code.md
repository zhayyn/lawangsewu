# 🔨 Skill: generate_code
# Agent: @engineer (Polyglot Builder)
# Trigger: Dipanggil setelah @pm mendapat approval

## Instruksi Eksekusi

Ketika skill ini diaktifkan, @engineer HARUS mengikuti prosedur berikut:

---

### Phase 1: READ — Ingest Spesifikasi

1. **Baca** spesifikasi teknis yang diapprove oleh user
2. **Baca** `.agents/context.md` untuk konvensi dan struktur saat ini
3. **Identifikasi** semua file yang perlu dibuat/dimodifikasi
4. **Buat** task checklist sebelum mulai coding

### Phase 2: EXECUTE — Konstruksi Kode

Ikuti urutan pembangunan ini:

```
1. Migration         (database dulu)
2. Model             (definisi data)
3. Service           (business logic)
4. Controller        (HTTP layer)
5. Route             (endpoint registration)
6. Vue Page          (frontend)
7. Test              (verifikasi)
```

#### Aturan Per Layer:

**Migration:**
```php
// Format nama: YYYY_MM_DD_HHMMSS_create_[tabel]_table.php
// Selalu gunakan Blueprint dengan tipe data eksplisit
// Selalu tambahkan $table->timestamps()
```

**Model:**
```php
// Lokasi: app/Models/
// Wajib definisikan $fillable
// Wajib definisikan $casts jika ada boolean/datetime/json
// Relasi didefinisikan sebagai method
```

**Service:**
```php
// Lokasi: app/Services/
// Gunakan untuk business logic kompleks
// Controller TIDAK boleh mengandung logic berat — delegasi ke Service
// Return value harus predictable (array, Collection, atau DTO)
```

**Controller:**
```php
// Lokasi: app/Http/Controllers/
// Method naming: index, store, show, update, destroy (RESTful)
// Atau: custom action names (call, serve, skip, complete, postpone)
// Return: Inertia::render() untuk page, redirect() untuk actions
// Validasi input di controller method langsung atau via FormRequest
```

**Route:**
```php
// Lokasi: routes/web.php
// Naming: lawangsewu.[modul].[aksi]
// Middleware: ['auth', 'verified', 'active', 'role:xxx']
// Group by access level
```

**Vue Page:**
```vue
<!-- Lokasi: resources/js/Pages/Lawangsewu/ -->
<!-- Layout: LawangsewuLayout -->
<!-- Props via Inertia defineProps() -->
<!-- Gunakan Composition API (<script setup>) -->
<!-- Router: Inertia router (router.post, router.visit) -->
<!-- Routing helpers: route() dari Ziggy -->
```

**Test:**
```php
// Lokasi: tests/Feature/Portal/ atau tests/Feature/Admin/
// Gunakan RefreshDatabase trait
// Nama method: test_[behavior_description]
// Minimal: happy path + unauthorized + edge case
```

### Phase 3: SAVE — Commit ke Struktur

- Semua file langsung ditulis ke struktur Lawangsewu yang ada
- TIDAK ada `app_build/` atau staging directory
- Setiap file baru harus di-announce ke user

---

## Constraint Assertions

- ❌ **DILARANG** menginstall package baru tanpa approval user
- ❌ **DILARANG** mengubah file konfigurasi (.env, config/) tanpa approval
- ❌ **DILARANG** berasumsi — jika spec ambigu, tanyakan ke user
- ❌ **DILARANG** membuat endpoint tanpa middleware guard
- ✅ **WAJIB** mengikuti konvensi yang ada di context.md
- ✅ **WAJIB** menambahkan test untuk setiap fitur baru
- ✅ **WAJIB** memastikan kode bisa di-serve tanpa error

## Output

- File kode langsung di struktur proyek
- Task progress tracker (checklist)
