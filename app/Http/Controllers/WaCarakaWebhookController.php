<?php

namespace App\Http\Controllers;

use App\Services\WaCarakaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WaCarakaWebhookController
 *
 * Receives inbound message pushes from the WA bridge adapter (Node.js).
 * This endpoint is NOT behind SSO — it's verified by a shared token.
 *
 * POST /api/wa-caraka/webhook/inbound
 * Headers: X-WA-V2-Token: <shared_token>
 * Body: { from, to, text, type, id, timestamp, ... }
 */
class WaCarakaWebhookController extends Controller
{
    public function __construct(protected WaCarakaService $wa) {}

    /**
     * Handle inbound message webhook from WA bridge.
     */
    public function inbound(Request $request)
    {
        // ── Token verification ──────────────────────────────────────────────
        $expectedToken = config('wa_caraka.token', '');
        $receivedToken = $request->header('X-WA-V2-Token', '');

        if ($expectedToken !== '' && !hash_equals($expectedToken, $receivedToken)) {
            Log::warning('[WaCaraka Webhook] Token mismatch from ' . $request->ip());
            return response()->json(['ok' => false, 'error' => 'Unauthorized.'], 401);
        }

        $payload = $request->all();

        // ── Normalize: bridge vs legacy field names ──────────────────────────
        // Bridge sends: { from, to, text, type, id, timestamp, raw }
        // Legacy sends: { from, message, number, ... }
        $from = $payload['from']
            ?? $payload['number']
            ?? $payload['remote']
            ?? $payload['remoteJid']
            ?? '';

        // Normalize 'text' field
        if (!isset($payload['text']) && isset($payload['message'])) {
            $payload['text'] = $payload['message'];
        }
        if (!isset($payload['body']) && isset($payload['text'])) {
            $payload['body'] = $payload['text'];
        }

        // Normalize 'from' field
        if (empty($payload['from'])) {
            $payload['from'] = $from;
        }

        if (empty($from)) {
            Log::warning('[WaCaraka Webhook] Missing "from" field', ['payload_keys' => array_keys($payload)]);
            return response()->json(['ok' => false, 'error' => 'Missing "from" field.'], 422);
        }

        try {
            $message = $this->wa->handleInbound($payload);

            return response()->json([
                'ok'        => true,
                'messageId' => $message->id,
                'stored'    => $message->wasRecentlyCreated,
                'duplicate' => !$message->wasRecentlyCreated,
            ], 201);
        } catch (\Exception $e) {
            Log::error('[WaCaraka Webhook] handleInbound failed', [
                'error'   => $e->getMessage(),
                'payload' => array_keys($payload),
            ]);
            return response()->json(['ok' => false, 'error' => 'Internal error.'], 500);
        }
    }
}
