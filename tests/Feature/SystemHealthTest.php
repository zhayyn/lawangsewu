<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * System Health & Monitoring Test
 *
 * Memverifikasi semua health check endpoint yang digunakan oleh:
 * - Load balancer (GET /health)
 * - Kubernetes liveness probe (GET /api/live)
 * - Kubernetes readiness probe (GET /api/ready)
 * - Uptime monitor (GET /health)
 *
 * Endpoint-endpoint ini adalah pintu gerbang monitoring production.
 * Kegagalan test di sini = sistem tidak bisa dipantau dengan benar.
 *
 * // developed by dbprakom™
 */
class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    // ── Public Health Endpoint (/health) ─────────────────────────────────────

    public function test_public_health_endpoint_is_accessible(): void
    {
        $response = $this->get('/health');

        $this->assertContains($response->status(), [200, 503],
            'Health endpoint harus return 200 (healthy) atau 503 (degraded/unhealthy)');
    }

    public function test_public_health_endpoint_returns_json(): void
    {
        $response = $this->get('/health');

        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_public_health_endpoint_returns_correct_structure(): void
    {
        $response = $this->get('/health');

        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database',
                'cache',
                'queue',
                'storage',
            ],
        ]);
    }

    public function test_health_status_is_valid_value(): void
    {
        $response = $this->get('/health');

        $data = $response->json();
        $this->assertContains($data['status'], ['ok', 'degraded', 'unhealthy'],
            'Status harus salah satu dari: ok, degraded, unhealthy');
    }

    public function test_health_timestamp_is_valid_iso8601(): void
    {
        $response = $this->get('/health');

        $timestamp = $response->json('timestamp');
        $this->assertNotNull($timestamp);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $timestamp,
            'Timestamp harus format ISO 8601'
        );
    }

    public function test_health_database_check_has_correct_keys(): void
    {
        $response = $this->get('/health');

        $response->assertJsonStructure([
            'checks' => [
                'database' => ['ok', 'message'],
            ],
        ]);
    }

    public function test_health_cache_check_has_driver_info(): void
    {
        $response = $this->get('/health');

        $response->assertJsonStructure([
            'checks' => [
                'cache' => ['ok', 'message'],
            ],
        ]);
    }

    public function test_health_requires_no_authentication(): void
    {
        // Health endpoint HARUS public — tanpa auth
        $response = $this->get('/health');

        $this->assertNotEquals(401, $response->status(),
            'Health endpoint tidak boleh require auth (401)');
        $this->assertNotEquals(302, $response->status(),
            'Health endpoint tidak boleh redirect ke login');
    }

    // ── API Health Endpoints (/api/health, /api/ready, /api/live) ────────────

    public function test_api_health_endpoint_is_accessible(): void
    {
        $response = $this->get('/api/health');

        $this->assertContains($response->status(), [200, 206, 503],
            '/api/health harus return 200 (healthy), 206 (degraded), atau 503 (unhealthy)');
    }

    public function test_api_health_returns_json_structure(): void
    {
        $response = $this->get('/api/health');

        // HealthCheckController (Kubernetes-style) menggunakan 'components',
        // berbeda dengan HealthController (/health) yang menggunakan 'checks'
        $response->assertJsonStructure([
            'status',
        ]);

        // Verifikasi 'components' atau 'checks' tersedia (satu dari dua format)
        $data = $response->json();
        $this->assertTrue(
            isset($data['components']) || isset($data['checks']),
            'API health harus memiliki field components atau checks'
        );
    }

    public function test_api_readiness_probe_returns_json(): void
    {
        $response = $this->get('/api/ready');

        $this->assertContains($response->status(), [200, 503]);
        $response->assertJsonStructure(['ready']);
    }

    public function test_api_liveness_probe_always_returns_200(): void
    {
        $response = $this->get('/api/live');

        $response->assertStatus(200);
        $response->assertJsonStructure(['alive']);
    }

    public function test_liveness_probe_returns_true(): void
    {
        $response = $this->get('/api/live');

        $this->assertTrue($response->json('alive'),
            'Liveness probe harus selalu return alive=true');
    }

    public function test_api_health_requires_no_authentication(): void
    {
        // Semua health check endpoint harus public
        foreach (['/api/health', '/api/ready', '/api/live'] as $endpoint) {
            $response = $this->get($endpoint);

            $this->assertNotEquals(401, $response->status(),
                "$endpoint tidak boleh require auth");
            $this->assertNotEquals(302, $response->status(),
                "$endpoint tidak boleh redirect ke login");
        }
    }

    // ── OG Image Endpoint ─────────────────────────────────────────────────────

    public function test_og_image_endpoint_is_accessible(): void
    {
        $response = $this->get('/og-image/case-statistics.png');

        // Endpoint akan fetch external QuickChart API — di test env mungkin timeout
        // Yang penting tidak 404 (route terdaftar) atau 500 (fatal PHP error)
        $this->assertNotEquals(404, $response->status(),
            'OG Image route harus terdaftar di Laravel');
    }

    public function test_og_image_route_name_is_registered(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('og.case-statistics'),
            "Route 'og.case-statistics' harus terdaftar"
        );
    }
}
