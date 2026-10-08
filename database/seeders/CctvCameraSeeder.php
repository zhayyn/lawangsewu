<?php

namespace Database\Seeders;

use App\Models\CctvCamera;
use Illuminate\Database\Seeder;

class CctvCameraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $relayBaseUrl = rtrim((string) env('CCTV_RELAY_HLS_BASE_URL', '/cctv'), '/');
        $legacyFallbacks = $this->legacyAcoFallbacks();

        foreach ($this->relayCameras() as $index => $camera) {
            $sortOrder = $index + 1;
            $key = sprintf('cam-%02d', $sortOrder);
            $fallbackSrc = $legacyFallbacks[$key] ?? null;

            CctvCamera::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $camera['name'],
                    'zone' => $camera['zone'],
                    'iframe_src' => $fallbackSrc ?: $this->streamUrl($relayBaseUrl, $camera['sd']),
                    'primary_sd_src' => $this->streamUrl($relayBaseUrl, $camera['sd']),
                    'primary_hd_src' => $this->streamUrl($relayBaseUrl, $camera['hd']),
                    'fallback_src' => $fallbackSrc,
                    'stream_provider' => 'mediamtx-relay',
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'is_featured' => $sortOrder <= 8,
                ],
            );
        }

        CctvCamera::query()
            ->whereNotIn('key', collect(range(1, 24))->map(fn (int $number) => sprintf('cam-%02d', $number))->all())
            ->update(['is_active' => false]);
    }

    private function streamUrl(string $baseUrl, string $path): string
    {
        return "{$baseUrl}/{$path}/index.m3u8";
    }

    private function relayCameras(): array
    {
        return [
            ['name' => 'R. Kepaniteraan', 'zone' => 'Pelayanan', 'sd' => 'kepaniteraan-sd', 'hd' => 'kepaniteraan-hd'],
            ['name' => 'R. PTSP Tampa', 'zone' => 'Pelayanan', 'sd' => 'ptsp-tampa-sd', 'hd' => 'ptsp-tampa-hd'],
            ['name' => 'R. Mediasi', 'zone' => 'Persidangan', 'sd' => 'mediasi-sd', 'hd' => 'mediasi-hd'],
            ['name' => 'Resepsionis', 'zone' => 'Pelayanan', 'sd' => 'resepsionis-sd', 'hd' => 'resepsionis-hd'],
            ['name' => 'R. PTSP', 'zone' => 'Pelayanan', 'sd' => 'ptsp-sd', 'hd' => 'ptsp-hd'],
            ['name' => 'R. Sidang 2', 'zone' => 'Persidangan', 'sd' => 'sidang-2-sd', 'hd' => 'sidang-2-hd'],
            ['name' => 'R. Tunggu Sidang', 'zone' => 'Persidangan', 'sd' => 'tunggu-sidang-sd', 'hd' => 'tunggu-sidang-hd'],
            ['name' => 'Parkir Pegawai', 'zone' => 'Publik', 'sd' => 'parkir-pegawai-sd', 'hd' => 'parkir-pegawai-hd'],
            ['name' => 'R. Tunggu Sidang / Kantin', 'zone' => 'Persidangan', 'sd' => 'tunggu-sidang-2-sd', 'hd' => 'tunggu-sidang-2-hd'],
            ['name' => 'Halaman Apel', 'zone' => 'Publik', 'sd' => 'halaman-apel-sd', 'hd' => 'halaman-apel-hd'],
            ['name' => 'R. Sidang 3', 'zone' => 'Persidangan', 'sd' => 'sidang-3-sd', 'hd' => 'sidang-3-hd'],
            ['name' => 'R. Hakim', 'zone' => 'Internal', 'sd' => 'r-hakim-sd', 'hd' => 'r-hakim-hd'],
            ['name' => 'R. Sidang Utama', 'zone' => 'Persidangan', 'sd' => 'sidang-utama-sd', 'hd' => 'sidang-utama-hd'],
            ['name' => 'Ruang Tamu', 'zone' => 'Pelayanan', 'sd' => 'ruang-tamu-sd', 'hd' => 'ruang-tamu-hd'],
            ['name' => 'Mushola', 'zone' => 'Publik', 'sd' => 'mushola-sd', 'hd' => 'mushola-hd'],
            ['name' => 'R. Kesekretariatan', 'zone' => 'Internal', 'sd' => 'kesekretariatan-sd', 'hd' => 'kesekretariatan-hd'],
            ['name' => 'R. Ketua', 'zone' => 'Pimpinan', 'sd' => 'r-ketua-sd', 'hd' => 'r-ketua-hd'],
            ['name' => 'R. Jurusita', 'zone' => 'Internal', 'sd' => 'r-jurusita-sd', 'hd' => 'r-jurusita-hd'],
            ['name' => 'R. Panitera Pen', 'zone' => 'Internal', 'sd' => 'r-panitera-pen-sd', 'hd' => 'r-panitera-pen-hd'],
            ['name' => 'Media Center', 'zone' => 'Internal', 'sd' => 'media-center-sd', 'hd' => 'media-center-hd'],
            ['name' => 'R. Arsipp', 'zone' => 'Arsip', 'sd' => 'r-arsipp-sd', 'hd' => 'r-arsipp-hd'],
            ['name' => 'R. Sekretaris', 'zone' => 'Pimpinan', 'sd' => 'r-sekretaris-sd', 'hd' => 'r-sekretaris-hd'],
            ['name' => 'R. Panitera', 'zone' => 'Pimpinan', 'sd' => 'r-panitera-sd', 'hd' => 'r-panitera-hd'],
            ['name' => 'R. Wakil Ketua', 'zone' => 'Pimpinan', 'sd' => 'r-wakil-ketua-sd', 'hd' => 'r-wakil-ketua-hd'],
        ];
    }

    private function legacyAcoFallbacks(): array
    {
        return [
            'cam-01' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=791812220302586661119919',
            'cam-02' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=676126671457726160248336',
            'cam-03' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=346757213980282344773097',
            'cam-04' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=468995953693552788174579',
            'cam-05' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=294845469417324798557461',
            'cam-06' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=732569255745925893285146',
            'cam-07' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=435181780055428886288575',
            'cam-08' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=676947402312235056589299',
            'cam-09' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=677295601409126511399000',
            'cam-10' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=312658181499950658971882',
            'cam-11' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=205511738780824667226886',
            'cam-12' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=511288893430786632453336',
            'cam-13' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=335765586220600432427251',
            'cam-14' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=034645762529047600434701',
            'cam-15' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=568066814603989710646569',
            'cam-16' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=821357043236161061507984',
            'cam-17' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=012091613387042156626644',
            'cam-18' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=046012598761606911148970',
            'cam-19' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=2591876294855800362974449',
        ];
    }
}
