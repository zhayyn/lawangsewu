<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PendopoSatelliteTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_viewer_can_open_pendopo_satellite_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('lawangsewu.satellite.pendopo'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Lawangsewu/Satellite/Pendopo')
            ->where('pendopoUrl', route('lawangsewu.guestbook.form') . '?embedded=1')
            ->where('metricUrl', route('lawangsewu.satellite.pendopo.metric'))
        );
    }

    public function test_metric_endpoint_stores_pendopo_snapshot(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        Cache::forget('pendopo:metric:snapshot:v1');

        $response = $this->actingAs($user)->get(route('lawangsewu.satellite.pendopo.metric', [
            'event' => 'iframe_load',
            'duration_ms' => 1234,
            'compact' => 1,
            'mode' => 'iframe',
        ]));

        $response->assertOk()->assertJsonPath('status', 'ok');

        $snapshot = Cache::get('pendopo:metric:snapshot:v1', []);

        $this->assertSame(1, $snapshot['total_events'] ?? 0);
        $this->assertSame(1, $snapshot['events']['iframe_load'] ?? 0);
        $this->assertSame(1, $snapshot['compact_hits'] ?? 0);
        $this->assertSame([1234], $snapshot['load_samples_ms'] ?? []);
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
