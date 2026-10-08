<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaCarakaConversation;
use App\Models\WaCarakaMessage;
use App\Services\WaCaraka\WaCarakaHttpClient;
use App\Services\WaCaraka\WaCarakaConversationService as WaConvoSvc;
use App\Services\WaCaraka\WaCarakaMessageService;
use App\Services\WaCaraka\WaCarakaStatsService;
use App\Services\WaCarakaService;
use App\Support\LawangsewuPortal;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * WaCarakaPersonalAdminController
 *
 * Duplikat WaCarakaAdminController untuk instance Personal.
 * Perbedaan SATU-SATUNYA: runtime diarahkan ke WSL (via Tailscale).
 * Protected by 'superadmin' middleware in routes/web.php.
 */
class WaCarakaPersonalAdminController extends Controller
{
    protected WaCarakaService $wa;

    public function __construct()
    {
        // Bangun HTTP client baru yang mengarah ke runtime personal (WSL via Tailscale)
        $personalHttp = new WaCarakaHttpClient(
            baseUrl  : config('wa_caraka.personal_base_url', 'http://127.0.0.1:8791'),
            token    : config('wa_caraka.personal_token', ''),
            timeout  : (int) config('wa_caraka.personal_timeout', 30),
            fallbackUrl: '',
        );

        $msgSvc   = new WaCarakaMessageService(
            $personalHttp,
            app(WaConvoSvc::class),
            app(WaCarakaStatsService::class),
        );

        $this->wa = new WaCarakaService(
            $personalHttp,
            $msgSvc,
            app(WaConvoSvc::class),
            app(WaCarakaStatsService::class),
        );

        // Scope DB ke source=personal
        WaCarakaMessage::$activeSource = 'personal';
        WaCarakaConversation::$activeSource = 'personal';
    }

    /**
     * Admin dashboard untuk WA Personal instance.
     */
    public function index()
    {
        return Inertia::render('Admin/WaCarakaPersonalManager', [
            'appMeta'      => LawangsewuPortal::appMeta(),
            'navGroups'    => LawangsewuPortal::navGroups(),

            'stats'        => $this->wa->stats(),
            'messageStats' => $this->wa->messageStats(),
            'recentLogs'   => $this->wa->recentLogs(30),
            'conversations'=> $this->wa->conversations(20),

            'config' => [
                'baseUrl'        => $this->wa->baseUrl(),
                'broadcastLimit' => $this->wa->broadcastLimit(),
                'loggingEnabled' => config('wa_caraka.logging_enabled', true),
                'timeout'        => config('wa_caraka.personal_timeout', 30),
                'background'     => \Illuminate\Support\Facades\Cache::get('wacaraka_personal_background'),
                'closingTemplate' => \Illuminate\Support\Facades\Cache::get(
                    'wacaraka_personal_closing_template',
                    "Baik, terima kasih sudah menghubungi kami.\n\nوَالسَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ"
                ),
                'instance' => 'personal',
                'label'    => config('wa_caraka.personal_label', 'WA Personal'),
            ],
        ]);
    }

    /**
     * API actions — identik dengan WaCarakaAdminController tapi untuk runtime personal.
     */
    public function api(Request $request, string $action)
    {
        // Pastikan scope DB personal aktif
        WaCarakaMessage::$activeSource = 'personal';
        WaCarakaConversation::$activeSource = 'personal';

        $user   = auth()->user();
        $userId = $user->id;
        $sender = $user->name ?? $user->email ?? 'superadmin';

        $result = match (true) {
            // Device
            $action === 'health'        => $this->wa->health(),
            $action === 'qr'            => $this->wa->qr(),
            $action === 'restart'       => $this->wa->restart(),
            $action === 'reconnect'     => $this->wa->reconnect(),
            $action === 'disconnect'    => $this->wa->disconnect(),

            // History & Inbox
            $action === 'history'       => $this->wa->history(),
            $action === 'history/clear' => $this->wa->clearHistory(),
            $action === 'inbox/clear'   => $this->wa->clearInbox(),

            // Stats
            $action === 'stats'         => ['ok' => true, 'status' => 200, 'data' => $this->wa->stats()],
            $action === 'message-stats' => ['ok' => true, 'status' => 200, 'data' => $this->wa->messageStats()],

            // Logs & percakapan
            $action === 'logs'          => ['ok' => true, 'status' => 200, 'data' => $this->wa->recentLogs(100)],
            $action === 'conversations' => ['ok' => true, 'status' => 200, 'data' => $this->wa->conversations(50)],
            $action === 'pull-inbox'    => $this->wa->pullInbox($request->query('since')),

            // Semua pesan (paginated)
            $action === 'messages'      => $this->handleMessages($request),

            // Kirim & broadcast
            $action === 'send-text'     => $this->handleSendText($request, $sender, $userId),
            $action === 'broadcast'     => $this->handleBroadcast($request, $sender, $userId),

            // Pengaturan
            $action === 'save-background'         => $this->handleSaveBackground($request),
            $action === 'save-closing-template'   => $this->handleSaveClosingTemplate($request),
            $action === 'get-closing-template'    => $this->handleGetClosingTemplate(),

            default => ['ok' => false, 'status' => 404, 'error' => 'Aksi tidak valid.'],
        };

        if (!$result['ok']) {
            $code = in_array($result['status'], [0, null]) ? 502 : $result['status'];
            return response()->json([
                'ok'     => false,
                'error'  => $result['error'] ?? $result['message'] ?? 'Terjadi kesalahan.',
                'detail' => $result['detail'] ?? null,
            ], $code);
        }

        return response()->json($result['data'] ?? ['ok' => true], $result['status']);
    }

    private function handleMessages(Request $request): array
    {
        $page  = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(10, (int) $request->query('limit', 50)));

        $query = WaCarakaMessage::query()
            ->with('user:id,name,alias')
            ->orderByDesc('created_at');

        if ($request->filled('direction')) $query->where('direction', $request->query('direction'));
        if ($request->filled('status'))    $query->where('status', $request->query('status'));
        if ($request->filled('remote_number')) $query->where('remote_number', $request->query('remote_number'));

        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        return [
            'ok'     => true,
            'status' => 200,
            'data'   => [
                'messages'    => $paginated->items(),
                'total'       => $paginated->total(),
                'currentPage' => $paginated->currentPage(),
                'lastPage'    => $paginated->lastPage(),
            ],
        ];
    }

    private function handleSendText(Request $request, string $sender, int $userId): array
    {
        $validated = $request->validate([
            'to'   => 'required|string|min:8|max:20',
            'text' => 'required|string|max:4096',
        ]);

        return $this->wa->sendText($validated['to'], $validated['text'], $sender, $userId);
    }

    private function handleBroadcast(Request $request, string $sender, int $userId): array
    {
        $validated = $request->validate([
            'recipients'   => 'required|array|min:1|max:' . $this->wa->broadcastLimit(),
            'recipients.*' => 'required|string|min:8|max:20',
            'text'         => 'required|string|max:4096',
        ]);

        return $this->wa->broadcastText($validated['recipients'], $validated['text'], $sender, $userId);
    }

    private function handleSaveBackground(Request $request): array
    {
        $validated = $request->validate(['background' => 'nullable|string|max:1000']);
        \Illuminate\Support\Facades\Cache::forever('wacaraka_personal_background', $validated['background']);
        return ['ok' => true, 'status' => 200];
    }

    private function handleSaveClosingTemplate(Request $request): array
    {
        $validated = $request->validate(['template' => 'nullable|string|max:2000']);
        \Illuminate\Support\Facades\Cache::forever('wacaraka_personal_closing_template', $validated['template'] ?: null);
        return ['ok' => true, 'status' => 200];
    }

    private function handleGetClosingTemplate(): array
    {
        $default = "Baik, terima kasih sudah menghubungi kami.\n\nوَالسَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ";
        return [
            'ok'     => true,
            'status' => 200,
            'data'   => ['template' => \Illuminate\Support\Facades\Cache::get('wacaraka_personal_closing_template', $default)],
        ];
    }
}
