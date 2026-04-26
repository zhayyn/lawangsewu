<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Database Optimization Service
 * 
 * Implements query optimization patterns:
 * - Eager loading relationships
 * - Query result caching
 * - N+1 problem prevention
 * - Performance monitoring
 */
class DatabaseOptimizationService
{
    /**
     * Eager load relationships with cache
     */
    public static function eagerLoadWithCache($query, $relationships, $cacheMinutes = 60)
    {
        $cacheKey = self::generateCacheKey($query, $relationships);
        
        return cache()->remember($cacheKey, $cacheMinutes * 60, function () use ($query, $relationships) {
            return $query->with($relationships)->get();
        });
    }

    /**
     * Optimize SIPP queries with caching
     * 
     * SIPP is remote database, so cache aggressively
     */
    public static function getSippCasesOptimized($caseIds, $cacheMinutes = 120)
    {
        $cacheKey = 'sipp_cases:' . hash('sha256', implode(',', $caseIds));
        
        return cache()->remember($cacheKey, $cacheMinutes * 60, function () use ($caseIds) {
            Log::info('SIPP query cache miss', [
                'case_count' => count($caseIds),
                'cache_key' => substr($cacheKey, 0, 20) . '...',
            ]);
            
            return \DB::connection('sipp')
                ->table('perkara')
                ->whereIn('id', $caseIds)
                ->get();
        });
    }

    /**
     * Batch eager loading for controllers
     * 
     * Prevents N+1 in PtspQueue, SidangQueue, Widget controllers
     */
    public static function eagerLoadConversations($conversationIds)
    {
        return \App\Models\Conversation::whereIn('id', $conversationIds)
            ->with([
                'chatMessages' => fn($q) => $q->latest()->limit(5),
                'chatMessages.user' => fn($q) => $q->select('id', 'name', 'email'),
                'user' => fn($q) => $q->select('id', 'name', 'email', 'avatar_url'),
            ])
            ->get();
    }

    /**
     * Load chat messages with user relationships
     * 
     * Used in ChatController and related endpoints
     */
    public static function loadChatMessagesOptimized($conversationId, $limit = 50)
    {
        return \App\Models\ChatMessage::where('conversation_id', $conversationId)
            ->with([
                'user' => fn($q) => $q->select('id', 'name', 'email', 'avatar_url'),
                'conversation' => fn($q) => $q->select('id', 'case_id', 'nomor_perkara'),
            ])
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse(); // Chronological order
    }

    /**
     * Load WA messages with context
     */
    public static function loadWaMessagesOptimized($conversationId, $limit = 50)
    {
        return \App\Models\WaMessage::where('conversation_id', $conversationId)
            ->with([
                'conversation' => fn($q) => $q->select('id', 'phone', 'contact_name'),
                'template' => fn($q) => $q->select('id', 'name'),
            ])
            ->latest()
            ->limit($limit)
            ->get()
            ->reverse();
    }

    /**
     * Load queue tickets with all related data
     */
    public static function loadQueueTicketsOptimized($date = null, $type = 'ptsp')
    {
        $query = $type === 'ptsp' 
            ? \App\Models\PtspQueueTicket::query()
            : \App\Models\SidangQueueTicket::query();

        if ($date) {
            $query = $query->whereDate('created_at', $date);
        }

        return $query->with([
            'counter' => fn($q) => $q->select('id', 'name', 'display_label'),
            'service' => fn($q) => $q->select('id', 'name', 'code'),
        ])
            ->orderByRaw("case when status = 'called' then 0 when status = 'waiting' then 1 when status = 'served' then 2 else 3 end")
            ->orderBy('id')
            ->get();
    }

    /**
     * Generate cache key from query
     */
    private static function generateCacheKey(Builder|string $query, $relationships): string
    {
        if (is_string($query)) {
            return "query:{$query}:" . md5(json_encode($relationships));
        }

        return "query:" . md5($query->toSql()) . ":" . md5(json_encode($relationships));
    }

    /**
     * Clear related caches
     */
    public static function clearCache($pattern = null)
    {
        if ($pattern) {
            // Redis-based cache clearing
            cache()->tags(['query'])->flush();
            Log::info('Cache cleared', ['pattern' => $pattern]);
        }
    }

    /**
     * Monitor query performance
     */
    public static function monitorQueryPerformance()
    {
        \DB::listen(function ($query) {
            $duration = $query->time; // milliseconds

            // Log slow SIPP queries
            if (str_contains($query->sql, 'SIPP') && $duration > 500) {
                Log::warning('Slow SIPP query', [
                    'duration_ms' => $duration,
                    'sql' => substr($query->sql, 0, 100),
                ]);
            }

            // Log slow local queries
            if ($duration > 1000) {
                Log::warning('Slow database query', [
                    'duration_ms' => $duration,
                    'sql' => substr($query->sql, 0, 100),
                    'bindings' => $query->bindings,
                ]);
            }
        });
    }
}
