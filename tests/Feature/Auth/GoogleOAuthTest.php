<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_unregistered_google_user_is_created_as_inactive_and_redirected_to_pending(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn((object) [
            'id' => 'google-12345',
            'email' => 'new-google-user@example.test',
            'name' => 'New Google User',
            'nickname' => null,
            'avatar' => 'https://example.test/avatar.png',
        ]);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('access.pending', ['reason' => 'unregistered'], false));

        $this->assertDatabaseHas('users', [
            'email' => 'new-google-user@example.test',
            'google_id' => 'google-12345',
            'is_active' => false,
            'role' => 'viewer',
        ]);

        $this->assertGuest();
    }

    public function test_existing_inactive_google_user_redirects_to_pending(): void
    {
        User::factory()->create([
            'name' => 'Inactive Google User',
            'email' => 'inactive-google@example.test',
            'google_id' => 'google-existing-1',
            'is_active' => false,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn((object) [
            'id' => 'google-existing-1',
            'email' => 'inactive-google@example.test',
            'name' => 'Inactive Google User',
            'nickname' => null,
            'avatar' => 'https://example.test/avatar.png',
        ]);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('access.pending', ['reason' => 'pending'], false));
        $this->assertGuest();
    }

    public function test_existing_active_google_user_is_logged_in(): void
    {
        $user = User::factory()->create([
            'name' => 'Active Google User',
            'email' => 'active-google@example.test',
            'google_id' => 'google-active-1',
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn((object) [
            'id' => 'google-active-1',
            'email' => 'active-google@example.test',
            'name' => 'Active Google User',
            'nickname' => null,
            'avatar' => 'https://example.test/avatar.png',
        ]);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_pending_google_user_can_be_approved_by_superadmin_and_login_afterwards(): void
    {
        $googlePayload = (object) [
            'id' => 'google-approval-1',
            'email' => 'approval-flow@example.test',
            'name' => 'Approval Flow User',
            'nickname' => null,
            'avatar' => 'https://example.test/avatar.png',
        ];

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->twice()->andReturn($googlePayload, $googlePayload);
        Socialite::shouldReceive('driver')->with('google')->twice()->andReturn($provider);

        $firstCallback = $this->get('/auth/google/callback');
        $firstCallback->assertRedirect(route('access.pending', ['reason' => 'unregistered'], false));

        $pendingUser = User::query()->where('email', 'approval-flow@example.test')->firstOrFail();
        $this->assertFalse((bool) $pendingUser->is_active);

        $superadmin = User::factory()->create([
            'email' => Config::string('auth.super_admin_email'),
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $approval = $this->actingAs($superadmin)->patch(route('admin.users.update', $pendingUser), [
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $approval->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'is_active' => true,
            'role' => 'viewer',
        ]);

        $this->post('/logout');

        $secondCallback = $this->get('/auth/google/callback');

        $secondCallback->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($pendingUser->fresh());
    }
}
