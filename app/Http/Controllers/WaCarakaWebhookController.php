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
        \App\Models\WaCarakaMessage::$activeSource = 'office';
        \App\Models\WaCarakaConversation::$activeSource = 'office';

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

    /**
     * Handle inbound message webhook dari WA bridge personal (runtime di WSL).
     * Endpoint terpisah agar token-nya bisa berbeda dari instance kantor.
     */
    public function inboundPersonal(Request $request)
    {
        \App\Models\WaCarakaMessage::$activeSource = 'personal';
        \App\Models\WaCarakaConversation::$activeSource = 'personal';

        Log::info('[WaCaraka/Personal Webhook] Raw Inbound Webhook Received', [
            'headers' => [
                'x-webhook-token'  => $request->header('x-webhook-token'),
                'authorization'    => $request->hasHeader('authorization'),
                'x-wa-v2-token'    => $request->header('X-WA-V2-Token'),
            ],
            'payload' => $request->all(),
        ]);

        if ($auth = $this->verifyPersonalWebhookToken($request)) {
            return $auth;
        }

        // Tandai payload ini dari instance personal sebelum diproses
        $payload = $request->all();
        $jsonError = null;
        if (empty($payload)) {
            $content = $request->getContent();
            $payload = json_decode($content, true, 512, JSON_INVALID_UTF8_SUBSTITUTE) ?? [];
        }

        // Inject instance marker agar percakapan bisa dibedakan di UI dan database
        $payload['_instance'] = 'personal';
        $payload['source']    = 'personal'; // untuk kolom source di wa_caraka_conversations & wa_caraka_messages

        $raw = is_array($payload['raw'] ?? null) ? $payload['raw'] : [];
        
        $from = '';
        $fromCandidates = [
            $payload['from'] ?? null,
            $payload['jid'] ?? null,
            $payload['remoteJid'] ?? null,
            $payload['chatId'] ?? null,
            $raw['remoteJid'] ?? null,
            $raw['chatId'] ?? null,
        ];
        foreach ($fromCandidates as $candidate) {
            $trimmed = trim((string) $candidate);
            if ($trimmed !== '') {
                $from = $trimmed;
                break;
            }
        }

        // Abaikan update status dari kontak (status@broadcast)
        if ($from === 'status@broadcast' || ($payload['from'] ?? '') === 'status@broadcast' || ($payload['jid'] ?? '') === 'status@broadcast') {
            return response()->json(['ok' => true, 'skipped' => true, 'reason' => 'status-broadcast'], 200);
        }

        if (!isset($payload['text']) && isset($payload['message'])) {
            $payload['text'] = $payload['message'];
        }
        
        // Normalisasi sender name dari field 'sender' (berada di root payload)
        if (!isset($payload['pushName']) && isset($payload['sender'])) {
            $payload['pushName'] = $payload['sender'];
        }
        // Teruskan juga ke sub-key jika dibutuhkan oleh service tertentu
        if (!isset($payload['metadata']['pushName']) && isset($payload['sender'])) {
            $payload['metadata']['pushName'] = $payload['sender'];
        }

        if (empty($payload['from'])) {
            $payload['from'] = $from;
        }

        // Pastikan nomor lid/JID yang tidak punya to menjadi ke local
        if (empty($payload['to'])) {
            $payload['to'] = $payload['local'] ?? null;
        }

        if (empty($from)) {
            Log::warning('[WaCaraka/Personal Webhook] Missing "from" field', [
                'payload_keys' => array_keys($payload),
            ]);
            return response()->json(['ok' => false, 'error' => 'Missing "from" field.'], 422);
        }

        if ($this->wa->shouldIgnoreInboundPayload($payload)) {
            return response()->json(['ok' => true, 'skipped' => true, 'reason' => 'synthetic-health-check'], 202);
        }

        try {
            $message = $this->wa->handleInbound($payload);

            return response()->json([
                'ok'        => true,
                'messageId' => $message->id,
                'stored'    => $message->wasRecentlyCreated,
                'duplicate' => !$message->wasRecentlyCreated,
                'instance'  => 'personal',
            ], 201);
        } catch (\Exception $e) {
            Log::error('[WaCaraka/Personal Webhook] handleInbound failed', [
                'error'   => $e->getMessage(),
                'payload' => array_keys($payload),
            ]);
            return response()->json(['ok' => false, 'error' => 'Internal error.'], 500);
        }
    }

    private function verifyPersonalWebhookToken(Request $request)
    {
        $expectedToken = config('wa_caraka.personal_webhook_token', '');

        // Terima token dari berbagai header yang mungkin dikirim runtime:
        // 1. x-webhook-token        (dikirim oleh WaCaraka Personal WSL runtime)
        // 2. Authorization: Bearer  (alternatif dari runtime yang sama)
        // 3. X-WA-V2-Token          (format lama / fallback)
        $receivedToken = $request->header('x-webhook-token', '')
            ?: $this->extractBearerToken($request)
            ?: $request->header('X-WA-V2-Token', '');

        if ($expectedToken === '') {
            // Token belum dikonfigurasi — tolak semua request untuk keamanan
            Log::warning('[WaCaraka/Personal Webhook] Token belum dikonfigurasi (WA_WEBHOOK_TOKEN_PERSONAL kosong). Request ditolak dari ' . $request->ip());
            return response()->json(['ok' => false, 'error' => 'Webhook personal belum dikonfigurasi.'], 503);
        }

        if (!hash_equals($expectedToken, $receivedToken)) {
            Log::warning('[WaCaraka/Personal Webhook] Token mismatch dari ' . $request->ip(), [
                'header_present' => [
                    'x-webhook-token'  => $request->hasHeader('x-webhook-token'),
                    'authorization'    => $request->hasHeader('authorization'),
                    'x-wa-v2-token'    => $request->hasHeader('X-WA-V2-Token'),
                ],
            ]);
            return response()->json(['ok' => false, 'error' => 'Unauthorized.'], 401);
        }

        return null;
    }

    /**
     * Ekstrak token dari header Authorization: Bearer <token>
     */
    private function extractBearerToken(Request $request): string
    {
        $auth = $request->header('Authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return trim(substr($auth, 7));
        }
        return '';
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
