<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WidgetCompatController extends Controller
{
    public function html(string $page): Response
    {
        $publicMap = [
            'daftar-widget' => public_path('daftar-widget.html'),
            'widget-links' => public_path('widget-links.html'),
        ];

        if (isset($publicMap[$page])) {
            $publicFile = $publicMap[$page];

            if (! is_file($publicFile)) {
                abort(404, 'Direktori widget belum tersedia.');
            }

            return response()->file($publicFile, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }

        $map = [
            'berita-pengadilan' => 'berita-pengadilan.html',
            'berita-pasmg' => 'berita-pasmg.html',
            'panduan-embed-pengumuman' => 'pa-semarang-embed-snippet.html',
            'bridge-server10' => 'server10-data-app.html',
            'biaya-proses-berperkara' => 'biaya-proses-berperkara.html',
            'biaya-perkara' => 'biaya-proses-berperkara.html',
            'monitor-wa' => 'wa-v2-qr-viewer.html',
            'agenda-kegiatan' => 'agenda-kegiatan.html',
        ];

        if (! isset($map[$page])) {
            abort(404);
        }

        $file = base_path('widgets/views/html/public/'.$map[$page]);

        if (! is_file($file)) {
            abort(404, 'Widget HTML tidak ditemukan.');
        }

        return response()->file($file, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function phpPublic(string $page): Response
    {
        $map = [
            'monitor-persidangan' => 'monitor-antrian-sidang.php',
            'monitor-antrian-sidang' => 'monitor-antrian-sidang.php',
            'antrian-persidangan' => 'antrian-sidang.php',
            'antrian-sidang' => 'antrian-sidang.php',
            'dashboard-perkara' => 'statistik-perkara.php',
            'statistik-perkara' => 'statistik-perkara.php',
            'dashboard-ecourt' => 'statistik-ecourt.php',
            'statistik-ecourt' => 'statistik-ecourt.php',
            'dashboard-hakim' => 'statistik-hakim.php',
            'statistik-hakim' => 'statistik-hakim.php',
            'widget-pengumuman' => 'widget-pengumuman-rss.php',
            'pengumuman-rss-widget' => 'widget-pengumuman-rss.php',
            'pengumuman-peradilan' => 'pa-semarang-pengumuman.php',
            'pa-semarang-pengumuman' => 'pa-semarang-pengumuman.php',
            'pengumuman-peradilan-embed' => 'pa-semarang-pengumuman-embed.php',
            'pa-semarang-pengumuman-embed' => 'pa-semarang-pengumuman-embed.php',
            'radius-ghaib' => 'biaya-radius-ghaib.php',
            'biaya-radius-ghaib' => 'biaya-radius-ghaib.php',
            'radius-kecamatan' => 'tabel-radius-kecamatan.php',
            'tabel-radius-kecamatan' => 'tabel-radius-kecamatan.php',
            'info-persidangan' => 'info-persidangan.php',
            'info-persidangan-hijautua' => 'info-persidangan-hijautua.php',
            'info-persidangan-stabilo' => 'info-persidangan-stabilo.php',
        ];

        if (! isset($map[$page])) {
            abort(404);
        }

        return $this->executePhpScript(base_path('widgets/views/php/public/'.$map[$page]));
    }

    public function apiPengumuman(Request $request, ?string $source = null): Response
    {
        if ($source !== null && $source !== '') {
            $_GET['source'] = $source;
            $_REQUEST['source'] = $source;
        }

        return $this->executePhpScript(
            base_path('widgets/views/php/api/api-pengumuman-rss.php'),
            'application/json; charset=utf-8'
        );
    }

    public function apiPengumumanAlias(Request $request, ?string $source = null): Response
    {
        if ($source !== null && $source !== '') {
            $_GET['source'] = $source;
            $_REQUEST['source'] = $source;
        }

        return $this->executePhpScript(
            base_path('widgets/views/php/api/api-pengumuman.php'),
            'application/json; charset=utf-8'
        );
    }

    public function apiStatistik(): Response
    {
        return $this->executePhpScript(
            base_path('widgets/views/php/api/statistik-data.php'),
            'application/json; charset=utf-8'
        );
    }

    public function apiJadwal(): Response
    {
        return $this->executePhpScript(
            base_path('widgets/views/php/api/jadwal-persidangan-api.php'),
            'application/json; charset=utf-8'
        );
    }

    public function apiServer10(Request $request, ?string $mode = null): Response
    {
        if ($mode !== null && $mode !== '') {
            $_GET['mode'] = $mode;
            $_REQUEST['mode'] = $mode;
        }

        return $this->executePhpScript(
            base_path('widgets/views/php/api/api-server10.php'),
            'application/json; charset=utf-8'
        );
    }

    public function apiWaV2(Request $request, ?string $path = null): Response
    {
        if ($path !== null && $path !== '') {
            $_GET['path'] = $path;
            $_REQUEST['path'] = $path;
        }

        return $this->executePhpScript(
            base_path('widgets/views/php/api/api-wa-v2.php'),
            'application/json; charset=utf-8'
        );
    }

    private function executePhpScript(string $file, string $contentType = 'text/html; charset=UTF-8'): Response
    {
        if (! is_file($file)) {
            abort(404, 'File widget tidak ditemukan.');
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
            'Content-Type' => $contentType,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
