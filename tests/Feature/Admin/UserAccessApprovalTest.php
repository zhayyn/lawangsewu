<?php

namespace Tests\Feature\Admin;

use App\Models\FeaturePermission;
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
            'is_superadmin' => true,
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

    public function test_useradmin_can_update_non_admin_user_access(): void
    {
        $userAdmin = User::factory()->create([
            'is_active' => true,
            'role' => 'useradmin',
            'email_verified_at' => now(),
            'email' => 'useradmin@example.test',
        ]);

        $target = User::factory()->create([
            'google_id' => 'google-target-1',
            'is_active' => false,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($userAdmin)->patch(route('admin.users.update', $target), [
            'role' => 'operator',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    public function test_useradmin_cannot_update_admin_or_superadmin_account(): void
    {
        Config::set('auth.super_admin_email', 'founder@example.test');

        $userAdmin = User::factory()->create([
            'is_active' => true,
            'role' => 'useradmin',
            'email_verified_at' => now(),
            'email' => 'useradmin2@example.test',
        ]);

        $adminTarget = User::factory()->create([
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
            'email' => 'admin-target@example.test',
        ]);

        $response = $this->actingAs($userAdmin)->patch(route('admin.users.update', $adminTarget), [
            'role' => 'operator',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $adminTarget->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $superTarget = User::factory()->create([
            'is_active' => true,
            'role' => 'admin',
            'is_superadmin' => true,
            'email_verified_at' => now(),
            'email' => 'founder@example.test',
        ]);

        $response2 = $this->actingAs($userAdmin)->patch(route('admin.users.update', $superTarget), [
            'role' => 'viewer',
            'is_active' => false,
        ]);

        $response2->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $superTarget->id,
            'role' => 'admin',
            'is_active' => true,
            'is_superadmin' => true,
        ]);
    }

    public function test_superadmin_can_allowlist_non_gmail_google_account(): void
    {
        $superadmin = User::factory()->create([
            'email' => Config::string('auth.super_admin_email'),
            'is_superadmin' => true,
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

    public function test_useradmin_blocked_from_allowlist_by_feature_permission(): void
    {
        $userAdmin = User::factory()->create([
            'is_active' => true,
            'role' => 'useradmin',
            'email_verified_at' => now(),
            'email' => 'useradmin-feature@example.test',
        ]);

        $response = $this->actingAs($userAdmin)->post(route('admin.users.allowlist.store'), [
            'email' => 'blocked@example.test',
            'note' => 'Should fail',
            'auto_activate' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_superadmin_can_manage_role_feature_permission(): void
    {
        Config::set('auth.super_admin_email', 'founder-roleperm@example.test');

        $superadmin = User::factory()->create([
            'email' => 'founder-roleperm@example.test',
            'is_superadmin' => true,
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->patch(route('admin.permissions.role.update'), [
            'role' => 'useradmin',
            'feature_key' => 'admin.users',
            'enabled' => false,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('feature_permissions', [
            'role_id' => FeaturePermission::ROLE_MAP['useradmin'],
            'user_id' => null,
            'feature_key' => 'admin.users',
            'enabled' => false,
        ]);

        $userAdmin = User::factory()->create([
            'is_active' => true,
            'role' => 'useradmin',
            'email_verified_at' => now(),
            'email' => 'useradmin-after-toggle@example.test',
        ]);

        $this->assertFalse(FeaturePermission::hasAccess($userAdmin, 'admin.users'));
    }

    public function test_superadmin_can_set_and_clear_user_feature_override(): void
    {
        Config::set('auth.super_admin_email', 'founder-userperm@example.test');

        $superadmin = User::factory()->create([
            'email' => 'founder-userperm@example.test',
            'is_superadmin' => true,
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $target = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $setResponse = $this->actingAs($superadmin)->patch(route('admin.permissions.user.update'), [
            'user_id' => $target->id,
            'feature_key' => 'nav.chat',
            'enabled' => true,
        ]);

        $setResponse->assertRedirect();

        $this->assertDatabaseHas('feature_permissions', [
            'user_id' => $target->id,
            'role_id' => null,
            'feature_key' => 'nav.chat',
            'enabled' => true,
        ]);

        $this->assertTrue(FeaturePermission::hasAccess($target->fresh(), 'nav.chat'));

        $clearResponse = $this->actingAs($superadmin)->delete(route('admin.permissions.user.clear'), [
            'user_id' => $target->id,
            'feature_key' => 'nav.chat',
        ]);

        $clearResponse->assertRedirect();

        $this->assertDatabaseMissing('feature_permissions', [
            'user_id' => $target->id,
            'feature_key' => 'nav.chat',
        ]);

        $this->assertTrue(FeaturePermission::hasAccess($target->fresh(), 'nav.chat'));
    }

    public function test_superadmin_can_open_user_access_page_and_manage_allowlist_and_permissions(): void
    {
        Config::set('auth.super_admin_email', 'founder-full-access@example.test');

        $superadmin = User::factory()->create([
            'email' => 'founder-full-access@example.test',
            'is_superadmin' => true,
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $target = User::factory()->create([
            'email' => 'target-full-access@example.test',
            'role' => 'viewer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->actingAs($superadmin)
            ->post(route('admin.users.allowlist.store'), [
                'email' => 'allowed-full-access@example.test',
                'note' => 'Full access audit',
                'auto_activate' => true,
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->patch(route('admin.permissions.role.update'), [
                'role' => 'operator',
                'feature_key' => 'admin.users',
                'enabled' => true,
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->patch(route('admin.permissions.user.update'), [
                'user_id' => $target->id,
                'feature_key' => 'admin.cctv',
                'enabled' => true,
            ])
            ->assertRedirect();
    }

    public function test_configured_superadmin_email_without_flag_can_still_manage_user_access_end_to_end(): void
    {
        Config::set('auth.super_admin_email', 'founder-email-only@example.test');

        $superadmin = User::factory()->create([
            'email' => 'founder-email-only@example.test',
            'is_superadmin' => false,
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $target = User::factory()->create([
            'email' => 'target-email-only@example.test',
            'role' => 'viewer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->assertTrue($superadmin->fresh()->isSuperAdmin());

        $this->actingAs($superadmin)
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->actingAs($superadmin)
            ->post(route('admin.users.allowlist.store'), [
                'email' => 'allowed-email-only@example.test',
                'note' => 'Email fallback audit',
                'auto_activate' => true,
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->patch(route('admin.permissions.role.update'), [
                'role' => 'operator',
                'feature_key' => 'admin.users',
                'enabled' => false,
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->patch(route('admin.permissions.user.update'), [
                'user_id' => $target->id,
                'feature_key' => 'admin.cctv',
                'enabled' => true,
            ])
            ->assertRedirect();
    }
}
