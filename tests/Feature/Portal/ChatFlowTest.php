<?php

namespace Tests\Feature\Portal;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChatFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_operator_can_open_chat_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/chat');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Lawangsewu/Chat')
            ->has('initialMessages')
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
        $this->assertSame(1, ChatMessage::query()->count());
    }

    public function test_guest_cannot_send_chat_message(): void
    {
        $response = $this->post('/chat', [
            'content' => 'Unauthorized',
        ]);

        $response->assertRedirect('/login');
    }
}
