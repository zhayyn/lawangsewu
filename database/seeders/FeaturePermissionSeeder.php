<?php

namespace Database\Seeders;

use App\Models\FeaturePermission;
use Illuminate\Database\Seeder;

class FeaturePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = config('features.features', []);
        
        // Map role names to role IDs
        $roleMap = [
            'admin' => 1,
            'useradmin' => 2,
            'operator' => 3,
            'viewer' => 4,
        ];

        // Clear existing role-level permissions (but keep user overrides)
        FeaturePermission::whereNotNull('role_id')->delete();

        // Seed default role permissions from config
        foreach ($features as $feature) {
            $defaultRoles = $feature['default_roles'] ?? [];
            
            foreach ($roleMap as $roleName => $roleId) {
                $isEnabled = in_array($roleName, $defaultRoles);
                
                FeaturePermission::create([
                    'role_id' => $roleId,
                    'user_id' => null,
                    'feature_key' => $feature['key'],
                    'enabled' => $isEnabled,
                ]);
            }
        }

        $this->command->info('Feature permissions seeded successfully.');
    }
}
