# Cloudflare Access Playbook (Admin Lawangsewu)

Tujuan: akses admin hanya untuk user terotorisasi, tanpa membuka panel admin langsung ke internet.

## 1) Buat Access Application

Di Cloudflare Zero Trust:
- Access -> Applications -> Add an application
- Type: Self-hosted
- Name: `Lawangsewu Admin`
- Domain: `lawangsewu.pa-semarang.go.id`

## 2) Session settings

- Session Duration: `8 hours`
- App Launcher visible: optional
- Enable: `Block page` for unauthorized users

## 3) Policy (minimum)

### Policy 1: Allow Admin
- Action: `Allow`
- Include:
  - Emails ending in `@pa-semarang.go.id`
  - or specific email group admin
- Require:
  - One-time PIN or IdP MFA

### Policy 2: Bypass Public Pages
- Action: `Bypass`
- Paths:
  - `/info-persidangan*`
  - `/widgets/*`
  - `/api-pengumuman*`
- Notes: hanya path publik, jangan bypass path admin.

### Policy 3: Deny All
- Action: `Deny`
- Include: Everyone

## 4) Protect Admin Paths (required)

Pastikan path berikut TERPROTEKSI Access:
- `/lawangsewu/gateway*`
- `/wa-caraka-admin*`
- `/wa-caraka/dashboard-ci4-admin/public*`

Catatan:
- Jika app path-based digunakan, buat app terpisah per prefix path agar kontrol lebih presisi.
- Jika pakai satu app host-wide, gunakan Bypass hanya untuk endpoint publik.

## 5) Cloudflare WAF interaction

- WAF skip/challenge rules untuk endpoint publik tetap boleh ada.
- Untuk path admin, jangan `Skip` challenge/security; biarkan Access policy jadi kontrol utama sebelum app origin.

## 6) Validation checklist

Dari browser non-login:
- `https://lawangsewu.pa-semarang.go.id/lawangsewu/gateway` -> harus minta Access login.
- `https://lawangsewu.pa-semarang.go.id/wa-caraka-admin/login` -> redirect/flow ke Access + SSO portal.
- `https://lawangsewu.pa-semarang.go.id/info-persidangan` -> tetap publik normal.

Dari akun admin terdaftar:
- Setelah Access pass, portal login Lawangsewu tampil normal.
- WA Caraka admin tetap SSO via portal, bukan login lokal langsung.

## 7) Emergency rollback

Jika lockout admin terjadi:
1. Zero Trust -> Access -> disable app sementara.
2. Login origin via VPN/SSH internal.
3. Koreksi policy include/require.
4. Re-enable app dan retest.
