<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HealthController
 *
 * Endpoint publik (tanpa auth) untuk monitoring status sistem Lawangsewu.
 * Digunakan oleh:
 *   - Uptime monitor (UptimeRobot, Betterstack, dll.)
 *   - Dashboard systemHealth (status real Reverb, DB, cache)
 *   - Devops troubleshooting
 *
 * GET /health        → ringkasan overall
 * GET /health/detail → detail tiap komponen (hanya untuk admin / internal)
 */
class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = $this->runChecks();
        $allOk  = collect($checks)->every(fn ($c) => $c['ok']);

        return response()->json([
            'status'    => $allOk ? 'ok' : 'degraded',
            'timestamp' => now()->setTimezone('Asia/Jakarta')->toIso8601String(),
            'checks'    => $checks,
        ], $allOk ? 200 : 503);
    }

    // ──────────────────────────────────────────────
    // Internal checks
    // ──────────────────────────────────────────────

    private function runChecks(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
            'queue'    => $this->checkQueue(),
            'reverb'   => $this->checkReverbConfig(),
            'storage'  => $this->checkStorage(),
        ];
    }

    private function checkDatabase(): array
    {
        try {
            DB::statement('SELECT 1');
            $migrationCount = DB::table('migrations')->count();
            return ['ok' => true, 'message' => 'Connected', 'migrations' => $migrationCount];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health:ping:' . now()->timestamp;
            Cache::put($key, 1, 10);
            $hit = Cache::get($key) === 1;
            Cache::forget($key);
            return ['ok' => $hit, 'message' => $hit ? 'Cache read/write OK' : 'Cache read failed', 'driver' => config('cache.default')];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => 'Cache error: ' . $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        $connection = config('queue.default');
        return [
            'ok'         => true,
            'message'    => 'Queue driver: ' . $connection,
            'connection' => $connection,
        ];
    }

    private function checkReverbConfig(): array
    {
        $appKey = config('broadcasting.connections.reverb.key') ?? env('REVERB_APP_KEY');
        $host   = env('REVERB_HOST');
        $port   = env('REVERB_PORT', 8080);
        $enabled = env('VITE_REVERB_ENABLED', 'false') === 'true';

        return [
            'ok'      => !empty($appKey),
            'message' => $enabled ? 'Enabled — ' . $host . ':' . $port : 'Configured (disabled by flag)',
            'enabled' => $enabled,
            'host'    => $host,
            'port'    => (int) $port,
        ];
    }

    private function checkStorage(): array
    {
        $logPath = storage_path('logs');
        $ok      = is_writable($logPath);
        return [
            'ok'      => $ok,
            'message' => $ok ? 'Storage writable' : 'Storage not writable: ' . $logPath,
            'path'    => $logPath,
        ];
    }
}
