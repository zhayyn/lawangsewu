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
        collect([
            ['key' => 'cam-01', 'name' => 'PTSP Lobby', 'zone' => 'Pelayanan', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=791812220302586661119919', 'sort_order' => 1, 'is_featured' => true],
            ['key' => 'cam-02', 'name' => 'Ruang Tunggu PTSP', 'zone' => 'Pelayanan', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=676126671457726160248336', 'sort_order' => 2, 'is_featured' => true],
            ['key' => 'cam-03', 'name' => 'Ruang Sidang 1', 'zone' => 'Persidangan', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=346757213980282344773097', 'sort_order' => 3, 'is_featured' => true],
            ['key' => 'cam-04', 'name' => 'Gerbang Depan', 'zone' => 'Publik', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=468995953693552788174579', 'sort_order' => 4, 'is_featured' => true],
            ['key' => 'cam-05', 'name' => 'Koridor Barat', 'zone' => 'Pelayanan', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=294845469417324798557461', 'sort_order' => 5, 'is_featured' => false],
            ['key' => 'cam-06', 'name' => 'Koridor Timur', 'zone' => 'Pelayanan', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=732569255745925893285146', 'sort_order' => 6, 'is_featured' => false],
            ['key' => 'cam-07', 'name' => 'Pos Satpam', 'zone' => 'Publik', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=435181780055428886288575', 'sort_order' => 7, 'is_featured' => false],
            ['key' => 'cam-08', 'name' => 'Area Parkir', 'zone' => 'Publik', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=676947402312235056589299', 'sort_order' => 8, 'is_featured' => false],
            ['key' => 'cam-09', 'name' => 'Ruang Arsip', 'zone' => 'Internal', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=677295601409126511399000', 'sort_order' => 9, 'is_featured' => false],
            ['key' => 'cam-10', 'name' => 'Posbakum', 'zone' => 'Pelayanan', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=312658181499950658971882', 'sort_order' => 10, 'is_featured' => false],
            ['key' => 'cam-11', 'name' => 'Ruang Mediasi', 'zone' => 'Persidangan', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=205511738780824667226886', 'sort_order' => 11, 'is_featured' => false],
            ['key' => 'cam-12', 'name' => 'Selasar Utama', 'zone' => 'Publik', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=511288893430786632453336', 'sort_order' => 12, 'is_featured' => false],
            ['key' => 'cam-13', 'name' => 'Aula Serbaguna', 'zone' => 'Internal', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=335765586220600432427251', 'sort_order' => 13, 'is_featured' => false],
            ['key' => 'cam-14', 'name' => 'Halaman Belakang', 'zone' => 'Publik', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=034645762529047600434701', 'sort_order' => 14, 'is_featured' => false],
            ['key' => 'cam-15', 'name' => 'Pintu Samping', 'zone' => 'Publik', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=568066814603989710646569', 'sort_order' => 15, 'is_featured' => false],
            ['key' => 'cam-16', 'name' => 'Tangga Lantai 2', 'zone' => 'Internal', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=821357043236161061507984', 'sort_order' => 16, 'is_featured' => false],
            ['key' => 'cam-17', 'name' => 'Ruang Server PTIP', 'zone' => 'PTIP', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=012091613387042156626644', 'sort_order' => 17, 'is_featured' => false],
            ['key' => 'cam-18', 'name' => 'Ruang Hakim', 'zone' => 'Persidangan', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=046012598761606911148970', 'sort_order' => 18, 'is_featured' => false],
            ['key' => 'cam-19', 'name' => 'Area Mushola', 'zone' => 'Internal', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=2591876294855800362974449', 'sort_order' => 19, 'is_featured' => false],
        ])->each(function (array $camera): void {
            CctvCamera::query()->updateOrCreate(
                ['key' => $camera['key']],
                $camera + ['is_active' => true],
            );
        });
    }
}
