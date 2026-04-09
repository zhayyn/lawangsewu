<?php

use App\Http\Controllers\Admin\CctvCameraController;
use App\Http\Controllers\Admin\PendopoAdminController;
use App\Http\Controllers\Admin\UserAccessController;
use App\Http\Controllers\GuestbookController;
use App\Http\Controllers\PtspQueueController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\SidangQueueController;
use App\Http\Controllers\SippHubController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WidgetCompatController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('api')->group(function () {
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

Route::get('/daftar-widget', [WidgetCompatController::class, 'html'])->defaults('page', 'daftar-widget');
Route::get('/widget-links', [WidgetCompatController::class, 'html'])->defaults('page', 'widget-links');

Route::get('/berita-pengadilan', [WidgetCompatController::class, 'html'])->defaults('page', 'berita-pengadilan');
Route::get('/berita-pasmg', [WidgetCompatController::class, 'html'])->defaults('page', 'berita-pasmg');
Route::get('/panduan-embed-pengumuman', [WidgetCompatController::class, 'html'])->defaults('page', 'panduan-embed-pengumuman');
Route::get('/bridge-server10', [WidgetCompatController::class, 'html'])->defaults('page', 'bridge-server10');
Route::get('/biaya-proses-berperkara', [WidgetCompatController::class, 'html'])->defaults('page', 'biaya-proses-berperkara');
Route::get('/biaya-perkara', [WidgetCompatController::class, 'html'])->defaults('page', 'biaya-perkara');
Route::get('/monitor-wa', [WidgetCompatController::class, 'html'])->defaults('page', 'monitor-wa');

Route::get('/monitor-persidangan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'monitor-persidangan');
Route::get('/monitor-antrian-sidang', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'monitor-antrian-sidang');
Route::get('/antrian-persidangan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'antrian-persidangan');
Route::get('/antrian-sidang', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'antrian-sidang');
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

Route::prefix('lawangsewu')->group(function () {
    Route::get('/pengumuman-peradilan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pengumuman-peradilan');
    Route::get('/pengumuman-peradilan-embed', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pengumuman-peradilan-embed');
    Route::get('/pengumuman-rss-widget', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'pengumuman-rss-widget');
    Route::get('/dashboard-perkara', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'dashboard-perkara');
    Route::get('/monitor-persidangan', [WidgetCompatController::class, 'phpPublic'])->defaults('page', 'monitor-persidangan');

    Route::prefix('api')->group(function () {
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

Route::middleware(['auth', 'verified', 'active', 'role:viewer,operator,admin'])->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('lawangsewu.dashboard');
    Route::get('/cctv', [PortalController::class, 'cctv'])->name('lawangsewu.cctv');
    Route::get('/chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('lawangsewu.chat');
    Route::post('/chat', [\App\Http\Controllers\ChatController::class, 'store'])->name('lawangsewu.chat.store');

    // Satellite Integration
    Route::get('/satellite/pendopo', [\App\Http\Controllers\SatelliteController::class, 'pendopo'])->name('lawangsewu.satellite.pendopo');
});

Route::middleware(['auth', 'verified', 'active', 'role:viewer,operator,admin'])->group(function () {
    Route::get('/buku-tamu', [GuestbookController::class, 'form'])->name('lawangsewu.guestbook.form');
    Route::post('/buku-tamu', [GuestbookController::class, 'store'])->name('lawangsewu.guestbook.store');
    Route::get('/buku-tamu/daftar/{period?}', [GuestbookController::class, 'listing'])->name('lawangsewu.guestbook.list');
    Route::get('/antrian-ptsp', [PtspQueueController::class, 'index'])->name('lawangsewu.ptsp.index');
    Route::get('/antrian-sidang-v2', [SidangQueueController::class, 'index'])->name('lawangsewu.sidang.index');
    Route::get('/pilar-smg', [PortalController::class, 'pilar'])->name('lawangsewu.pilar.index');
    Route::get('/sipp-hub', [SippHubController::class, 'index'])->name('lawangsewu.sipp.index');
});

Route::middleware(['auth', 'verified', 'active', 'role:operator,admin'])->group(function () {
    Route::get('/buku-tamu/detail/{id}', [GuestbookController::class, 'detail'])->name('lawangsewu.guestbook.detail');
    Route::get('/buku-tamu/cetak/{id}', [GuestbookController::class, 'printCard'])->name('lawangsewu.guestbook.cetak');
    Route::match(['get', 'post'], '/buku-tamu/laporan', [GuestbookController::class, 'report'])->name('lawangsewu.guestbook.report');
});

Route::middleware(['auth', 'verified', 'active', 'role:operator,admin'])->group(function () {
    Route::post('/antrian-ptsp', [PtspQueueController::class, 'store'])->name('lawangsewu.ptsp.store');
    Route::post('/antrian-ptsp/{ticket}/call', [PtspQueueController::class, 'call'])->name('lawangsewu.ptsp.call');
    Route::post('/antrian-ptsp/{ticket}/serve', [PtspQueueController::class, 'serve'])->name('lawangsewu.ptsp.serve');
    Route::post('/antrian-ptsp/{ticket}/skip', [PtspQueueController::class, 'skip'])->name('lawangsewu.ptsp.skip');

    Route::post('/antrian-sidang-v2', [SidangQueueController::class, 'store'])->name('lawangsewu.sidang.store');
    Route::post('/antrian-sidang-v2/{ticket}/call', [SidangQueueController::class, 'call'])->name('lawangsewu.sidang.call');
    Route::post('/antrian-sidang-v2/{ticket}/complete', [SidangQueueController::class, 'complete'])->name('lawangsewu.sidang.complete');
    Route::post('/antrian-sidang-v2/{ticket}/postpone', [SidangQueueController::class, 'postpone'])->name('lawangsewu.sidang.postpone');

    Route::post('/sipp-hub/refresh', [SippHubController::class, 'refreshCache'])->name('lawangsewu.sipp.refresh');
});

Route::middleware(['auth', 'active', 'role:viewer,operator,admin'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.save');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'active', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserAccessController::class, 'index'])->name('users.index');
    Route::post('/users', [UserAccessController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [UserAccessController::class, 'update'])->name('users.update');
    Route::post('/users/allowlist', [UserAccessController::class, 'storeAllowlist'])->name('users.allowlist.store');
    Route::patch('/users/allowlist/{entry}', [UserAccessController::class, 'updateAllowlist'])->name('users.allowlist.update');
    Route::delete('/users/allowlist/{entry}', [UserAccessController::class, 'destroyAllowlist'])->name('users.allowlist.destroy');
    Route::get('/pendopo', [PendopoAdminController::class, 'index'])->name('pendopo.index');
    Route::patch('/pendopo/settings', [PendopoAdminController::class, 'updateSettings'])->name('pendopo.settings.update');
    Route::post('/pendopo/sync-legacy', [PendopoAdminController::class, 'syncLegacy'])->name('pendopo.sync');
    Route::delete('/pendopo/entries/{entry}', [PendopoAdminController::class, 'destroyEntry'])->name('pendopo.entries.destroy');
    Route::get('/cctv', [CctvCameraController::class, 'index'])->name('cctv.index');
    Route::post('/cctv', [CctvCameraController::class, 'store'])->name('cctv.store');
    Route::patch('/cctv/{camera}', [CctvCameraController::class, 'update'])->name('cctv.update');
});

require __DIR__.'/auth.php';
