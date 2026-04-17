<?php

namespace App\Services;

use App\Models\CctvCamera;
use App\Models\PtspQueueTicket;
use App\Models\SidangQueueTicket;
use App\Models\SippCache;
use App\Models\WaCarakaConversation;
use App\Models\WaCarakaLog;
use App\Models\WaCarakaMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SystemMonitorService
{
    public function snapshot(): array
    {
        return Cache::remember('system-monitor:snapshot:v2', now()->addSeconds(20), function (): array {
            $cpu = $this->cpuStats();
            $memory = $this->memoryStats();
            $disk = $this->diskStats();
            $pendopoMetrics = $this->pendopoMetricsSummary();
            $backup = $this->backupStatus();
            $waCaraka = $this->waCarakaStats();
            $health = $this->healthScore($cpu, $memory, $disk, $pendopoMetrics, $backup, $waCaraka);

            return [
                'captured_at' => now('Asia/Jakarta')->toDateTimeString(),
                'host' => [
                    'hostname' => gethostname() ?: 'unknown',
                    'os' => PHP_OS_FAMILY,
                    'kernel' => php_uname('r') ?: 'unknown',
                    'php' => PHP_VERSION,
                    'laravel' => app()->version(),
                    'timezone' => config('app.timezone'),
                    'uptime' => $this->formatUptime($this->uptimeSeconds()),
                ],
                'cpu' => $cpu,
                'memory' => $memory,
                'disk' => $disk,
                'runtime' => [
                    ['label' => 'Queue Driver', 'value' => (string) config('queue.default', 'sync')],
                    ['label' => 'Session Driver', 'value' => (string) config('session.driver', 'file')],
                    ['label' => 'Cache Driver', 'value' => (string) config('cache.default', 'file')],
                    ['label' => 'Reverb', 'value' => env('VITE_REVERB_ENABLED', 'false') === 'true' ? 'Aktif' : 'Fallback'],
                    ['label' => 'WA Async Dispatch', 'value' => config('wa_caraka.async_dispatch', true) ? 'Aktif' : 'Nonaktif'],
                ],
                'application' => [
                    'cctv_active' => Schema::hasTable('cctv_cameras') ? CctvCamera::query()->active()->count() : null,
                    'wa_today' => Schema::hasTable('wa_caraka_logs') ? WaCarakaLog::query()->whereDate('created_at', today())->count() : null,
                    'ptsp_waiting' => Schema::hasTable('ptsp_queue_tickets') ? PtspQueueTicket::query()->today()->where('status', 'waiting')->count() : null,
                    'sidang_waiting' => Schema::hasTable('sidang_queue_tickets') ? SidangQueueTicket::query()->today()->where('status', 'waiting')->count() : null,
                    'sipp_cache_active' => Schema::hasTable('sipp_caches') ? SippCache::query()->active()->count() : null,
                    'jobs_queue_rows' => Schema::hasTable('jobs') ? (int) \DB::table('jobs')->count() : null,
                    'failed_jobs_rows' => Schema::hasTable('failed_jobs') ? (int) \DB::table('failed_jobs')->count() : null,
                ],
                'storage' => [
                    'storage_mb' => $this->bytesToMb($this->directorySize(storage_path())),
                    'logs_mb' => $this->bytesToMb($this->directorySize(storage_path('logs'))),
                ],
                'backup' => $backup,
                'pendopo_metrics' => $pendopoMetrics,
                'wa_caraka' => $waCaraka,
                'health' => $health,
                'alerts' => $this->buildAlerts($cpu, $memory, $disk, $pendopoMetrics, $backup, $waCaraka),
            ];
        });
    }

    private function cpuStats(): array
    {
        $load = sys_getloadavg() ?: [0.0, 0.0, 0.0];

        return [
            'cores' => $this->cpuCoreCount(),
            'usage_pct' => $this->cpuUsagePercent(),
            'load_1' => round((float) ($load[0] ?? 0), 2),
            'load_5' => round((float) ($load[1] ?? 0), 2),
            'load_15' => round((float) ($load[2] ?? 0), 2),
            'temperature_c' => $this->cpuTemperatureC(),
        ];
    }

    private function memoryStats(): array
    {
        $memInfo = $this->readMemInfo();
        $totalKb = (int) ($memInfo['MemTotal'] ?? 0);
        $availableKb = (int) ($memInfo['MemAvailable'] ?? 0);
        $swapTotalKb = (int) ($memInfo['SwapTotal'] ?? 0);
        $swapFreeKb = (int) ($memInfo['SwapFree'] ?? 0);

        $usedKb = max(0, $totalKb - $availableKb);
        $usedPct = $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 1) : null;

        $swapUsedKb = max(0, $swapTotalKb - $swapFreeKb);
        $swapUsedPct = $swapTotalKb > 0 ? round(($swapUsedKb / $swapTotalKb) * 100, 1) : null;

        return [
            'total_mb' => round($totalKb / 1024, 1),
            'available_mb' => round($availableKb / 1024, 1),
            'used_mb' => round($usedKb / 1024, 1),
            'used_pct' => $usedPct,
            'swap_total_mb' => round($swapTotalKb / 1024, 1),
            'swap_used_mb' => round($swapUsedKb / 1024, 1),
            'swap_used_pct' => $swapUsedPct,
        ];
    }

    private function diskStats(): array
    {
        $path = base_path();
        $total = @disk_total_space($path) ?: 0;
        $free = @disk_free_space($path) ?: 0;
        $used = max(0, $total - $free);

        return [
            'path' => $path,
            'total_gb' => $this->bytesToGb($total),
            'used_gb' => $this->bytesToGb($used),
            'free_gb' => $this->bytesToGb($free),
            'used_pct' => $total > 0 ? round(($used / $total) * 100, 1) : null,
        ];
    }

    private function pendopoMetricsSummary(): array
    {
        $snapshot = Cache::get('pendopo:metric:snapshot:v1', [
            'events' => [],
            'load_samples_ms' => [],
            'compact_hits' => 0,
            'total_events' => 0,
            'last_event_at' => null,
        ]);

        $samples = array_values(array_map('intval', (array) ($snapshot['load_samples_ms'] ?? [])));
        sort($samples);

        $count = count($samples);
        $p50 = $count > 0 ? $samples[(int) floor(($count - 1) * 0.50)] : null;
        $p95 = $count > 0 ? $samples[(int) floor(($count - 1) * 0.95)] : null;
        $avg = $count > 0 ? (int) round(array_sum($samples) / $count) : null;

        $loads = (int) (($snapshot['events']['iframe_load'] ?? 0));
        $timeouts = (int) (($snapshot['events']['iframe_timeout'] ?? 0));
        $attempts = $loads + $timeouts;

        return [
            'total_events' => (int) ($snapshot['total_events'] ?? 0),
            'compact_hits' => (int) ($snapshot['compact_hits'] ?? 0),
            'iframe_loads' => $loads,
            'iframe_timeouts' => $timeouts,
            'timeout_rate_pct' => $attempts > 0 ? round(($timeouts / $attempts) * 100, 1) : null,
            'samples' => $count,
            'avg_ms' => $avg,
            'p50_ms' => $p50,
            'p95_ms' => $p95,
            'last_event_at' => $snapshot['last_event_at'] ?? null,
        ];
    }

    private function waCarakaStats(): array
    {
        $lastArchive = Cache::get('wacaraka:last_archive');
        $archivesPath = storage_path('app/archives');
        $archiveFiles = is_dir($archivesPath) ? count(glob($archivesPath . '/wa_*.json') ?: []) : 0;

        // Ukuran direktori arsip
        $archiveSizeMb = $this->bytesToMb($this->directorySize($archivesPath));

        // Baris saat ini di database
        $totalMessages    = Schema::hasTable('wa_caraka_messages') ? WaCarakaMessage::count() : null;
        $totalLogs        = Schema::hasTable('wa_caraka_logs') ? WaCarakaLog::count() : null;
        $totalConvos      = Schema::hasTable('wa_caraka_conversations') ? WaCarakaConversation::count() : null;
        $todayMessages    = Schema::hasTable('wa_caraka_messages') ? WaCarakaMessage::whereDate('created_at', today())->count() : null;
        $unreplied        = Schema::hasTable('wa_caraka_messages') ? WaCarakaMessage::where('direction', 'inbound')->whereNull('replied_at')->count() : null;

        // Estimasi ukuran baris (kasar, bukan ukuran disk sesungguhnya)
        $estimatedRows = ($totalMessages ?? 0) + ($totalLogs ?? 0);

        // Next archive schedule: tiap tgl 1 jam 01:00
        $nextArchive = now()->startOfMonth()->addMonth()->setHour(1)->setMinute(0);

        // Perlu perhatian jika baris messages sudah > 200.000 (sesuai retensi 1 tahun)
        $dbBloatWarning = ($totalMessages ?? 0) > 200000;

        return [
            'total_messages'      => $totalMessages,
            'total_logs'          => $totalLogs,
            'total_conversations' => $totalConvos,
            'today_messages'      => $todayMessages,
            'unreplied'           => $unreplied,
            'estimated_rows'      => $estimatedRows,
            'archive_files'       => $archiveFiles,
            'archive_size_mb'     => $archiveSizeMb,
            'db_bloat_warning'    => $dbBloatWarning,
            'last_archive'        => $lastArchive,
            'next_archive_at'     => $nextArchive->timezone('Asia/Jakarta')->toDateTimeString(),
            'retention_days'      => 365,
        ];
    }

    private function buildAlerts(array $cpu, array $memory, array $disk, array $pendopoMetrics, array $backup, array $waCaraka = []): array
    {
        $alerts = [];

        if (($cpu['temperature_c'] ?? null) !== null && $cpu['temperature_c'] >= 85) {
            $alerts[] = ['level' => 'danger', 'message' => 'Suhu CPU tinggi (>= 85°C).'];
        }

        if (($memory['used_pct'] ?? null) !== null && $memory['used_pct'] >= 85) {
            $alerts[] = ['level' => 'warning', 'message' => 'Pemakaian RAM tinggi (>= 85%).'];
        }

        if (($disk['used_pct'] ?? null) !== null && $disk['used_pct'] >= 90) {
            $alerts[] = ['level' => 'danger', 'message' => 'Kapasitas disk hampir penuh (>= 90%).'];
        }

        if (($pendopoMetrics['timeout_rate_pct'] ?? null) !== null && $pendopoMetrics['timeout_rate_pct'] >= 8) {
            $alerts[] = ['level' => 'warning', 'message' => 'Timeout Pendopo meningkat (>= 8%).'];
        }

        if (($backup['status'] ?? 'missing') === 'missing') {
            $alerts[] = ['level' => 'danger', 'message' => 'Backup sistem tidak ditemukan.'];
        }

        if (($backup['status'] ?? null) === 'stale') {
            $alerts[] = ['level' => 'warning', 'message' => 'Backup terakhir sudah terlalu lama.'];
        }

        if (!empty($waCaraka['db_bloat_warning'])) {
            $rows = number_format($waCaraka['total_messages'] ?? 0);
            $alerts[] = ['level' => 'warning', 'message' => "Database WA Caraka membengkak ({$rows} pesan). Pertimbangkan menjalankan arsip lebih awal."];
        }

        if (($waCaraka['unreplied'] ?? 0) > 20) {
            $count = $waCaraka['unreplied'];
            $alerts[] = ['level' => 'warning', 'message' => "Ada {$count} pesan WA masuk yang belum dibalas."];
        }

        if ($alerts === []) {
            $alerts[] = ['level' => 'ok', 'message' => 'Kondisi umum sistem stabil.'];
        }

        return $alerts;
    }

    private function backupStatus(): array
    {
        $directories = [
            storage_path('app/backups'),
            storage_path('app/backup'),
            storage_path('app/private/backups'),
            base_path('backups'),
        ];

        $latest = null;

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = glob($dir . '/*');
            if (!is_array($iterator)) {
                continue;
            }

            foreach ($iterator as $path) {
                if (!is_file($path)) {
                    continue;
                }

                $mtime = @filemtime($path);
                if ($mtime === false) {
                    continue;
                }

                if ($latest === null || $mtime > $latest['timestamp']) {
                    $latest = [
                        'path' => $path,
                        'name' => basename($path),
                        'timestamp' => $mtime,
                        'size_mb' => $this->bytesToMb((int) (@filesize($path) ?: 0)),
                    ];
                }
            }
        }

        if ($latest === null) {
            return [
                'status' => 'missing',
                'condition_pct' => 0,
                'health' => 'Kritis',
                'latest_file' => null,
                'last_backup_at' => null,
                'age_hours' => null,
                'size_mb' => null,
            ];
        }

        $ageHours = max(0, (int) floor((time() - $latest['timestamp']) / 3600));

        if ($ageHours <= 24) {
            $status = 'fresh';
            $conditionPct = 100;
            $health = 'Sangat Baik';
        } elseif ($ageHours <= 72) {
            $status = 'stale';
            $conditionPct = 65;
            $health = 'Perlu Perhatian';
        } else {
            $status = 'critical';
            $conditionPct = 25;
            $health = 'Kritis';
        }

        return [
            'status' => $status,
            'condition_pct' => $conditionPct,
            'health' => $health,
            'latest_file' => $latest['name'],
            'last_backup_at' => now('Asia/Jakarta')->setTimestamp($latest['timestamp'])->toDateTimeString(),
            'age_hours' => $ageHours,
            'size_mb' => $latest['size_mb'],
        ];
    }

    private function healthScore(array $cpu, array $memory, array $disk, array $pendopoMetrics, array $backup, array $waCaraka = []): array
    {
        $score = 100;

        if (($cpu['temperature_c'] ?? 0) >= 90) {
            $score -= 20;
        } elseif (($cpu['temperature_c'] ?? 0) >= 80) {
            $score -= 10;
        }

        if (($memory['used_pct'] ?? 0) >= 90) {
            $score -= 20;
        } elseif (($memory['used_pct'] ?? 0) >= 80) {
            $score -= 10;
        }

        if (($disk['used_pct'] ?? 0) >= 95) {
            $score -= 25;
        } elseif (($disk['used_pct'] ?? 0) >= 85) {
            $score -= 12;
        }

        if (($pendopoMetrics['timeout_rate_pct'] ?? 0) >= 12) {
            $score -= 15;
        } elseif (($pendopoMetrics['timeout_rate_pct'] ?? 0) >= 8) {
            $score -= 8;
        }

        if (($backup['status'] ?? 'missing') === 'missing') {
            $score -= 35;
        } elseif (($backup['status'] ?? '') === 'critical') {
            $score -= 25;
        } elseif (($backup['status'] ?? '') === 'stale') {
            $score -= 10;
        }

        // WA Caraka: database bloat penalty
        if (!empty($waCaraka['db_bloat_warning'])) {
            $score -= 8;
        }

        $score = max(0, min(100, $score));

        if ($score >= 85) {
            $grade = 'Sehat';
        } elseif ($score >= 65) {
            $grade = 'Waspada';
        } else {
            $grade = 'Kritis';
        }

        return [
            'score_pct' => $score,
            'grade' => $grade,
        ];
    }

    private function cpuCoreCount(): int
    {
        $cpuInfoPath = '/proc/cpuinfo';
        if (!is_readable($cpuInfoPath)) {
            return 1;
        }

        $contents = file_get_contents($cpuInfoPath) ?: '';
        preg_match_all('/^processor\s*:/m', $contents, $matches);

        return max(1, count($matches[0]));
    }

    private function cpuUsagePercent(): ?float
    {
        $first = $this->readCpuTimes();
        if ($first === null) {
            return null;
        }

        usleep(120000);

        $second = $this->readCpuTimes();
        if ($second === null) {
            return null;
        }

        $totalDelta = $second['total'] - $first['total'];
        $idleDelta = $second['idle'] - $first['idle'];

        if ($totalDelta <= 0) {
            return null;
        }

        return round((1 - ($idleDelta / $totalDelta)) * 100, 1);
    }

    private function readCpuTimes(): ?array
    {
        $line = @file('/proc/stat')[0] ?? null;
        if ($line === null) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($line));
        if (!is_array($parts) || count($parts) < 5) {
            return null;
        }

        array_shift($parts);
        $values = array_map('intval', $parts);
        $idle = ($values[3] ?? 0) + ($values[4] ?? 0);

        return [
            'total' => array_sum($values),
            'idle' => $idle,
        ];
    }

    private function cpuTemperatureC(): ?float
    {
        $zoneFiles = glob('/sys/class/thermal/thermal_zone*/temp') ?: [];
        $temps = [];

        foreach ($zoneFiles as $file) {
            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            $value = (float) trim($raw);
            if ($value <= 0) {
                continue;
            }

            $temps[] = $value > 1000 ? ($value / 1000) : $value;
        }

        if ($temps === []) {
            return null;
        }

        return round(max($temps), 1);
    }

    private function readMemInfo(): array
    {
        $path = '/proc/meminfo';
        if (!is_readable($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $data = [];
        foreach ($lines as $line) {
            if (preg_match('/^([A-Za-z_\(\)]+):\s+(\d+)/', $line, $matches) !== 1) {
                continue;
            }

            $data[$matches[1]] = (int) $matches[2];
        }

        return $data;
    }

    private function uptimeSeconds(): int
    {
        $path = '/proc/uptime';
        if (!is_readable($path)) {
            return 0;
        }

        $raw = trim((string) file_get_contents($path));
        $parts = preg_split('/\s+/', $raw);

        return isset($parts[0]) ? (int) floor((float) $parts[0]) : 0;
    }

    private function formatUptime(int $seconds): string
    {
        if ($seconds <= 0) {
            return 'N/A';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%d hari %d jam %d menit', $days, $hours, $minutes);
    }

    private function directorySize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Throwable) {
            return 0;
        }

        return $size;
    }

    private function bytesToMb(int $bytes): float
    {
        return round($bytes / 1048576, 2);
    }

    private function bytesToGb(int $bytes): float
    {
        return round($bytes / 1073741824, 2);
    }
}
