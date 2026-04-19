<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'alias' => 'Operator Satu',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Operator Satu', $user->alias);
        $this->assertSame($user->getOriginal('name'), $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_avatar_can_be_updated(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/profile/update', [
                'alias' => 'Viewer QA',
                'email' => $user->email,
                'avatar_file' => UploadedFile::fake()->image('avatar.png', 300, 300),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Viewer QA', $user->alias);
        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('/storage/profile-avatars/', $user->avatar);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $user->avatar));
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_cannot_delete_their_own_account(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertForbidden();

        $this->assertAuthenticated();
        $this->assertNotNull($user->fresh());
    }

    public function test_delete_account_request_is_always_forbidden_even_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response->assertForbidden();

        $this->assertNotNull($user->fresh());
    }
}
