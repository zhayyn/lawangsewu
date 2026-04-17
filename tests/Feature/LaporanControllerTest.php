<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanControllerTest extends TestCase
{
    public function test_superadmin_can_access_laporan_page(): void
    {
        $user = User::where('email', 'dbprakom@gmail.com')->first();
        $this->assertNotNull($user, 'Superadmin user not found');

        $response = $this->actingAs($user)->get('/admin/laporan');

        $response->assertStatus(200);
    }

    public function test_laporan_page_returns_inertia_component(): void
    {
        $user = User::where('email', 'dbprakom@gmail.com')->first();

        $response = $this->actingAs($user)->get('/admin/laporan');

        $response->assertInertia(fn ($page) => $page->component('Admin/Laporan'));
    }
}
