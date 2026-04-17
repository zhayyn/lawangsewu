<?php

namespace App\Http\Controllers;

use App\Services\LegacyPendopoSyncService;
use App\Support\LawangsewuPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SatelliteController extends Controller
{
    private const METRIC_CACHE_KEY = 'pendopo:metric:snapshot:v1';

    /**
     * Display the Pendopo guestbook in a Lawangsewu frame.
     */
    public function pendopo(LegacyPendopoSyncService $service): Response
    {
        $summary = $service->dashboardSummary();

        return Inertia::render('Lawangsewu/Satellite/Pendopo', [
            'appMeta'    => LawangsewuPortal::appMeta(),
            'navGroups'  => LawangsewuPortal::navGroups(),
            'pendopoUrl' => route('lawangsewu.guestbook.form') . '?embedded=1',
            'stats'      => $summary['stats'],
            'guestbookListUrl' => route('lawangsewu.guestbook.list', ['period' => 'all']),
            'metricUrl'  => route('lawangsewu.satellite.pendopo.metric'),
        ]);
    }

    /**
     * Store lightweight client metric for Pendopo access diagnostics.
     */
    public function metric(Request $request): JsonResponse
    {
        $event = strtolower((string) $request->query('event', 'unknown'));
        $allowedEvents = ['iframe_load', 'iframe_timeout', 'auto_redirect', 'open_direct', 'retry_iframe'];

        if (! in_array($event, $allowedEvents, true)) {
            return response()->json(['status' => 'ignored'], 202);
        }

        $durationMs = (int) $request->query('duration_ms', 0);
        $isCompact = $request->boolean('compact', false);
        $mode = (string) $request->query('mode', 'unknown');
        $durationMs = max(0, $durationMs);

        $snapshot = Cache::get(self::METRIC_CACHE_KEY, [
            'events' => [],
            'load_samples_ms' => [],
            'compact_hits' => 0,
            'total_events' => 0,
            'last_event_at' => null,
        ]);

        $snapshot['events'][$event] = (int) ($snapshot['events'][$event] ?? 0) + 1;
        $snapshot['total_events'] = (int) ($snapshot['total_events'] ?? 0) + 1;

        if ($isCompact) {
            $snapshot['compact_hits'] = (int) ($snapshot['compact_hits'] ?? 0) + 1;
        }

        if ($event === 'iframe_load' || $event === 'iframe_timeout') {
            $samples = array_values(array_map('intval', (array) ($snapshot['load_samples_ms'] ?? [])));
            $samples[] = $durationMs;

            if (count($samples) > 250) {
                $samples = array_slice($samples, -250);
            }

            $snapshot['load_samples_ms'] = $samples;
        }

        $snapshot['last_event_at'] = now('Asia/Jakarta')->toDateTimeString();

        Cache::put(self::METRIC_CACHE_KEY, $snapshot, now()->addHours(24));

        Log::info('pendopo.metric', [
            'event' => $event,
            'duration_ms' => $durationMs,
            'mode' => $mode,
            'compact' => $isCompact,
            'user_id' => optional($request->user())->id,
            'user_agent' => substr((string) $request->userAgent(), 0, 180),
            'ip' => $request->ip(),
            'at' => now('Asia/Jakarta')->toDateTimeString(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
