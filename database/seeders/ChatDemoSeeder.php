<?php

namespace Database\Seeders;

use App\Models\ChatAlias;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ChatDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operators = collect([
            ['alias' => 'Arjuna_Hukum', 'department' => 'Umum / Keuangan', 'status' => 'online', 'accent_color' => '#238636'],
            ['alias' => 'Bima_Sakti_01', 'department' => 'PTSP', 'status' => 'online', 'accent_color' => '#2f81f7'],
            ['alias' => 'Gatotkaca_IT', 'department' => 'PTIP', 'status' => 'online', 'accent_color' => '#8957e5'],
            ['alias' => 'Nakula_Sidang', 'department' => 'Persidangan', 'status' => 'away', 'accent_color' => '#db6d28'],
            ['alias' => 'Sadewa_Monitor', 'department' => 'CCTV', 'status' => 'busy', 'accent_color' => '#d29922'],
            ['alias' => 'Srikandi_PTSP', 'department' => 'Pelayanan', 'status' => 'online', 'accent_color' => '#238636'],
        ])->mapWithKeys(function (array $operator) {
            ChatAlias::query()->updateOrCreate(
                ['alias' => $operator['alias']],
                $operator,
            );

            $user = User::query()->updateOrCreate(
                ['email' => strtolower($operator['alias']).'@lawangsewu.test'],
                [
                    'name' => str_replace('_', ' ', $operator['alias']),
                    'alias' => $operator['alias'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'role' => 'operator',
                    'email_verified_at' => now(),
                ],
            );

            return [$operator['alias'] => $user];
        });

        collect([
            ['alias' => 'Bima_Sakti_01', 'content' => 'Bu, meja 3 blangko habis. Mohon kirim ulang ke lobby utama.', 'minutes_ago' => 14],
            ['alias' => 'Arjuna_Hukum', 'content' => 'Siap, tim umum langsung bergerak. ETA 3 menit.', 'minutes_ago' => 12],
            ['alias' => 'Gatotkaca_IT', 'content' => 'Stream kamera lobby stabil. Latency rata-rata 1.2 detik.', 'minutes_ago' => 10],
            ['alias' => 'Nakula_Sidang', 'content' => 'Sidang perdata jam 10:00 dipindah ke Ruang Sidang 2.', 'minutes_ago' => 8],
            ['alias' => 'Srikandi_PTSP', 'content' => 'Nomor antrian A-034 telah dipanggil. Lanjut A-035 bila tidak hadir.', 'minutes_ago' => 5],
            ['alias' => 'Sadewa_Monitor', 'content' => 'Empat kamera prioritas aktif. Gerbang depan sedikit silau saat siang.', 'minutes_ago' => 2],
        ])->each(function (array $message) use ($operators): void {
            ChatMessage::query()->updateOrCreate(
                [
                    'user_id' => $operators[$message['alias']]->id,
                    'type' => 'global',
                    'content' => $message['content'],
                ],
                [
                    'created_at' => now()->subMinutes($message['minutes_ago']),
                    'updated_at' => now()->subMinutes($message['minutes_ago']),
                ],
            );
        });
    }
}
