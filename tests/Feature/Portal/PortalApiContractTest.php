<?php

namespace Tests\Feature\Portal;

use App\Models\CctvCamera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_message_api_contract_returns_expected_shape(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'alias' => 'Operator_API',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/lawangsewu/chat/messages', [
            'content' => 'Kontrak API chat message.',
            'type' => 'global',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Pesan berhasil dikirim.')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'type',
                    'senderName',
                    'alias',
                    'body',
                    'avatar',
                    'sentAt',
                    'isOwn',
                ],
            ]);
    }

    public function test_cameras_api_contract_returns_network_summary_and_cameras(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        CctvCamera::query()->create([
            'key' => 'contract-cam-1',
            'name' => 'Contract Cam 1',
            'zone' => 'Publik',
            'iframe_src' => 'https://example.test/contract-cam-1',
            'sort_order' => 1,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/lawangsewu/cameras');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'networkSummary' => ['cameraCount', 'locationCount', 'status', 'latency'],
                    'cameras' => [
                        '*' => ['id', 'key', 'name', 'zone', 'iframeSrc', 'status', 'featured', 'resolution', 'updatedAt', 'signal', 'sortOrder'],
                    ],
                ],
            ]);
    }
}
