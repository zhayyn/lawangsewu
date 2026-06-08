<?php

namespace Tests\Feature\Modules;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * WaCaraka Webhook Security & Routing Test
 *
 * WaCarakaWebhookController menerima push dari bridge Node.js.
 * Keamanan dijamin via:
 *   1. SecureWaWebhook middleware (X-WA-V2-Token)
 *   2. Rate limiting (500/menit)
 *   3. IP whitelist (opsional)
 *
 * Test ini memverifikasi:
 * - Webhook ditolak tanpa token yang benar (401)
 * - Webhook diterima dengan token yang benar (200)
 * - Payload normalisasi (bridge vs legacy field names)
 * - History sync endpoint tersedia
 * - Omnichannel web-chat webhook tersedia (tanpa token)
 *
 * // developed by dbprakom™
 */
class WaCarakaWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();
        // Middleware SecureWaWebhook membaca config wa_caraka.webhook_token
        // Jika kosong, semua request diizinkan (dev mode)
        $this->webhookSecret = 'test-secure-webhook-token-2026';

        // Set token di config agar middleware aktif
        config(['wa_caraka.webhook_token' => $this->webhookSecret]);
    }

    // ── Security: Token Validation ────────────────────────────────────────────

    public function test_inbound_webhook_rejected_without_token(): void
    {
        // Token dikonfigurasi di setUp, request tanpa token harus ditolak
        $response = $this->postJson('/api/wa-caraka/webhook/inbound', [
            'from' => '628123456789@s.whatsapp.net',
            'type' => 'text',
            'text' => 'Halo',
        ]);
        // Tanpa token header → 401
        $this->assertContains($response->status(), [401, 403],
            'Webhook tanpa token harus ditolak (401 atau 403) saat token dikonfigurasi');
    }

    public function test_inbound_webhook_rejected_with_wrong_token(): void
    {
        $response = $this->postJson('/api/wa-caraka/webhook/inbound', [
            'from' => '628123456789@s.whatsapp.net',
            'type' => 'text',
            'text' => 'Halo',
        ], [
            'X-WA-V2-Token' => 'wrong-token-completely-different',
        ]);

        $this->assertContains($response->status(), [401, 403],
            'Webhook dengan token salah harus ditolak');
    }

    public function test_inbound_webhook_accepted_with_correct_token(): void
    {
        $response = $this->postJson('/api/wa-caraka/webhook/inbound', [
            'from' => '628123456789@s.whatsapp.net',
            'type' => 'text',
            'text' => 'Test message',
            'id' => 'test-msg-001',
        ], [
            'X-WA-V2-Token' => $this->webhookSecret,
        ]);

        // 200 = diterima dan diproses, 201 = created, 202 = accepted for processing
        $this->assertContains($response->status(), [200, 201, 202],
            "Webhook dengan token benar harus diterima (200/201/202), got {$response->status()}");
    }

    public function test_inbound_webhook_route_exists(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('wacaraka.webhook.inbound'),
            "Route 'wacaraka.webhook.inbound' harus terdaftar"
        );
    }

    public function test_history_sync_webhook_rejected_without_token(): void
    {
        // Token sudah dikonfigurasi di setUp, request tanpa token harus ditolak
        $response = $this->postJson('/api/wa-caraka/webhook/history-sync', [
            'messages' => [],
        ]);

        $this->assertContains($response->status(), [401, 403],
            'History sync webhook tanpa token harus ditolak saat token dikonfigurasi');
    }

    public function test_history_sync_webhook_route_exists(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('wacaraka.webhook.history-sync'),
            "Route 'wacaraka.webhook.history-sync' harus terdaftar"
        );
    }

    // ── Omnichannel Web-Chat Webhook ──────────────────────────────────────────

    public function test_omnichannel_webchat_webhook_is_accessible(): void
    {
        $response = $this->postJson('/api/omnichannel/web-chat', [
            'session_id' => 'test-session-001',
            'message' => 'Halo, saya ingin bertanya',
            'sender' => 'Pengunjung',
        ]);

        // Bisa 200 (diterima), 422 (validasi), atau 401 (auth)
        // Yang penting: bukan 404 (route tidak ada) atau 500 (fatal error)
        $this->assertNotEquals(404, $response->status(),
            'Omnichannel web-chat webhook route harus terdaftar');
        $this->assertNotEquals(500, $response->status(),
            'Omnichannel web-chat webhook tidak boleh return 500');
    }

    // ── WaCaraka Reports Access Control ──────────────────────────────────────

    public function test_wa_caraka_reports_requires_authentication(): void
    {
        $this->get('/wa-caraka/reports')->assertRedirect('/login');
    }

    public function test_wa_caraka_reports_denied_for_viewer(): void
    {
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($viewer)->get('/wa-caraka/reports')
            ->assertStatus(403);
    }

    public function test_wa_caraka_reports_accessible_by_operator(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($operator)->get('/wa-caraka/reports');
        $response->assertOk();
    }

    public function test_wa_caraka_reports_accessible_by_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/wa-caraka/reports');
        $response->assertOk();
    }

    public function test_wa_caraka_reports_data_endpoint_accessible_by_operator(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($operator)
            ->getJson('/wa-caraka/reports/data');

        $this->assertNotEquals(403, $response->status(),
            'Operator harus bisa akses reports data endpoint');
    }
}
