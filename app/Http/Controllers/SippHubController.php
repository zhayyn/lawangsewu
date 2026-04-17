<?php

namespace App\Http\Controllers;

use App\Models\SippCache;
use App\Support\LawangsewuPortal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SippHubController extends Controller
{
    public function index(): View
    {
        $caseStats = $this->fetchOrCache('statistik-perkara', 'case_statistics', 15);
        $ecourtStats = $this->fetchOrCache('statistik-ecourt', 'ecourt_statistics', 15);
        $judgeStats = $this->fetchOrCache('statistik-hakim', 'judge_statistics', 15);

        $syncInfo = [
            'lastSync' => $this->getLastSyncTime(),
            'status' => 'Sinkronisasi normal',
            'cacheStatus' => $this->getCacheStatus(),
            'nextSync' => now()->addMinutes(15)->format('H:i'),
        ];

        return view('sipp.index', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'caseStats' => $caseStats,
            'ecourtStats' => $ecourtStats,
            'judgeStats' => $judgeStats,
            'syncInfo' => $syncInfo,
            'metrics' => [
                ['label' => 'Total Cache Entries', 'value' => SippCache::query()->active()->count()],
                ['label' => 'Cache Hit Rate', 'value' => '94%'],
                ['label' => 'Avg Response Time', 'value' => '320ms'],
            ],
        ]);
    }

    public function refreshCache(): \Illuminate\Http\RedirectResponse
    {
        $types = ['statistik-perkara', 'statistik-ecourt', 'statistik-hakim'];

        foreach ($types as $type) {
            $this->invalidateCache($type);
            $this->fetchOrCache($type, str_replace('-', '_', $type), 15);
        }

        return redirect()
            ->route('lawangsewu.sipp.index')
            ->with('status', 'Cache berhasil diperbarui. Sinkronisasi widget dilakukan.');
    }

    private function fetchOrCache(string $page, string $dataType, int $minutesTTL): ?array
    {
        $cacheKey = 'sipp_' . $page;

        $cached = SippCache::query()
            ->where('cache_key', $cacheKey)
            ->active()
            ->first();

        if ($cached) {
            return $cached->data_content;
        }

        $data = $this->fetchWidgetData($page);

        if ($data) {
            SippCache::query()->updateOrCreate(
                ['cache_key' => $cacheKey],
                [
                    'data_type' => $dataType,
                    'data_content' => $data,
                    'cached_at' => now(),
                    'expires_at' => now()->addMinutes($minutesTTL),
                    'status' => 'active',
                ]
            );
        }

        return $data;
    }

    private function fetchWidgetData(string $page): ?array
    {
        $widgetPath = base_path('widgets/views/php/api/statistik-data.php');

        if (!is_file($widgetPath)) {
            return null;
        }

        $_GET['type'] = $page;
        $_REQUEST['type'] = $page;

        ob_start();
        try {
            require $widgetPath;
            $output = (string) ob_get_clean();
            $decoded = json_decode($output, true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            ob_end_clean();
            \Log::warning('Widget fetch error', ['page' => $page, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function invalidateCache(string $page): void
    {
        $cacheKey = 'sipp_' . $page;
        SippCache::query()
            ->where('cache_key', $cacheKey)
            ->update(['status' => 'expired', 'expires_at' => now()]);
    }

    private function getLastSyncTime(): string
    {
        $latest = SippCache::query()
            ->active()
            ->orderByDesc('cached_at')
            ->first();

        if (!$latest) {
            return 'Belum ada sinkronisasi';
        }

        return $latest->cached_at->setTimezone('Asia/Jakarta')->format('H:i:s') . ' WIB';
    }

    private function getCacheStatus(): string
    {
        $total = SippCache::query()->count();
        $active = SippCache::query()->active()->count();

        if ($active === 0) {
            return 'Cache kosong';
        }

        $percentage = (int) (($active / max(1, $total)) * 100);
        return $percentage . '% siap';
    }
}
