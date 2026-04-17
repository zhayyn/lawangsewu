<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilarModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_viewer_can_open_pilar_module_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/pilar-smg');

        $response->assertOk();
        $response->assertSee('Pilar Antrian PASMG');
        $response->assertSee('hub antrean terpadu', escape: false);
    }
}
