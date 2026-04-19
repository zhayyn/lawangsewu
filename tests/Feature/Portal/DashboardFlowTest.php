<?php

namespace Tests\Feature\Portal;

use App\Models\CctvCamera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_operator_can_open_dashboard_payload(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        CctvCamera::query()->create([
            'key' => 'cam-main-lobby',
            'name' => 'Main Lobby',
            'zone' => 'Publik',
            'iframe_src' => 'https://example.test/cam/main-lobby',
            'sort_order' => 1,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Lawangsewu/Dashboard')
            ->where('navGroups', function ($groups): bool {
                $labels = collect($groups)->flatMap(fn (array $group) => $group['items'] ?? [])->pluck('label')->values()->all();

                 // Operator sees: Dashboard Utama + Chat Internal (Dashboard category)
                 //               + Antrian PTSP + WA Live PTSP (Pelayanan category)
                 sort($labels);
                 $expected = ['Antrian PTSP', 'Chat Internal', 'Dashboard Utama', 'WA Live PTSP'];
                 sort($expected);
                 return $labels === $expected;
            })
            ->where('quickActions', function ($items): bool {
                $labels = collect($items)->pluck('label')->values()->all();

                return $labels === ['Buka Chat', 'Buka Antrian PTSP', 'WA Live PTSP'];
            })
            ->where('modules', function ($items): bool {
                $labels = collect($items)->pluck('title')->values()->all();

                return $labels === ['Antrian PTSP', 'Chat Internal', 'WA Live PTSP'];
            })
            ->has('metrics')
            ->has('cameras')
            ->has('messages')
            ->has('channels')
        );
    }

    public function test_active_operator_can_open_cctv_payload(): void
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
            'iframe_src' => 'https://example.test/cam/ptsp-lobby',
            'sort_order' => 1,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $response = $this->actingAs($user)->get('/cctv');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Lawangsewu/Cctv')
            ->has('cameras', 1)
            ->where('networkSummary.cameraCount', 1)
            ->where('networkSummary.locationCount', 1)
        );
    }

    public function test_active_viewer_can_open_dashboard_payload(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Lawangsewu/Dashboard')
            ->has('metrics')
        );
    }

    public function test_inactive_user_is_redirected_from_dashboard(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login', absolute: false));
    }
}
