<?php

namespace Tests\Feature\Modules;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Omnichannel LiveChat Module Test
 *
 * Modul ini mencakup:
 * - GET /omnichannel/leaderboard → Leaderboard petugas PTSP
 * - POST /api/omnichannel/web-chat (API, tanpa auth)
 *
 * Temuan Audit:
 * 1. Route leaderboard sebelumnya hanya menggunakan middleware ['auth'] — sudah diperbaiki
 *    ke ['auth', 'verified', 'active', 'role:viewer,operator,useradmin,admin']
 * 2. WebHook endpoint POST /api/omnichannel/web-chat tidak dilindungi auth sama sekali
 *    (ini disengaja sebagai webhook eksternal, tapi perlu rate limiting)
 * 3. LeaderboardController menggunakan mock data untuk 'avg_response_time_ms'
 *    (belum ada metrik nyata — ini technical debt yang perlu diselesaikan)
 */
class OmnichannelLiveChatTest extends TestCase
{
    use RefreshDatabase;

    // ── Leaderboard: Access Control Tests ────────────────────────────────────

    public function test_guest_cannot_access_leaderboard(): void
    {
        $response = $this->get('/omnichannel/leaderboard');

        // Guest harus di-redirect ke login
        $response->assertRedirect();
        $this->assertStringContainsString('login', $response->headers->get('Location', ''));
    }

    public function test_inactive_user_cannot_access_leaderboard(): void
    {
        $inactiveUser = User::factory()->create([
            'role' => 'operator',
            'is_active' => false, // ← user tidak aktif
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($inactiveUser)->get('/omnichannel/leaderboard');

        // Middleware 'active' harus menolak user yang tidak aktif
        $this->assertNotEquals(200, $response->status(),
            'Inactive user should NOT be able to access leaderboard.');
    }

    public function test_active_viewer_can_access_leaderboard(): void
    {
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get('/omnichannel/leaderboard');

        // Viewer harus bisa mengakses leaderboard
        $response->assertOk();
    }

    public function test_active_operator_can_access_leaderboard(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($operator)->get('/omnichannel/leaderboard');

        $response->assertOk();
    }

    public function test_leaderboard_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/omnichannel/leaderboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Lawangsewu/LiveChat/Leaderboard')
            ->has('mostConversations')
            ->has('mostResponsive')
        );
    }

    public function test_leaderboard_returns_array_data_types(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/omnichannel/leaderboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('mostConversations', fn ($val) => is_array($val) || (is_object($val) && method_exists($val, 'toArray')))
            ->where('mostResponsive', fn ($val) => is_array($val) || (is_object($val) && method_exists($val, 'toArray')))
        );
    }

    // ── Leaderboard: Empty State Tests ────────────────────────────────────────

    public function test_leaderboard_works_when_no_conversations_exist(): void
    {
        // omni_conversations kosong → mostConversations harus [] bukan error
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
            'is_superadmin' => true,
        ]);

        $response = $this->actingAs($user)->get('/omnichannel/leaderboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Lawangsewu/LiveChat/Leaderboard')
        );
    }

    // ── WebHook API Tests ─────────────────────────────────────────────────────

    public function test_webchat_webhook_accepts_valid_payload(): void
    {
        $response = $this->postJson('/api/omnichannel/web-chat', [
            'session_id' => 'session-test-001',
            'customer_name' => 'Budi Santoso',
            'message' => 'Halo, saya butuh informasi sidang.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
    }

    public function test_webchat_webhook_rejects_missing_session_id(): void
    {
        $response = $this->postJson('/api/omnichannel/web-chat', [
            'customer_name' => 'Budi',
            'message' => 'Test pesan',
        ]);

        $response->assertStatus(422);
    }

    public function test_webchat_webhook_rejects_missing_message(): void
    {
        $response = $this->postJson('/api/omnichannel/web-chat', [
            'session_id' => 'session-test-002',
            'customer_name' => 'Budi',
        ]);

        $response->assertStatus(422);
    }

    public function test_webchat_webhook_creates_conversation_and_message(): void
    {
        $sessionId = 'session-' . uniqid();

        $response = $this->postJson('/api/omnichannel/web-chat', [
            'session_id' => $sessionId,
            'customer_name' => 'Ani Rahayu',
            'message' => 'Apa jadwal sidang saya?',
        ]);

        $response->assertOk();

        // Verifikasi conversation dibuat
        $this->assertDatabaseHas('omni_conversations', [
            'channel' => 'web',
            'channel_identity_id' => $sessionId,
            'customer_name' => 'Ani Rahayu',
        ]);

        // Verifikasi message dibuat
        $this->assertDatabaseHas('omni_messages', [
            'direction' => 'inbound',
            'type' => 'text',
            'content' => 'Apa jadwal sidang saya?',
        ]);
    }

    public function test_webchat_webhook_reuses_existing_conversation(): void
    {
        $sessionId = 'session-reuse-' . uniqid();

        // Pesan pertama → buat conversation baru
        $this->postJson('/api/omnichannel/web-chat', [
            'session_id' => $sessionId,
            'customer_name' => 'Sari',
            'message' => 'Pesan pertama',
        ])->assertOk();

        // Pesan kedua → reuse conversation yang sama
        $this->postJson('/api/omnichannel/web-chat', [
            'session_id' => $sessionId,
            'message' => 'Pesan kedua',
        ])->assertOk();

        // Harus hanya 1 conversation
        $this->assertDatabaseCount('omni_conversations', 1);

        // Tapi 2 pesan
        $this->assertDatabaseCount('omni_messages', 2);
    }

    public function test_webchat_webhook_works_without_customer_name(): void
    {
        // customer_name nullable
        $response = $this->postJson('/api/omnichannel/web-chat', [
            'session_id' => 'session-anon-' . uniqid(),
            'message' => 'Pertanyaan anonim',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('omni_conversations', [
            'customer_name' => 'Guest',
        ]);
    }

    // ── Route Registration Tests ──────────────────────────────────────────────

    public function test_leaderboard_route_is_named_correctly(): void
    {
        $this->assertEquals(
            url('/omnichannel/leaderboard'),
            route('livechat.leaderboard'),
            "Route 'livechat.leaderboard' harus mengarah ke /omnichannel/leaderboard"
        );
    }
}
