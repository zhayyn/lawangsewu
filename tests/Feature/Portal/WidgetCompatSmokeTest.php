<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Widget Compat Smoke Test
 *
 * Modul Widget Compat menyajikan konten publik tanpa auth:
 * - /api/pengumuman-rss, /api/jadwal-persidangan, /api/statistik-data
 * - /monitor-persidangan, /antrian-sidang, /statistik-perkara, dll (PHP public widgets)
 * - /daftar-widget, /berita-pengadilan (HTML widgets)
 *
 * CATATAN: PHP public widgets (phpPublic) memanggil script .php via executePhpScript().
 * Jika file widget tidak ada, controller akan abort(404).
 * API JSON endpoint (apiStatistik, apiJadwal, dll) juga bergantung pada file PHP eksternal.
 * Test ini memverifikasi: route terdaftar, dapat diakses tanpa auth, dan merespons
 * dengan HTTP status yang valid (bukan 500 Internal Server Error).
 */
class WidgetCompatSmokeTest extends TestCase
{
    use RefreshDatabase;

    // ── API Endpoint Tests (throttle:60,1) ────────────────────────────────────

    public function test_api_statistik_data_is_publicly_accessible(): void
    {
        $response = $this->get('/api/statistik-data');

        // Bisa 200 (berhasil) atau 404 (file widget tidak ada) — tidak boleh 500
        $this->assertNotEquals(500, $response->status(),
            'Endpoint /api/statistik-data must not return 500 Internal Server Error.');
        $this->assertTrue(in_array($response->status(), [200, 404]),
            "Expected 200 or 404, got {$response->status()}");
    }

    public function test_api_jadwal_persidangan_route_exists(): void
    {
        // SIPP-DEPENDENT: Endpoint ini memanggil PHP script yang melakukan require langsung
        // ke file yang konek ke DB SIPP. Di test environment, koneksi SIPP tidak tersedia
        // sehingga PHP process crash dengan fatal error (Premature end of PHP process).
        // Test ini di-skip sampai ada mock/stub SIPP untuk test environment.
        $this->markTestSkipped(
            'SIPP-dependent: /api/jadwal-persidangan memanggil PHP script yang membutuhkan ' .
            'koneksi database SIPP (192.168.88.10). Skip di test environment tanpa SIPP.'
        );
    }

    public function test_api_pengumuman_rss_is_publicly_accessible(): void
    {
        $response = $this->get('/api/pengumuman-rss');

        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_api_pengumuman_with_source_param(): void
    {
        $response = $this->get('/api/pengumuman-rss/pa-semarang');

        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_api_wa_v2_is_publicly_accessible(): void
    {
        $response = $this->get('/api/wa-v2');

        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    // ── HTML Widget Tests ─────────────────────────────────────────────────────

    public function test_berita_pengadilan_page_accessible(): void
    {
        $response = $this->get('/berita-pengadilan');

        // HTML widget memanggil file di widgets/views/html/public/
        // Jika file ada → 200, jika tidak ada → 404
        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_monitor_wa_page_accessible(): void
    {
        $response = $this->get('/monitor-wa');

        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    // ── PHP Public Widget Tests ───────────────────────────────────────────────

    public function test_monitor_persidangan_is_publicly_accessible(): void
    {
        $response = $this->get('/monitor-persidangan');

        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_statistik_perkara_is_publicly_accessible(): void
    {
        $response = $this->get('/statistik-perkara');

        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_antrian_sidang_is_publicly_accessible(): void
    {
        $response = $this->get('/antrian-sidang');

        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_statistik_embed_is_publicly_accessible(): void
    {
        $response = $this->get('/statistik-embed');

        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    // ── Security Tests: Widget Routes Must NOT Require Auth ──────────────────

    public function test_widget_routes_accessible_without_authentication(): void
    {
        // Pastikan semua route widget ini TIDAK butuh login
        $publicRoutes = [
            '/api/statistik-data',
            '/api/pengumuman-rss',
            '/api/jadwal-persidangan',
            '/monitor-persidangan',
            '/antrian-sidang',
            '/statistik-perkara',
        ];

        foreach ($publicRoutes as $route) {
            $response = $this->get($route);
            $this->assertNotEquals(302, $response->status(),
                "Route {$route} should NOT redirect to login (must be publicly accessible).");
            $this->assertNotEquals(401, $response->status(),
                "Route {$route} should NOT return 401 Unauthorized.");
        }
    }

    // ── Rate Limiting Test ─────────────────────────────────────────────────────

    public function test_api_endpoint_has_proper_content_type(): void
    {
        $response = $this->get('/api/statistik-data');

        if ($response->status() === 200) {
            // Jika berhasil, harus content-type JSON
            $this->assertStringContainsString('json', $response->headers->get('Content-Type', ''));
        }

        // Jika 404 karena file widget tidak ada, itu masih acceptable
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    // ── Backward Compatibility Test ───────────────────────────────────────────

    public function test_lawangsewu_prefixed_api_routes_also_work(): void
    {
        // Route prefix /lawangsewu/api/* juga harus tersedia untuk backward compat
        $response = $this->get('/lawangsewu/api/statistik-data');

        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_unknown_widget_page_returns_404_not_500(): void
    {
        // Route yang tidak ada di map harus abort 404, bukan 500
        $response = $this->get('/widget-yang-tidak-ada-sama-sekali');

        // Route tidak ditemukan sama sekali oleh Laravel → 404
        $response->assertStatus(404);
    }
}
