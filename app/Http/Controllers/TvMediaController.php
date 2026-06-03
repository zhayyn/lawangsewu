<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TvMediaController extends Controller
{
    /**
     * Halaman utama TV Media Slideshow.
     * Public route — tidak memerlukan autentikasi.
     */
    public function index(): Response
    {
        $file = base_path('widgets/views/php/public/tvmedia.php');

        if (! is_file($file)) {
            abort(404, 'File tvmedia tidak ditemukan.');
        }

        if (! defined('LAWANGSEWU_ROOT')) {
            define('LAWANGSEWU_ROOT', base_path());
        }

        $cwd = getcwd();
        if ($cwd !== false) {
            chdir(dirname($file));
        }

        ob_start();

        try {
            require $file;
            $content = ob_get_clean();
        } finally {
            if ($cwd !== false) {
                chdir($cwd);
            }
        }

        return response((string) $content, 200, [
            'Content-Type'            => 'text/html; charset=UTF-8',
            'Cache-Control'           => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Frame-Options'         => 'SAMEORIGIN',
            'X-Content-Type-Options'  => 'nosniff',
        ]);
    }

    /**
     * Endpoint JSON konfigurasi playlist default.
     * GET /tvmedia/config
     */
    public function config(): Response
    {
        $playlist = $this->defaultPlaylist();

        return response()->json([
            'ok'       => true,
            'playlist' => $playlist,
            'version'  => 1,
        ], 200, [
            'Cache-Control'               => 'no-store, no-cache, must-revalidate, max-age=0',
            'Access-Control-Allow-Origin' => "'self'",
        ]);
    }

    /**
     * Playlist slide default untuk TV Media.
     * Bisa dioverride via admin panel (localStorage) di sisi klien.
     */
    private function defaultPlaylist(): array
    {
        return [
            [
                'id'       => 'canva-1',
                'type'     => 'iframe',
                'label'    => 'Presentasi Canva',
                'src'      => 'https://canva.link/8vi85t0lp0pfib4',
                'duration' => 30000,
                'fallback' => 'Canva presentation tidak dapat dimuat. Pastikan link sudah di-set ke mode embed/present.',
            ],
            [
                'id'       => 'statistik-perkara',
                'type'     => 'widget',
                'label'    => 'Statistik Perkara',
                'src'      => '/statistik-perkara',
                'duration' => 25000,
                'fallback' => null,
            ],
            [
                'id'       => 'monitor-sidang',
                'type'     => 'widget',
                'label'    => 'Monitor Antrian Sidang',
                'src'      => '/monitor-antrian-sidang',
                'duration' => 20000,
                'fallback' => null,
            ],
        ];
    }
}
