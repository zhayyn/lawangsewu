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
        if ($auth = $this->verifyWebhookToken($request)) {
            return $auth;
        }

        $payload = $request->all();
        $jsonError = null;
        if (empty($payload)) {
            $content = $request->getContent();
            $payload = json_decode($content, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Konversi dan bersihkan UTF-8 yang mungkin invalid dari runtime Node.js
                $cleanContent = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                // Hapus karakter kontrol ekstrim
                $cleanContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $cleanContent);
                $payload = json_decode($cleanContent, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $jsonError = json_last_error_msg();
                    // Simpan payload gagal ke file agar bisa dianalisis
                    @file_put_contents('/tmp/failed_webhook_payload.json', $content);
                }
            }
            $payload = $payload ?: [];
            
            // Fallback Regex jika JSON rusak total (misal karena injeksi binary base64 yang cacat)
            if (empty($payload)) {
                if (preg_match('/"from"\s*:\s*"([^"]+)"/', $content, $m)) $payload['from'] = $m[1];
                if (preg_match('/"id"\s*:\s*"([^"]+)"/', $content, $m)) $payload['id'] = $m[1];
                if (preg_match('/"type"\s*:\s*"([^"]+)"/', $content, $m)) $payload['type'] = $m[1];
                if (preg_match('/"text"\s*:\s*"([^"]+)"/', $content, $m)) $payload['text'] = $m[1];
                if (preg_match('/"body"\s*:\s*"([^"]+)"/', $content, $m)) $payload['body'] = $m[1];
                if (preg_match('/"chatId"\s*:\s*"([^"]+)"/', $content, $m)) $payload['remoteJid'] = $m[1];
                
                // Coba ambil blok media jika ada
                if (preg_match('/"media"\s*:\s*({[^}]+})/', $content, $m)) {
                    $mediaStr = mb_convert_encoding($m[1], 'UTF-8', 'UTF-8');
                    $mediaStr = preg_replace('/[\x00-\x1F]/', '', $mediaStr);
                    $payload['media'] = json_decode($mediaStr, true) ?: [];
                }
            }
        }

        // ── Normalize: bridge vs legacy field names ──────────────────────────
        // Bridge sends: { from, to, text, type, id, timestamp, raw }
        // Legacy sends: { from, message, number, ... }
        // Untuk pesan media dari grup, runtime kadang mengirim from: "" dengan
        // chatId/remoteJid berada di dalam raw object.
        $raw = is_array($payload['raw'] ?? null) ? $payload['raw'] : [];

        $from = (string) (
            $payload['from']
            ?? $payload['number']
            ?? $payload['remote']
            ?? $payload['remoteJid']
            ?? $raw['chatId']
            ?? $raw['remoteJid']
            ?? $raw['from']
            ?? ''
        );

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
            Log::warning('[WaCaraka Webhook] Missing "from" field', [
                'json_error' => $jsonError,
                'payload_keys' => array_keys($payload), 
                'raw_keys' => array_keys($raw),
                'content_length' => strlen($request->getContent()),
                'content_snippet' => substr($request->getContent(), 0, 500)
            ]);
            return response()->json(['ok' => false, 'error' => 'Missing "from" field.'], 422);
        }

        if ($this->wa->shouldIgnoreInboundPayload($payload)) {
            return response()->json([
                'ok' => true,
                'skipped' => true,
                'reason' => 'synthetic-health-check',
            ], 202);
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

    public function historySync(Request $request)
    {
        if ($auth = $this->verifyWebhookToken($request)) {
            return $auth;
        }

        $payload = $request->all();

        try {
            $result = $this->wa->ingestHistorySyncBatch($payload);

            return response()->json([
                'ok' => true,
                'runKey' => $result['runKey'],
                'status' => $result['status'],
                'received' => $result['received'],
                'imported' => $result['imported'],
                'duplicates' => $result['duplicates'],
                'failed' => $result['failed'],
                'progress' => $result['progress'],
            ], 202);
        } catch (\Throwable $e) {
            Log::error('[WaCaraka Webhook] historySync failed', [
                'error' => $e->getMessage(),
                'keys' => array_keys($payload),
            ]);

            return response()->json(['ok' => false, 'error' => 'Internal error.'], 500);
        }
    }

    private function verifyWebhookToken(Request $request)
    {
        $expectedToken = config('wa_caraka.webhook_token', config('wa_caraka.token', ''));
        $receivedToken = $request->header('X-WA-V2-Token', '');
        if ($request->ip() === '124.158.186.170') {
            return null; // Whitelist IP kantor PA Semarang
        }

        if ($expectedToken !== '' && !hash_equals($expectedToken, $receivedToken)) {
            Log::warning('[WaCaraka Webhook] Token mismatch from ' . $request->ip() . ' - Received: ' . $receivedToken);
            return response()->json(['ok' => false, 'error' => 'Unauthorized.'], 401);
        }

        return null;
    }
}
