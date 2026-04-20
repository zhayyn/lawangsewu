<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_access_laporan_page(): void
    {
        $user = User::factory()->create([
            'email' => 'dbprakom@gmail.com',
            'role' => 'admin',
            'is_superadmin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/admin/laporan');

        $response->assertStatus(200);
    }

    public function test_laporan_page_returns_inertia_component(): void
    {
        $user = User::factory()->create([
            'email' => 'dbprakom@gmail.com',
            'role' => 'admin',
            'is_superadmin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/admin/laporan');

        $response->assertInertia(fn ($page) => $page->component('Admin/Laporan'));
    }
}
