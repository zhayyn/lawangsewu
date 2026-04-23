<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * LogSlowRequests
 *
 * Mencatat request yang memakan waktu lebih dari ambang batas ke log.
 * Berguna untuk mendeteksi bottleneck query, N+1, atau backend lambat
 * tanpa perlu APM eksternal.
 *
 * Konfigurasi melalui env:
 *   LOG_SLOW_THRESHOLD_MS=500  (default: 500ms)
 *
 * Analogi: ini seperti pencatat waktu tempuh truk di jalan tol —
 * kalau lebih dari batas, langsung dicatat ke laporan inspeksi.
 */
class LogSlowRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $elapsedMs = (int) round((microtime(true) - $start) * 1000);
        $threshold = (int) env('LOG_SLOW_THRESHOLD_MS', 500);
        $channel = (string) env('LOG_SLOW_CHANNEL', 'slow_request');

        if ($elapsedMs >= $threshold) {
            try {
                Log::channel($channel)->warning('[SlowRequest] ' . $request->method() . ' ' . $request->path(), [
                    'elapsed_ms' => $elapsedMs,
                    'threshold'  => $threshold,
                    'user_id'    => $request->user()?->id,
                    'route'      => optional($request->route())?->getName(),
                    'ip'         => $request->ip(),
                    'status'     => $response->getStatusCode(),
                ]);
            } catch (\Throwable $e) {
                // Observability should never break the request lifecycle.
            }
        }

        return $response;
    }
}
