<?php

namespace App\Services;

/**
 * TailscaleService
 *
 * Mengelola jaringan Tailscale PA Semarang:
 * - Inventaris device (server, port, layanan)
 * - Probe TCP per device (reachable check)
 * - Latency ping (ICMP via exec)
 * - Tailscale CLI status (tailscale status --json)
 * - Port scan multi-port per device
 *
 * developed by zhayyn™
 */
class TailscaleService
{
    public const ADVERTISED_SUBNET = '192.168.88.0/24';

    /**
     * Registry lengkap semua server + layanan di jaringan PA Semarang.
     * Setiap device bisa punya banyak 'services' (port berbeda).
     */
    public const NETWORK = [
        'web' => [
            'label'       => 'Server Web (Lawangsewu)',
            'ip'          => '192.168.88.9',
            'description' => 'Server utama — Lawangsewu V2, Pilar Antrian',
            'os'          => 'Ubuntu 22.04',
            'role'        => 'web',
            'services'    => [
                ['name' => 'Lawangsewu',        'port' => 443,  'protocol' => 'HTTPS', 'url' => 'https://lawangsewu.pa-semarang.go.id'],
                ['name' => 'Pilar (Antrian)',   'port' => 8088, 'protocol' => 'HTTP',  'url' => 'http://192.168.88.9:8088'],
                ['name' => 'WA Caraka Runtime', 'port' => 8089, 'protocol' => 'HTTP',  'url' => 'http://192.168.88.9:8089'],
                ['name' => 'SSH',               'port' => 22,   'protocol' => 'SSH',   'url' => null],
            ],
        ],
        'sipp' => [
            'label'       => 'Server SIPP',
            'ip'          => '192.168.88.10',
            'description' => 'Server SIPP — database perkara dan informasi sidang',
            'os'          => 'Windows Server',
            'role'        => 'database',
            'services'    => [
                ['name' => 'SIPP Web',  'port' => 80,   'protocol' => 'HTTP', 'url' => 'http://192.168.88.10'],
                ['name' => 'MySQL',     'port' => 3306, 'protocol' => 'TCP',  'url' => null],
                ['name' => 'RDP',       'port' => 3389, 'protocol' => 'RDP',  'url' => null],
            ],
        ],
        'antrian' => [
            'label'       => 'Server Antrian Legacy',
            'ip'          => '192.168.88.9',
            'description' => 'Legacy antrian PTSP — akan dimigrasikan ke Pilar Antrian PASMG',
            'os'          => 'Ubuntu 22.04',
            'role'        => 'legacy',
            'services'    => [
                ['name' => 'Antrian PTSP', 'port' => 8088, 'protocol' => 'HTTP', 'url' => 'http://192.168.88.9:8088'],
            ],
        ],
    ];

    // ── Device API ────────────────────────────────────────────

    public function allDevices(): array
    {
        return self::NETWORK;
    }

    public function deviceStatus(string $deviceKey): array
    {
        $this->assertRegistered($deviceKey);
        $device = self::NETWORK[$deviceKey];

        $services = array_map(function (array $svc) use ($device) {
            $reachable = $this->probePort($device['ip'], $svc['port']);
            return array_merge($svc, ['reachable' => $reachable]);
        }, $device['services']);

        $anyReachable = collect($services)->contains('reachable', true);

        return [
            'key'        => $deviceKey,
            'label'      => $device['label'],
            'ip'         => $device['ip'],
            'description'=> $device['description'],
            'os'         => $device['os'],
            'role'       => $device['role'],
            'services'   => $services,
            'reachable'  => $anyReachable,
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    public function networkSnapshot(): array
    {
        return array_values(array_map(
            fn(string $key) => $this->deviceStatus($key),
            array_keys(self::NETWORK)
        ));
    }

    // ── Ping / Latency ───────────────────────────────────────

    /**
     * Ping ICMP ke host, return latency dalam ms (atau null jika tidak reachable).
     */
    public function ping(string $ip): ?float
    {
        $ip = escapeshellarg($ip);
        $output = shell_exec("ping -c 1 -W 2 {$ip} 2>/dev/null");

        if ($output === null) {
            return null;
        }

        // Parse: "rtt min/avg/max/mdev = 0.123/0.123/0.123/0.000 ms"
        if (preg_match('/rtt.*?=\s*[\d.]+\/([\d.]+)\//', $output, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    /**
     * Ping semua device sekaligus.
     */
    public function pingAll(): array
    {
        $results = [];
        foreach (array_keys(self::NETWORK) as $key) {
            $ip = self::NETWORK[$key]['ip'];
            $results[$key] = [
                'ip'      => $ip,
                'latency' => $this->ping($ip),
            ];
        }
        return $results;
    }

    // ── Tailscale CLI ────────────────────────────────────────

    /**
     * Ambil output `tailscale status --json` dan parse menjadi array.
     * Return null jika tailscale tidak terinstall atau gagal.
     */
    public function tailscaleStatus(): ?array
    {
        $raw = shell_exec('tailscale status --json 2>/dev/null');

        if (empty($raw)) {
            return null;
        }

        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * Info ringkas dari tailscale status: IP Tailscale lokal, node name, peers.
     */
    public function tailscaleInfo(): array
    {
        $status = $this->tailscaleStatus();

        if ($status === null) {
            return [
                'available'  => false,
                'self'       => null,
                'peers'      => [],
                'peer_count' => 0,
            ];
        }

        $self  = $status['Self'] ?? null;
        $peers = $status['Peer'] ?? [];

        $peerList = array_values(array_map(function (array $peer) {
            return [
                'hostname'   => $peer['HostName'] ?? '-',
                'dns_name'   => $peer['DNSName']  ?? '-',
                'tailscale_ips' => $peer['TailscaleIPs'] ?? [],
                'online'     => $peer['Online'] ?? false,
                'os'         => $peer['OS'] ?? '-',
                'last_seen'  => $peer['LastSeen'] ?? null,
            ];
        }, $peers));

        return [
            'available'  => true,
            'self'       => $self ? [
                'hostname'       => $self['HostName']      ?? '-',
                'dns_name'       => $self['DNSName']       ?? '-',
                'tailscale_ips'  => $self['TailscaleIPs']  ?? [],
                'os'             => $self['OS']             ?? '-',
                'relay'          => $self['Relay']          ?? '-',
                'advertised_routes' => $self['AdvertisedRoutes'] ?? [],
                'approved_routes'   => $self['AllowedIPs']       ?? [],
            ] : null,
            'peers'      => $peerList,
            'peer_count' => count($peerList),
        ];
    }

    /**
     * Jalankan `tailscale up --advertise-routes=...` — return output command.
     * HANYA dijalankan oleh superadmin via explicit action.
     */
    public function advertisedSubnet(): string
    {
        return self::ADVERTISED_SUBNET;
    }

    public function tailscaleUpCommand(): string
    {
        return 'sudo tailscale up --advertise-routes=' . self::ADVERTISED_SUBNET;
    }

    // ── Helpers ──────────────────────────────────────────────

    private function assertRegistered(string $deviceKey): void
    {
        if (!array_key_exists($deviceKey, self::NETWORK)) {
            throw new \InvalidArgumentException(
                "Device '{$deviceKey}' tidak terdaftar dalam jaringan Tailscale PA Semarang."
            );
        }
    }

    private function probePort(string $ip, int $port, int $timeoutSeconds = 2): bool
    {
        $conn = @fsockopen($ip, $port, $errno, $errstr, $timeoutSeconds);

        if ($conn === false) {
            return false;
        }

        fclose($conn);
        return true;
    }
}
