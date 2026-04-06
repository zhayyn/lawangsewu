<?php

namespace App\Core\Traits;

use App\Core\Models\Permission;

trait HasRolesAndPermissions
{
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getRolePermissions()
            ->contains(fn (Permission $item) => $item->name === $permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getRolePermissions()
            ->whereIn('name', $permissions)
            ->isNotEmpty();
    }

    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $count = $this->getRolePermissions()
            ->whereIn('name', $permissions)
            ->count();

        return $count === count($permissions);
    }

    public function getRolePermissions()
    {
        return Permission::forRole($this->role);
    }

    public function getPermissionsInModule(string $module)
    {
        return $this->getRolePermissions()->where('module', $module)->values();
    }

    public function hasRole(string $role): bool
    {
        if ($role === 'superadmin') {
            return $this->isSuperAdmin();
        }

        return $this->role === $role;
    }

    public function isAtLeast(string $targetRole): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $hierarchy = ['viewer' => 1, 'operator' => 2, 'admin' => 3, 'superadmin' => 4];

        $userLevel = $hierarchy[$this->role] ?? 0;
        $targetLevel = $hierarchy[$targetRole] ?? 0;

        return $userLevel >= $targetLevel;
    }
}
