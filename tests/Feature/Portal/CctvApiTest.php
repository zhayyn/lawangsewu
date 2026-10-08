<?php

namespace Tests\Feature\Portal;

use App\Models\CctvCamera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CctvApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_operator_can_fetch_cctv_payload(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        CctvCamera::query()->create([
            'key' => 'cam-ptsp-lobby',
            'name' => 'PTSP Lobby',
            'zone' => 'Pelayanan',
            'iframe_src' => 'https://example.test/camera/ptsp-lobby',
            'primary_sd_src' => 'http://192.168.88.200:8889/ptsp-sd/',
            'primary_hd_src' => 'http://192.168.88.200:8889/ptsp-hd/',
            'fallback_src' => 'https://example.test/camera/ptsp-lobby',
            'stream_provider' => 'mediamtx-relay',
            'sort_order' => 1,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/lawangsewu/cameras');

        $response
            ->assertOk()
            ->assertJsonPath('data.networkSummary.cameraCount', 1)
            ->assertJsonPath('data.cameras.0.name', 'PTSP Lobby')
            ->assertJsonPath('data.cameras.0.zone', 'Pelayanan')
            ->assertJsonPath('data.cameras.0.previewSrc', '/cctv/ptsp-sd/index.m3u8')
            ->assertJsonPath('data.cameras.0.fullSrc', '/cctv/ptsp-hd/index.m3u8')
            ->assertJsonPath('data.cameras.0.fallbackSrc', 'https://example.test/camera/ptsp-lobby')
            ->assertJsonPath('data.cameras.0.streamType', 'hls')
            ->assertJsonPath('data.cameras.0.status', 'LIVE');
    }

    public function test_inactive_user_cannot_fetch_cctv_payload(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/lawangsewu/cameras');

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Akun belum aktif. Hubungi admin untuk aktivasi.');
    }
}
