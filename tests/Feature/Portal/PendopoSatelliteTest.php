<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendopoSatelliteTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_viewer_is_redirected_to_guestbook_from_pendopo_satellite_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('lawangsewu.satellite.pendopo'));

        $response->assertRedirect(route('lawangsewu.guestbook.form', ['embedded' => 1]));
    }

    public function test_metric_endpoint_returns_gone_for_deprecated_pendopo_module(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('lawangsewu.satellite.pendopo.metric', [
            'event' => 'iframe_load',
            'duration_ms' => 1234,
            'compact' => 1,
            'mode' => 'iframe',
        ]));

        $response
            ->assertStatus(410)
            ->assertJsonPath('status', 'deprecated');
    }

    public function test_embedded_guestbook_form_uses_lite_mode(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('lawangsewu.guestbook.form', [
            'embedded' => 1,
        ]));

        $response
            ->assertOk()
            ->assertSee('Form Pendopo Cepat')
            ->assertDontSee('Layanan Front Desk');
    }
}
