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
        $expectedToken = config('wa_caraka.token', env('LW_WA_V2_TOKEN', ''));

        // Jika token tidak dikonfigurasi, izinkan (dev mode)
        if ($expectedToken === '') {
            return $next($request);
        }

        $receivedToken = $request->header('X-WA-V2-Token', '');

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
