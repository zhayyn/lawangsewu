<?php

namespace App\Services\WaCaraka;

use App\Models\WaCarakaConversation;
use App\Models\WaCarakaDailyMetric;
use App\Models\WaCarakaLog;
use App\Models\WaCarakaMessage;
use App\Support\WaCarakaDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * WaCarakaStatsService
 *
 * Handles all statistics and metrics for the WA Caraka module.
 * Provides both legacy log-based stats and enhanced message-based analytics.
 */
class WaCarakaStatsService
{
    /**
     * Stats from the legacy wa_caraka_logs table.
     *
     * @return array
     */
    public function stats(): array
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_logs')) {
            return [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'today' => 0,
                'lastSent' => 'Belum ada',
            ];
        }

        return [
            'total'    => WaCarakaLog::count(),
            'sent'     => WaCarakaLog::where('status', 'sent')->count(),
            'failed'   => WaCarakaLog::where('status', 'failed')->count(),
            'today'    => WaCarakaLog::whereDate('created_at', today())->count(),
            'lastSent' => optional(WaCarakaLog::latest()->first())?->created_at?->diffForHumans() ?? 'Belum ada',
        ];
    }

    /**
     * Enhanced stats from the new wa_caraka_messages table.
     *
     * @return array
     */
    public function messageStats(): array
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            return [
                'totalMessages' => 0,
                'inbound' => 0,
                'outbound' => 0,
                'unreplied' => 0,
                'todayInbound' => 0,
                'todayOutbound' => 0,
                'conversations' => 0,
            ];
        }

        // Pending reply is counted from conversations shown in inbox:
        // chat terakhir bukan dari WA Caraka (direction terakhir !== outbound).
        // Use effective runtime timestamp when available; pull-inbox can import
        // older messages with newer DB ids.
        $inboxConversationIds = WaCarakaConversation::query()
            ->whereIn('conversation_id', function ($query) {
                $query->from('wa_caraka_messages')
                    ->select('conversation_id')
                    ->whereNotNull('conversation_id')
                    ->groupBy('conversation_id');
            })
            ->pluck('conversation_id')
            ->filter()
            ->values();

        $latestByConversation = [];

        WaCarakaMessage::query()
            ->select(['conversation_id', 'direction', 'created_at', 'metadata', 'replied_at'])
            ->whereIn('conversation_id', $inboxConversationIds)
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$latestByConversation) {
                foreach ($rows as $row) {
                    $metadata = is_array($row->metadata) ? $row->metadata : [];
                    $rawTs = $metadata['timestamp'] ?? ($metadata['raw']['timestamp'] ?? null);

                    $effectiveAt = $row->created_at;
                    if (is_string($rawTs) && trim($rawTs) !== '') {
                        try {
                            $parsed = Carbon::parse($rawTs);
                            if ($parsed) {
                                $effectiveAt = $parsed;
                            }
                        } catch (\Throwable $e) {
                            // Ignore malformed runtime timestamp, fallback to created_at.
                        }
                    }

                    $cid = (string) $row->conversation_id;
                    if (!isset($latestByConversation[$cid]) || $effectiveAt->gte($latestByConversation[$cid]['at'])) {
                        $latestByConversation[$cid] = [
                            'at' => $effectiveAt,
                            'direction' => $row->direction,
                            'replied_at' => $row->replied_at,
                        ];
                    }
                }
            });

        $unrepliedConversations = collect($latestByConversation)
            ->filter(fn ($item) => ($item['direction'] ?? null) === 'inbound' && is_null($item['replied_at'] ?? null))
            ->count();

        $excludeNumbers = ['engine-health-check', 'tokenless-route-check', 'status@broadcast', 'health-check', 'health_check'];

        return [
            'totalMessages'  => WaCarakaMessage::whereNotIn('remote_number', $excludeNumbers)->count(),
            'inbound'        => WaCarakaMessage::inbound()->whereNotIn('remote_number', $excludeNumbers)->count(),
            'outbound'       => WaCarakaMessage::outbound()->whereNotIn('remote_number', $excludeNumbers)->count(),
            'unreplied'      => $unrepliedConversations,
            'todayInbound'   => WaCarakaMessage::inbound()->whereNotIn('remote_number', $excludeNumbers)->today()->count(),
            'todayOutbound'  => WaCarakaMessage::outbound()->whereNotIn('remote_number', $excludeNumbers)->today()->count(),
            'conversations'  => WaCarakaMessage::whereNotIn('remote_number', $excludeNumbers)->distinct('conversation_id')->count('conversation_id'),
        ];
    }

    /**
     * Recent log entries from legacy table.
     *
     * @param  int  $limit
     * @return array
     */
    public function recentLogs(int $limit = 20): array
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_logs')) {
            return [];
        }

        $displayLimit = (int) config('wa_caraka.log_display_limit', $limit);

        return WaCarakaLog::latest()
            ->limit(min($limit, $displayLimit))
            ->get()
            ->map(fn (WaCarakaLog $log) => [
                'id'       => $log->id,
                'sender'   => $log->sender,
                'receiver' => $log->receiver,
                'message'  => $log->message,
                'type'     => $log->type,
                'status'   => $log->status,
                'sentAt'   => $log->created_at?->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
            ])
            ->all();
    }

    /**
     * Record a daily metric for a message.
     *
     * @param  WaCarakaMessage  $message
     * @return void
     */
    public function recordDailyMetricForMessage(WaCarakaMessage $message): void
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_daily_metrics')) {
            return;
        }

        $metricDate = ($message->created_at ?? now())
            ->copy()
            ->setTimezone(config('app.timezone'))
            ->toDateString();

        $scopeKey = 'global';
        $isHistory = (bool) data_get($message->metadata, 'historySync', false)
            || data_get($message->metadata, 'syncSource') === 'history';

        $metric = WaCarakaDailyMetric::query()->firstOrCreate(
            ['scope_key' => $scopeKey, 'metric_date' => $metricDate],
            [
                'total_messages' => 0,
                'inbound_messages' => 0,
                'outbound_messages' => 0,
                'history_inbound_messages' => 0,
                'history_outbound_messages' => 0,
                'realtime_inbound_messages' => 0,
                'realtime_outbound_messages' => 0,
            ],
        );

        $metric->increment('total_messages');

        if ($message->direction === 'outbound') {
            $metric->increment('outbound_messages');
            $metric->increment($isHistory ? 'history_outbound_messages' : 'realtime_outbound_messages');
        } else {
            $metric->increment('inbound_messages');
            $metric->increment($isHistory ? 'history_inbound_messages' : 'realtime_inbound_messages');
        }
    }

    /**
     * Rebuild all daily metrics from message history.
     *
     * @return array
     */
    public function rebuildDailyMetrics(): array
    {
        if (
            !WaCarakaDatabase::hasTable('wa_caraka_daily_metrics')
            || !WaCarakaDatabase::hasTable('wa_caraka_messages')
        ) {
            return ['days' => 0, 'messages' => 0];
        }

        WaCarakaDatabase::transaction(function () {
            WaCarakaDailyMetric::query()->delete();

            $rows = WaCarakaMessage::query()
                ->selectRaw('DATE(created_at) as metric_date')
                ->selectRaw('COUNT(*) as total_messages')
                ->selectRaw("SUM(CASE WHEN direction = 'inbound' THEN 1 ELSE 0 END) as inbound_messages")
                ->selectRaw("SUM(CASE WHEN direction = 'outbound' THEN 1 ELSE 0 END) as outbound_messages")
                ->selectRaw("SUM(CASE WHEN direction = 'inbound' AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.syncSource')) = 'history' THEN 1 ELSE 0 END) as history_inbound_messages")
                ->selectRaw("SUM(CASE WHEN direction = 'outbound' AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.syncSource')) = 'history' THEN 1 ELSE 0 END) as history_outbound_messages")
                ->selectRaw("SUM(CASE WHEN direction = 'inbound' AND (JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.syncSource')) IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.syncSource')) != 'history') THEN 1 ELSE 0 END) as realtime_inbound_messages")
                ->selectRaw("SUM(CASE WHEN direction = 'outbound' AND (JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.syncSource')) IS NULL OR JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.syncSource')) != 'history') THEN 1 ELSE 0 END) as realtime_outbound_messages")
                ->groupBy('metric_date')
                ->orderBy('metric_date')
                ->get();

            foreach ($rows as $row) {
                WaCarakaDailyMetric::query()->create([
                    'scope_key' => 'global',
                    'metric_date' => $row->metric_date,
                    'total_messages' => (int) $row->total_messages,
                    'inbound_messages' => (int) $row->inbound_messages,
                    'outbound_messages' => (int) $row->outbound_messages,
                    'history_inbound_messages' => (int) $row->history_inbound_messages,
                    'history_outbound_messages' => (int) $row->history_outbound_messages,
                    'realtime_inbound_messages' => (int) $row->realtime_inbound_messages,
                    'realtime_outbound_messages' => (int) $row->realtime_outbound_messages,
                ]);
            }
        });

        return [
            'days' => WaCarakaDailyMetric::query()->count(),
            'messages' => WaCarakaMessage::query()->count(),
        ];
    }
}
