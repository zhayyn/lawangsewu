<?php

namespace App\Http\Controllers;

use App\Models\WaCarakaConversation;
use App\Models\WaCarakaMessage;
use App\Services\WaCaraka\WaCarakaHttpClient;
use App\Services\WaCaraka\WaCarakaConversationService as WaConvoSvcInternal;
use App\Services\WaCaraka\WaCarakaMessageService;
use App\Services\WaCaraka\WaCarakaStatsService;
use App\Services\WaCarakaConversationService;
use App\Services\WaCarakaService;
use App\Services\WaCarakaTicketService;
use App\Support\LawangsewuPortal;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * WaCarakaPersonalController
 *
 * Duplikat fungsional WaCarakaController untuk instance Personal.
 * Perbedaan SATU-SATUNYA: runtime diarahkan ke WSL (via Tailscale).
 *
 * Semua fitur inbox, kirim pesan, media, handover, dll identik —
 * diwarisi dari WaCarakaController via inheritance.
 *
 * Akses dibatasi ke superadmin only.
 */
class WaCarakaPersonalController extends WaCarakaController
{
    public function __construct(
        WaCarakaConversationService $conversations,
        WaCarakaTicketService $tickets,
    ) {
        // Bangun WaCarakaHttpClient baru yang mengarah ke runtime personal (WSL via Tailscale)
        $personalHttp = new WaCarakaHttpClient(
            baseUrl  : config('wa_caraka.personal_base_url', 'http://127.0.0.1:8791'),
            token    : config('wa_caraka.personal_token', ''),
            timeout  : (int) config('wa_caraka.personal_timeout', 30),
            fallbackUrl: '', // Tidak ada fallback untuk instance personal
        );
        $personalHttp->setUseBearerAuth(true); // WSL runtime menggunakan Bearer auth

        // Bangun seluruh WaCarakaService stack dengan HTTP client yang mengarah ke runtime personal
        $personalWa = new WaCarakaService(
            $personalHttp,
            new WaCarakaMessageService(
                $personalHttp,
                app(WaConvoSvcInternal::class),
                app(WaCarakaStatsService::class),
            ),
            app(WaConvoSvcInternal::class),
            app(WaCarakaStatsService::class),
        );

        // Panggil parent constructor dengan WaCarakaService yang sudah dikonfigurasi ke personal
        parent::__construct($personalWa, $conversations, $tickets);

        // Scope DB ke source='personal' agar inbox hanya menampilkan percakapan personal
        WaCarakaMessage::$activeSource = 'personal';
        WaCarakaConversation::$activeSource = 'personal';
    }

    public function index()
    {
        $user = request()->user();

        if (!$user->isSuperAdmin()) {
            abort(403, 'Akses WA Personal hanya untuk superadmin.');
        }

        // Scope DB ke source='personal'
        WaCarakaMessage::$activeSource = 'personal';
        WaCarakaConversation::$activeSource = 'personal';

        return Inertia::render('Lawangsewu/WaCarakaPersonal/Index', [
            'appMeta'      => LawangsewuPortal::appMeta(),
            'navGroups'    => LawangsewuPortal::navGroups(),
            'authUser'     => $user,
            'config'       => [
                'baseUrl'       => $this->wa->baseUrl(),
                'background'    => \Illuminate\Support\Facades\Cache::get('wacaraka_personal_background'),
                'maxMediaBytes' => (int) config('wa_caraka.max_media_bytes', 15 * 1024 * 1024),
            ],
            'messageStats' => \Illuminate\Support\Facades\Cache::remember('wacaraka_personal_msg_stats', 60, fn () => $this->wa->messageStats()),
            'convoStats'   => \Illuminate\Support\Facades\Cache::remember('wacaraka_personal_conv_stats_' . $user->id, 60, fn () => $this->conversations->stats($user)),
            'ticketStats'  => [], // WA Personal tidak pakai antrian tiket
            'recentTickets' => [],
        ]);
    }

    /**
     * Override reports() — render view yang sama tapi dengan data source='personal'.
     * Data buildReportStats() otomatis terfilter karena activeSource sudah di-set di constructor.
     */
    public function reports()
    {
        $user = request()->user();

        if (!$user->isSuperAdmin()) {
            abort(403, 'Akses laporan WA Personal hanya untuk superadmin.');
        }

        WaCarakaMessage::$activeSource = 'personal';
        WaCarakaConversation::$activeSource = 'personal';

        return Inertia::render('Lawangsewu/WaCaraka/Reports', [
            'appMeta'     => LawangsewuPortal::appMeta(),
            'navGroups'   => LawangsewuPortal::navGroups(),
            'authUser'    => $user,
            'reportStats' => $this->buildReportStats(),
        ]);
    }

    /**
     * Override downloadMedia() — pastikan scope DB ke personal sebelum serve media.
     * Logika fetch media dari runtime sudah benar karena $this->wa mengarah ke WSL.
     */
    public function downloadMedia(\Illuminate\Http\Request $request, string $path)
    {
        $user = $request->user();

        if (!$user || !$user->isSuperAdmin()) {
            abort(403);
        }

        WaCarakaMessage::$activeSource = 'personal';
        WaCarakaConversation::$activeSource = 'personal';

        return parent::downloadMedia($request, $path);
    }


    /**
     * Override proxy() — hanya superadmin + paksa scope DB=personal.
     * WaCarakaService sudah dikonfigurasi ke runtime WSL di constructor.
     *
     * Catatan: aksi 'personal-health', 'personal-qr', dll dari halaman WA Caraka kantor
     * di-remap ke aksi langsung karena controller ini SUDAH mengarah ke runtime personal.
     */
    public function proxy(Request $request, string $action)
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            return response()->json([
                'ok'    => false,
                'error' => 'Aksi ini hanya dapat dilakukan oleh superadmin.',
            ], 403);
        }

        // Pastikan scope DB tetap 'personal' di setiap request
        WaCarakaMessage::$activeSource = 'personal';
        WaCarakaConversation::$activeSource = 'personal';

        // Remap aksi 'personal-*' ke aksi langsung karena runtime sudah di-scope ke personal
        $actionMap = [
            'personal-health'    => 'health',
            'personal-qr'        => 'qr',
            'personal-reconnect' => 'reconnect',
            'personal-logout'    => 'disconnect',
        ];
        if (isset($actionMap[$action])) {
            $action = $actionMap[$action];
        }

        // Karena runtime personal (dari office baileys) tidak memiliki endpoint berikut, kita mock agar tidak 404
        if ($action === 'lid-mappings') {
            return response()->json(['ok' => true, 'data' => ['pairs' => []]]);
        }
        if ($action === 'resolve-contacts') {
            return response()->json(['ok' => true, 'data' => ['items' => []]]);
        }

        // Delegate ke parent::proxy() — semua logika bisnis diwarisi dari WaCarakaController
        return parent::proxy($request, $action);
    }
}
