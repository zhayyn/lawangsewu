<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * VertexAiService
 *
 * Wrapper untuk REST API Google Cloud Vertex AI (Gemini).
 * Menggunakan Service Account JWT untuk autentikasi — tanpa gRPC.
 * Bearer token di-cache selama 55 menit untuk efisiensi.
 */
class VertexAiService
{
    private const TOKEN_CACHE_KEY   = 'vertex_ai_bearer_token';
    private const TOKEN_TTL_SECONDS = 55 * 60; // 55 menit (token GCP berlaku 1 jam)
    private const GOOGLE_TOKEN_URL  = 'https://oauth2.googleapis.com/token';
    private const SCOPE             = 'https://www.googleapis.com/auth/cloud-platform';

    public function __construct(
        private readonly string $projectId,
        private readonly string $location,
        private readonly string $model,
        private readonly string $serviceAccountJsonBase64,
    ) {}

    /**
     * Kirim request ke Vertex AI dengan sistem prompt dan konten pengguna.
     *
     * @param  string  $systemPrompt  Instruksi sistem untuk model
     * @param  string  $userContent   Input dari pengguna / draf yang akan diproses
     * @param  float   $temperature   Tingkat kreativitas model (0.0 - 1.0)
     * @return array{ok: bool, text?: string, error?: string}
     */
    public function generate(string $systemPrompt, string $userContent, float $temperature = 0.2): array
    {
        $bearerToken = $this->getBearerToken();

        if (! $bearerToken) {
            return ['ok' => false, 'error' => 'Gagal mendapatkan token autentikasi Vertex AI.'];
        }

        $endpoint = sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
            $this->location,
            $this->projectId,
            $this->location,
            $this->model,
        );

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [['text' => $userContent]],
                ],
            ],
            'generationConfig' => [
                'temperature'     => $temperature,
                'maxOutputTokens' => 8192,
                'candidateCount'  => 1,
            ],
        ];

        try {
            $response = Http::timeout(60)
                ->withToken($bearerToken)
                ->post($endpoint, $payload);

            if ($response->failed()) {
                Log::warning('[VertexAI] Request gagal', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return [
                    'ok'    => false,
                    'error' => 'Vertex AI mengembalikan error: HTTP ' . $response->status(),
                ];
            }

            $data = $response->json();
            $text = data_get($data, 'candidates.0.content.parts.0.text', '');

            if (empty(trim($text))) {
                return ['ok' => false, 'error' => 'Model tidak menghasilkan teks. Coba ulangi permintaan.'];
            }

            return ['ok' => true, 'text' => trim($text)];
        } catch (\Throwable $e) {
            Log::error('[VertexAI] Exception saat generate', ['message' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'Koneksi ke Vertex AI gagal. Periksa konfigurasi jaringan.'];
        }
    }

    /**
     * Dapatkan Bearer Token dari cache atau generate JWT baru lalu tukar.
     */
    private function getBearerToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_TTL_SECONDS, function () {
            return $this->fetchNewBearerToken();
        });
    }

    /**
     * Buat JWT self-signed dari Service Account, lalu tukar dengan Bearer Token GCP.
     */
    private function fetchNewBearerToken(): ?string
    {
        try {
            $jsonRaw = base64_decode($this->serviceAccountJsonBase64);
            $sa      = json_decode($jsonRaw, true, 512, JSON_THROW_ON_ERROR);

            $privateKey   = $sa['private_key'];
            $clientEmail  = $sa['client_email'];

            $now    = time();
            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64UrlEncode(json_encode([
                'iss'   => $clientEmail,
                'scope' => self::SCOPE,
                'aud'   => self::GOOGLE_TOKEN_URL,
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $signingInput = "{$header}.{$claims}";
            $key          = openssl_pkey_get_private($privateKey);

            if (! $key) {
                Log::error('[VertexAI] Gagal memuat private key dari Service Account JSON.');
                return null;
            }

            openssl_sign($signingInput, $signature, $key, 'SHA256');
            $jwt = $signingInput . '.' . $this->base64UrlEncode($signature);

            $tokenResponse = Http::asForm()->post(self::GOOGLE_TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if ($tokenResponse->failed()) {
                Log::error('[VertexAI] Gagal menukar JWT dengan access token.', [
                    'body' => $tokenResponse->body(),
                ]);
                return null;
            }

            return $tokenResponse->json('access_token');
        } catch (\Throwable $e) {
            Log::error('[VertexAI] Exception saat fetch token', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

// developed by dbprakom™
