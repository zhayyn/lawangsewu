<?php

use App\Http\Controllers\Admin\CctvCameraController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\NetworkMonitorController;
use App\Http\Controllers\Admin\PendopoAdminController;
use App\Http\Controllers\Admin\SystemMonitorController;
use App\Http\Controllers\Admin\UserAccessController;
use App\Http\Controllers\Admin\WaCarakaAdminController;
use App\Http\Controllers\GuestbookController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PtspQueueController;
use App\Http\Controllers\PelayananPtspController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\SidangQueueController;
use App\Http\Controllers\SippHubController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TailscaleDashboardController;
use App\Http\Controllers\WidgetCompatController;
use App\Http\Controllers\TdmsController;
use App\Http\Controllers\PakPpController;
use App\Http\Controllers\TvMediaController;
use App\Http\Controllers\WaCarakaController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('api')->middleware('throttle:60,1')->group(function () {
    Route::get('/pengumuman-rss', [WidgetCompatController::class, 'apiPengumuman']);
    Route::get('/pengumuman-rss/{source}', [WidgetCompatController::class, 'apiPengumuman']);
    Route::get('/pengumuman', [WidgetCompatController::class, 'apiPengumumanAlias']);
    Route::get('/pengumuman/{source}', [WidgetCompatController::class, 'apiPengumumanAlias']);
    Route::match(['get', 'post'], '/statistik-data', [WidgetCompatController::class, 'apiStatistik']);
    Route::match(['get', 'post'], '/jadwal-persidangan', [WidgetCompatController::class, 'apiJadwal']);
    Route::match(['get', 'post'], '/server10', [WidgetCompatController::class, 'apiServer10']);
    Route::match(['get', 'post'], '/server10/{mode}', [WidgetCompatController::class, 'apiServer10'])
        ->whereIn('mode', ['health', 'capabilities']);
    Route::match(['get', 'post'], '/wa-v2', [WidgetCompatController::class, 'apiWaV2']);
    Route::match(['get', 'post'], '/wa-v2/{path}', [WidgetCompatController::class, 'apiWaV2'])
        ->where('path', '.*');
});

// System health check — unauthenticated, untuk uptime monitoring
Route::get('/health', [HealthController::class, 'index'])->name('health');

Route::get('/og-image/case-statistics.png', [\App\Http\Controllers\OgImageController::class, 'caseStatisticsPreview'])->name('og.case-statistics');

Route::get('/daftar-widget', [WidgetCompatController::class, 'html'])->defaults('page', 'daftar-widget');
Route::get('/widget-links', [WidgetCompatController::class, 'html'])->defaults('page', 'widget-links');

Route::get('/berita-pengadilan', [WidgetCompatController::class, 'html'])->defaults('page', 'berita-pengadilan');
Route::get('/berita-pasmg', [WidgetCompatController::class, 'html'])->defaults('page', 'berita-pasmg');
Route::get('/panduan-embed-pengumuman', [WidgetCompatController::class, 'html'])->defaults('page', 'panduan-embed-pengumuman');
Route::get('/bridge-server10', [WidgetCompatController::class, 'html'])->defaults('page', 'bridge-server10');
Route::get('/biaya-proses-berperkara', [WidgetCompatController::class, 'html'])->defaults('page', 'biaya-proses-berperkara');
Route::get('/biaya-perkara', [WidgetCompatController::class, 'html'])->defaults('page', 'biaya-perkara');
Route::get('/monitor-wa', [WidgetCompatController::class, 'html'])->defaults('page', 'monitor-wa');
Route::get('/statistik-embed', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'statistik-embed');

Route::get('/monitor-persidangan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'monitor-persidangan');
Route::get('/monitor-antrian-sidang', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'monitor-antrian-sidang');
Route::get('/antrian-persidangan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'antrian-persidangan');
Route::get('/antrian-sidang', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'antrian-sidang');
Route::get('/daftar-pegawai', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'daftar-pegawai');
Route::get('/dashboard-perkara', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'dashboard-perkara');
Route::get('/statistik-perkara', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'statistik-perkara');
Route::get('/dashboard-ecourt', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'dashboard-ecourt');
Route::get('/statistik-ecourt', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'statistik-ecourt');
Route::get('/dashboard-hakim', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'dashboard-hakim');
Route::get('/statistik-hakim', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'statistik-hakim');
Route::get('/widget-pengumuman', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'widget-pengumuman');
Route::get('/pengumuman-rss-widget', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pengumuman-rss-widget');
Route::get('/pengumuman-peradilan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pengumuman-peradilan');
Route::get('/pa-semarang-pengumuman', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pa-semarang-pengumuman');
Route::get('/pengumuman-peradilan-embed', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pengumuman-peradilan-embed');
Route::get('/pa-semarang-pengumuman-embed', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pa-semarang-pengumuman-embed');
Route::get('/radius-ghaib', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'radius-ghaib');
Route::get('/biaya-radius-ghaib', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'biaya-radius-ghaib');
Route::get('/radius-kecamatan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'radius-kecamatan');
Route::get('/tabel-radius-kecamatan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'tabel-radius-kecamatan');
Route::get('/info-persidangan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'info-persidangan');
Route::get('/info-persidangan-hijautua', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'info-persidangan-hijautua');
Route::get('/info-persidangan-stabilo', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'info-persidangan-stabilo');
Route::get('/daftar-pip', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'daftar-pip');
Route::get('/daftar-relaas-ghaib', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'daftar-relaas-ghaib');

// Backward-compatible root endpoint used by some legacy embeds.
Route::match(['get', 'post'], '/statistik-data', [WidgetCompatController::class, 'apiStatistik'])
    ->middleware('throttle:60,1');

// ── TV Media Slideshow — public, tanpa autentikasi ─────────────────────────
Route::get('/tvmedia', [TvMediaController::class, 'index'])->name('lawangsewu.tvmedia');
Route::get('/tvmedia/config', [TvMediaController::class, 'config'])->name('lawangsewu.tvmedia.config');

// ── TV Media Admin Panel — dilindungi autentikasi superadmin ─────────────────
Route::get('/tvmedia/prakom', [TvMediaController::class, 'adminPanel'])
    ->middleware(['auth', 'verified', 'active', 'superadmin'])
    ->name('lawangsewu.tvmedia.prakom');

// ── TV Media Upload & Media Library — superadmin only ────────────────────────
Route::middleware(['auth', 'verified', 'active', 'superadmin'])->group(function () {
    Route::post('/tvmedia/prakom/upload', [TvMediaController::class, 'upload'])
        ->name('lawangsewu.tvmedia.upload');
    Route::get('/tvmedia/prakom/uploads', [TvMediaController::class, 'listUploads'])
        ->name('lawangsewu.tvmedia.uploads');
    Route::delete('/tvmedia/prakom/uploads/{filename}', [TvMediaController::class, 'deleteUpload'])
        ->where('filename', '[^/]+')
        ->name('lawangsewu.tvmedia.upload.delete');
});

Route::prefix('lawangsewu')->group(function () {
    Route::get('/pengumuman-peradilan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pengumuman-peradilan');
    Route::get('/pengumuman-peradilan-embed', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pengumuman-peradilan-embed');
    Route::get('/pengumuman-rss-widget', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pengumuman-rss-widget');
    Route::get('/dashboard-perkara', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'dashboard-perkara');
    Route::get('/monitor-persidangan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'monitor-persidangan');
    // Backward-compatible direct endpoint used by legacy/public widgets.
    Route::match(['get', 'post'], '/statistik-data', [WidgetCompatController::class, 'apiStatistik'])
        ->middleware('throttle:60,1');

    Route::prefix('api')->middleware('throttle:60,1')->group(function () {
        Route::get('/pengumuman-rss', [WidgetCompatController::class, 'apiPengumuman']);
        Route::get('/pengumuman-rss/{source}', [WidgetCompatController::class, 'apiPengumuman']);
        Route::get('/pengumuman', [WidgetCompatController::class, 'apiPengumumanAlias']);
        Route::get('/pengumuman/{source}', [WidgetCompatController::class, 'apiPengumumanAlias']);
        Route::match(['get', 'post'], '/statistik-data', [WidgetCompatController::class, 'apiStatistik']);
        Route::match(['get', 'post'], '/server10', [WidgetCompatController::class, 'apiServer10']);
        Route::match(['get', 'post'], '/server10/{mode}', [WidgetCompatController::class, 'apiServer10'])
            ->whereIn('mode', ['health', 'capabilities']);
        Route::match(['get', 'post'], '/wa-v2', [WidgetCompatController::class, 'apiWaV2']);
        Route::match(['get', 'post'], '/wa-v2/{path}', [WidgetCompatController::class, 'apiWaV2'])
            ->where('path', '.*');
    });
});

Route::get('/access/pending', function (\Illuminate\Http\Request $request) {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Auth/PendingAccess', [
        'reason' => $request->query('reason', 'pending'),
    ]);
})->name('access.pending');

Route::middleware(['auth', 'verified', 'active', 'role:viewer,operator,useradmin,admin'])->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('lawangsewu.dashboard');
    Route::get('/cctv', [PortalController::class, 'cctv'])->name('lawangsewu.cctv');
    Route::get('/chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('lawangsewu.chat');
    Route::get('/chat/messages', [\App\Http\Controllers\ChatController::class, 'messages'])->name('lawangsewu.chat.messages');
    Route::post('/chat', [\App\Http\Controllers\ChatController::class, 'store'])->name('lawangsewu.chat.store');
    Route::delete('/chat/{message}', [\App\Http\Controllers\ChatController::class, 'destroy'])->name('lawangsewu.chat.destroy');
    Route::post('/chat/{message}/delete', [\App\Http\Controllers\ChatController::class, 'destroy'])->name('lawangsewu.chat.destroy.post');
    Route::post('/chat/clear', [\App\Http\Controllers\ChatController::class, 'destroyAll'])->name('lawangsewu.chat.clear');
    Route::get('/chat/media/{message}', [\App\Http\Controllers\ChatController::class, 'media'])->name('lawangsewu.chat.media');

    // Backward-compatibility redirect: Pendopo is consolidated into Buku Tamu.
    Route::get('/satellite/pendopo', function () {
        return redirect()->route('lawangsewu.guestbook.form', ['embedded' => 1]);
    })->name('lawangsewu.satellite.pendopo');

    Route::get('/satellite/pendopo/metric', function () {
        return response()->json([
            'status' => 'deprecated',
            'message' => 'Pendopo module has been consolidated into Buku Tamu.',
        ], 410);
    })->name('lawangsewu.satellite.pendopo.metric');
});

Route::middleware(['auth', 'verified', 'active', 'role:viewer,operator,useradmin,admin'])->group(function () {
    Route::get('/buku-tamu', [GuestbookController::class, 'form'])->name('lawangsewu.guestbook.form');
    Route::post('/buku-tamu', [GuestbookController::class, 'store'])->name('lawangsewu.guestbook.store');
    Route::get('/buku-tamu/daftar/{period?}', [GuestbookController::class, 'listing'])->name('lawangsewu.guestbook.list');
    Route::get('/antrian-ptsp', [PtspQueueController::class, 'index'])->name('lawangsewu.ptsp.index');

    // ── Pelayanan PTSP (modul mandiri, terhubung SIPP) ────────────────────
    Route::get('/pelayanan-ptsp', [PelayananPtspController::class, 'index'])->name('lawangsewu.pelayananptsp.index');
    Route::get('/pelayanan-ptsp/penyerahan-ac', [PelayananPtspController::class, 'indexPenyerahan'])->name('lawangsewu.pelayananptsp.penyerahan');
    Route::get('/pelayanan-ptsp/laporan', [PelayananPtspController::class, 'laporan'])->name('lawangsewu.pelayananptsp.laporan');
    Route::get('/pelayanan-ptsp/api/sipp-lookup', [PelayananPtspController::class, 'sippLookup'])->name('lawangsewu.pelayananptsp.sipp-lookup')->middleware('throttle:30,1');
    Route::get('/antrian-sidang-v2', [SidangQueueController::class, 'index'])->name('lawangsewu.sidang.index');
    Route::get('/pilar-smg', [PortalController::class, 'pilar'])->name('lawangsewu.pilar.index');
    Route::get('/sipp-hub', [SippHubController::class, 'index'])->name('lawangsewu.sipp.index');
});

Route::middleware(['auth', 'verified', 'active', 'role:operator,admin'])->group(function () {
    // ── PAK PP — Personal Asisten Khusus Panitera Pengganti ──────────
    Route::get('/pak-pp', [PakPpController::class, 'index'])->name('lawangsewu.pakpp.index');
    Route::post('/pak-pp/api/generate', [PakPpController::class, 'generate'])->name('lawangsewu.pakpp.generate')->middleware('throttle:20,1');
    Route::post('/pak-pp/api/review', [PakPpController::class, 'review'])->name('lawangsewu.pakpp.review')->middleware('throttle:20,1');
    Route::post('/pak-pp/api/export-docx', [PakPpController::class, 'exportDocx'])->name('lawangsewu.pakpp.export-docx');
    Route::post('/pak-pp/api/export-pdf', [PakPpController::class, 'exportPdf'])->name('lawangsewu.pakpp.export-pdf');
    Route::get('/buku-tamu/detail/{id}', [GuestbookController::class, 'detail'])->name('lawangsewu.guestbook.detail');
    Route::get('/buku-tamu/cetak/{id}', [GuestbookController::class, 'printCard'])->name('lawangsewu.guestbook.cetak');
    Route::match(['get', 'post'], '/buku-tamu/laporan', [GuestbookController::class, 'report'])->name('lawangsewu.guestbook.report');
    
    // Guestbook Management (Kelola Pendopo)
    Route::get('/buku-tamu/kelola', [GuestbookController::class, 'manage'])->name('lawangsewu.guestbook.manage');
    Route::delete('/buku-tamu/kelola/{id}', [GuestbookController::class, 'destroy'])->name('lawangsewu.guestbook.destroy');
    Route::post('/buku-tamu/kelola/bulk-delete', [GuestbookController::class, 'bulkDestroy'])->name('lawangsewu.guestbook.bulk-delete');
    Route::post('/buku-tamu/kelola/settings', [GuestbookController::class, 'saveSettings'])->name('lawangsewu.guestbook.settings');
    Route::patch('/buku-tamu/kelola/{id}/rename', [GuestbookController::class, 'rename'])->name('lawangsewu.guestbook.rename');
    Route::patch('/buku-tamu/kelola/{id}/update-info', [GuestbookController::class, 'updateInfo'])->name('lawangsewu.guestbook.update-info');

    // WA Caraka Dashboard & Operator Tools
    Route::get('/wa-caraka', [WaCarakaController::class, 'index'])->name('lawangsewu.wacaraka.index')->middleware('feature:nav.wacaraka');
    Route::match(['get', 'post'], '/wa-caraka/api/{action}', [WaCarakaController::class, 'proxy'])
        ->where('action', '.*')
        ->name('lawangsewu.wacaraka.api')
        ->middleware('feature:nav.wacaraka');
    
    // WA Caraka Media Download (proxied from bridge)
    // Supports: /wa-caraka/media/{token}, /wa-caraka/media/{token}.ext, /wa-caraka/media/{token}/{filename}
    Route::get('/wa-caraka/media/{path}', [WaCarakaController::class, 'downloadMedia'])
        ->where('path', '.+')
        ->name('lawangsewu.wacaraka.media')
        ->middleware('feature:nav.wacaraka');

    // ── TDMS — Tech Device Management System ──────────────────────
    Route::get('/tdms', [TdmsController::class, 'index'])->name('lawangsewu.tdms.index');
    Route::get('/tdms/assets', [TdmsController::class, 'assets'])->name('lawangsewu.tdms.assets');
    Route::get('/tdms/service-records', [TdmsController::class, 'serviceRecords'])->name('lawangsewu.tdms.service-records');
    Route::get('/tdms/maintenance', [TdmsController::class, 'maintenance'])->name('lawangsewu.tdms.maintenance');
    Route::match(['get', 'post'], '/tdms/api/{action}', [TdmsController::class, 'api'])
        ->where('action', '.*')
        ->name('lawangsewu.tdms.api');
    // QR Code scan — accessible by all auth users
    Route::get('/tdms/qr/{token}', [TdmsController::class, 'assetQrDetail'])->name('lawangsewu.tdms.qr');
});

Route::middleware(['auth', 'verified', 'active', 'role:operator,useradmin,admin', 'feature:nav.wacaraka'])->group(function () {
    Route::get('/wa-caraka/reports', [WaCarakaController::class, 'reports'])->name('lawangsewu.wacaraka.reports');
    Route::get('/wa-caraka/reports/data', [WaCarakaController::class, 'reportsData'])->name('lawangsewu.wacaraka.reports.data');
    Route::get('/wa-caraka/reports/pdf', [WaCarakaController::class, 'reportsPdf'])->name('lawangsewu.wacaraka.reports.pdf');
});

Route::middleware(['auth', 'verified', 'active', 'role:operator,admin'])->group(function () {
    Route::post('/antrian-ptsp', [PtspQueueController::class, 'store'])->name('lawangsewu.ptsp.store');
    Route::post('/antrian-ptsp/{ticket}/call', [PtspQueueController::class, 'call'])->name('lawangsewu.ptsp.call');
    Route::post('/antrian-ptsp/{ticket}/serve', [PtspQueueController::class, 'serve'])->name('lawangsewu.ptsp.serve');
    Route::post('/antrian-ptsp/{ticket}/skip', [PtspQueueController::class, 'skip'])->name('lawangsewu.ptsp.skip');

    // Pelayanan PTSP — operator actions
    Route::post('/pelayanan-ptsp/antrian', [PelayananPtspController::class, 'storeAntrian'])->name('lawangsewu.pelayananptsp.antrian.store');
    Route::post('/pelayanan-ptsp/antrian/{antrian}/call', [PelayananPtspController::class, 'callAntrian'])->name('lawangsewu.pelayananptsp.antrian.call');
    Route::post('/pelayanan-ptsp/antrian/{antrian}/serve', [PelayananPtspController::class, 'serveAntrian'])->name('lawangsewu.pelayananptsp.antrian.serve');
    Route::post('/pelayanan-ptsp/antrian/{antrian}/skip', [PelayananPtspController::class, 'skipAntrian'])->name('lawangsewu.pelayananptsp.antrian.skip');
    Route::post('/pelayanan-ptsp/penyerahan-ac', [PelayananPtspController::class, 'storePenyerahan'])->name('lawangsewu.pelayananptsp.penyerahan.store');

    Route::post('/antrian-sidang-v2', [SidangQueueController::class, 'store'])->name('lawangsewu.sidang.store');
    Route::post('/antrian-sidang-v2/{ticket}/call', [SidangQueueController::class, 'call'])->name('lawangsewu.sidang.call');
    Route::post('/antrian-sidang-v2/{ticket}/complete', [SidangQueueController::class, 'complete'])->name('lawangsewu.sidang.complete');
    Route::post('/antrian-sidang-v2/{ticket}/postpone', [SidangQueueController::class, 'postpone'])->name('lawangsewu.sidang.postpone');

    Route::post('/sipp-hub/refresh', [SippHubController::class, 'refreshCache'])->name('lawangsewu.sipp.refresh');
});

Route::middleware(['auth', 'active', 'role:viewer,operator,useradmin,admin'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.save');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('permission:admin.users')->group(function () {
        Route::get('/users', [UserAccessController::class, 'index'])->name('users.index');
        Route::post('/users', [UserAccessController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserAccessController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserAccessController::class, 'destroy'])->name('users.destroy');
    });

    // AI Knowledge Base
    Route::get('/ai-knowledge', [\App\Http\Controllers\Admin\AiKnowledgeBaseController::class, 'index'])->name('ai-knowledge.index');
    Route::post('/ai-knowledge', [\App\Http\Controllers\Admin\AiKnowledgeBaseController::class, 'store'])->name('ai-knowledge.store');
    Route::patch('/ai-knowledge/{knowledge}', [\App\Http\Controllers\Admin\AiKnowledgeBaseController::class, 'update'])->name('ai-knowledge.update');
    Route::delete('/ai-knowledge/{knowledge}', [\App\Http\Controllers\Admin\AiKnowledgeBaseController::class, 'destroy'])->name('ai-knowledge.destroy');
});

Route::middleware(['auth', 'verified', 'active', 'superadmin'])->group(function () {
    Route::get('/tailscale', [TailscaleDashboardController::class, 'index'])->name('lawangsewu.tailscale.index');
    Route::get('/tailscale/network-status', [TailscaleDashboardController::class, 'networkStatus'])->name('lawangsewu.tailscale.network-status');
    Route::get('/tailscale/device/{deviceKey}', [TailscaleDashboardController::class, 'deviceDetail'])->name('lawangsewu.tailscale.device');
    Route::get('/tailscale/ping-all', [TailscaleDashboardController::class, 'pingAll'])->name('lawangsewu.tailscale.ping-all');
    Route::get('/tailscale/cli-info', [TailscaleDashboardController::class, 'tailscaleCliInfo'])->name('lawangsewu.tailscale.cli-info');
});

Route::middleware(['auth', 'verified', 'active', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/users/allowlist', [UserAccessController::class, 'storeAllowlist'])->name('users.allowlist.store');
    Route::patch('/users/allowlist/{entry}', [UserAccessController::class, 'updateAllowlist'])->name('users.allowlist.update');
    Route::delete('/users/allowlist/{entry}', [UserAccessController::class, 'destroyAllowlist'])->name('users.allowlist.destroy');

    Route::patch('/permissions/role', [UserAccessController::class, 'updateRoleFeaturePermission'])->name('permissions.role.update');
    Route::patch('/permissions/user', [UserAccessController::class, 'updateUserFeaturePermission'])->name('permissions.user.update');
    Route::delete('/permissions/user', [UserAccessController::class, 'clearUserFeaturePermission'])->name('permissions.user.clear');

    Route::middleware('permission:admin.system-monitor')->group(function () {
        Route::get('/system-monitor', [SystemMonitorController::class, 'index'])->name('system-monitor.index');
    });

    Route::middleware('permission:admin.network-monitor')->group(function () {
        Route::get('/network-monitor', [NetworkMonitorController::class, 'index'])->name('network-monitor.index');
        Route::get('/network-monitor/api', [NetworkMonitorController::class, 'api'])->name('network-monitor.api');
    });

    Route::middleware('permission:admin.laporan')->group(function () {
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::post('/laporan/generate', [LaporanController::class, 'generate'])->name('laporan.generate');
        Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    });

    Route::get('/pendopo', [PendopoAdminController::class, 'index'])->name('pendopo.index');
    Route::patch('/pendopo/settings', [PendopoAdminController::class, 'updateSettings'])->name('pendopo.settings.update');
    Route::post('/pendopo/sync-legacy', [PendopoAdminController::class, 'syncLegacy'])->name('pendopo.sync');
    Route::delete('/pendopo/entries/{entry}', [PendopoAdminController::class, 'destroyEntry'])->name('pendopo.entries.destroy');

    // SIKEP Sync Admin
    Route::get('/sikep-sync', [\App\Http\Controllers\Admin\SikepSyncController::class, 'index'])->name('sikep-sync.index');
    Route::post('/sikep-sync', [\App\Http\Controllers\Admin\SikepSyncController::class, 'sync'])->name('sikep-sync.store');

    Route::middleware('permission:admin.cctv')->group(function () {
        Route::get('/cctv', [CctvCameraController::class, 'index'])->name('cctv.index');
        Route::post('/cctv', [CctvCameraController::class, 'store'])->name('cctv.store');
        Route::patch('/cctv/{camera}', [CctvCameraController::class, 'update'])->name('cctv.update');
    });

    // WA Caraka Admin (superadmin only)
    Route::get('/wa-caraka', [WaCarakaAdminController::class, 'index'])->name('wacaraka.index');
    Route::match(['get', 'post'], '/wa-caraka/api/{action}', [WaCarakaAdminController::class, 'api'])
        ->where('action', '.*')
        ->name('wacaraka.api');

    // OAuth2 SSO Admin
    Route::get('/oauth2', [\App\Http\Controllers\Admin\OAuth2AdminController::class, 'index'])->name('oauth2.index');
    Route::post('/oauth2', [\App\Http\Controllers\Admin\OAuth2AdminController::class, 'store'])->name('oauth2.store');
    // Passport doesn't use standard route model binding for clients by default if not set, 
    // but we can use the ID.
    Route::delete('/oauth2/{client}', [\App\Http\Controllers\Admin\OAuth2AdminController::class, 'destroy'])->name('oauth2.destroy');
});

require __DIR__.'/auth.php';

use App\Http\Controllers\Omnichannel\LeaderboardController;
Route::middleware(['auth', 'verified', 'active', 'role:viewer,operator,useradmin,admin'])->group(function () {
    Route::get('/omnichannel/leaderboard', [LeaderboardController::class, 'index'])->name('livechat.leaderboard');
});
