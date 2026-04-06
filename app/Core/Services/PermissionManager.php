<?php

namespace App\Core\Services;

use App\Core\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Cache;

class PermissionManager
{
    private const CACHE_PREFIX = 'permissions.';
    private const CACHE_TTL = 3600;

    public function check(string $role, string $permission): bool
    {
        return $this->forRole($role)
            ->contains(fn (Permission $item) => $item->name === $permission);
    }

    public function forRole(string $role)
    {
        return Cache::remember(
            self::CACHE_PREFIX . $role,
            self::CACHE_TTL,
            fn() => Permission::forRole($role)
        );
    }

    public function byModule(string $module)
    {
        return Permission::byModule($module);
    }

    public function all()
    {
        return Permission::all();
    }

    public function grantToRole(string $role, string $permissionName): void
    {
        $permission = Permission::byName($permissionName);
        if ($permission && !RolePermission::where('role', $role)
            ->where('permission_id', $permission->id)->exists()) {
            RolePermission::create(['role' => $role, 'permission_id' => $permission->id]);
            $this->clearRoleCache($role);
        }
    }

    public function revokeFromRole(string $role, string $permissionName): void
    {
        $permission = Permission::byName($permissionName);
        if ($permission) {
            RolePermission::where('role', $role)
                ->where('permission_id', $permission->id)
                ->delete();
            $this->clearRoleCache($role);
        }
    }

    public function clearRoleCache(string $role): void
    {
        Cache::forget(self::CACHE_PREFIX . $role);
    }

    public function clearAllCaches(): void
    {
        foreach (['viewer', 'operator', 'admin', 'superadmin'] as $role) {
            $this->clearRoleCache($role);
        }
    }

    public function seedDefaultPermissions(): array
    {
        $permissions = [
            ['name' => 'dashboard.view', 'description' => 'View dashboard', 'module' => 'core'],
            ['name' => 'cctv.view', 'description' => 'View CCTV', 'module' => 'core'],
            ['name' => 'cctv.manage', 'description' => 'Manage CCTV', 'module' => 'core'],
            ['name' => 'chat.view', 'description' => 'View chat', 'module' => 'core'],
            ['name' => 'chat.send', 'description' => 'Send chat messages', 'module' => 'core'],
            ['name' => 'chat.manage', 'description' => 'Manage chat messages', 'module' => 'core'],
            ['name' => 'admin.users.view', 'description' => 'View users', 'module' => 'admin'],
            ['name' => 'admin.users.manage', 'description' => 'Manage users', 'module' => 'admin'],
            ['name' => 'admin.roles.view', 'description' => 'View roles', 'module' => 'admin'],
            ['name' => 'admin.roles.manage', 'description' => 'Manage roles', 'module' => 'admin'],
            ['name' => 'admin.reports.view', 'description' => 'View reports', 'module' => 'admin'],
            ['name' => 'antrian.view', 'description' => 'View antrian', 'module' => 'antrian'],
            ['name' => 'antrian.update', 'description' => 'Update antrian', 'module' => 'antrian'],
            ['name' => 'antrian.manage', 'description' => 'Manage antrian', 'module' => 'antrian'],
            ['name' => 'buku_tamu.view', 'description' => 'View buku tamu', 'module' => 'buku_tamu'],
            ['name' => 'buku_tamu.create', 'description' => 'Create buku tamu', 'module' => 'buku_tamu'],
            ['name' => 'buku_tamu.manage', 'description' => 'Manage buku tamu', 'module' => 'buku_tamu'],
        ];

        $created = [];
        foreach ($permissions as $perm) {
            $existing = Permission::where('name', $perm['name'])->first();
            if (!$existing) {
                $p = Permission::create($perm);
                $created[] = $p;
            }
        }

        $rolePermissions = [
            'viewer' => ['dashboard.view', 'cctv.view', 'chat.view'],
            'operator' => [
                'dashboard.view', 'cctv.view', 'cctv.manage', 'chat.view', 'chat.send',
                'antrian.view', 'antrian.update', 'buku_tamu.view'
            ],
            'admin' => [
                'dashboard.view', 'cctv.view', 'cctv.manage', 'chat.view', 'chat.send', 'chat.manage',
                'admin.users.view', 'admin.users.manage', 'admin.roles.view', 'admin.roles.manage',
                'admin.reports.view', 'antrian.view', 'antrian.update', 'antrian.manage',
                'buku_tamu.view', 'buku_tamu.create', 'buku_tamu.manage'
            ],
        ];

        $rolePermCount = 0;
        foreach ($rolePermissions as $role => $perms) {
            foreach ($perms as $permName) {
                $perm = Permission::byName($permName);
                if ($perm && !RolePermission::where('role', $role)
                    ->where('permission_id', $perm->id)->exists()) {
                    RolePermission::create(['role' => $role, 'permission_id' => $perm->id]);
                    $rolePermCount++;
                }
            }
        }

        $this->clearAllCaches();

        return [
            'permissions_created' => count($created),
            'role_permissions_created' => $rolePermCount,
        ];
    }
}
