<?php

namespace Tests\Feature\Auth;

use App\Models\GoogleAccessAllowlist;
use App\Models\User;
use App\Services\GoogleIdTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowlisted_google_user_is_created_as_inactive_and_redirected_to_pending(): void
    {
        GoogleAccessAllowlist::create([
            'email' => 'new-google-user@example.test',
            'auto_activate' => false,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn((object) [
            'id' => 'google-12345',
            'email' => 'new-google-user@example.test',
            'name' => 'New Google User',
            'nickname' => null,
            'avatar' => 'https://example.test/avatar.png',
        ]);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback?code=test-code-1');

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

        $response = $this->get('/auth/google/callback?code=test-code-2');

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

        $response = $this->get('/auth/google/callback?code=test-code-3');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_pending_google_user_can_be_approved_by_superadmin_and_login_afterwards(): void
    {
        GoogleAccessAllowlist::create([
            'email' => 'approval-flow@example.test',
            'auto_activate' => false,
        ]);

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

        $firstCallback = $this->get('/auth/google/callback?code=test-code-4');
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

        $secondCallback = $this->get('/auth/google/callback?code=test-code-5');

        $secondCallback->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($pendingUser->fresh());
    }

    public function test_google_user_with_null_name_uses_email_prefix_as_display_name(): void
    {
        GoogleAccessAllowlist::create([
            'email' => 'petugas@pa-semarang.go.id',
            'auto_activate' => false,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn((object) [
            'id' => 'google-no-name',
            'email' => 'petugas@pa-semarang.go.id',
            'name' => null,
            'nickname' => null,
            'avatar' => null,
        ]);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google/callback?code=test-code-6');

        $this->assertDatabaseHas('users', [
            'email' => 'petugas@pa-semarang.go.id',
            'name' => 'petugas',
        ]);
    }

    public function test_non_allowlisted_google_user_is_blocked_before_account_creation(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn((object) [
            'id' => 'google-outsider',
            'email' => 'outsider@pa-semarang.go.id',
            'name' => 'Outsider',
            'nickname' => null,
            'avatar' => 'https://example.test/avatar.png',
        ]);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback?code=test-code-7');

        $response->assertRedirect(route('access.pending', ['reason' => 'not-allowed'], false));
        $this->assertDatabaseMissing('users', [
            'email' => 'outsider@pa-semarang.go.id',
        ]);
        $this->assertGuest();
    }

    public function test_allowlisted_google_workspace_user_can_auto_activate_and_login(): void
    {
        GoogleAccessAllowlist::create([
            'email' => 'pegawai@pa-semarang.go.id',
            'auto_activate' => true,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn((object) [
            'id' => 'google-workspace-1',
            'email' => 'pegawai@pa-semarang.go.id',
            'name' => 'Pegawai Workspace',
            'nickname' => null,
            'avatar' => 'https://example.test/avatar.png',
        ]);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback?code=test-code-8');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'pegawai@pa-semarang.go.id',
            'google_id' => 'google-workspace-1',
            'is_active' => true,
            'role' => 'viewer',
        ]);
        $this->assertAuthenticated();
    }

    public function test_callback_retries_stateless_when_google_returns_invalid_state(): void
    {
        GoogleAccessAllowlist::create([
            'email' => 'fallback@pa-semarang.go.id',
            'auto_activate' => true,
        ]);

        $statefulProvider = Mockery::mock();
        $statefulProvider->shouldReceive('user')->once()->andThrow(new InvalidStateException());

        $statelessProvider = Mockery::mock();
        $statelessProvider->shouldReceive('stateless')->once()->andReturnSelf();
        $statelessProvider->shouldReceive('user')->once()->andReturn((object) [
            'id' => 'google-fallback-1',
            'email' => 'fallback@pa-semarang.go.id',
            'name' => 'Fallback User',
            'nickname' => null,
            'avatar' => 'https://example.test/avatar.png',
        ]);

        Socialite::shouldReceive('driver')->with('google')->twice()->andReturn($statefulProvider, $statelessProvider);

        $response = $this->get('/auth/google/callback?code=test-code-9');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_google_error_callback_redirects_to_access_pending(): void
    {
        $response = $this->get('/auth/google/callback?error=access_denied&error_description=User+denied+access');

        $response->assertRedirect(route('access.pending', ['reason' => 'error'], false));
    }

    public function test_callback_without_code_redirects_to_login(): void
    {
        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_superadmin_can_login_via_google_id_token(): void
    {
        $verifier = Mockery::mock(GoogleIdTokenVerifier::class);
        $verifier->shouldReceive('verify')->once()->with('token-superadmin')->andReturn([
            'id' => 'google-superadmin-id',
            'email' => Config::string('auth.super_admin_email'),
            'name' => 'DB Prakom',
            'avatar' => 'https://example.test/superadmin-avatar.png',
        ]);

        $this->app->instance(GoogleIdTokenVerifier::class, $verifier);

        $response = $this->postJson(route('auth.google.credential'), [
            'credential' => 'token-superadmin',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('redirect', route('dashboard'));

        $user = User::query()->where('email', Config::string('auth.super_admin_email'))->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => Config::string('auth.super_admin_email'),
            'google_id' => 'google-superadmin-id',
            'is_active' => true,
            'role' => 'admin',
            'is_superadmin' => true,
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_non_allowlisted_google_id_token_is_redirected_to_pending(): void
    {
        $verifier = Mockery::mock(GoogleIdTokenVerifier::class);
        $verifier->shouldReceive('verify')->once()->with('token-outsider')->andReturn([
            'id' => 'google-outsider-id',
            'email' => 'outsider@pa-semarang.go.id',
            'name' => 'Outsider',
            'avatar' => 'https://example.test/outsider-avatar.png',
        ]);

        $this->app->instance(GoogleIdTokenVerifier::class, $verifier);

        $response = $this->postJson(route('auth.google.credential'), [
            'credential' => 'token-outsider',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('redirect', route('access.pending', ['reason' => 'not-allowed']));

        $this->assertDatabaseMissing('users', [
            'email' => 'outsider@pa-semarang.go.id',
        ]);
        $this->assertGuest();
    }

    public function test_invalid_google_id_token_returns_json_error(): void
    {
        $verifier = Mockery::mock(GoogleIdTokenVerifier::class);
        $verifier->shouldReceive('verify')->once()->with('token-invalid')->andThrow(new \RuntimeException('Token Google tidak valid.'));

        $this->app->instance(GoogleIdTokenVerifier::class, $verifier);

        $response = $this->postJson(route('auth.google.credential'), [
            'credential' => 'token-invalid',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Otentikasi Google gagal: Token Google tidak valid.');
    }
}
