<?php

namespace App\Providers;

use App\Services\PakPpDocxExportService;
use App\Services\VertexAiService;
use Laravel\Passport\Passport;
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
    }
}
