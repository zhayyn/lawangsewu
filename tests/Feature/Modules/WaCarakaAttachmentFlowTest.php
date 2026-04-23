<?php

namespace Tests\Feature\Modules;

use App\Models\User;
use App\Models\WaCarakaConversation;
use App\Models\WaCarakaMessage;
use App\Services\WaCarakaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WaCarakaAttachmentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_store_outbound_document_message(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-media' => Http::response([
                'ok' => true,
                'messageId' => 'doc-001',
                'media' => [
                    'kind' => 'document',
                    'mimetype' => 'application/pdf',
                    'fileName' => 'panduan.pdf',
                ],
            ], 200),
        ]);

        $user = User::factory()->create(['role' => 'operator']);
        $service = app(WaCarakaService::class);

        $response = $service->sendMedia('628123456789', [
            'media_kind' => 'document',
            'media_url' => 'data:application/pdf;base64,SGVsbG8=',
            'mime_type' => 'application/pdf',
            'file_name' => 'panduan.pdf',
            'caption' => 'Panduan layanan',
        ], 'OPERATOR', $user->id);

        $this->assertTrue($response['ok']);
        $this->assertDatabaseHas('wa_caraka_messages', [
            'remote_number' => '628123456789',
            'direction' => 'outbound',
            'message_type' => 'document',
            'status' => 'sent',
            'user_id' => $user->id,
        ]);
    }

    public function test_service_infers_rtf_mime_type_when_missing(): void
    {
        Http::fake([
            'http://127.0.0.1:8790/send-media' => Http::response([
                'ok' => true,
                'messageId' => 'doc-rtf-001',
                'media' => [
                    'kind' => 'document',
                    'mimetype' => 'application/rtf',
                    'fileName' => 'berita-acara.rtf',
                ],
            ], 200),
        ]);

        $user = User::factory()->create(['role' => 'operator']);
        $service = app(WaCarakaService::class);

        $response = $service->sendMedia('628123456789', [
            'media_kind' => 'document',
            'media_url' => 'data:application/rtf;base64,e1xydGYxXGFuc2kgSGVsbG99',
            'file_name' => 'berita-acara.rtf',
            'caption' => 'Berkas RTF',
        ], 'OPERATOR', $user->id);

        $this->assertTrue($response['ok']);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:8790/send-media'
                && $request['mime_type'] === 'application/rtf'
                && $request['file_name'] === 'berita-acara.rtf';
        });
    }

    public function test_service_can_store_outbound_sticker_message(): void
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

        $user = User::factory()->create(['role' => 'operator']);
        $service = app(WaCarakaService::class);

        $response = $service->sendMedia('628777888999', [
            'media_kind' => 'sticker',
            'media_url' => 'data:image/webp;base64,UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoQABAAPp1moqI=',
            'mime_type' => 'image/webp',
            'file_name' => 'operator.webp',
        ], 'OPERATOR', $user->id);

        $this->assertTrue($response['ok']);
        $this->assertDatabaseHas('wa_caraka_messages', [
            'remote_number' => '628777888999',
            'direction' => 'outbound',
            'message_type' => 'sticker',
            'status' => 'sent',
        ]);
    }

    public function test_inbound_media_token_is_exposed_as_proxy_url(): void
    {
        $service = app(WaCarakaService::class);

        $message = $service->handleInbound([
            'from' => '628111222333',
            'to' => '628001234567',
            'text' => '',
            'type' => 'document',
            'id' => 'wa-media-attachment-001',
            'media' => [
                'kind' => 'document',
                'mimetype' => 'application/pdf',
                'fileName' => 'berkas.pdf',
                'mediaToken' => str_repeat('b', 32),
            ],
        ]);

        $this->assertSame(
            route('lawangsewu.wacaraka.media', ['path' => str_repeat('b', 32)]),
            data_get($message->metadata, 'media.dataUrl'),
        );
    }

    public function test_inbox_returns_preview_for_last_message(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $remote = '628111222333';
        $conversationId = WaCarakaMessage::conversationIdFor($remote);

        WaCarakaConversation::create([
            'conversation_id' => $conversationId,
            'remote_number' => $remote,
            'status' => 'open',
            'last_activity_at' => now(),
        ]);

        WaCarakaMessage::create([
            'direction' => 'inbound',
            'remote_number' => $remote,
            'message_text' => 'Dokumen pengajuan sudah saya kirim lengkap.',
            'message_type' => 'document',
            'status' => 'received',
            'conversation_id' => $conversationId,
            'metadata' => [],
        ]);

        $this->actingAs($user)
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'inbox']))
            ->assertOk()
            ->assertJsonFragment([
                'conversationId' => $conversationId,
                'lastMessagePreview' => 'Dokumen pengajuan sudah saya kirim lengkap.',
            ]);
    }

    public function test_inbox_returns_last_message_media_preview_metadata(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $remote = '628111222333';
        $conversationId = WaCarakaMessage::conversationIdFor($remote);

        WaCarakaConversation::create([
            'conversation_id' => $conversationId,
            'remote_number' => $remote,
            'status' => 'open',
            'last_activity_at' => now(),
        ]);

        WaCarakaMessage::create([
            'direction' => 'inbound',
            'remote_number' => $remote,
            'message_text' => '',
            'message_type' => 'image',
            'status' => 'received',
            'conversation_id' => $conversationId,
            'metadata' => [
                'media' => [
                    'kind' => 'image',
                    'dataUrl' => 'data:image/jpeg;base64,SGVsbG8=',
                    'fileName' => 'foto.jpg',
                    'mimetype' => 'image/jpeg',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->getJson(route('lawangsewu.wacaraka.api', ['action' => 'inbox']))
            ->assertOk()
            ->assertJsonFragment([
                'conversationId' => $conversationId,
                'kind' => 'image',
                'fileName' => 'foto.jpg',
                'hasVisualPreview' => true,
            ]);
    }

    public function test_send_media_rejects_payload_over_configured_limit(): void
    {
        config()->set('wa_caraka.max_media_bytes', 32);

        $user = User::factory()->create(['role' => 'operator']);

        $payload = base64_encode(str_repeat('A', 80));

        $this->actingAs($user)
            ->postJson(route('lawangsewu.wacaraka.api', ['action' => 'send-media']), [
                'to' => '628123456789',
                'media_kind' => 'document',
                'media_url' => 'data:application/pdf;base64,' . $payload,
                'file_name' => 'besar.pdf',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'error' => 'Ukuran file melebihi batas maksimum 1 MB.',
            ]);
    }

    public function test_conversation_messages_can_be_loaded_by_conversation_id(): void
    {
        $service = app(WaCarakaService::class);
        $conversationId = 'wa_personal_628123_abc123';

        WaCarakaMessage::create([
            'direction' => 'inbound',
            'remote_number' => '628111222333',
            'message_text' => 'Pesan pertama',
            'message_type' => 'text',
            'status' => 'received',
            'conversation_id' => $conversationId,
            'metadata' => [],
        ]);

        WaCarakaMessage::create([
            'direction' => 'outbound',
            'remote_number' => '628999888777',
            'message_text' => 'Balasan operator',
            'message_type' => 'text',
            'status' => 'sent',
            'conversation_id' => $conversationId,
            'metadata' => [],
        ]);

        $messages = $service->conversationMessages($conversationId, 50, true);

        $this->assertCount(2, $messages);
        $this->assertSame('Pesan pertama', $messages[0]['text']);
        $this->assertSame('Balasan operator', $messages[1]['text']);
    }

    public function test_conversation_messages_keep_same_logical_thread_without_loading_other_chat(): void
    {
        $service = app(WaCarakaService::class);
        $conversationA = 'wa_personal_628111222333_old_test_case';
        $conversationB = 'wa_personal_628999000111_active_test_case';

        WaCarakaConversation::create([
            'conversation_id' => $conversationA,
            'remote_number' => '628111222333@s.whatsapp.net',
            'status' => 'pending',
            'last_activity_at' => now()->subMinute(),
        ]);

        WaCarakaConversation::create([
            'conversation_id' => $conversationB,
            'remote_number' => '628999000111',
            'status' => 'pending',
            'last_activity_at' => now(),
        ]);

        WaCarakaMessage::create([
            'direction' => 'inbound',
            'remote_number' => '628111222333',
            'message_text' => 'Chat A',
            'message_type' => 'text',
            'status' => 'received',
            'conversation_id' => $conversationA,
            'metadata' => [],
        ]);

        WaCarakaMessage::create([
            'direction' => 'inbound',
            'remote_number' => '628999000111',
            'message_text' => 'Chat B',
            'message_type' => 'text',
            'status' => 'received',
            'conversation_id' => $conversationB,
            'metadata' => [],
        ]);

        $messages = $service->conversationMessages($conversationB, 50, true);

        $this->assertCount(1, $messages);
        $this->assertSame('Chat B', $messages[0]['text']);
    }
}
