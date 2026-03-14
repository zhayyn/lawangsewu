# AI Capability Register Lawangsewu

Dokumen ini menerapkan aturan penting: setiap fitur AI harus diberi label kemampuan agar tidak terjadi salah tafsir saat rollout, training, atau briefing pimpinan.

## Status Verifikasi Saat Ini

Register ini sudah bisa dipakai untuk mencegah overclaim, tetapi verifikasi capability harus dianggap proses yang terus berjalan, bukan sekali selesai.

Artinya:

- label di dokumen ini adalah status kerja terbaik berdasarkan bukti yang sudah ada
- label bisa naik atau turun jika koneksi nyata, retrieval, atau sumber data berubah
- jika ada keraguan, label yang benar tetap `perlu verifikasi`, bukan asumsi optimistis

Status yang dipakai:

- `prompt-only`
- `DB/API-connected`
- `RAG-connected`
- `hybrid`
- `perlu verifikasi`

## Cara Membaca Label

- `prompt-only`: menjawab dari model umum atau system prompt, tanpa membaca data resmi saat itu
- `DB/API-connected`: menjawab atau menampilkan hasil dengan data live dari database/API
- `RAG-connected`: menjawab dengan bantuan dokumen atau knowledge base yang diambil saat runtime
- `hybrid`: gabungan lebih dari satu pola
- `perlu verifikasi`: kode memberi indikasi, tetapi koneksi aktual atau alur retrieval belum cukup dibuktikan

## Register Awal

| Fitur | Lokasi | Label Saat Ini | Dasar Observasi | Catatan Praktik |
|---|---|---|---|---|
| Mas Satset Lab portal | `gateway/mas-satset-ai.php` | `hybrid` | membaca/menulis `wacaraka_faq` via DB dan memanggil endpoint website chat | secara praktik jangan disebut prompt-only |
| Website chat Mas Satset | endpoint WordPress `wp-json/pa-chat/v1/ask` yang dipanggil dari `gateway/mas-satset-ai.php` | `perlu verifikasi` | terlihat dipakai untuk uji chat, tetapi pipeline retrieval final belum dibekukan di sini | jangan klaim RAG penuh sebelum jalur retrieval dibuktikan |
| FAQ knowledge Mas Satset | `wacaraka_faq` dipakai dari `gateway/mas-satset-ai.php` | `RAG-connected` atau `knowledge-connected` | ada knowledge base internal yang disimpan dan diprioritaskan untuk jawaban sebelum fallback | aman disebut knowledge-connected; istilah RAG perlu verifikasi implementasi retrieval detail |
| WA Caraka runtime status | dashboard admin CI4 dan runtime health | `DB/API-connected` | membaca status runtime, health, device, observability | aman disebut data-connected |
| WA Caraka LLM health | `DashboardController` memanggil `/llm/health` | `DB/API-connected` | bergantung ke API runtime LLM service | ini bukan bukti bahwa jawabannya RAG |
| WA Caraka LLM test | route `wa/llm-test` dan tampilan admin | `perlu verifikasi` | ada test prompt dan pengelolaan LLM, tetapi tidak otomatis berarti terhubung ke dokumen internal | jangan klaim RAG tanpa bukti retrieval |
| Generate via LLM lalu kirim | UI WA admin | `prompt-only` atau `perlu verifikasi` | ada mode generate via LLM, tapi dari kode yang terlihat belum cukup untuk menyatakan data-connected | aman diasumsikan prompt-driven sampai dibuktikan lain |
| Knowledge alert check | WA admin ops | `DB/API-connected` | mengolah knowledge alert dan file ops/log | ini fungsi operasional, bukan chatbot knowledge publik |

## Penjelasan dengan Perumpamaan

Bayangkan setiap fitur AI adalah petugas informasi.

### `prompt-only`

Petugas menjawab hanya dari ingatan dan gaya bicaranya.

Artinya:

- dia bisa terdengar pintar
- tetapi belum tentu melihat data terbaru
- tidak boleh dianggap sumber resmi real-time

### `DB/API-connected`

Petugas menjawab sambil membuka komputer internal.

Artinya:

- dia bisa membaca status live
- dia lebih tepat untuk data operasional
- tetapi belum tentu punya pemahaman dokumen kebijakan lengkap

### `RAG-connected`

Petugas menjawab sambil membuka rak arsip dan buku pedoman resmi.

Artinya:

- dia menggunakan basis pengetahuan internal
- update dokumen dapat mengubah jawaban
- tapi tetap perlu tahu arsip mana yang sah dan terbaru

### `hybrid`

Petugas membuka komputer sekaligus rak arsip.

Artinya:

- ada kemungkinan kombinasi data live dan knowledge base
- label ini hanya dipakai jika kedua sisi benar-benar ada

## Aturan Komunikasi ke User dan Pimpinan

### Boleh bilang begini

- fitur ini `DB/API-connected`, jadi membaca data status live
- fitur ini `knowledge-connected`, jadi jawaban dipengaruhi knowledge base internal
- fitur ini masih `prompt-only`, jadi belum membaca database internal

### Jangan bilang begini

- AI ini sudah tahu semua data internal
- AI ini pasti akurat karena pakai model
- AI ini pasti memakai dokumen resmi, jika belum ada bukti retrieval

## Praktik Operasional yang Direkomendasikan

Setiap fitur AI nanti harus memiliki metadata minimal berikut:

- nama fitur
- owner fitur
- label capability
- sumber data/respons
- batas jawaban
- risiko salah tafsir
- tanggal verifikasi terakhir

## Template Kartu Identitas AI

Gunakan format berikut untuk tiap fitur:

- Nama fitur:
- Owner:
- Label: `prompt-only` / `DB/API-connected` / `RAG-connected` / `hybrid` / `perlu verifikasi`
- Sumber data:
- Batas jawaban:
- Risiko salah tafsir:
- Terakhir diverifikasi:

## Keputusan Praktis untuk Lawangsewu

Saat fitur AI dipasang di portal atau aplikasi sibling nanti:

1. tentukan labelnya lebih dulu
2. jangan umumkan lebih tinggi dari kemampuan nyata
3. jika belum jelas, pakai label `perlu verifikasi`
4. jika sudah memakai data resmi, sebutkan sumbernya
5. jika masih prompt-only, nyatakan secara jujur

## Kesimpulan Operasional Saat Ini

Untuk konteks migrasi Lawangsewu secara keseluruhan:

- pelabelan AI sudah menjadi aturan yang jelas
- register awalnya sudah tersedia dan bisa dipakai
- tetapi status capability tiap fitur tetap harus diverifikasi sebelum rollout, training, atau briefing pimpinan
- jadi bagian AI juga **belum bisa dianggap selesai total** hanya karena register awalnya sudah ditulis