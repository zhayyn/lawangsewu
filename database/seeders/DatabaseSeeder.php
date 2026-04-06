<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'operator@lawangsewu.test',
        ], [
            'name' => 'Operator PTIP',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => 'operator',
        ]);

        $this->call([
            CctvCameraSeeder::class,
            ChatAliasSeeder::class,
            ChatDemoSeeder::class,
        ]);
    }
}
