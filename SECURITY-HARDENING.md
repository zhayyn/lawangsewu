# Security Hardening Runbook (Lawangsewu)

Dokumen ini untuk memastikan server `lawangsewu` dan repository GitHub tetap aman secara operasional.

## 1) Quick Audit (otomatis)

Jalankan:

```bash
/var/www/html/lawangsewu/scripts/security-audit.sh
```

Audit akan mengecek:

- file sensitif yang tidak boleh ter-track Git
- pola secret berisiko di tracked files
- endpoint sensitif publik (`/.env`, `/scripts/`, `/projects/`) sudah `403`

## 2) Rotasi Secret (wajib saat ada indikasi bocor)

Runbook detail copy-paste ada di:

- `SECRET-ROTATION.md`

Urutan aman:

1. Buat credential DB baru (user + password kuat)
2. Update runtime env di server (jangan commit):
   - `projects/website-pa-semarang/lumpiapasar-s9-active/.env`
   - `gateway/.env`
3. Reload service yang relevan (jika perlu)
4. Verifikasi endpoint utama tetap `200`
5. Cabut credential lama

## 3) Hardening GitHub (manual di web UI)

Aktifkan untuk repo `zhayyn/lawangsewu`:

- Branch protection untuk `main`
  - Require pull request sebelum merge
  - Require status checks (minimal CI)
  - Block force push dan branch deletion
- Security
  - Enable secret scanning
  - Enable push protection
  - Enable Dependabot alerts + security updates
- Akun admin
  - Wajib 2FA
  - Gunakan personal access token dengan scope minimal

## 4) Hardening Cloudflare

Ikuti checklist di file:

- `CLOUDFLARE-CHECKLIST.md`

Fokus: hindari challenge tidak perlu di endpoint monitor sambil tetap menjaga proteksi global.

## 5) Backup & Recovery

Ikuti SOP di:

- `BACKUP-RECOVERY.md`

Minimal yang wajib aktif:

- backup harian terenkripsi
- offsite backup harian
- uji restore berkala

## 6) Monitoring Rutin

Jadwal minimum (disarankan mingguan):

- jalankan `scripts/security-audit.sh`
- review `git log --since='7 days ago' --name-status`
- review Cloudflare Security Events
- review auth log server (`/var/log/auth.log` atau ekuivalen)
