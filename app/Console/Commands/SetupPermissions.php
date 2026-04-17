<?php

namespace App\Console\Commands;

use App\Core\Services\PermissionManager;
use App\Models\User;
use Illuminate\Console\Command;

class SetupPermissions extends Command
{
    protected $signature = 'permissions:setup';
    protected $description = 'Setup RBAC permissions and roles';

    public function handle()
    {
        try {
            $this->info('🔄 Setting up RBAC permissions...');

            $manager = new PermissionManager();
            $result = $manager->seedDefaultPermissions();

            $this->line('');
            $this->info('✅ Permissions created: ' . $result['permissions_created']);
            $this->info('✅ Role-permission mappings: ' . $result['role_permissions_created']);

            // Set superadmin based on config
            $superAdminEmail = config('auth.super_admin_email');
            if ($superAdminEmail) {
                $superAdmin = User::where('email', $superAdminEmail)->first();
                if ($superAdmin && !$superAdmin->is_superadmin) {
                    $superAdmin->update(['is_superadmin' => true]);
                    $this->info('✅ Superadmin privilege granted to: ' . $superAdminEmail);
                }
            }

            $this->line('');
            $this->info('🎉 RBAC Setup complete!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
