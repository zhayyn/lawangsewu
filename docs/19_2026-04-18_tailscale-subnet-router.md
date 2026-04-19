# Tailscale Subnet Router — Integrasi Jaringan PA Semarang

**Tanggal:** 18 April 2026  
**Sprint:** 4  
**Author:** zhayyn™  

---

## Latar Belakang

Server-server operasional PA Semarang (SIPP, Web, Antrian) berada di jaringan lokal
`192.168.88.0/24` — jaringan privat yang **tidak dapat diakses langsung** dari internet.

Masalahnya: aplikasi Lawangsewu berjalan di server yang berbeda. Tanpa terowongan jaringan,
Laravel tidak bisa menjangkau `192.168.88.10` (SIPP) maupun `192.168.88.9` (Web/Antrian).

Solusinya: **Tailscale Subnet Router**.

---

## Analogi: Pos Satpam dengan Talkie-Walkie

Bayangkan kompleks kantor PA Semarang seperti sebuah **perumahan tertutup** (private network `192.168.88.0/24`).

- Setiap rumah di sana (server SIPP, Web, Antrian) hanya bisa dikunjungi kalau kamu **sudah ada di dalam kompleks**.
- Lawangsewu berada di luar — di kota.

**Tailscale** adalah seperti memasang **pos satpam** di pintu gerbang kompleks. Satpam itu membawa talkie-walkie yang terhubung ke jaringan Tailscale (internet terenkripsi). Begitu kamu mendaftar ke Tailscale dan satpam mengenalimu, kamu bisa minta satpam untuk menyampaikan paketmu ke rumah mana saja di dalam kompleks — **seolah kamu ada di dalam**.

Perintah `--advertise-routes=192.168.88.0/24` adalah instruksi kepada satpam:
> *"Umumkan ke semua anggota Tailscale bahwa kamu bisa meneruskan paket ke seluruh komplek 192.168.88.0/24."*

---

## Topologi Jaringan

```
[ Internet / Tailscale Mesh ]
          │
          ▼
┌─────────────────────────┐
│  Server Lawangsewu      │  ← Node Tailscale aktif
│  (subnet router)        │     menjalankan tailscale up
│  --advertise-routes=    │     --advertise-routes=192.168.88.0/24
│    192.168.88.0/24      │
└────────────┬────────────┘
             │ (akses lokal via NIC)
             ▼
    ┌────────────────────┐
    │  LAN 192.168.88.x  │
    ├────────────────────┤
    │  .9   Server Web   │  port 80  (WordPress PA Semarang)
    │  .9   Server Antri │  port 8088 (Pilar Queue API)
    │  .10  Server SIPP  │  port 80  (Database SIPP)
    └────────────────────┘
```

---

## Perintah Aktivasi

Jalankan **sekali** di server Lawangsewu sebagai root:

```bash
sudo tailscale up --advertise-routes=192.168.88.0/24
```

Kemudian, di Tailscale Admin Console, **setujui route** tersebut:

> Machines → [nama server] → Edit route settings → ✅ `192.168.88.0/24`

Tanpa persetujuan dari Admin Console, route tidak akan aktif meskipun perintah sudah dijalankan.

---

## Pemetaan Server PA Semarang

| Key | IP | Port | Layanan |
|---|---|---|---|
| `sipp` | `192.168.88.10` | `80` | Database perkara SIPP |
| `web` | `192.168.88.9` | `80` | Website resmi PA Semarang |
| `antrian` | `192.168.88.9` | `8088` | Pilar Queue API |

---

## Kode yang Dihasilkan

### `TailscaleService` — Service Pattern

```php
// app/Services/TailscaleService.php

private const ADVERTISED_SUBNET = '192.168.88.0/24';
private const TAILSCALE_UP_COMMAND = 'sudo tailscale up --advertise-routes=' . self::ADVERTISED_SUBNET;

private const PA_SEMARANG_NETWORK = [
    'sipp'    => ['ip' => '192.168.88.10', 'label' => 'Server SIPP',    'port' => 80],
    'web'     => ['ip' => '192.168.88.9',  'label' => 'Server Web',     'port' => 80],
    'antrian' => ['ip' => '192.168.88.9',  'label' => 'Server Antrian', 'port' => 8088],
];
```

**Mengapa Service Pattern dan bukan langsung di Controller?**

| Aspek | Langsung di Controller | Service Pattern |
|---|---|---|
| Testability | Harus simulasikan HTTP request | `new TailscaleService()` langsung di-test |
| Reuse | Copy-paste ke controller lain | Inject ke mana saja |
| Perubahan IP | Edit banyak file | Edit satu konstanta |
| Single Responsibility | Controller jadi gemuk | Controller = routing saja |

### Introduce Assertion

```php
private function assertDeviceRegistered(string $deviceKey): void
{
    if (!array_key_exists($deviceKey, self::PA_SEMARANG_NETWORK)) {
        throw new InvalidArgumentException(
            "Perangkat '{$deviceKey}' tidak terdaftar dalam jaringan Tailscale PA Semarang."
        );
    }
}
```

Assertion ini adalah *garda depan* — seperti resepsionis yang menolak tamu yang tidak ada di daftar tamu, sebelum tamu tersebut masuk lebih jauh ke gedung.

---

## Checklist Aktivasi

- ✅ Install Tailscale di server Lawangsewu: `curl -fsSL https://tailscale.com/install.sh | sh`
- ✅ Login ke jaringan Tailscale: `sudo tailscale login`
- ✅ Aktifkan subnet router: `sudo tailscale up --advertise-routes=192.168.88.0/24`
- ✅ Approve route di Tailscale Admin Console
- ✅ Verifikasi koneksi: `ping 192.168.88.10` dari server Lawangsewu
- ✅ Jalankan `TailscaleService::networkSnapshot()` via Artisan Tinker untuk validasi probe

---

*developed by zhayyn™*
