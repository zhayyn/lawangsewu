<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * Halaman Admin Panel TV Media (Prakom).
     * Diakses melalui /tvmedia/prakom — dilindungi middleware superadmin.
     * Merender tvmedia.php yang sama namun dengan flag admin=1 aktif
     * sehingga panel kelola konten otomatis terbuka tanpa perlu query string manual.
     */
    public function adminPanel(): Response
    {
        $file = base_path('widgets/views/php/public/tvmedia.php');

        if (! is_file($file)) {
            abort(404, 'File tvmedia tidak ditemukan.');
        }

        if (! defined('LAWANGSEWU_ROOT')) {
            define('LAWANGSEWU_ROOT', base_path());
        }

        /* Simulasikan ?admin=1 agar JS di tvmedia.php menampilkan tombol kelola */
        $originalQuery = $_SERVER['QUERY_STRING'] ?? '';
        $_SERVER['QUERY_STRING'] = 'admin=1';
        $_GET['admin'] = '1';

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
            /* Kembalikan query string asli */
            $_SERVER['QUERY_STRING'] = $originalQuery;
            unset($_GET['admin']);
        }

        /* Inject CSRF token meta tag ke <head> agar JS upload bisa autentikasi */
        $csrfToken  = csrf_token();
        $csrfMeta   = '<meta name="csrf-token" content="' . e($csrfToken) . '">';
        $content    = str_replace('<head>', '<head>' . $csrfMeta, (string) $content);

        /* Inject script untuk auto-buka admin panel setelah halaman selesai init */
        $adminAutoOpenScript = '<script>
/* Auto-open admin panel karena diakses via /tvmedia/prakom */
window.__TVMEDIA_ADMIN_MODE__ = true;
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() {
        if (typeof tgAdmin === "function") {
            var panel = document.getElementById("adm");
            if (panel && !panel.classList.contains("open")) {
                tgAdmin();
            }
        }
    }, 650);
});
</script>';

        /* Inject sebelum </body> */
        $content = str_replace('</body>', $adminAutoOpenScript . '</body>', (string) $content);

        return response($content, 200, [
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

    /**
     * Upload gambar/video untuk slide TV Media.
     * POST /tvmedia/prakom/upload
     * Hanya bisa diakses oleh superadmin (middleware di route).
     *
     * Accepted: image/jpeg, image/png, image/gif, image/webp, video/mp4, video/webm
     * Max size: 15 MB (gambar) atau 80 MB (video)
     * Disimpan di: storage/app/public/tvmedia/{slug-unik}.{ext}
     * URL publik: /storage/tvmedia/{filename}
     */
    public function upload(Request $request): JsonResponse
    {
        /* ── Validasi ─────────────────────────────────────────── */
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpeg,png,gif,webp,mp4,webm',
                'max:81920', /* 80 MB total max — PHP akan reject di file itu sendiri jika terlalu besar */
            ],
        ], [
            'file.required' => 'File harus dipilih.',
            'file.mimes'    => 'Format yang didukung: JPG, PNG, GIF, WebP (gambar) atau MP4, WebM (video).',
            'file.max'      => 'Ukuran file maksimal 80 MB.',
        ]);

        $file      = $request->file('file');
        $origName  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext       = strtolower($file->getClientOriginalExtension());
        $slug      = Str::slug($origName ?: 'media') . '-' . now()->format('Ymd-His') . '-' . Str::random(6);
        $filename  = $slug . '.' . $ext;

        /* ── Batas ukuran per tipe ────────────────────────────── */
        $isVideo   = in_array($ext, ['mp4', 'webm'], true);
        $maxBytes  = $isVideo ? (80 * 1024 * 1024) : (15 * 1024 * 1024);

        if ($file->getSize() > $maxBytes) {
            return response()->json([
                'ok'    => false,
                'error' => $isVideo
                    ? 'Video terlalu besar. Maksimal 80 MB.'
                    : 'Gambar terlalu besar. Maksimal 15 MB.',
            ], 422);
        }

        /* ── Simpan ke storage/app/public/tvmedia/ ────────────── */
        $stored = $file->storeAs('tvmedia', $filename, 'public');

        if (! $stored) {
            return response()->json([
                'ok'    => false,
                'error' => 'Gagal menyimpan file. Periksa izin direktori storage.',
            ], 500);
        }

        $publicUrl = '/storage/tvmedia/' . $filename;

        return response()->json([
            'ok'       => true,
            'url'      => $publicUrl,
            'name'     => $file->getClientOriginalName(),
            'filename' => $filename,
            'size'     => $file->getSize(),
            'type'     => $isVideo ? 'video' : 'image',
        ]);
    }

    /**
     * Daftar file yang sudah diupload ke tvmedia storage.
     * GET /tvmedia/prakom/uploads
     */
    public function listUploads(): JsonResponse
    {
        $files = collect(Storage::disk('public')->files('tvmedia'))
            ->map(function (string $path): array {
                $filename = basename($path);
                $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $isVideo  = in_array($ext, ['mp4', 'webm'], true);

                return [
                    'filename'  => $filename,
                    'url'       => '/storage/' . $path,
                    'size'      => Storage::disk('public')->size($path),
                    'type'      => $isVideo ? 'video' : 'image',
                    'modified'  => Storage::disk('public')->lastModified($path),
                ];
            })
            ->sortByDesc('modified')
            ->values()
            ->all();

        return response()->json(['ok' => true, 'files' => $files]);
    }

    /**
     * Hapus file upload dari tvmedia storage.
     * DELETE /tvmedia/prakom/uploads/{filename}
     */
    public function deleteUpload(string $filename): JsonResponse
    {
        /* Cegah path traversal */
        $filename = basename($filename);
        $path     = 'tvmedia/' . $filename;

        if (! Storage::disk('public')->exists($path)) {
            return response()->json(['ok' => false, 'error' => 'File tidak ditemukan.'], 404);
        }

        Storage::disk('public')->delete($path);

        return response()->json(['ok' => true, 'deleted' => $filename]);
    }
}
