<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * PetugasWaCarakaSeeder
 *
 * Creates dummy operator accounts for Petugas PTSP and Kasir
 * who will use the WA Caraka inbox in their daily activities.
 *
 * All accounts use role: 'operator'.
 * Passwords are set to a safe default — MUST be changed in production!
 */
class PetugasWaCarakaSeeder extends Seeder
{
    public function run(): void
    {
        $petugas = [
            // Petugas PTSP
            [
                'name'     => 'Petugas PTSP 1',
                'alias'    => 'ptsp-1',
                'email'    => 'ptsp1@pa-semarang.go.id',
                'role'     => 'operator',
                'unit'     => 'PTSP',
            ],
            [
                'name'     => 'Petugas PTSP 2',
                'alias'    => 'ptsp-2',
                'email'    => 'ptsp2@pa-semarang.go.id',
                'role'     => 'operator',
                'unit'     => 'PTSP',
            ],
            [
                'name'     => 'Petugas PTSP 3',
                'alias'    => 'ptsp-3',
                'email'    => 'ptsp3@pa-semarang.go.id',
                'role'     => 'operator',
                'unit'     => 'PTSP',
            ],

            // Petugas Kasir
            [
                'name'     => 'Petugas Kasir 1',
                'alias'    => 'kasir-1',
                'email'    => 'kasir1@pa-semarang.go.id',
                'role'     => 'operator',
                'unit'     => 'Kasir',
            ],
            [
                'name'     => 'Petugas Kasir 2',
                'alias'    => 'kasir-2',
                'email'    => 'kasir2@pa-semarang.go.id',
                'role'     => 'operator',
                'unit'     => 'Kasir',
            ],
        ];

        foreach ($petugas as $data) {
            $email = $data['email'];

            if (User::where('email', $email)->exists()) {
                $this->command->line("  → Skip (sudah ada): {$email}");
                continue;
            }

            User::create([
                'name'              => $data['name'],
                'alias'             => $data['alias'],
                'email'             => $email,
                'password'          => Hash::make('Semarang2025!'),
                'role'              => $data['role'],
                'is_active'         => true,
                'is_superadmin'     => false,
                'email_verified_at' => now(),
            ]);

            $this->command->info("  ✓ Dibuat: [{$data['unit']}] {$data['name']} <{$email}>");
        }

        $this->command->comment('  Password default: Semarang2025! — WAJIB diganti di produksi!');
    }
}
