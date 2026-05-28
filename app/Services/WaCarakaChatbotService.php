<?php

namespace App\Services;

use App\Models\WaCarakaMenu;
use App\Models\WaCarakaSession;
use App\Models\WaCarakaTicket;
use Illuminate\Support\Facades\Log;

/**
 * WaCarakaChatbotService
 *
 * Auto-reply engine for inbound WhatsApp messages.
 * Translates wamehehe's proses_message() logic into clean Laravel service.
 *
 * Flow:
 *   1. Check if user has a pending 2-stage session → process input
 *   2. Check if message matches a menu command → route by type
 *   3. Otherwise → send default menu
 *
 * SIPP queries (menu 7-11) are delegated to SippService
 * which uses an independent connection to 192.168.88.10.
 */
class WaCarakaChatbotService
{
    public function __construct(
        protected SippService $sipp,
    ) {}
    /**
     * Main entry point — process an inbound message and return auto-reply text.
     * Returns null if no auto-reply should be sent.
     */
    public function processInbound(string $from, string $text): ?string
    {
        $text = strtolower(trim($text));

        if (empty($text)) {
            return null;
        }

        try {
            // Step 1: Check for pending 2-stage session
            $session = WaCarakaSession::findActiveForNumber($from);

            if ($session) {
                return $this->handleSessionInput($from, $text, $session);
            }

            // Step 2: Check if message matches a menu command
            $menu = WaCarakaMenu::findByCommand($text);

            if ($menu) {
                return $this->handleMenuCommand($from, $text, $menu);
            }

            // Step 3: Not a menu command → fallback to Ollama AI
            try {
                $aiReply = app(\App\Services\OllamaService::class)->generateReply($text);
                return $aiReply;
            } catch (\Exception $e) {
                // If Ollama fails, fallback to standard menu
                return $this->buildDefaultMenu();
            }

        } catch (\Exception $e) {
            Log::error('[WaCaraka:Chatbot] Error processing message', [
                'from'  => $from,
                'text'  => $text,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build the default menu text from active menu entries.
     */
    public function buildDefaultMenu(): string
    {
        $menus = WaCarakaMenu::active()
            ->orderBy('sort_order')
            ->get();

        if ($menus->isEmpty()) {
            return "*SiNofita PA Semarang*\n_Assalamualaikum wr. wb._\n\nLayanan chatbot sedang dalam konfigurasi.";
        }

        // Use menu command "1" response as default if available
        $defaultMenu = $menus->firstWhere('command', '1');
        if ($defaultMenu && $defaultMenu->response_text) {
            return $defaultMenu->response_text;
        }

        // Build from list
        $lines = [
            "*SiNofita PA Semarang*",
            "_Assalamualaikum wr. wb._",
            "",
            "Silahkan ketik angka sesuai informasi yang Anda butuhkan:",
            "",
        ];

        foreach ($menus->where('command', '!=', '1') as $menu) {
            $lines[] = "*{$menu->command}*. {$menu->label}";
        }

        $lines[] = "";
        $lines[] = "Ketik angka pilihan Anda:";

        return implode("\n", $lines);
    }

    /**
     * Handle a recognized menu command.
     */
    protected function handleMenuCommand(string $from, string $text, WaCarakaMenu $menu): string
    {
        if ($menu->isDirect()) {
            return $this->handleDirectReply($menu);
        }

        if ($menu->isPrompt()) {
            return $this->handlePrompt($from, $menu);
        }

        if ($menu->requiresInput()) {
            return $this->handleInputPrompt($from, $menu);
        }

        return $this->buildDefaultMenu();
    }

    /**
     * Handle direct-reply menus (type = 'direct').
     */
    protected function handleDirectReply(WaCarakaMenu $menu): string
    {
        return $menu->response_text ?? 'Informasi tidak tersedia saat ini.';
    }

    /**
     * Handle prompt menus (type = 'prompt') — show sub-menu and create session.
     */
    protected function handlePrompt(string $from, WaCarakaMenu $menu): string
    {
        WaCarakaSession::clearForNumber($from);

        WaCarakaSession::create([
            'remote_number'   => $from,
            'pending_command' => $menu->command,
            'prompt_sent'     => $menu->prompt_text ?? $menu->response_text,
            'expires_at'      => now()->addMinutes(30),
        ]);

        return $menu->prompt_text ?? $menu->response_text ?? 'Silahkan pilih:';
    }

    /**
     * Handle input-requiring menus (type = 'input') — ask for input.
     */
    protected function handleInputPrompt(string $from, WaCarakaMenu $menu): string
    {
        WaCarakaSession::clearForNumber($from);

        $prompt = $menu->prompt_text ?? '*Masukkan data yang diminta:*';

        WaCarakaSession::create([
            'remote_number'   => $from,
            'pending_command' => $menu->command,
            'prompt_sent'     => $prompt,
            'expires_at'      => now()->addMinutes(30),
        ]);

        return $prompt;
    }

    /**
     * Process input for a pending 2-stage session.
     */
    protected function handleSessionInput(string $from, string $input, WaCarakaSession $session): string
    {
        $command = $session->pending_command;

        // If user sends another menu command instead of input, reset
        $menu = WaCarakaMenu::findByCommand($input);
        if ($menu) {
            WaCarakaSession::clearForNumber($from);
            return $this->handleMenuCommand($from, $input, $menu);
        }

        $originalMenu = WaCarakaMenu::findByCommand($command);
        WaCarakaSession::clearForNumber($from);

        if (!$originalMenu) {
            return $this->buildDefaultMenu();
        }

        // Ticket creation (pengaduan / konsultasi)
        if ($originalMenu->createsTicket()) {
            return $this->createTicketFromInput($from, $input, $originalMenu);
        }

        // Prompt sub-menu selection
        if ($originalMenu->isPrompt()) {
            return $this->handlePromptSelection($from, $input, $originalMenu);
        }

        // Dynamic response (SIPP or legacy)
        return $this->processDynamicResponse($input, $originalMenu, $from);
    }

    /**
     * Create a ticket from chatbot input (menu 12 = Pengaduan, 13 = Konsultasi).
     */
    protected function createTicketFromInput(string $from, string $input, WaCarakaMenu $menu): string
    {
        try {
            $ticket = WaCarakaTicket::create([
                'type'          => $menu->creates_ticket_type,
                'remote_number' => $from,
                'message'       => $input,
                'status'        => 'open',
            ]);

            Log::info('[WaCaraka:Chatbot] Ticket created', [
                'ticket_id' => $ticket->id,
                'type'      => $ticket->type,
                'from'      => $from,
            ]);

            return $menu->response_text
                ?? "Terima kasih. {$menu->label} Anda telah kami terima dan akan segera ditindaklanjuti.";

        } catch (\Exception $e) {
            Log::error('[WaCaraka:Chatbot] Failed to create ticket', [
                'from'  => $from,
                'type'  => $menu->creates_ticket_type,
                'error' => $e->getMessage(),
            ]);
            return 'Maaf, terjadi gangguan saat memproses permintaan Anda. Silahkan coba lagi.';
        }
    }

    /**
     * Handle sub-menu selection from a prompt-type menu.
     */
    protected function handlePromptSelection(string $from, string $input, WaCarakaMenu $menu): string
    {
        $subMenu = WaCarakaMenu::findByCommand($input);
        if ($subMenu) {
            return $this->handleDirectReply($subMenu);
        }

        return $this->processDynamicResponse($input, $menu, $from);
    }

    /**
     * Process a dynamic response:
     * - If menu has sipp_query_type → SippService (rate-limited, validated, read-only)
     * - If menu has response_query  → legacy raw SQL on main DB
     * - Otherwise                  → static response_text
     *
     * @param string $from  Sender phone number — passed to SIPP rate limiter only
     */
    protected function processDynamicResponse(string $input, WaCarakaMenu $menu, string $from = ''): string
    {
        // ── SIPP query via SippService (preferred) ──
        if (!empty($menu->sipp_query_type)) {
            return $this->sipp->query($menu->sipp_query_type, $input, $from);
        }

        // ── Legacy raw SQL fallback (main DB only) ──
        if ($menu->response_query) {
            try {
                $query = str_replace('#', '"' . $input . '%"', $menu->response_query);
                $query = trim($query);

                if (stripos($query, 'SELECT') === 0) {
                    $result = \DB::select($query);
                    if (!empty($result)) {
                        $row   = (array) $result[0];
                        $pesan = $row['pesan'] ?? $row['value'] ?? null;
                        if ($pesan && strlen($pesan) > 10) {
                            return $pesan . ($menu->extra_text ? ' ' . $menu->extra_text : '');
                        }
                    }
                }

                return 'Maaf data tidak ditemukan';

            } catch (\Exception $e) {
                Log::warning('[WaCaraka:Chatbot] Dynamic query failed', [
                    'command' => $menu->command,
                    'code'    => $e->getCode(), // code only — avoid leaking schema info
                ]);
                return 'Maaf, layanan pencarian data sedang tidak tersedia. Silahkan coba lagi nanti.';
            }
        }

        return $menu->response_text ?? 'Maaf data tidak ditemukan';
    }
}
