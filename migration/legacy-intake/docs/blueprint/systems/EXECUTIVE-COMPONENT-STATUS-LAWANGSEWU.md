# Dashboard Status Komponen Lawangsewu

Dokumen ini dibuat untuk pembacaan cepat tingkat pimpinan, koordinator, atau operator senior yang ingin tahu komponen apa saja yang ada, statusnya sekarang, dan tingkat kehati-hatiannya.

## Cara Membaca

Bayangkan ini seperti papan status gedung dalam satu kawasan.

- `aktif`: gedung kerja harian yang sedang dipakai
- `staging`: gedung uji coba yang sudah berdiri tetapi belum dibuka penuh
- `legacy`: gedung lama yang masih menyuplai layanan penting
- `wrapper`: pintu depan kecil yang mengantar ke gedung utama di belakang
- `helper`: alat bantu operasional, bukan layanan utama
- `arsip`: gudang referensi dan sejarah

## Status Eksekutif

| Komponen | Status | Fungsi singkat | Risiko jika salah ubah | Tindakan aman |
| --- | --- | --- | --- | --- |
| Lawangsewu root | aktif | rumah utama dokumen, launcher, dan integrasi | kebingungan orientasi repo dan operasional | rapikan indeks, jangan ubah struktur sembarangan |
| gateway/ | aktif | login, portal, launcher, SSO mapping | gangguan akses operator dan alur portal | ubah hati-hati, verifikasi login/launcher |
| widgets/ | aktif | halaman publik, statistik, pengumuman, embed | route publik rusak atau tampilan publik terganggu | jaga kompatibilitas route dan UI publik |
| wa-caraka/ | aktif | runtime WhatsApp, health, knowledge, LLM bridge | komunikasi WA dan AI ops terganggu | cek health dan capability sebelum ubah |
| wa-caraka-admin/ | wrapper | pintu masuk aman ke dashboard admin | admin tidak bisa masuk atau routing salah | jaga wrapper tetap tipis dan stabil |
| projects/lawangsewu-core-ci4/ | staging | calon core portal jangka panjang | kebingungan antara staging dan produksi | perlakukan sebagai ruang uji, bukan live runtime |
| gateway/node-service/ | staging | service Node tambahan, scraper, worker | eksperimen bocor ke jalur live | tetap isolasi dari PHP live |
| projects/lawangsewu-business-laravel/ | staging | lane ekspansi aplikasi bisnis | salah anggap sebagai portal utama | posisikan sebagai ekspansi, bukan pengganti |
| projects/website-pa-semarang/lumpiapasar-base/ | legacy | app lama dengan path dan endpoint aktif | integrasi lama putus, path rusak, deploy kacau | jangan rename atau bongkar tanpa tracing penuh |
| archive/, pasarjohar/, Api-Caraka/ | arsip/referensi | pembanding, snapshot, referensi teknis | hilang jejak historis atau pembanding teknis | biarkan pasif, ubah hanya jika ada alasan jelas |

## Fokus Perhatian Mingguan

| Area | Pertanyaan cepat |
| --- | --- |
| Portal | login gateway masih normal atau tidak |
| Publik | widget dan halaman publik utama masih tampil normal atau tidak |
| WA runtime | health, ready, db, dan knowledge masih sehat atau tidak |
| AI | masih `prompt-only` atau sudah benar-benar terhubung data |
| Legacy | ada perubahan yang berisiko memutus path lama atau tidak |
| Arsip | ada artefak runtime yang seharusnya tidak lagi ter-track atau tidak |

## Cara Memahami AI Secara Eksekutif

Bayangkan AI seperti petugas informasi.

- `prompt-only`: petugas hanya bicara dari hafalan
- `DB/API-connected`: petugas bisa cek data langsung ke sistem lain
- `RAG-connected`: petugas bisa cari arsip dan knowledge base
- `hybrid`: petugas menggabungkan hafalan, data langsung, dan arsip

Artinya, label AI tidak boleh diasumsikan dari nama fitur saja. Harus dicek capability aktualnya sebelum training, rollout, atau briefing.

## Kesimpulan Singkat

Kalau hanya mengingat satu hal:

- `gateway/`, `widgets/`, dan `wa-caraka/` adalah area aktif
- `projects/lawangsewu-core-ci4/`, `gateway/node-service/`, dan Laravel sibling app adalah area berkembang
- `lumpiapasar-base/` adalah area lama yang masih penting
- `archive/` dan area referensi lain adalah gudang pembanding