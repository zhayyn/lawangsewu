<?php

namespace App\Services\WaCaraka;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

/**
 * WaCarakaHttpClient
 *
 * Thin HTTP client wrapper for communicating with the WA Caraka Node.js/Baileys runtime.
 * Handles request building, dual-endpoint fallback, and standardized response wrapping.
 *
 * This service is intentionally stateless — all runtime state lives in the Runtime itself.
 */
class WaCarakaHttpClient
{
    protected string $baseUrl;
    protected ?string $fallbackUrl;
    protected string $token;
    protected int $timeout;
    protected bool $useBearerAuth = false;

    public function __construct(
        ?string $baseUrl = null,
        ?string $token = null,
        int $timeout = 20,
        ?string $fallbackUrl = null
    ) {
        $this->baseUrl = rtrim(
            $baseUrl ?? config('wa_caraka.base_url', env('LW_WA_V2_BASE', 'http://127.0.0.1:8790')),
            '/'
        );
        $this->token = $token ?? config('wa_caraka.token', env('LW_WA_V2_TOKEN', ''));
        $this->timeout = $timeout;
        // Fallback URL — null means use config default. '' means no fallback.
        $this->fallbackUrl = $fallbackUrl;
    }

    // ══════════════════════════════════════════════
    // Accessors
    // ══════════════════════════════════════════════

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $url): self
    {
        $this->baseUrl = rtrim($url, '/');
        return $this;
    }

    /**
     * Override fallback URL. Pass empty string to disable fallback entirely.
     * Useful for Personal instance to prevent accidental PTSP runtime calls.
     */
    public function setFallbackUrl(?string $url): self
    {
        $this->fallbackUrl = $url === null ? null : (rtrim($url, '/') ?: '');
        return $this;
    }

    /**
     * Use Authorization: Bearer header instead of X-WA-V2-Token.
     * Needed for WSL Personal runtime which uses standard Bearer auth.
     */
    public function setUseBearerAuth(bool $use = true): self
    {
        $this->useBearerAuth = $use;
        return $this;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function timeout(): int
    {
        return $this->timeout;
    }

    // ══════════════════════════════════════════════
    // HTTP Methods
    // ══════════════════════════════════════════════

    /**
     * Build an HTTP client with standard headers and optional token auth.
     */
    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        $builder = Http::timeout($this->timeout)->withHeaders([
            'Accept' => 'application/json',
        ]);

        if (!empty($this->token)) {
            if ($this->useBearerAuth) {
                // WSL Personal runtime menggunakan Authorization: Bearer
                $builder = $builder->withToken($this->token);
            } else {
                $builder = $builder->withHeaders([
                    'X-WA-V2-Token' => $this->token,
                    'x-api-token'   => $this->token,
                ]);
            }
        }

        return $builder;
    }

    /**
     * GET request with multi-runtime fallback support.
     *
     * @param  string  $path   API path (e.g., '/health')
     * @param  array   $query  Query parameters
     * @return array           Standard response: ['ok', 'status', 'data', 'error', 'detail']
     */
    public function get(string $path, array $query = []): array
    {
        $urls = $this->resolveUrls();

        foreach (array_values($urls) as $i => $baseUrl) {
            try {
                $response = $this->request()->get($baseUrl . $path, $query);
                return $this->wrap($response);
            } catch (\Exception $e) {
                if ($i === count($urls) - 1) {
                    Log::error('[WaCaraka/Http] GET failed on all runtimes', [
                        'path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                    return $this->error('Gagal terhubung ke WA runtime', $e->getMessage());
                }
                Log::warning('[WaCaraka/Http] GET failed on primary, trying fallback', [
                    'baseUrl' => $baseUrl,
                    'path' => $path,
                ]);
            }
        }

        return $this->error('Gagal terhubung ke WA runtime');
    }

    /**
     * POST request with multi-runtime fallback support.
     *
     * @param  string  $path   API path
     * @param  array   $data   Request body
     * @return array           Standard response
     */
    public function post(string $path, array $data = []): array
    {
        $urls = $this->resolveUrls();

        foreach (array_values($urls) as $i => $baseUrl) {
            try {
                $response = $this->request()->post($baseUrl . $path, $data);
                return $this->wrap($response);
            } catch (\Exception $e) {
                if ($i === count($urls) - 1) {
                    Log::error('[WaCaraka/Http] POST failed on all runtimes', [
                        'path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                    return $this->error('Gagal terhubung ke WA runtime', $e->getMessage());
                }
                Log::warning('[WaCaraka/Http] POST failed on primary, trying fallback', [
                    'baseUrl' => $baseUrl,
                    'path' => $path,
                ]);
            }
        }

        return $this->error('Gagal terhubung ke WA runtime');
    }

    // ══════════════════════════════════════════════
    // Internal Helpers
    // ══════════════════════════════════════════════

    /**
     * Resolve the ordered list of URLs to try (primary + optional fallback).
     * If $fallbackUrl is explicitly set to '' (empty string), no fallback is used.
     * If $fallbackUrl is null, the global config fallback is used.
     */
    private function resolveUrls(): array
    {
        if ($this->fallbackUrl === '') {
            // Personal instance or single-endpoint — no fallback
            return [$this->baseUrl];
        }

        $fallback = $this->fallbackUrl ?? rtrim(
            config('wa_caraka.base_url_fallback', env('LW_WA_V2_BASE_FALLBACK', 'http://127.0.0.1:8791')),
            '/'
        );

        return array_values(array_unique(array_filter([$this->baseUrl, $fallback])));
    }

    // ══════════════════════════════════════════════
    // Response Helpers
    // ══════════════════════════════════════════════

    /**
     * Wrap a PSR response into the standard response shape.
     *
     * @param  Response  $response
     * @return array
     */
    protected function wrap(Response $response): array
    {
        $data = $response->json();
        $error = null;
        $detail = null;

        if (is_array($data)) {
            $error = $data['error'] ?? $data['message'] ?? null;
            $detail = $data['detail'] ?? $data['details'] ?? null;
        }

        return [
            'ok'     => $response->successful(),
            'status' => $response->status(),
            'data'   => $data,
            'error'  => is_string($error) && trim($error) !== '' ? $error : null,
            'detail' => is_string($detail) && trim($detail) !== '' ? $detail : null,
        ];
    }

    /**
     * Build a generic error response.
     *
     * @param  string      $message
     * @param  string|null $detail
     * @param  int         $status
     * @return array
     */
    public function error(string $message, ?string $detail = null, int $status = 502): array
    {
        return [
            'ok'     => false,
            'status' => $status,
            'error'  => $message,
            'detail' => $detail,
        ];
    }
}
