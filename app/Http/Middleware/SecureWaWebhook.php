<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecureWaWebhook
 *
 * Middleware untuk mengamankan endpoint webhook WA Caraka.
 * Verifikasi dilakukan dengan token bersama (shared secret).
 * Endpoint ini dipanggil oleh wa-bridge (Node.js) dari localhost.
 */
class SecureWaWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        // 'webhook_token' (WA_WEBHOOK_TOKEN) adalah token khusus untuk inbound webhook dari wa-bridge.
        // Ini BERBEDA dari 'token' (LW_WA_V2_TOKEN) yang dipakai untuk auth ke runtime.
        $expectedToken = config('wa_caraka.webhook_token', env('WA_WEBHOOK_TOKEN', ''));

        // Jika token tidak dikonfigurasi, izinkan (dev mode / webhook tanpa auth)
        if ($expectedToken === '') {
            return $next($request);
        }

        $receivedToken = $request->header('X-WA-V2-Token', $request->header('Authorization', ''));

        if (! hash_equals($expectedToken, $receivedToken)) {
            Log::warning('[SecureWaWebhook] Token mismatch', [
                'ip'             => $request->ip(),
                'path'           => $request->path(),
                'has_token'      => ! empty($receivedToken),
            ]);

            return response()->json(['ok' => false, 'error' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
