<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\NetworkMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NetworkMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_open_network_monitor_page(): void
    {
        $this->app->instance(NetworkMonitorService::class, $this->fakeNetworkMonitorService());

        $superadmin = $this->superadminUser();

        $response = $this->actingAs($superadmin)->get(route('admin.network-monitor.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/NetworkMonitor')
            ->has('snapshot')
            ->has('snapshot.interfaces.eth0')
            ->has('snapshot.bandwidth_history')
            ->where('endpoints.api', route('admin.network-monitor.api'))
            ->where('snapshot.captured_at', '12:00:00')
        );
    }

    public function test_superadmin_can_fetch_network_monitor_api(): void
    {
        $this->app->instance(NetworkMonitorService::class, $this->fakeNetworkMonitorService());

        $superadmin = $this->superadminUser();

        $response = $this->actingAs($superadmin)
            ->getJson(route('admin.network-monitor.api'));

        $response
            ->assertOk()
            ->assertJsonPath('interfaces.eth0.status', 'active')
            ->assertJsonPath('system_net.established', 2);
    }

    public function test_non_superadmin_cannot_access_network_monitor_page(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'is_superadmin' => false,
            'role' => 'admin',
            'email_verified_at' => now(),
            'email' => 'admin.biasa@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.network-monitor.index'));

        $response->assertForbidden();
    }

    private function superadminUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
            'is_superadmin' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    private function fakeNetworkMonitorService(): NetworkMonitorService
    {
        return new class extends NetworkMonitorService {
            public function snapshot(): array
            {
                return [
                    'captured_at' => '12:00:00',
                    'interfaces' => [
                        'eth0' => [
                            'name' => 'eth0',
                            'rx_bytes' => 1024,
                            'tx_bytes' => 2048,
                            'rx_rate' => 128,
                            'tx_rate' => 256,
                            'rx_human' => '128 B/s',
                            'tx_human' => '256 B/s',
                            'total_bytes' => 3072,
                            'status' => 'active',
                        ],
                    ],
                    'bandwidth_history' => [
                        ['time' => '12:00:00', 'rx' => 1024, 'tx' => 2048],
                    ],
                    'top_talkers' => [],
                    'stream_health' => [],
                    'system_net' => [
                        'total_connections' => 3,
                        'established' => 2,
                        'time_wait' => 1,
                        'listen' => 0,
                        'dns_queries' => 0,
                    ],
                ];
            }
        };
    }
}
