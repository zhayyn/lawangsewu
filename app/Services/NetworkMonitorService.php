<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class NetworkMonitorService
{
    private const CACHE_TTL = 5; // seconds

    public function snapshot(): array
    {
        return Cache::remember('network-monitor:snapshot', now()->addSeconds(self::CACHE_TTL), function (): array {
            return [
                'captured_at' => now('Asia/Jakarta')->format('H:i:s'),
                'interfaces' => $this->getNetworkInterfaces(),
                'bandwidth_history' => $this->getBandwidthHistory(),
                'top_talkers' => $this->getTopTalkers(),
                'stream_health' => $this->getStreamHealth(),
                'system_net' => $this->getSystemNetworkStats(),
            ];
        });
    }

    private function getNetworkInterfaces(): array
    {
        $interfaces = [];
        $path = '/proc/net/dev';

        if (!is_readable($path)) {
            return $interfaces;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        // Skip header lines
        $dataLines = array_slice($lines, 2);

        foreach ($dataLines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 10) continue;

            $name = trim($parts[0], ':');
            if ($this->shouldIgnoreInterface($name)) continue;

            $rxBytes = (int) ($parts[1] ?? 0);
            $txBytes = (int) ($parts[9] ?? 0);

            // Get previous values for speed calculation
            $prev = $this->getPreviousStats($name);

            $intervalSeconds = $prev ? max(1, time() - (int) ($prev['timestamp'] ?? time())) : 0;
            $rxRate = $prev ? $this->calculateRate($rxBytes, $prev['rx'], $intervalSeconds) : 0;
            $txRate = $prev ? $this->calculateRate($txBytes, $prev['tx'], $intervalSeconds) : 0;

            $interfaces[$name] = [
                'name' => $name,
                'rx_bytes' => $rxBytes,
                'tx_bytes' => $txBytes,
                'rx_rate' => $rxRate,
                'tx_rate' => $txRate,
                'rx_human' => $this->humanBandwidth($rxRate),
                'tx_human' => $this->humanBandwidth($txRate),
                'total_bytes' => $rxBytes + $txBytes,
                'status' => $this->getInterfaceStatus($name),
            ];

            // Store current for next calculation
            $this->storeStats($name, $rxBytes, $txBytes);
        }

        return $interfaces;
    }

    private function shouldIgnoreInterface(string $name): bool
    {
        if ($name === 'lo') {
            return true;
        }

        foreach (['docker', 'br-', 'veth', 'weave', 'flannel', 'cilium'] as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function getPreviousStats(string $interface): ?array
    {
        $cacheKey = "netif:{$interface}:prev";
        return Cache::get($cacheKey);
    }

    private function storeStats(string $interface, int $rx, int $tx): void
    {
        Cache::put("netif:{$interface}:prev", [
            'rx' => $rx,
            'tx' => $tx,
            'timestamp' => time(),
        ], 10);
    }

    private function calculateRate(int $current, int $previous, int $intervalSeconds): float
    {
        if ($intervalSeconds <= 0) return 0;
        $delta = $current >= $previous ? $current - $previous : 0;
        return round($delta / $intervalSeconds, 0);
    }

    private function humanBandwidth(float $bytesPerSec): string
    {
        if ($bytesPerSec >= 1073741824) {
            return sprintf('%.1f GB/s', $bytesPerSec / 1073741824);
        } elseif ($bytesPerSec >= 1048576) {
            return sprintf('%.1f MB/s', $bytesPerSec / 1048576);
        } elseif ($bytesPerSec >= 1024) {
            return sprintf('%.1f KB/s', $bytesPerSec / 1024);
        }
        return sprintf('%.0f B/s', $bytesPerSec);
    }

    private function getInterfaceStatus(string $interface): string
    {
        $path = "/sys/class/net/{$interface}/operstate";
        if (is_readable($path)) {
            $state = trim(file_get_contents($path) ?: '');
            return $state === 'up' ? 'active' : 'down';
        }
        return 'unknown';
    }

    private function getBandwidthHistory(): array
    {
        $history = Cache::get('network-monitor:history', []);
        $current = $this->getCurrentBandwidth();

        $history[] = [
            'time' => now('Asia/Jakarta')->format('H:i:s'),
            'rx' => $current['rx'],
            'tx' => $current['tx'],
        ];

        // Keep last 60 samples (5 minutes at 5s interval)
        $history = array_slice($history, -60);

        Cache::put('network-monitor:history', $history, 600);

        return $history;
    }

    private function getCurrentBandwidth(): array
    {
        $totalRx = 0;
        $totalTx = 0;

        if (!is_readable('/proc/net/dev')) {
            return ['rx' => 0, 'tx' => 0];
        }

        $lines = file('/proc/net/dev', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach (array_slice($lines, 2) as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 10) continue;

            $name = trim($parts[0], ':');
            if ($this->shouldIgnoreInterface($name)) continue;

            $totalRx += (int) ($parts[1] ?? 0);
            $totalTx += (int) ($parts[9] ?? 0);
        }

        return ['rx' => $totalRx, 'tx' => $totalTx];
    }

    private function getTopTalkers(): array
    {
        // Parse connections to find top talkers
        $connections = [];

        // Read /proc/net/tcp and /proc/net/udp
        foreach (['tcp', 'udp'] as $proto) {
            $path = "/proc/net/{$proto}";
            if (!is_readable($path)) continue;

            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            // Skip header
            foreach (array_slice($lines, 1) as $line) {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) < 10) continue;

                // Local address is column 1, remote is column 2
                $localAddr = $this->parseIpAddress($parts[1] ?? '');
                $state = $this->parseTcpState($parts[3] ?? '', $proto);

                $key = $localAddr ?: 'unknown';
                if (!isset($connections[$key])) {
                    $connections[$key] = ['ip' => $key, 'connections' => 0, 'state' => []];
                }
                $connections[$key]['connections']++;
                $connections[$key]['state'][$state] = ($connections[$key]['state'][$state] ?? 0) + 1;
            }
        }

        // Sort by connections and take top 10
        uasort($connections, fn($a, $b) => $b['connections'] <=> $a['connections']);
        return array_slice($connections, 0, 10);
    }

    private function parseIpAddress(string $hex): string
    {
        if (strlen($hex) < 14) return '';

        $ipHex = substr($hex, 0, 8);
        $portHex = substr($hex, 9, 4);

        // Little-endian to big-endian
        $ip = [];
        for ($i = 3; $i >= 0; $i--) {
            $byte = substr($ipHex, $i * 2, 2);
            $ip[] = hexdec($byte);
        }

        $port = hexdec($portHex);

        // Filter internal addresses
        $ipStr = implode('.', $ip);
        if (preg_match('/^(127\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)/', $ipStr)) {
            return '';
        }

        return $ipStr;
    }

    private function parseTcpState(string $stateHex, string $proto): string
    {
        if ($proto === 'udp') return 'LISTEN';

        $states = [
            '01' => 'ESTABLISHED',
            '02' => 'SYN_SENT',
            '03' => 'SYN_RECV',
            '04' => 'FIN_WAIT1',
            '05' => 'FIN_WAIT2',
            '06' => 'TIME_WAIT',
            '07' => 'CLOSE',
            '08' => 'CLOSE_WAIT',
            '09' => 'LAST_ACK',
            '0A' => 'LISTEN',
            '0B' => 'CLOSING',
        ];

        return $states[strtoupper($stateHex)] ?? 'UNKNOWN';
    }

    private function getStreamHealth(): array
    {
        $streams = [];

        // Get CCTV cameras from database
        $cameras = $this->getCctvCameras();

        // Try multiple CCTV proxy endpoints
        $endpoints = $this->getCctvEndpoints();

        foreach ($endpoints as $endpointUrl) {
            $context = stream_context_create([
                'http' => ['timeout' => 2, 'ignore_errors' => true]
            ]);

            // Try go2rtc API
            $go2rtcUrl = $endpointUrl . '/api/streams';
            $response = @file_get_contents($go2rtcUrl, false, $context);

            if ($response !== false) {
                $data = json_decode($response, true);
                if (is_array($data) && !empty($data)) {
                    foreach ($data as $name => $config) {
                        $status = 'idle';
                        $codec = 'N/A';

                        // Check if stream has active producer
                        if (isset($config['producers']) && is_array($config['producers'])) {
                            foreach ($config['producers'] as $producer) {
                                if (($producer['ready'] ?? false) === true) {
                                    $status = 'live';
                                    $codec = $producer['codec'] ?? 'auto';
                                    break;
                                }
                            }
                        }

                        // Check if source is connected
                        if (isset($config['source']) && !empty($config['source'])) {
                            $status = $status === 'live' ? 'live' : 'connecting';
                        }

                        $streams[] = [
                            'name' => $name,
                            'status' => $status,
                            'source' => $config['source'] ?? null,
                            'codec' => $codec,
                            'proxy' => $endpointUrl,
                        ];
                    }
                    break; // Use first working endpoint
                }
            }

            // Try MediaMTX API
            $mediamtxUrl = $endpointUrl . '/v3/paths/list';
            $response = @file_get_contents($mediamtxUrl, false, $context);

            if ($response !== false) {
                $data = json_decode($response, true);
                if (isset($data['items']) && is_array($data['items']) && !empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $name = $item['name'] ?? 'unknown';
                        $ready = $item['ready'] ?? false;

                        $streams[] = [
                            'name' => $name,
                            'status' => $ready ? 'live' : 'idle',
                            'source' => $item['source'] ?? null,
                            'codec' => $this->extractCodec($item),
                            'proxy' => $endpointUrl,
                        ];
                    }
                    break;
                }
            }
        }

        // Fallback: use database cameras if no proxy detected
        if (empty($streams)) {
            foreach ($cameras as $camera) {
                $streams[] = [
                    'name' => $camera['name'],
                    'status' => 'configured',
                    'source' => $camera['key'],
                    'codec' => 'RTSP',
                    'proxy' => null,
                ];
            }
        }

        return $streams;
    }

    private function getCctvEndpoints(): array
    {
        $endpoints = [];

        // Remote CCTV server (192.168.88.200) - prioritize this
        $remoteUrl = env('CCTV_SERVER_URL', '');
        if ($remoteUrl) {
            $endpoints[] = rtrim($remoteUrl, '/');
        }

        // Local CCTV proxy
        $localUrl = env('CCTV_PROXY_URL', 'http://localhost:1984');
        if ($localUrl && !in_array($localUrl, $endpoints)) {
            $endpoints[] = rtrim($localUrl, '/');
        }

        // Default fallback
        if (empty($endpoints)) {
            $endpoints[] = 'http://localhost:1984';
        }

        return array_unique($endpoints);
    }

    private function getCctvProxyUrl(): string
    {
        // Check environment for CCTV proxy URL
        $envUrl = env('CCTV_PROXY_URL', 'http://localhost:1984');

        // Allow override for remote CCTV server (192.168.88.200)
        $remoteUrl = env('CCTV_SERVER_URL', null);
        if ($remoteUrl) {
            return $remoteUrl;
        }

        return $envUrl;
    }

    private function getCctvCameras(): array
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('cctv_cameras')) {
                return [];
            }

            $cameras = \App\Models\CctvCamera::query()
                ->active()
                ->orderBy('sort_order')
                ->get(['key', 'name', 'zone'])
                ->toArray();

            return $cameras;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function extractCodec(array $item): string
    {
        $producer = $item['producer'] ?? [];
        if (isset($producer['codecs'])) {
            return implode(', ', $producer['codecs']);
        }
        return 'N/A';
    }

    private function getCctvFromDocker(): array
    {
        $streams = [];

        // Check for CCTV-related containers
        $output = shell_exec("docker ps --filter 'name=cctv' --format '{{.Names}}' 2>/dev/null");

        if ($output) {
            $containers = array_filter(explode("\n", trim($output)));
            foreach ($containers as $container) {
                $streams[] = [
                    'name' => $container,
                    'status' => 'active',
                    'source' => 'docker',
                    'codec' => 'auto',
                ];
            }
        }

        return $streams;
    }

    private function getSystemNetworkStats(): array
    {
        $stats = [
            'total_connections' => 0,
            'established' => 0,
            'time_wait' => 0,
            'listen' => 0,
            'dns_queries' => 0,
        ];

        $states = $this->readTcpStates();

        $stats['total_connections'] = array_sum($states);
        $stats['established'] = $states['01'] ?? 0;
        $stats['time_wait'] = $states['06'] ?? 0;
        $stats['listen'] = $states['0A'] ?? 0;

        // DNS queries from /proc/net/stat/nscd or similar
        // Simplified: just return what we can parse
        $stats['dns_queries'] = 0;

        return $stats;
    }

    private function readTcpStates(): array
    {
        $states = [];

        foreach (['/proc/net/tcp', '/proc/net/tcp6'] as $path) {
            if (!is_readable($path)) {
                continue;
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

            foreach (array_slice($lines, 1) as $line) {
                $parts = preg_split('/\s+/', trim($line));
                $state = strtoupper($parts[3] ?? '');

                if ($state === '') {
                    continue;
                }

                $states[$state] = ($states[$state] ?? 0) + 1;
            }
        }

        return $states;
    }

    public function getInterfaceDetails(string $interface): array
    {
        $path = "/sys/class/net/{$interface}";

        if (!is_dir($path)) {
            return ['error' => 'Interface not found'];
        }

        return [
            'name' => $interface,
            'mtu' => $this->readSysfs("{$path}/mtu"),
            'speed' => $this->readSysfs("{$path}/speed"),
            'duplex' => $this->readSysfs("{$path}/duplex"),
            'operstate' => $this->readSysfs("{$path}/operstate"),
            'address' => $this->readSysfs("{$path}/address"),
            'flags' => $this->readSysfs("{$path}/flags"),
        ];
    }

    private function readSysfs(string $path): string
    {
        if (!is_readable($path)) {
            return 'N/A';
        }
        return trim(file_get_contents($path) ?: 'N/A');
    }
}
