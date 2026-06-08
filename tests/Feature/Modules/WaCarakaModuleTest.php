<?php

namespace Tests\Feature\Modules;

use App\Events\WaCarakaMessageReceived;
use App\Models\User;
use App\Models\WaCarakaDailyMetric;
use App\Models\WaCarakaLog;
use App\Models\WaCarakaMenu;
use App\Models\WaCarakaMessage;
use App\Models\WaCarakaMonthlySnapshot;
use App\Models\WaCarakaSession;
use App\Models\WaCarakaSyncRun;
use App\Models\WaCarakaTicket;
use App\Services\WaCarakaChatbotService;
use App\Services\WaCarakaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WaCarakaModuleTest
 *
 * Covers SSO integration, route access control, proxy actions,
 * permission-aware action gating, webhook inbound, inbox queries,
 * local DB logging, chatbot auto-reply, and ticket management.
 */
class WaCarakaModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetWaCarakaFixtures();
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function operatorUser(): User
    {
        return User::factory()->create(['role' => 'operator']);
    }

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function superadminUser(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_superadmin' => true]);
    }

    private function viewerUser(): User
    {
        return User::factory()->create(['role' => 'viewer']);
    }

    private function seedDefaultMenus(): void
    {
        foreach ([
            [
                'command' => '1',
                'label' => 'Menu Utama',
                'type' => 'direct',
                'sort_order' => 0,
                'response_text' => '*SiNofita PA Semarang* Menu Utama: ketik 2-16',
            ],
            [
                'command' => '2',
                'label' => 'Syarat Perceraian',
                'type' => 'direct',
                'sort_order' => 2,
                'response_text' => 'Syarat perceraian: KTP, Buku Nikah, KK, dll.',
            ],
            [
                'command' => '7',
                'label' => 'Cek Status Perkara',
                'type' => 'input',
                'sort_order' => 7,
                'prompt_text' => 'Masukkan nomor perkara:',
                'response_text' => null,
            ],
            [
                'command' => '12',
                'label' => 'Pengaduan',
                'type' => 'input',
                'sort_order' => 12,
                'prompt_text' => 'Silahkan ketik aduan Anda:',
                'response_text' => 'Terima kasih. Aduan Anda telah kami terima.',
                'creates_ticket_type' => 'pengaduan',
            ],
            [
                'command' => '13',
                'label' => 'Konsultasi',
                'type' => 'input',
                'sort_order' => 13,
                'prompt_text' => 'Silahkan ketik permasalahan Anda:',
                'response_text' => 'Terima kasih. Pertanyaan Anda telah kami terima.',
                'creates_ticket_type' => 'konsultasi',
            ],
        ] as $menu) {
            WaCarakaMenu::query()->updateOrCreate(
                ['command' => $menu['command']],
                $menu,
            );
        }
    }

    private function resetWaCarakaFixtures(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'wa_caraka_conversation_marks',
            'wa_caraka_handovers',
            'wa_caraka_sync_runs',
            'wa_caraka_tickets',
            'wa_caraka_sessions',
            'wa_caraka_daily_metrics',
            'wa_caraka_monthly_snapshots',
            'wa_caraka_conversations',
            'wa_caraka_messages',
            'wa_caraka_logs',
            'wa_caraka_menus',
            'wa_caraka_settings',
        ] as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    // ──────────────────────────────────────────────
    // SSO / Access Control
    // ──────────────────────────────────────────────

    public function test_guest_cannot_access_wa_caraka_dashboard(): void
    {
        $response = $this->get(route('lawangsewu.wacaraka.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_viewer_cannot_access_wa_caraka_dashboard(): void
    {
        $this->actingAs($this->viewerUser())
            ->get(route('lawangsewu.wacaraka.index'))
            ->assertForbidden();
    }

    public function test_operator_can_access_wa_caraka_dashboard(): void
    {
        Http::fake(['http://127.0.0.1:8790/*' => Http::response(['status' => 'connected'], 200)]);

        $this->actingAs($this->operatorUser())
            ->get(route('lawangsewu.wacaraka.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Lawangsewu/WaCaraka/Index')
                ->where('config.baseUrl', 'http://127.0.0.1:8790')
                ->has('messageStats')
                ->has('convoStats')
                ->has('ticketStats')
                ->has('recentTickets')
            );
    }

    // ──────────────────────────────────────────────
    // Permission-Aware Actions
    // ──────────────────────────────────────────────

    public function test_operator_cannot_restart_device(): void
    {
        $this->actingAs($this->operatorUser())
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'restart']))
            ->assertForbidden()
            ->assertJsonFragment(['error' => 'Aksi ini hanya dapat dilakukan oleh admin.']);
    }

    public function test_operator_cannot_broadcast(): void
    {
        $this->actingAs($this->operatorUser())
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'broadcast']), [
                'recipients' => ['628111000001'],
                'text'       => 'test',
            ])
            ->assertForbidden();
    }

    public function test_operator_can_check_health(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/health' => Http::response(['status' => 'connected'], 200),
        ]);

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'health']))
            ->assertOk();
    }

    public function test_admin_can_restart_device(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/restart' => Http::response(['ok' => true], 200),
        ]);

        $this->actingAs($this->adminUser())
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'restart']))
            ->assertOk();
    }

    // ──────────────────────────────────────────────
    // Proxy Actions
    // ──────────────────────────────────────────────

    public function test_proxy_health_returns_runtime_data(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/health' => Http::response(['status' => 'connected'], 200),
        ]);

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'health']))
            ->assertOk()
            ->assertJsonFragment(['status' => 'connected']);
    }

    public function test_proxy_invalid_action_returns_404(): void
    {
        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'nonexistent']))
            ->assertNotFound();
    }

    public function test_send_text_validates_required_fields(): void
    {
        $this->actingAs($this->operatorUser())
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'send-text']), [])
            ->assertUnprocessable();
    }

    public function test_send_text_logs_to_both_tables(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-text' => Http::response(['ok' => true, 'messageId' => 'abc123'], 200),
        ]);

        $user = $this->operatorUser();

        $this->actingAs($user)
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'send-text']), [
                'to'   => '628123456789',
                'text' => 'Test pesan dari operator',
            ])
            ->assertOk();

        // Legacy log table
        $this->assertDatabaseHas('wa_caraka_logs', [
            'receiver' => '628123456789',
            'status'   => 'sent',
        ]);

        // New messages table
        $this->assertDatabaseHas('wa_caraka_messages', [
            'remote_number' => '628123456789',
            'direction'     => 'outbound',
            'status'        => 'sent',
            'user_id'       => $user->id,
        ]);
    }

    public function test_failed_send_text_logs_as_failed(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-text' => Http::response(['error' => 'Device disconnected'], 502),
        ]);

        $this->actingAs($this->operatorUser())
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'send-text']), [
                'to'   => '628999999999',
                'text' => 'Test pesan gagal',
            ]);

        $this->assertDatabaseHas('wa_caraka_messages', [
            'remote_number' => '628999999999',
            'direction'     => 'outbound',
            'status'        => 'failed',
        ]);
    }

    public function test_send_media_document_is_logged_to_message_table(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-media' => Http::response([
                'ok' => true,
                'messageId' => 'media-doc-001',
                'media' => [
                    'kind' => 'document',
                    'mimetype' => 'application/pdf',
                    'fileName' => 'panduan.pdf',
                ],
            ], 200),
        ]);

        $user = $this->operatorUser();

        $this->actingAs($user)
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'send-media']), [
                'to' => '628123456789',
                'media_kind' => 'document',
                'media_url' => 'data:application/pdf;base64,SGVsbG8=',
                'mime_type' => 'application/pdf',
                'file_name' => 'panduan.pdf',
                'caption' => 'Panduan layanan',
            ])
            ->assertOk()
            ->assertJsonFragment(['ok' => true]);

        $this->assertDatabaseHas('wa_caraka_messages', [
            'remote_number' => '628123456789',
            'direction' => 'outbound',
            'message_type' => 'document',
            'status' => 'sent',
            'user_id' => $user->id,
        ]);
    }

    public function test_send_media_rtf_without_explicit_mime_type_is_normalized(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-media' => Http::response([
                'ok' => true,
                'messageId' => 'media-rtf-001',
                'media' => [
                    'kind' => 'document',
                    'mimetype' => 'application/rtf',
                    'fileName' => 'surat-kuasa.rtf',
                ],
            ], 200),
        ]);

        $user = $this->operatorUser();

        $this->actingAs($user)
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'send-media']), [
                'to' => '628123456789',
                'media_kind' => 'document',
                'media_url' => 'data:application/rtf;base64,e1xydGYxXGFuc2kgSGVsbG99',
                'file_name' => 'surat-kuasa.rtf',
                'caption' => 'Dokumen RTF',
            ])
            ->assertOk()
            ->assertJsonFragment(['ok' => true]);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:8790/send-media'
                && $request['mime_type'] === 'application/rtf'
                && $request['file_name'] === 'surat-kuasa.rtf';
        });
    }

    public function test_send_media_accepts_sticker_payload(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-media' => Http::response([
                'ok' => true,
                'messageId' => 'sticker-001',
                'media' => [
                    'kind' => 'sticker',
                    'mimetype' => 'image/webp',
                    'fileName' => 'operator.webp',
                ],
            ], 200),
        ]);

        $this->actingAs($this->operatorUser())
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'send-media']), [
                'to' => '628888777666',
                'media_kind' => 'sticker',
                'media_url' => 'data:image/webp;base64,UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoQABAAPp1moqI=',
                'mime_type' => 'image/webp',
                'file_name' => 'operator.webp',
            ])
            ->assertOk()
            ->assertJsonFragment(['ok' => true]);

        $this->assertDatabaseHas('wa_caraka_messages', [
            'remote_number' => '628888777666',
            'direction' => 'outbound',
            'message_type' => 'sticker',
            'status' => 'sent',
        ]);
    }

    public function test_send_media_accepts_multipart_file_payload(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-media' => Http::response([
                'ok' => true,
                'messageId' => 'media-file-001',
                'media' => [
                    'kind' => 'image',
                    'mimetype' => 'image/png',
                    'fileName' => 'gugatan.png',
                ],
            ], 200),
        ]);

        $this->actingAs($this->operatorUser())
            ->post(route('lawangsewu.wacaraka.api', ['action' => 'send-media']), [
                'to' => '628123456789',
                'media_kind' => 'image',
                'media_file' => UploadedFile::fake()->image('gugatan.png', 320, 480)->size(128),
                'mime_type' => 'image/png',
                'file_name' => 'gugatan.png',
                'caption' => 'Contoh gugatan',
            ])
            ->assertOk()
            ->assertJsonFragment(['messageId' => 'media-file-001']);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:8790/send-media'
                && $request['media_kind'] === 'image'
                && str_starts_with((string) $request['media_url'], 'data:image/png;base64,')
                && $request['file_name'] === 'gugatan.png';
        });

        $this->assertDatabaseHas('wa_caraka_messages', [
            'remote_number' => '628123456789',
            'direction' => 'outbound',
            'message_type' => 'image',
            'status' => 'sent',
        ]);
    }

    public function test_failed_send_media_returns_runtime_error_message(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-media' => Http::response([
                'ok' => false,
                'error' => 'Nomor tidak valid/tidak terdaftar',
            ], 502),
        ]);

        $this->actingAs($this->operatorUser())
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'send-media']), [
                'to' => '628000000000',
                'media_kind' => 'image',
                'media_url' => 'data:image/png;base64,SGVsbG8=',
                'mime_type' => 'image/png',
                'file_name' => 'contoh.png',
            ])
            ->assertStatus(502)
            ->assertJsonFragment([
                'error' => 'Nomor tidak valid/tidak terdaftar',
            ]);

        $this->assertDatabaseHas('wa_caraka_messages', [
            'remote_number' => '628000000000',
            'direction' => 'outbound',
            'message_type' => 'image',
            'status' => 'failed',
        ]);
    }

    public function test_conversation_messages_omit_raw_media_payloads(): void
    {
        $conversationId = 'wa_lid_123456789_abc123';

        \App\Models\WaCarakaConversation::create([
            'conversation_id' => $conversationId,
            'remote_number' => '123456789@lid',
            'status' => 'open',
            'last_activity_at' => now(),
        ]);

        WaCarakaMessage::create([
            'direction' => 'inbound',
            'remote_number' => '123456789@lid',
            'message_text' => 'audio.mp3',
            'message_type' => 'document',
            'status' => 'received',
            'conversation_id' => $conversationId,
            'metadata' => [
                'type' => 'document',
                'media' => [
                    'kind' => 'document',
                    'fileName' => 'audio.mp3',
                    'dataUrl' => 'data:audio/mpeg;base64,QUJD',
                ],
                'raw' => [
                    'mediaData' => str_repeat('A', 4096),
                    'fileName' => 'audio.mp3',
                ],
            ],
        ]);

        $messages = app(WaCarakaService::class)->conversationMessages($conversationId);

        $this->assertSame('audio.mp3', $messages[0]['metadata']['media']['fileName']);
        $this->assertArrayNotHasKey('dataUrl', $messages[0]['metadata']['media']);
        $this->assertArrayNotHasKey('mediaData', $messages[0]['metadata']['raw']);
    }

    // ──────────────────────────────────────────────
    // Stats / Logs API
    // ──────────────────────────────────────────────

    public function test_stats_action_returns_local_db_counts(): void
    {
        WaCarakaLog::factory()->count(3)->create(['status' => 'sent']);
        WaCarakaLog::factory()->count(1)->create(['status' => 'failed']);

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'stats']))
            ->assertOk()
            ->assertJsonFragment(['sent' => 3, 'failed' => 1]);
    }

    public function test_message_stats_returns_two_way_counts(): void
    {
        WaCarakaMessage::factory()->count(3)->inbound()->create();
        WaCarakaMessage::factory()->count(2)->outbound()->create();

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'message-stats']))
            ->assertOk()
            ->assertJsonFragment(['inbound' => 3, 'outbound' => 2]);
    }

    public function test_logs_action_returns_recent_entries(): void
    {
        WaCarakaLog::factory()->count(5)->create();

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'logs']))
            ->assertOk()
            ->assertJsonCount(5);
    }

    // ──────────────────────────────────────────────
    // Inbox / Conversations
    // ──────────────────────────────────────────────

    public function test_conversations_returns_grouped_data(): void
    {
        $number = '628111222333';
        $convoId = 'wa_628111222333';

        // Create the conversation record (inbox now queries wa_caraka_conversations)
        \App\Models\WaCarakaConversation::create([
            'conversation_id'  => $convoId,
            'remote_number'    => $number,
            'status'           => 'pending',
            'last_activity_at' => now(),
        ]);

        WaCarakaMessage::factory()->count(3)->create([
            'remote_number'   => $number,
            'conversation_id' => $convoId,
        ]);

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'inbox']))
            ->assertOk()
            ->assertJsonFragment(['remoteNumber' => $number]);
    }

    public function test_inbox_deduplicates_same_logical_contact_even_if_remote_format_differs(): void
    {
        \App\Models\WaCarakaConversation::create([
            'conversation_id' => 'wa_personal_628111222333_old',
            'remote_number' => '628111222333',
            'status' => 'pending',
            'last_activity_at' => now()->subMinute(),
        ]);

        \App\Models\WaCarakaConversation::create([
            'conversation_id' => 'wa_personal_628111222333_new',
            'remote_number' => '628111222333@s.whatsapp.net',
            'status' => 'pending',
            'last_activity_at' => now(),
        ]);

        WaCarakaMessage::create([
            'direction' => 'inbound',
            'remote_number' => '628111222333',
            'message_text' => 'Pesan lama',
            'message_type' => 'text',
            'status' => 'received',
            'conversation_id' => 'wa_personal_628111222333_old',
            'metadata' => [],
        ]);

        WaCarakaMessage::create([
            'direction' => 'inbound',
            'remote_number' => '628111222333@s.whatsapp.net',
            'message_text' => 'Pesan baru',
            'message_type' => 'text',
            'status' => 'received',
            'conversation_id' => 'wa_personal_628111222333_new',
            'metadata' => [],
        ]);

        $conversations = $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'inbox']))
            ->assertOk()
            ->json('conversations');

        $matchingThreads = collect($conversations)
            ->filter(fn ($conversation) => str_contains((string) ($conversation['remoteNumber'] ?? ''), '628111222333'))
            ->values();

        $this->assertCount(1, $matchingThreads);
        $this->assertSame('Pesan baru', $matchingThreads[0]['lastMessagePreview']);
    }

    public function test_inbox_shows_unresolved_lid_conversations(): void
    {
        \App\Models\WaCarakaConversation::create([
            'conversation_id' => 'wa_lid_167430794035369_3494d36120',
            'remote_number' => '167430794035369@lid',
            'remote_name' => 'AAA',
            'status' => 'pending',
            'unread_count' => 3,
            'last_activity_at' => now(),
        ]);

        WaCarakaMessage::create([
            'direction' => 'inbound',
            'remote_number' => '167430794035369@lid',
            'message_text' => 'Selamat malam',
            'message_type' => 'text',
            'status' => 'received',
            'conversation_id' => 'wa_lid_167430794035369_3494d36120',
            'metadata' => [
                'fromLid' => '167430794035369@lid',
                'isLid' => true,
            ],
        ]);

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'inbox']))
            ->assertOk()
            ->assertJsonFragment([
                'conversationId' => 'wa_lid_167430794035369_3494d36120',
                'remoteNumber' => '167430794035369@lid',
                'lastMessagePreview' => 'Selamat malam',
            ]);
    }

    public function test_pull_inbox_stores_new_messages_from_runtime(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/messages' => Http::response([
                'messages' => [[
                    'id' => 'wamid.inbox-1',
                    'from' => '628123456789',
                    'to' => '628001234567',
                    'text' => 'Halo dari inbox polling',
                    'type' => 'text',
                ]],
            ], 200),
        ]);

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'pull-inbox']))
            ->assertOk()
            ->assertJsonFragment([
                'supported' => true,
                'pulled' => 1,
                'stored' => 1,
            ]);

        $this->assertDatabaseHas('wa_caraka_messages', [
            'wa_message_id' => 'wamid.inbox-1',
            'remote_number' => '628123456789',
            'direction' => 'inbound',
        ]);
    }

    public function test_pull_inbox_falls_back_when_runtime_does_not_support_messages_endpoint(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/messages' => Http::response([
                'error' => 'Not Found',
            ], 404),
        ]);

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'pull-inbox']))
            ->assertOk()
            ->assertJsonFragment([
                'supported' => false,
                'stored' => 0,
            ]);
    }

    // ──────────────────────────────────────────────
    // Broadcast
    // ──────────────────────────────────────────────

    public function test_admin_broadcast_sends_to_multiple_recipients(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-text' => Http::response(['ok' => true], 200),
        ]);

        $this->actingAs($this->adminUser())
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'broadcast']), [
                'recipients' => ['628111000001', '628222000002', '628333000003'],
                'text'       => 'Pengumuman dari PA Semarang.',
            ])
            ->assertOk()
            ->assertJsonFragment(['total' => 3, 'succeeded' => 3, 'failed' => 0]);

        $this->assertEquals(3, WaCarakaMessage::outbound()->count());
    }

    // ──────────────────────────────────────────────
    // Webhook Inbound
    // ──────────────────────────────────────────────

    public function test_webhook_receives_inbound_message(): void
    {
        Event::fake([WaCarakaMessageReceived::class]);

        config(['wa_caraka.token' => 'test-secret-token']);

        $this->postJson(route('wacaraka.webhook.inbound'), [
            'from'  => '628555666777',
            'to'    => '628001234567',
            'text'  => 'Assalamualaikum, apakah ada jadwal sidang hari ini?',
            'type'  => 'text',
            'id'    => 'wa-msg-001',
        ], [
            'X-WA-V2-Token' => 'test-secret-token',
        ])
            ->assertCreated()
            ->assertJsonFragment(['stored' => true]);

        $this->assertDatabaseHas('wa_caraka_messages', [
            'remote_number' => '628555666777',
            'direction'     => 'inbound',
            'status'        => 'received',
            'wa_message_id' => 'wa-msg-001',
        ]);

        Event::assertDispatched(WaCarakaMessageReceived::class);
    }

    public function test_webhook_rejects_invalid_token(): void
    {
        config(['wa_caraka.token' => 'correct-token']);

        $this->postJson(route('wacaraka.webhook.inbound'), [
            'from' => '628111222333',
            'text' => 'test',
        ], [
            'X-WA-V2-Token' => 'wrong-token',
        ])
            ->assertUnauthorized();
    }

    public function test_webhook_rejects_missing_from(): void
    {
        config(['wa_caraka.token' => '']);

        $this->postJson(route('wacaraka.webhook.inbound'), [
            'text' => 'test tanpa from',
        ])
            ->assertUnprocessable();
    }

    public function test_webhook_media_token_is_normalized_to_proxy_url(): void
    {
        config(['wa_caraka.token' => '']);

        $this->postJson(route('wacaraka.webhook.inbound'), [
            'from' => '628111222333',
            'to' => '628001234567',
            'text' => '',
            'type' => 'document',
            'id' => 'wa-media-001',
            'media' => [
                'kind' => 'document',
                'fileName' => 'berkas.pdf',
                'mimetype' => 'application/pdf',
                'mediaToken' => str_repeat('a', 32),
            ],
        ])
            ->assertCreated()
            ->assertJsonFragment(['stored' => true]);

        $message = WaCarakaMessage::query()->where('wa_message_id', 'wa-media-001')->firstOrFail();

        $this->assertSame(
            route('lawangsewu.wacaraka.media', ['path' => str_repeat('a', 32)]),
            data_get($message->metadata, 'media.dataUrl'),
        );
    }

    public function test_history_sync_webhook_imports_messages_and_updates_metrics(): void
    {
        config(['wa_caraka.token' => 'history-token']);

        $this->postJson(route('wacaraka.webhook.history-sync'), [
            'runKey' => 'history-run-001',
            'startedAt' => now()->toIso8601String(),
            'progress' => 100,
            'isLatest' => true,
            'syncType' => 'INITIAL_STATUS_V3',
            'messages' => [
                [
                    'chatId' => '628123456789@s.whatsapp.net',
                    'from' => '628123456789',
                    'to' => '628001234567',
                    'text' => 'Pesan lama customer',
                    'type' => 'text',
                    'id' => 'history-msg-001',
                    'direction' => 'inbound',
                    'timestamp' => now()->subDays(3)->timestamp,
                    'syncSource' => 'history',
                    'historySync' => true,
                ],
                [
                    'chatId' => '628123456789@s.whatsapp.net',
                    'from' => '628001234567',
                    'to' => '628123456789',
                    'text' => 'Balasan lama operator',
                    'type' => 'text',
                    'id' => 'history-msg-002',
                    'direction' => 'outbound',
                    'fromMe' => true,
                    'timestamp' => now()->subDays(3)->addMinute()->timestamp,
                    'syncSource' => 'history',
                    'historySync' => true,
                ],
            ],
        ], [
            'X-WA-V2-Token' => 'history-token',
        ])->assertStatus(202)
            ->assertJsonFragment([
                'imported' => 2,
                'status' => 'completed',
            ]);

        $this->assertDatabaseHas('wa_caraka_messages', [
            'wa_message_id' => 'history-msg-001',
            'direction' => 'inbound',
        ]);
        $this->assertDatabaseHas('wa_caraka_messages', [
            'wa_message_id' => 'history-msg-002',
            'direction' => 'outbound',
        ]);

        $metric = WaCarakaDailyMetric::query()->where('metric_date', now()->subDays(3)->toDateString())->first();
        $this->assertNotNull($metric);
        $this->assertSame(1, $metric->inbound_messages);
        $this->assertSame(1, $metric->outbound_messages);
        $this->assertSame(1, $metric->history_inbound_messages);
        $this->assertSame(1, $metric->history_outbound_messages);

        $run = WaCarakaSyncRun::query()->where('run_key', 'history-run-001')->first();
        $this->assertNotNull($run);
        $this->assertSame('completed', $run->status);
        $this->assertSame(2, $run->messages_imported);
    }

    public function test_reports_data_uses_daily_metrics_summary_when_available(): void
    {
        WaCarakaDailyMetric::query()->create([
            'scope_key' => 'global',
            'metric_date' => now()->toDateString(),
            'total_messages' => 7,
            'inbound_messages' => 5,
            'outbound_messages' => 2,
            'history_inbound_messages' => 0,
            'history_outbound_messages' => 0,
            'realtime_inbound_messages' => 5,
            'realtime_outbound_messages' => 2,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->getJson(route('lawangsewu.wacaraka.reports.data'))
            ->assertOk()
            ->json();

        $this->assertSame(5, data_get($response, 'summary.inboundToday'));
        $this->assertSame(5, data_get($response, 'summary.inboundWeek'));
        $this->assertSame(5, data_get($response, 'summary.inboundMonth'));
    }

    public function test_admin_can_access_reports_page(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('lawangsewu.wacaraka.reports'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Lawangsewu/WaCaraka/Reports'));
    }

    public function test_reports_pdf_can_be_exported_by_admin(): void
    {
        WaCarakaDailyMetric::query()->create([
            'scope_key' => 'global',
            'metric_date' => now()->toDateString(),
            'total_messages' => 10,
            'inbound_messages' => 6,
            'outbound_messages' => 4,
            'history_inbound_messages' => 1,
            'history_outbound_messages' => 1,
            'realtime_inbound_messages' => 5,
            'realtime_outbound_messages' => 3,
        ]);

        $this->actingAs($this->adminUser())
            ->get(route('lawangsewu.wacaraka.reports.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_reports_data_includes_monthly_snapshot_timeline(): void
    {
        WaCarakaMonthlySnapshot::query()->create([
            'scope_key' => 'global',
            'month_key' => now()->format('Y-m'),
            'month_start' => now()->startOfMonth()->toDateString(),
            'month_end' => now()->endOfMonth()->toDateString(),
            'total_messages' => 12,
            'inbound_messages' => 8,
            'outbound_messages' => 4,
            'history_messages' => 3,
            'realtime_messages' => 9,
            'active_conversations' => 5,
            'unique_contacts' => 4,
            'top_operator_name' => 'Admin',
            'payload' => [
                'topContacts' => [],
                'topOperators' => [],
                'messageTypes' => [],
            ],
            'snapshot_taken_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser())
            ->getJson(route('lawangsewu.wacaraka.reports.data'))
            ->assertOk()
            ->json();

        $this->assertSame(now()->format('Y-m'), data_get($response, 'snapshots.timeline.0.monthKey'));
        $this->assertSame(12, data_get($response, 'snapshots.timeline.0.totalMessages'));
    }

    // ──────────────────────────────────────────────
    // Superadmin Admin Page
    // ──────────────────────────────────────────────

    public function test_superadmin_can_access_admin_wa_caraka(): void
    {
        Http::fake(['http://127.0.0.1:8790/*' => Http::response([], 200)]);

        $this->actingAs($this->superadminUser())
            ->get(route('admin.wacaraka.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/WaCarakaManager'));
    }

    public function test_operator_cannot_access_admin_wa_caraka(): void
    {
        $this->actingAs($this->operatorUser())
            ->get(route('admin.wacaraka.index'))
            ->assertForbidden();
    }

    // ══════════════════════════════════════════════
    // Chatbot Auto-Reply Tests
    // ══════════════════════════════════════════════

    public function test_chatbot_replies_with_default_menu_for_unknown_input(): void
    {
        $this->seedDefaultMenus();

        $chatbot = app(WaCarakaChatbotService::class);
        $reply = $chatbot->processInbound('628999999999', 'halo');

        $this->assertNotNull($reply);
        $this->assertStringContainsString('SiNofita PA Semarang', $reply);
        $this->assertStringContainsString('Menu Utama', $reply);
    }

    public function test_chatbot_replies_direct_command(): void
    {
        $this->seedDefaultMenus();

        $chatbot = app(WaCarakaChatbotService::class);
        $reply = $chatbot->processInbound('628999999999', '2');

        $this->assertNotNull($reply);
        $this->assertStringContainsString('Syarat perceraian', $reply);
    }

    public function test_chatbot_handles_two_stage_input(): void
    {
        $this->seedDefaultMenus();
        $chatbot = app(WaCarakaChatbotService::class);

        // Stage 1: User types "7" — should get prompt
        $reply1 = $chatbot->processInbound('628888888888', '7');
        $this->assertStringContainsString('nomor perkara', $reply1);

        // Session should exist
        $this->assertDatabaseHas('wa_caraka_sessions', [
            'remote_number'   => '628888888888',
            'pending_command' => '7',
        ]);

        // Stage 2: User sends nomor perkara
        $reply2 = $chatbot->processInbound('628888888888', '1234/pdt.g/2024');
        $this->assertNotNull($reply2);

        // Session should be cleared
        $this->assertDatabaseMissing('wa_caraka_sessions', [
            'remote_number' => '628888888888',
        ]);
    }

    public function test_chatbot_creates_pengaduan_ticket(): void
    {
        $this->seedDefaultMenus();
        $chatbot = app(WaCarakaChatbotService::class);

        // Stage 1: User types "12"
        $reply1 = $chatbot->processInbound('628777777777', '12');
        $this->assertStringContainsString('aduan', $reply1);

        // Stage 2: User sends aduan text
        $reply2 = $chatbot->processInbound('628777777777', 'Saya ingin melaporkan pelayanan yang lambat');
        $this->assertStringContainsString('kami terima', $reply2);

        // Ticket should exist
        $this->assertDatabaseHas('wa_caraka_tickets', [
            'type'          => 'pengaduan',
            'remote_number' => '628777777777',
            'status'        => 'open',
        ]);
    }

    public function test_chatbot_creates_konsultasi_ticket(): void
    {
        $this->seedDefaultMenus();
        $chatbot = app(WaCarakaChatbotService::class);

        // Stage 1: User types "13"
        $reply1 = $chatbot->processInbound('628666666666', '13');
        $this->assertStringContainsString('permasalahan', $reply1);

        // Stage 2: User sends konsultasi text
        $reply2 = $chatbot->processInbound('628666666666', 'Bagaimana cara mengurus akta cerai?');
        $this->assertStringContainsString('kami terima', $reply2);

        $this->assertDatabaseHas('wa_caraka_tickets', [
            'type'          => 'konsultasi',
            'remote_number' => '628666666666',
            'status'        => 'open',
        ]);
    }

    public function test_chatbot_session_allows_menu_reset(): void
    {
        $this->seedDefaultMenus();
        $chatbot = app(WaCarakaChatbotService::class);

        // Create a session for command 7
        $chatbot->processInbound('628555555555', '7');

        // Instead of sending input, user sends another menu command
        $reply = $chatbot->processInbound('628555555555', '2');
        $this->assertStringContainsString('Syarat perceraian', $reply);

        // Session should be cleared
        $this->assertDatabaseMissing('wa_caraka_sessions', [
            'remote_number' => '628555555555',
        ]);
    }

    // ══════════════════════════════════════════════
    // Ticket Management Tests
    // ══════════════════════════════════════════════

    public function test_operator_can_list_tickets(): void
    {
        WaCarakaTicket::create([
            'type'          => 'pengaduan',
            'remote_number' => '628111111111',
            'message'       => 'Test aduan',
            'status'        => 'open',
        ]);

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'tickets']))
            ->assertOk();
    }

    public function test_operator_can_reply_to_ticket(): void
    {
        $user = $this->operatorUser();
        $ticket = WaCarakaTicket::create([
            'type'          => 'pengaduan',
            'remote_number' => '628111111111',
            'message'       => 'Saya mengadu tentang pelayanan',
            'status'        => 'open',
        ]);

        $this->actingAs($user)
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'ticket-reply']), [
                'ticket_id' => $ticket->id,
                'reply'     => 'Terima kasih atas aduan Anda. Kami akan tindaklanjuti.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('wa_caraka_tickets', [
            'id'          => $ticket->id,
            'status'      => 'replied',
            'assigned_to' => $user->id,
        ]);
    }

    public function test_operator_can_send_ticket_reply_via_wa(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-text' => Http::response(['ok' => true, 'messageId' => 'ticket-reply-001'], 200),
        ]);

        $user = $this->operatorUser();
        $ticket = WaCarakaTicket::create([
            'type'          => 'pengaduan',
            'remote_number' => '628111111111',
            'message'       => 'Aduan test',
            'reply'         => 'Sudah kami tindaklanjuti.',
            'status'        => 'replied',
            'assigned_to'   => $user->id,
            'replied_at'    => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'ticket-send']), [
                'ticket_id' => $ticket->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('wa_caraka_tickets', [
            'id'     => $ticket->id,
            'status' => 'sent',
        ]);

        // Should also create a WA message log
        $this->assertDatabaseHas('wa_caraka_messages', [
            'remote_number' => '628111111111',
            'direction'     => 'outbound',
        ]);
    }

    public function test_operator_can_transfer_ticket(): void
    {
        $ticket = WaCarakaTicket::create([
            'type'          => 'pengaduan',
            'remote_number' => '628111111111',
            'message'       => 'Ini seharusnya konsultasi',
            'status'        => 'open',
        ]);

        $this->actingAs($this->operatorUser())
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'ticket-transfer']), [
                'ticket_id' => $ticket->id,
                'new_type'  => 'konsultasi',
            ])
            ->assertOk();

        $this->assertDatabaseHas('wa_caraka_tickets', [
            'id'   => $ticket->id,
            'type' => 'konsultasi',
        ]);
    }

    public function test_operator_can_close_ticket(): void
    {
        $ticket = WaCarakaTicket::create([
            'type'          => 'pengaduan',
            'remote_number' => '628111111111',
            'message'       => 'Aduan selesai',
            'status'        => 'sent',
        ]);

        $this->actingAs($this->operatorUser())
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'ticket-close']), [
                'ticket_id' => $ticket->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('wa_caraka_tickets', [
            'id'     => $ticket->id,
            'status' => 'closed',
        ]);
    }

    public function test_ticket_stats_returns_counts(): void
    {
        WaCarakaTicket::create([
            'type' => 'pengaduan', 'remote_number' => '628111111111',
            'message' => 'Test 1', 'status' => 'open',
        ]);
        WaCarakaTicket::create([
            'type' => 'konsultasi', 'remote_number' => '628222222222',
            'message' => 'Test 2', 'status' => 'open',
        ]);
        WaCarakaTicket::create([
            'type' => 'pengaduan', 'remote_number' => '628333333333',
            'message' => 'Test 3', 'status' => 'closed',
        ]);

        $this->actingAs($this->operatorUser())
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'ticket-stats']))
            ->assertOk()
            ->assertJsonFragment([
                'total'      => 3,
                'open'       => 2,
                'closed'     => 1,
                'pengaduan'  => 2,
                'konsultasi' => 1,
            ]);
    }
}
