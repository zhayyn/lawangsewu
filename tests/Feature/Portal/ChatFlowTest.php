<?php

namespace Tests\Feature\Portal;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChatFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_active_operator_can_open_chat_page(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $path = UploadedFile::fake()->image('preview.jpg')->store('chat-media/test', 'public');

        ChatMessage::query()->create([
            'user_id' => $user->id,
            'type' => 'global',
            'content' => '',
            'metadata' => [
                'attachment' => [
                    'kind' => 'image',
                    'disk' => 'public',
                    'path' => $path,
                    'mime' => 'image/jpeg',
                    'original_name' => 'preview.jpg',
                    'size_bytes' => 12345,
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get('/chat');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Lawangsewu/Chat')
            ->has('initialMessages')
            ->where('initialMessages.0.metadata.attachment.url', route('lawangsewu.chat.media', 1))
            ->has('activeUsers')
        );
    }

    public function test_active_operator_can_send_chat_message(): void
    {
        Event::fake([ChatMessageSent::class]);

        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $beforeCount = ChatMessage::query()->count();

        $response = $this->actingAs($user)->post('/chat', [
            'content' => 'Pesan uji integrasi chat.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'type' => 'global',
            'content' => 'Pesan uji integrasi chat.',
        ]);

        Event::assertDispatched(ChatMessageSent::class);
        $this->assertSame($beforeCount + 1, ChatMessage::query()->count());
    }

    public function test_active_operator_can_send_chat_image_attachment(): void
    {
        Event::fake([ChatMessageSent::class]);
        Storage::fake('public');

        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $beforeCount = ChatMessage::query()->count();

        $response = $this->actingAs($user)->post('/chat', [
            'content' => 'Laporan visual terkirim.',
            'attachment' => UploadedFile::fake()->image('laporan.jpg', 1200, 800)->size(450),
        ]);

        $response->assertRedirect();

        $message = ChatMessage::query()->latest('id')->firstOrFail();

        $this->assertSame('Laporan visual terkirim.', $message->content);
        $this->assertSame('image', data_get($message->metadata, 'attachment.kind'));
        $this->assertNotNull(data_get($message->metadata, 'attachment.path'));
        Storage::disk('public')->assertExists(data_get($message->metadata, 'attachment.path'));
        Event::assertDispatched(ChatMessageSent::class);
        $this->assertSame($beforeCount + 1, ChatMessage::query()->count());
    }

    public function test_authenticated_user_can_view_chat_attachment_media(): void
    {
        Storage::fake('public');

        $sender = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $viewer = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $path = UploadedFile::fake()->image('evidence.jpg')->store('chat-media/test', 'public');

        $message = ChatMessage::query()->create([
            'user_id' => $sender->id,
            'type' => 'global',
            'content' => '',
            'metadata' => [
                'attachment' => [
                    'kind' => 'image',
                    'disk' => 'public',
                    'path' => $path,
                    'mime' => 'image/jpeg',
                    'original_name' => 'evidence.jpg',
                    'size_bytes' => 12345,
                ],
            ],
        ]);

        $response = $this->actingAs($viewer)->get(route('lawangsewu.chat.media', $message));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/jpeg');
    }

    public function test_chat_attachment_must_not_exceed_two_megabytes(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $beforeCount = ChatMessage::query()->count();

        $response = $this->from('/chat')->actingAs($user)->post('/chat', [
            'content' => '',
            'attachment' => UploadedFile::fake()->create('oversize.mp4', 2500, 'video/mp4'),
        ]);

        $response
            ->assertRedirect('/chat')
            ->assertSessionHasErrors('attachment');

        $this->assertSame($beforeCount, ChatMessage::query()->count());
    }

    public function test_guest_cannot_send_chat_message(): void
    {
        $response = $this->post('/chat', [
            'content' => 'Unauthorized',
        ]);

        $response->assertRedirect('/login');
    }
}
