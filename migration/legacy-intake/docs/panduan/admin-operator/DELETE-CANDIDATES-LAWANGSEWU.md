# Delete Candidates Lawangsewu

Dokumen ini hanya berisi kandidat file atau bundle yang kemungkinan aman dihapus permanen setelah verifikasi manual. Tidak ada penghapusan yang dieksekusi dari daftar ini.

## Kriteria

- bukan pintu masuk utama dokumentasi lagi
- sudah tercakup oleh handbook utama atau appendix index
- terlihat sebagai artefak turunan, hasil render, atau duplikasi bundle
- tetap perlu verifikasi bahwa tidak ada kebutuhan publik, audit, atau referensi eksternal yang aktif

## Batch Sudah Dieksekusi

- `Walkthrough-DBPrakom/md/README-Walkthrough.md`
- `Walkthrough-DBPrakom/pdf/README-Walkthrough.pdf`
- `Walkthrough-DBPrakom/pdf/Walkthrough-DBPrakom-Master.pdf`
- `Walkthrough-DBPrakom/html/README-Walkthrough.html`
- seluruh isi `Walkthrough-DBPrakom/html/`
- seluruh isi `Walkthrough-DBPrakom/pdf/`
- `Walkthrough-DBPrakom/pdf/E-BOOK WA-CARAKA.pdf`
- `Walkthrough-DBPrakom/pdf/REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE-OFFICIAL.pdf`

Alasan:

- peran file-file tersebut sudah digantikan oleh handbook utama Lawangsewu atau hanya berupa render HTML turunan dari sumber markdown walkthrough
- rujukan aktif sudah dipindahkan ke `Walkthrough-DBPrakom/LAWANGSEWU-KOMPENDIUM-LENGKAP.pdf`
- untuk `E-BOOK WA-CARAKA.pdf`, rujukan aktif dipindahkan ke canonical appendix PDF
- seluruh rujukan aktif `walkthrough/pdf/*` sudah dipindahkan ke `docs/appendix/*`

## Kandidat Prioritas Rendah Risiko

- tidak ada kandidat prioritas rendah yang tersisa untuk bundle walkthrough

Alasan:

- bundle walkthrough saat ini lebih cocok diperlakukan sebagai artefak appendix, bukan dokumentasi utama root
- batch walkthrough yang tersisa sudah dipensiunkan atau dipindahkan ke appendix kanonik

## Kandidat yang Perlu Verifikasi Tambahan

- `docs/appendix/migration/MIGRATION-STATUS-EXECUTIVE-LAWANGSEWU.html`
- `docs/appendix/migration/MIGRATION-STATUS-EXECUTIVE-LAWANGSEWU.pdf`
- `docs/appendix/security/REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE.html`
- `docs/appendix/security/REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE-OFFICIAL.html`
- `docs/appendix/security/REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE-OFFICIAL.pdf`
- `docs/appendix/wa-caraka/E-BOOK WA-CARAKA.pdf`

Alasan:

- file-file ini kemungkinan besar adalah versi format turunan atau print-ready dari dokumen yang sekarang sudah punya induk handbook sendiri
- tetapi masih mungkin dipakai untuk briefing, print, atau distribusi eksternal

## Kandidat yang Jangan Dihapus Saat Ini

- `Walkthrough-DBPrakom/LAWANGSEWU-KOMPENDIUM-LENGKAP.pdf`
- `docs/root/OFFICIAL-DOCS-SET-LAWANGSEWU.md`
- `docs/root/APPENDIX-INDEX-LAWANGSEWU.md`
- `docs/root/DOCS-GUIDE-LAWANGSEWU.md`
- `docs/root/DOCS-INDEX-LAWANGSEWU.html`
- `docs/root/AUDIT-KONSOLIDASI-FOLDER-LAWANGSEWU.md`

## Catatan

Jika daftar hapus ini mau dieksekusi, langkah yang aman adalah mengeksekusi batch kecil per kelompok dan memeriksa referensi silang setelah tiap batch.