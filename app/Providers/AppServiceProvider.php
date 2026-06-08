<?php

namespace App\Providers;

use App\Services\PakPpDocxExportService;
use App\Services\VertexAiService;
use App\Services\WaCaraka\WaCarakaHttpClient;
use App\Services\WaCaraka\WaCarakaMessageService;
use App\Services\WaCaraka\WaCarakaConversationService;
use App\Services\WaCaraka\WaCarakaStatsService;
use App\Services\WaCarakaService;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── PAK PP — Vertex AI Service ─────────────────────────────────
        $this->app->singleton(VertexAiService::class, function () {
            return new VertexAiService(
                projectId:                   (string) config('services.vertex_ai.project_id', ''),
                location:                    (string) config('services.vertex_ai.location', 'us-central1'),
                model:                       (string) config('services.vertex_ai.model', 'gemini-2.5-flash'),
                serviceAccountJsonBase64:    (string) config('services.vertex_ai.service_account_json_base64', ''),
            );
        });

        $this->app->singleton(PakPpDocxExportService::class);

        // ── WA Caraka — Sub-Services (Dependency Injection) ──────────────
        // HTTP Client
        $this->app->singleton(WaCarakaHttpClient::class, function () {
            return new WaCarakaHttpClient(
                baseUrl: config('wa_caraka.base_url'),
                token: config('wa_caraka.token'),
                timeout: (int) config('wa_caraka.timeout', 20),
            );
        });

        // Stats Service (no dependencies)
        $this->app->singleton(WaCarakaStatsService::class);

        // Conversation Service (depends on HttpClient, Stats)
        $this->app->singleton(WaCarakaConversationService::class, function ($app) {
            return new WaCarakaConversationService(
                $app->make(WaCarakaHttpClient::class),
                $app->make(WaCarakaStatsService::class),
            );
        });

        // Message Service (depends on HttpClient, Conversation, Stats)
        $this->app->singleton(WaCarakaMessageService::class, function ($app) {
            return new WaCarakaMessageService(
                $app->make(WaCarakaHttpClient::class),
                $app->make(WaCarakaConversationService::class),
                $app->make(WaCarakaStatsService::class),
            );
        });

        // Main Service (facade/coordinator)
        $this->app->singleton(WaCarakaService::class, function ($app) {
            return new WaCarakaService(
                $app->make(WaCarakaHttpClient::class),
                $app->make(WaCarakaMessageService::class),
                $app->make(WaCarakaConversationService::class),
                $app->make(WaCarakaStatsService::class),
            );
        });

        // ── Passport OAuth2 Scopes — didaftarkan di register() agar tersedia
        // sebelum PassportServiceProvider membuat AuthorizationServer ──────
        Passport::tokensCan([
            'openid'  => 'Akses identitas dasar (OpenID Connect)',
            'profile' => 'Akses nama dan foto profil',
            'email'   => 'Akses alamat email',
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }
        Vite::prefetch(concurrency: 3);

        // ── Passport v13 — halaman otorisasi SSO untuk aplikasi klien eksternal ──
        // Tidak mempengaruhi login Google Socialite Lawangsewu yang sudah berjalan.
        Passport::authorizationView('oauth.authorize');

        // ── Token Expiry ────────────────────────────────────────────────────────
        Passport::tokensExpireIn(now()->addHours(1));
        Passport::refreshTokensExpireIn(now()->addDays(7));
    }
}
