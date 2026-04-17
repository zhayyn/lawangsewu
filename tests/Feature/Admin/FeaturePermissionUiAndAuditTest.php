<?php

namespace Tests\Feature\Admin;

use App\Models\FeaturePermission;
use App\Models\PermissionAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeaturePermissionUiAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_page_exposes_permission_data_for_ui_matrix_and_override_panel(): void
    {
        Config::set('auth.super_admin_email', 'founder-ui@example.test');

        $superadmin = User::factory()->create([
            'email' => 'founder-ui@example.test',
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        PermissionAuditLog::query()->create([
            'actor_user_id' => $superadmin->id,
            'target_user_id' => null,
            'scope' => 'role',
            'action' => 'role_permission_update',
            'role' => 'useradmin',
            'feature_key' => 'admin.users',
            'old_enabled' => true,
            'new_enabled' => false,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users')
            ->has('featureCatalog')
            ->has('roleFeaturePermissions')
            ->has('userFeaturePermissions')
            ->has('permissionAuditLogs', 1)
            ->where('permissionAuditLogs.0.action', 'role_permission_update')
            ->where('featureCatalog', fn ($items) => collect($items)->contains(fn (array $feature) => ($feature['key'] ?? null) === 'admin.users'))
            ->where('roleFeaturePermissions', function ($matrix): bool {
                $mapped = collect($matrix);
                $users = collect($mapped->get('admin.users', []));
                $allowlist = collect($mapped->get('admin.users.allowlist', []));

                return $users->get('admin') === true
                    && $users->get('useradmin') === true
                    && $allowlist->get('admin') === true
                    && $allowlist->get('useradmin') === false;
            })
        );
    }

    public function test_permission_toggle_actions_are_logged_in_permission_audit_log(): void
    {
        Config::set('auth.super_admin_email', 'founder-audit@example.test');

        $superadmin = User::factory()->create([
            'email' => 'founder-audit@example.test',
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $target = User::factory()->create([
            'email' => 'viewer-audit@example.test',
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($superadmin)->patch(route('admin.permissions.role.update'), [
            'role' => 'useradmin',
            'feature_key' => 'admin.users',
            'enabled' => false,
        ])->assertRedirect();

        $this->actingAs($superadmin)->patch(route('admin.permissions.user.update'), [
            'user_id' => $target->id,
            'feature_key' => 'admin.cctv',
            'enabled' => true,
        ])->assertRedirect();

        $this->actingAs($superadmin)->delete(route('admin.permissions.user.clear'), [
            'user_id' => $target->id,
            'feature_key' => 'admin.cctv',
        ])->assertRedirect();

        $this->assertDatabaseHas('permission_audit_logs', [
            'actor_user_id' => $superadmin->id,
            'scope' => 'role',
            'action' => 'role_permission_update',
            'role' => 'useradmin',
            'feature_key' => 'admin.users',
            'old_enabled' => true,
            'new_enabled' => false,
        ]);

        $this->assertDatabaseHas('permission_audit_logs', [
            'actor_user_id' => $superadmin->id,
            'target_user_id' => $target->id,
            'scope' => 'user',
            'action' => 'user_permission_override_set',
            'feature_key' => 'admin.cctv',
            'old_enabled' => null,
            'new_enabled' => true,
        ]);

        $this->assertDatabaseHas('permission_audit_logs', [
            'actor_user_id' => $superadmin->id,
            'target_user_id' => $target->id,
            'scope' => 'user',
            'action' => 'user_permission_override_cleared',
            'feature_key' => 'admin.cctv',
            'old_enabled' => true,
            'new_enabled' => null,
        ]);

        $this->assertSame(3, PermissionAuditLog::query()->count());

        $this->assertFalse(FeaturePermission::hasAccess($target->fresh(), 'admin.cctv'));
    }
}
