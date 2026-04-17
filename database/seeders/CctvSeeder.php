<?php

namespace Database\Seeders;

use App\Models\CctvCamera;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CctvSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cctvs = [
            ['key' => 'cam-01', 'name' => 'Kamera 01', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=791812220302586661119919'],
            // KAMERA 02, 03, 04 DIHAPUS (PRIVACY PIMPINAN)
            ['key' => 'cam-05', 'name' => 'Kamera 05', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=294845469417324798557461'],
            ['key' => 'cam-06', 'name' => 'Kamera 06', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=732569255745925893285146'],
            ['key' => 'cam-07', 'name' => 'Kamera 07', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=435181780055428886288575'],
            ['key' => 'cam-08', 'name' => 'Kamera 08', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=676947402312235056589299'],
            ['key' => 'cam-09', 'name' => 'Kamera 09', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=677295601409126511399000'],
            ['key' => 'cam-10', 'name' => 'Kamera 10', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=312658181499950658971882'],
            ['key' => 'cam-11', 'name' => 'Kamera 11', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=205511738780824667226886'],
            ['key' => 'cam-12', 'name' => 'Kamera 12', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=511288893430786632453336'],
            ['key' => 'cam-13', 'name' => 'Kamera 13', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=335765586220600432427251'],
            ['key' => 'cam-14', 'name' => 'Kamera 14', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=034645762529047600434701'],
            ['key' => 'cam-15', 'name' => 'Kamera 15', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=568066814603989710646569'],
            ['key' => 'cam-16', 'name' => 'Kamera 16', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=821357043236161061507984'],
            ['key' => 'cam-17', 'name' => 'Kamera 17', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=012091613387042156626644'],
            ['key' => 'cam-18', 'name' => 'Kamera 18', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=046012598761606911148970'],
            ['key' => 'cam-19', 'name' => 'Kamera 19', 'iframe_src' => 'https://pasemarang.cctvbadilag.my.id/400911PASEMARANG/play.html?name=259187629485580036297444']
        ];

        // Hapus data yang ada dulu agar kamera yang sengaja dibuang benar-benar hilang
        DB::table('cctv_cameras')->truncate();

        foreach ($cctvs as $cctv) {
            DB::table('cctv_cameras')->insert([
                'key' => $cctv['key'],
                'name' => $cctv['name'],
                'iframe_src' => $cctv['iframe_src'],
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }
    }
}
