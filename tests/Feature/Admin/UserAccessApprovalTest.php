<?php

namespace Tests\Feature\Admin;

use App\Models\GoogleAccessAllowlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UserAccessApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_activate_pending_google_user(): void
    {
        $superadmin = User::factory()->create([
            'email' => Config::string('auth.super_admin_email'),
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $pendingUser = User::factory()->create([
            'google_id' => 'google-pending-22',
            'is_active' => false,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->patch(route('admin.users.update', $pendingUser), [
            'role' => 'operator',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    public function test_non_superadmin_cannot_update_user_access(): void
    {
        $regularAdmin = User::factory()->create([
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
            'email' => 'admin-biasa@example.test',
        ]);

        $target = User::factory()->create([
            'google_id' => 'google-target-1',
            'is_active' => false,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($regularAdmin)->patch(route('admin.users.update', $target), [
            'role' => 'operator',
            'is_active' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_superadmin_can_allowlist_non_gmail_google_account(): void
    {
        $superadmin = User::factory()->create([
            'email' => Config::string('auth.super_admin_email'),
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->post(route('admin.users.allowlist.store'), [
            'email' => 'operator@pa-semarang.go.id',
            'note' => 'Operator PTSP',
            'auto_activate' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('google_access_allowlist', [
            'email' => 'operator@pa-semarang.go.id',
            'note' => 'Operator PTSP',
            'auto_activate' => true,
        ]);
        $this->assertSame(1, GoogleAccessAllowlist::query()->count());
    }
}
