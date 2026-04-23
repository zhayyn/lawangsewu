<?php

namespace App\Jobs;

use App\Services\WaCarakaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FetchWaRuntimeContactMetadata
 *
 * Asynchronously fetches contact metadata from the WA Caraka runtime
 * and stores it in Redis cache for quick access.
 *
 * This job reduces inbox load latency by deferring the runtime call
 * to a background queue instead of blocking on it during the inbox API response.
 *
 * OPTIMIZATION: Replaces blocking HTTP call in getInboxV2() with cached data + async job
 * - Expected latency reduction: 500ms-2s per inbox load
 */
class FetchWaRuntimeContactMetadata implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cache key for contact metadata
     */
    const CACHE_KEY_PREFIX = 'wa_caraka:contact_meta:';
    const CACHE_TTL = 600; // 10 minutes

    /**
     * Phone numbers to fetch metadata for
     * @var array<string>
     */
    public array $phoneNumbers;

    public function __construct(array $phoneNumbers)
    {
        $this->phoneNumbers = array_filter($phoneNumbers);
    }

    /**
     * Handle the job
     */
    public function handle(WaCarakaService $waCarakaService): void
    {
        if (empty($this->phoneNumbers)) {
            return;
        }

        try {
            // Fetch metadata from runtime
            $response = $waCarakaService->resolveContactsMeta($this->phoneNumbers);

            if (isset($response['data']['items']) && is_array($response['data']['items'])) {
                // Cache each contact's metadata
                foreach ($response['data']['items'] as $item) {
                    if (is_array($item) && !empty($item['jid'])) {
                        $cacheKey = self::CACHE_KEY_PREFIX . $item['jid'];
                        Cache::put($cacheKey, $item, self::CACHE_TTL);
                    }
                }

                Log::debug('[WaCaraka] Contact metadata cached', [
                    'count' => count($response['data']['items']),
                    'numbers' => $this->phoneNumbers,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[WaCaraka] Failed to fetch contact metadata', [
                'numbers' => $this->phoneNumbers,
                'error' => $e->getMessage(),
            ]);
            // Job fails silently - contact metadata is optional for inbox display
        }
    }

    /**
     * Get cached contact metadata for a phone number
     *
     * @param string $phoneNumber
     * @return array|null
     */
    public static function getCached(string $phoneNumber): ?array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $phoneNumber;
        return Cache::get($cacheKey);
    }

    /**
     * Get cached metadata for multiple phone numbers
     *
     * @param array<string> $phoneNumbers
     * @return array<string, array>
     */
    public static function getCachedMultiple(array $phoneNumbers): array
    {
        $result = [];
        foreach ($phoneNumbers as $number) {
            $cached = self::getCached($number);
            if ($cached) {
                $result[$number] = $cached;
            }
        }
        return $result;
    }

    /**
     * Queue metadata fetch if not cached
     *
     * @param array<string> $phoneNumbers
     * @return void
     */
    public static function ensureCached(array $phoneNumbers): void
    {
        $cached = self::getCachedMultiple($phoneNumbers);
        $missing = array_diff($phoneNumbers, array_keys($cached));

        if (!empty($missing)) {
            // Queue job to fetch missing numbers
            // This prevents thundering herd - only fetches what's actually needed
            self::dispatch($missing)->onQueue(config('wa_caraka.queue', 'default'));
        }
    }
}
