<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_open_system_monitor_page(): void
    {
        $superadmin = User::factory()->create([
            'email' => Config::string('auth.super_admin_email'),
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.system-monitor.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/SystemMonitor')
            ->has('snapshot')
            ->has('snapshot.health')
            ->has('snapshot.backup')
            ->where('snapshot.health.score_pct', fn ($value) => is_int($value) || is_float($value))
            ->where('snapshot.backup.condition_pct', fn ($value) => is_int($value))
        );
    }

    public function test_non_superadmin_cannot_access_system_monitor_page(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
            'email' => 'admin.biasa@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.system-monitor.index'));

        $response->assertForbidden();
    }
}
