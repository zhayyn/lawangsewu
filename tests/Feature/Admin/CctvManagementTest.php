<?php

namespace Tests\Feature\Admin;

use App\Models\CctvCamera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CctvManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_open_cctv_management_page(): void
    {
        $superadmin = User::factory()->create([
            'email' => Config::string('auth.super_admin_email'),
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        CctvCamera::query()->create([
            'key' => 'cam-lobby-utama',
            'name' => 'Lobby Utama',
            'zone' => 'Publik',
            'iframe_src' => 'https://example.test/cctv/lobby',
            'sort_order' => 1,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.cctv.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/CctvManager')
            ->has('cameras', 1)
            ->where('cameras.0.name', 'Lobby Utama')
            ->where('cameras.0.iframe_src', 'https://example.test/cctv/lobby')
        );
    }

    public function test_superadmin_can_update_camera_name_and_source_url(): void
    {
        $superadmin = User::factory()->create([
            'email' => Config::string('auth.super_admin_email'),
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $camera = CctvCamera::query()->create([
            'key' => 'cam-ptsp-awal',
            'name' => 'PTSP Awal',
            'zone' => 'Pelayanan',
            'iframe_src' => 'https://example.test/cctv/old',
            'sort_order' => 3,
            'is_active' => true,
            'is_featured' => false,
        ]);

        $response = $this->actingAs($superadmin)->patch(route('admin.cctv.update', $camera), [
            'name' => 'PTSP Front Desk',
            'zone' => 'PTSP',
            'iframe_src' => 'https://example.test/cctv/new-source',
            'sort_order' => 1,
            'is_active' => true,
            'is_featured' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('cctv_cameras', [
            'id' => $camera->id,
            'name' => 'PTSP Front Desk',
            'zone' => 'PTSP',
            'iframe_src' => 'https://example.test/cctv/new-source',
            'sort_order' => 1,
            'is_featured' => true,
        ]);
    }

    public function test_non_superadmin_cannot_manage_cctv(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-biasa@example.test',
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $camera = CctvCamera::query()->create([
            'key' => 'cam-ruang-sidang',
            'name' => 'Ruang Sidang',
            'zone' => 'Sidang',
            'iframe_src' => 'https://example.test/cctv/sidang',
            'sort_order' => 4,
            'is_active' => true,
            'is_featured' => false,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.cctv.update', $camera), [
            'name' => 'Ruang Sidang 1',
            'zone' => 'Sidang',
            'iframe_src' => 'https://example.test/cctv/sidang-1',
            'sort_order' => 4,
            'is_active' => true,
            'is_featured' => false,
        ]);

        $response->assertForbidden();
    }
}
