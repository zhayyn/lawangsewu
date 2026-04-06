<?php

namespace App\Console\Commands;

use App\Core\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Console\Command;

class VerifyRBAC extends Command
{
    protected $signature = 'rbac:verify {--fix : Auto-fix common issues}';
    protected $description = 'Verify RBAC integrity and permissions';

    public function handle()
    {
        $this->info('🔍 Verifying RBAC system...');
        $this->line('');

        $checks = [
            $this->checkPermissionsTable(),
            $this->checkRolePermissionsTable(),
            $this->checkUsersSuperadminColumn(),
            $this->checkDefaultPermissions(),
            $this->checkRolePermissionMappings(),
            $this->checkSuperadminUser(),
        ];

        $passed = count(array_filter($checks, fn($c) => $c['passed']));
        $total = count($checks);

        $this->line('');
        $this->info('═══════════════════════════════════════');
        $this->info("Results: {$passed}/{$total} checks passed");
        $this->info('═══════════════════════════════════════');

        if ($passed === $total) {
            $this->info('✅ All RBAC checks passed!');
            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    private function checkPermissionsTable(): array
    {
        try {
            $count = Permission::count();
            return ['name' => 'Permissions table exists', 'passed' => $count > 0];
        } catch (\Exception $e) {
            return ['name' => 'Permissions table exists', 'passed' => false];
        }
    }

    private function checkRolePermissionsTable(): array
    {
        try {
            $count = RolePermission::count();
            return ['name' => 'Role-permissions table exists', 'passed' => $count > 0];
        } catch (\Exception $e) {
            return ['name' => 'Role-permissions table exists', 'passed' => false];
        }
    }

    private function checkUsersSuperadminColumn(): array
    {
        try {
            $user = User::first();
            $has = isset($user->is_superadmin);
            return ['name' => 'Users table has is_superadmin column', 'passed' => $has];
        } catch (\Exception $e) {
            return ['name' => 'Users table has is_superadmin column', 'passed' => false];
        }
    }

    private function checkDefaultPermissions(): array
    {
        try {
            $count = Permission::count();
            return ['name' => 'Default permissions exist', 'passed' => $count >= 17];
        } catch (\Exception $e) {
            return ['name' => 'Default permissions exist', 'passed' => false];
        }
    }

    private function checkRolePermissionMappings(): array
    {
        try {
            $count = RolePermission::count();
            $viewerPerms = RolePermission::where('role', 'viewer')->count();
            return ['name' => 'Role-permission mappings configured', 'passed' => $count > 0 && $viewerPerms > 0];
        } catch (\Exception $e) {
            return ['name' => 'Role-permission mappings configured', 'passed' => false];
        }
    }

    private function checkSuperadminUser(): array
    {
        try {
            $superAdminEmail = config('auth.super_admin_email');
            if (!$superAdminEmail) {
                return ['name' => 'Superadmin user configured', 'passed' => true];
            }
            $superAdmin = User::where('email', $superAdminEmail)->where('is_superadmin', true)->first();
            return ['name' => 'Superadmin user configured', 'passed' => $superAdmin !== null];
        } catch (\Exception $e) {
            return ['name' => 'Superadmin user configured', 'passed' => false];
        }
    }
}
