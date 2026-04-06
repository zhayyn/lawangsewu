<?php

namespace Database\Seeders;

use App\Models\ChatAlias;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatAliasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aliases = [
            // Admin & Leadership
            ['alias' => 'Arjuna_Hukum', 'department' => 'Hukum', 'status' => 'online', 'accent_color' => '#3b82f6'],
            ['alias' => 'Sri_Kepala', 'department' => 'Pimpinan', 'status' => 'online', 'accent_color' => '#f97316'],
            
            // Kepaniteraan (Secretariat)
            ['alias' => 'Bima_Sakti_01', 'department' => 'Kepaniteraan', 'status' => 'online', 'accent_color' => '#06b6d4'],
            ['alias' => 'Dewi_Ratna_02', 'department' => 'Kepaniteraan', 'status' => 'online', 'accent_color' => '#ec4899'],
            ['alias' => 'Hendra_Admin_03', 'department' => 'Kepaniteraan', 'status' => 'idle', 'accent_color' => '#8b5cf6'],
            
            // Perdata (Civil Cases)
            ['alias' => 'Putri_Perdata_04', 'department' => 'Perdata', 'status' => 'online', 'accent_color' => '#10b981'],
            ['alias' => 'Rudi_Perkara_05', 'department' => 'Perdata', 'status' => 'online', 'accent_color' => '#f59e0b'],
            
            // Pidana (Criminal Cases)
            ['alias' => 'Maya_Pidana_06', 'department' => 'Pidana', 'status' => 'online', 'accent_color' => '#ef4444'],
            ['alias' => 'Andi_Sidang_07', 'department' => 'Pidana', 'status' => 'idle', 'accent_color' => '#6366f1'],
            
            // PTSP (Public Service)
            ['alias' => 'Sinta_PTSP_08', 'department' => 'PTSP', 'status' => 'online', 'accent_color' => '#14b8a6'],
            ['alias' => 'Doni_Loket_09', 'department' => 'PTSP', 'status' => 'online', 'accent_color' => '#d946ef'],
            
            // CCTV & Security
            ['alias' => 'Rio_CCTV_10', 'department' => 'Keamanan', 'status' => 'online', 'accent_color' => '#0ea5e9'],
            ['alias' => 'Gilang_Monitoring_11', 'department' => 'Keamanan', 'status' => 'online', 'accent_color' => '#06b6d4'],
            
            // General Staff
            ['alias' => 'Laila_Umum_12', 'department' => 'Umum', 'status' => 'idle', 'accent_color' => '#84cc16'],
            ['alias' => 'Tono_Tamu_13', 'department' => 'Penerimaan', 'status' => 'offline', 'accent_color' => '#94a3b8'],
        ];

        foreach ($aliases as $alias) {
            ChatAlias::updateOrCreate(
                ['alias' => $alias['alias']],
                $alias
            );
        }
    }
}
