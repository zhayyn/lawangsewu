<?php

namespace Tests\Feature\Admin;

use App\Models\GuestbookEntry;
use App\Models\GuestbookSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PendopoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_open_pendopo_management_page(): void
    {
        $superadmin = User::factory()->create([
            'email' => Config::string('auth.super_admin_email'),
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        GuestbookSetting::query()->updateOrCreate(
            ['id' => '1'],
            [
                'per_page' => 12,
                'require_identity_fields' => false,
                'event_name' => 'Pendopo PASMG',
            ]
        );

        GuestbookEntry::query()->create([
            'id' => '202604100001',
            'name' => 'Tamara',
            'position' => 'Viewer',
            'institution_category' => 'PERSEORANGAN',
            'institution' => 'Perseorangan',
            'purpose' => 'Kunjungan',
            'checkin' => now(),
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.pendopo.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/PendopoManager')
            ->where('settings.per_page', 12)
            ->where('settings.event_name', 'Pendopo PASMG')
            ->where('stats.all', 1)
            ->has('recentEntries', 1)
        );
    }

    public function test_superadmin_can_update_pendopo_settings(): void
    {
        $superadmin = User::factory()->create([
            'email' => Config::string('auth.super_admin_email'),
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->patch(route('admin.pendopo.settings.update'), [
            'per_page' => 25,
            'require_identity_fields' => true,
            'event_name' => 'Pendopo Baru',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('guestbook_settings', [
            'id' => '1',
            'per_page' => 25,
            'require_identity_fields' => 1,
            'event_name' => 'Pendopo Baru',
        ]);
    }
}
