<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaCarakaMessage;
use App\Services\WaCarakaService;
use App\Support\LawangsewuPortal;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * WaCarakaAdminController
 *
 * Superadmin-only control panel for the WA Caraka module.
 * Provides: device management, all message logs (both tables),
 * broadcast tools, config overview, and message statistics.
 *
 * Protected by 'superadmin' middleware in routes/web.php.
 */
class WaCarakaAdminController extends Controller
{
    public function __construct(protected WaCarakaService $wa) {}

    /**
     * Admin dashboard for WA Caraka.
     */
    public function index()
    {
        return Inertia::render('Admin/WaCarakaManager', [
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
                'timeout'        => config('wa_caraka.timeout', 20),
                'background'     => \Illuminate\Support\Facades\Cache::get('wacaraka_background'),
            ],
        ]);
    }

    /**
     * API actions for admin page (device control, stats, logs).
     */
    public function api(Request $request, string $action)
    {
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

            // History
            $action === 'history'       => $this->wa->history(),
            $action === 'history/clear' => $this->wa->clearHistory(),

            // Stats
            $action === 'stats'         => ['ok' => true, 'status' => 200, 'data' => $this->wa->stats()],
            $action === 'message-stats' => ['ok' => true, 'status' => 200, 'data' => $this->wa->messageStats()],

            // Logs & conversations
            $action === 'logs'          => ['ok' => true, 'status' => 200, 'data' => $this->wa->recentLogs(100)],
            $action === 'conversations' => ['ok' => true, 'status' => 200, 'data' => $this->wa->conversations(50)],
            $action === 'pull-inbox'    => $this->wa->pullInbox($request->query('since')),

            // All messages (paginated)
            $action === 'messages'      => $this->handleMessages($request),

            // Send & broadcast
            $action === 'send-text'     => $this->handleSendText($request, $sender, $userId),
            $action === 'broadcast'     => $this->handleBroadcast($request, $sender, $userId),

            // Settings
            $action === 'save-background' => $this->handleSaveBackground($request),

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

    // ──────────────────────────────────────────────
    // Private handlers
    // ──────────────────────────────────────────────

    private function handleMessages(Request $request): array
    {
        $page  = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(10, (int) $request->query('limit', 50)));

        $query = WaCarakaMessage::query()
            ->with('user:id,name,alias')
            ->orderByDesc('created_at');

        // Optional filters
        if ($request->filled('direction')) {
            $query->where('direction', $request->query('direction'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('remote_number')) {
            $query->where('remote_number', $request->query('remote_number'));
        }

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
        $validated = $request->validate([
            'background' => 'nullable|string|max:1000',
        ]);

        \Illuminate\Support\Facades\Cache::forever('wacaraka_background', $validated['background']);

        return ['ok' => true, 'status' => 200];
    }
}
