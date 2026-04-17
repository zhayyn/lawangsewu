<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleIdTokenVerifier
{
    /**
     * @return array{id: string, email: string, name: ?string, avatar: ?string}
     */
    public function verify(string $credential): array
    {
        $credential = trim($credential);

        if ($credential === '') {
            throw new RuntimeException('Token kredensial Google kosong.');
        }

        $clientId = trim((string) config('services.google.client_id'));

        if ($clientId === '') {
            throw new RuntimeException('GOOGLE_CLIENT_ID belum dikonfigurasi.');
        }

        try {
            $payload = (array) JWT::decode($credential, JWK::parseKeySet($this->signingKeys()));
        } catch (\Throwable $exception) {
            throw new RuntimeException('Token Google tidak valid.', previous: $exception);
        }

        $issuer = (string) ($payload['iss'] ?? '');
        $audience = $payload['aud'] ?? null;
        $email = strtolower((string) ($payload['email'] ?? ''));
        $subject = trim((string) ($payload['sub'] ?? ''));

        if (! in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            throw new RuntimeException('Issuer token Google tidak valid.');
        }

        $audiences = is_array($audience) ? $audience : [$audience];

        if (! in_array($clientId, $audiences, true)) {
            throw new RuntimeException('Client ID token Google tidak cocok.');
        }

        if ($subject === '') {
            throw new RuntimeException('ID akun Google tidak ditemukan.');
        }

        if ($email === '') {
            throw new RuntimeException('Email Google tidak ditemukan.');
        }

        if (! $this->isTruthy($payload['email_verified'] ?? false)) {
            throw new RuntimeException('Email Google belum terverifikasi.');
        }

        $name = trim((string) ($payload['name'] ?? ''));
        $avatar = trim((string) ($payload['picture'] ?? ''));

        return [
            'id' => $subject,
            'email' => $email,
            'name' => $name !== '' ? $name : null,
            'avatar' => $avatar !== '' ? $avatar : null,
        ];
    }

    /**
     * @return array{keys: array<int, array<string, mixed>>}
     */
    private function signingKeys(): array
    {
        $cacheKey = 'google:id-token:jwks';

        return Cache::remember($cacheKey, now()->addHour(), function (): array {
            $response = Http::acceptJson()
                ->timeout(10)
                ->get('https://www.googleapis.com/oauth2/v3/certs')
                ->throw();

            $jwks = $response->json();

            if (! is_array($jwks) || ! isset($jwks['keys']) || ! is_array($jwks['keys'])) {
                throw new RuntimeException('Kunci verifikasi Google tidak tersedia.');
            }

            return $jwks;
        });
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes'], true);
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return false;
    }
}
