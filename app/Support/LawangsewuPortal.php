<?php

namespace App\Support;

use App\Models\ChatAlias;
use App\Models\ChatMessage;
use App\Models\CctvCamera;
use App\Models\PtspQueueTicket;
use App\Models\ServiceCounter;
use App\Models\SidangQueueTicket;
use App\Models\SippCache;
use App\Models\WaCarakaLog;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class LawangsewuPortal
{
    protected static function currentUser()
    {
        return auth()->user();
    }

    protected static function isSuperAdmin(): bool
    {
        return (bool) self::currentUser()?->isSuperAdmin();
    }

    protected static function isOperatorOnlyView(): bool
    {
        $user = self::currentUser();

        return $user
            && ! $user->isSuperAdmin()
            && $user->role === 'operator';
    }

    /**
     * Check if the current user may see a given nav item identified by routeKey.
     * Superadmin always has access. For others, FeaturePermission is consulted
     * so that the superadmin can toggle individual menu items per role from the
     * Kelola Akses User interface.
     */
    protected static function canSeeNavItem(string $routeKey): bool
    {
        $user = self::currentUser();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        return \App\Models\FeaturePermission::hasAccess($user, 'nav.' . $routeKey);
    }

    protected static function filterNavItems(array $items): array
    {
        return array_values(array_filter($items, function (array $item): bool {
            return self::canSeeNavItem((string) ($item['routeKey'] ?? ''));
        }));
    }

    protected static function filterLaunchersForCurrentUser(array $items): array
    {
        return array_values(array_filter($items, function (array $item): bool {
            $routeKey = (string) ($item['routeKey'] ?? '');
            if ($routeKey !== '') {
                return self::canSeeNavItem($routeKey);
            }

            // Fallback for items without routeKey: always show to non-operator or superadmin
            $user = self::currentUser();
            if (! $user || $user->isSuperAdmin()) {
                return true;
            }

            return ! ($user->role === 'operator');
        }));
    }

    protected static function routeOrNull(string $name): ?string
    {
        return Route::has($name) ? route($name) : null;
    }

    public static function appMeta(): array
    {
        return [
            'name'    => 'Lawangsewu V2',
            'tagline' => 'Ekosistem digital internal PA Semarang',
            'sprint'  => 'Sprint 1-4 selesai: dashboard operasional, RBAC, WA Caraka, dan Pilar Antrian PASMG siap digunakan.',
            'status'  => 'Semua sprint utama selesai dan layanan inti siap pakai.',
        ];
    }

    public static function navGroups(): array
    {
        $groups = [
            [
                'label' => 'Dashboard',
                'items' => [
                    [
                        'label' => 'Dashboard Utama',
                        'short' => 'DB',
                        'routeKey' => 'dashboard',
                        'href' => route('lawangsewu.dashboard'),
                        'badge' => 'Ready',
                    ],
                    [
                        'label' => 'Chat Internal',
                        'short' => 'CH',
                        'routeKey' => 'chat',
                        'href' => route('lawangsewu.chat'),
                        'badge' => 'Ready',
                    ],
                ],
            ],
            [
                'label' => 'Pelayanan',
                'items' => [
                    ['label' => 'Buku Tamu', 'short' => 'BT', 'routeKey' => 'guestbook', 'href' => route('lawangsewu.guestbook.form'), 'badge' => 'Ready'],
                    ['label' => 'Antrian PTSP', 'short' => 'PT', 'routeKey' => 'ptsp', 'href' => route('lawangsewu.ptsp.index'), 'badge' => 'Ready'],
                    ['label' => 'Antrian Sidang', 'short' => 'SD', 'routeKey' => 'sidang', 'href' => route('lawangsewu.sidang.index'), 'badge' => 'Ready'],
                    ['label' => 'Pilar Antrian PASMG', 'short' => 'PL', 'routeKey' => 'pilar', 'href' => route('lawangsewu.pilar.index'), 'badge' => 'Ready'],
                    ['label' => 'WA Live PTSP', 'short' => 'WA', 'routeKey' => 'wacaraka', 'href' => self::routeOrNull('lawangsewu.wacaraka.index'), 'badge' => 'Ready'],
                ],
            ],
            [
                'label' => 'SIPP Hub & Data',
                'items' => [
                    ['label' => 'Monitoring CCTV', 'short' => 'CV', 'routeKey' => 'cctv', 'href' => route('lawangsewu.cctv'), 'badge' => '19'],
                    ['label' => 'SIPP Hub', 'short' => 'SP', 'routeKey' => 'sipp', 'href' => route('lawangsewu.sipp.index'), 'badge' => 'Ready'],
                    ...(self::isSuperAdmin()
                        ? [['label' => 'WA Caraka Admin', 'short' => 'WA⚙', 'routeKey' => 'wacaraka.admin', 'href' => self::routeOrNull('admin.wacaraka.index'), 'badge' => 'Admin']]
                        : []),
                ],
            ],
            [
                'label' => 'Organisasi',
                'items' => [
                    ['label' => 'Kepegawaian', 'short' => 'KG', 'routeKey' => 'kepegawaian', 'href' => null, 'badge' => null],
                    ['label' => 'PTIP', 'short' => 'PI', 'routeKey' => 'ptip', 'href' => null, 'badge' => null],
                    ['label' => 'Umum / Keuangan', 'short' => 'UK', 'routeKey' => 'keuangan', 'href' => null, 'badge' => null],
                    ['label' => 'Pandanaran AI', 'short' => 'AI', 'routeKey' => 'ai', 'href' => null, 'badge' => 'Beta'],
                ],
            ],
            [
                'label' => 'Pengaturan',
                'items' => [
                    ['label' => 'Alias & Tema', 'short' => 'AT', 'routeKey' => 'appearance', 'href' => null, 'badge' => null],
                    ['label' => 'Preferensi Operator', 'short' => 'OP', 'routeKey' => 'preferences', 'href' => null, 'badge' => null],
                ],
            ],
        ];

        return array_values(array_filter(array_map(function (array $group): array {
            $group['items'] = self::filterNavItems($group['items'] ?? []);

            return $group;
        }, $groups), fn (array $group): bool => ! empty($group['items'])));
    }

    public static function quickActions(): array
    {
        return self::filterLaunchersForCurrentUser([
            ['label' => 'Buka CCTV', 'href' => route('lawangsewu.cctv'), 'tone' => 'accent', 'routeKey' => 'cctv'],
            ['label' => 'Buka Chat', 'href' => route('lawangsewu.chat'), 'tone' => 'neutral', 'routeKey' => 'chat'],
            ['label' => 'Buka Buku Tamu', 'href' => route('lawangsewu.guestbook.form'), 'tone' => 'neutral', 'routeKey' => 'guestbook'],
            ['label' => 'Buka Antrian PTSP', 'href' => route('lawangsewu.ptsp.index'), 'tone' => 'neutral', 'routeKey' => 'ptsp'],
            ['label' => 'Buka Antrian Sidang', 'href' => route('lawangsewu.sidang.index'), 'tone' => 'neutral', 'routeKey' => 'sidang'],
            ['label' => 'Buka Pilar Antrian PASMG', 'href' => route('lawangsewu.pilar.index'), 'tone' => 'neutral', 'routeKey' => 'pilar'],
            ['label' => 'Buka SIPP Hub', 'href' => route('lawangsewu.sipp.index'), 'tone' => 'accent', 'routeKey' => 'sipp'],
            ['label' => 'WA Live PTSP', 'href' => self::routeOrNull('lawangsewu.wacaraka.index'), 'tone' => 'accent', 'routeKey' => 'wacaraka'],
        ]);
    }

    public static function metrics(): array
    {
        $ptspWaiting = '-';
        if (Schema::hasTable('ptsp_queue_tickets')) {
            $ptspWaiting = (string) PtspQueueTicket::query()->today()->where('status', 'waiting')->count();
        }

        $sidangWaiting = '-';
        if (Schema::hasTable('sidang_queue_tickets')) {
            $sidangWaiting = (string) SidangQueueTicket::query()->today()->where('status', 'waiting')->count();
        }

        $sippCacheCount = '-';
        if (Schema::hasTable('sipp_caches')) {
            $sippCacheCount = (string) SippCache::query()->active()->count();
        }

        return [
            ['title' => 'Antrian PTSP', 'value' => $ptspWaiting, 'trend' => 'Data hari ini', 'detail' => 'Loket aktif dan antrean berjalan', 'tone' => 'blue'],
            ['title' => 'Sidang Hari Ini', 'value' => $sidangWaiting, 'trend' => 'Data hari ini', 'detail' => 'Antrean persidangan aktif', 'tone' => 'violet'],
            ['title' => 'Buku Tamu', 'value' => '28', 'trend' => '8 tamu internal', 'detail' => 'Puncak kunjungan pukul 10:00', 'tone' => 'amber'],
            ['title' => 'SIPP Cache', 'value' => $sippCacheCount, 'trend' => 'Entri aktif', 'detail' => 'Widget cache siap dimuat', 'tone' => 'emerald'],
        ];
    }

    public static function modules(): array
    {
        return self::filterLaunchersForCurrentUser([
            ['title' => 'Buku Tamu', 'description' => 'Registrasi tamu dan kehadiran harian.', 'owner' => 'Pelayanan', 'badge' => 'Ready', 'href' => route('lawangsewu.guestbook.form'), 'routeKey' => 'guestbook'],
            ['title' => 'Antrian PTSP', 'description' => 'Manajemen loket dan nomor antre.', 'owner' => 'PTSP', 'badge' => 'Ready', 'href' => route('lawangsewu.ptsp.index'), 'routeKey' => 'ptsp'],
            ['title' => 'Chat Internal', 'description' => 'Kanal komunikasi internal operator dan koordinasi harian.', 'owner' => 'Internal', 'badge' => 'Ready', 'href' => route('lawangsewu.chat'), 'routeKey' => 'chat'],
            ['title' => 'Antrian Sidang', 'description' => 'Panggilan sidang dan status ruang.', 'owner' => 'Kepaniteraan', 'badge' => 'Ready', 'href' => route('lawangsewu.sidang.index'), 'routeKey' => 'sidang'],
            ['title' => 'Pilar Antrian PASMG', 'description' => 'Hub antrean terpadu — katalog loket, ruang sidang, dan queue authority.', 'owner' => 'Pelayanan', 'badge' => 'Ready', 'href' => route('lawangsewu.pilar.index'), 'routeKey' => 'pilar'],
            ['title' => 'SIPP Hub', 'description' => 'Widget statistik dan cache sinkron.', 'owner' => 'Data', 'badge' => 'Ready', 'href' => route('lawangsewu.sipp.index'), 'routeKey' => 'sipp'],
            ['title' => 'WA Live PTSP', 'description' => 'Inbox WhatsApp layanan PTSP untuk operator, takeover chat, dan pemantauan sesi device.', 'owner' => 'PTSP', 'badge' => 'Ready', 'href' => self::routeOrNull('lawangsewu.wacaraka.index'), 'routeKey' => 'wacaraka'],
            ['title' => 'Kepegawaian', 'description' => 'Jatidiri, identitas pegawai, dan SDM.', 'owner' => 'Organisasi', 'badge' => 'Ready', 'href' => null],
            ['title' => 'PTIP', 'description' => 'Monitoring server, perangkat, dan SLA.', 'owner' => 'PTIP', 'badge' => 'Ready', 'href' => null],
            ['title' => 'Umum / Keuangan', 'description' => 'Inventaris, kas, dan layanan umum.', 'owner' => 'Sekretariat', 'badge' => 'Ready', 'href' => null],
            ['title' => 'Pandanaran AI', 'description' => 'Asisten internal untuk tanya jawab cepat.', 'owner' => 'AI', 'badge' => 'Beta', 'href' => null],
        ]);
    }

    public static function hearings(): array
    {
        return [
            ['time' => '09:00', 'room' => 'Ruang Sidang 1', 'case' => 'Perdata 112/Pdt.G/2026', 'judge' => 'Majelis A', 'status' => 'Sedang berlangsung'],
            ['time' => '10:00', 'room' => 'Ruang Sidang 2', 'case' => 'Pidana 18/Pid.B/2026', 'judge' => 'Majelis B', 'status' => 'Persiapan'],
            ['time' => '11:30', 'room' => 'Ruang Mediasi', 'case' => 'Mediasi 06/Mdj/2026', 'judge' => 'Mediator Internal', 'status' => 'Menunggu pihak'],
            ['time' => '13:00', 'room' => 'Ruang Sidang 1', 'case' => 'Perdata 130/Pdt.G/2026', 'judge' => 'Majelis C', 'status' => 'Terjadwal'],
        ];
    }

    public static function alerts(): array
    {
        $cacheStatus = 'Cache siap';
        if (Schema::hasTable('sipp_caches')) {
            $active = SippCache::query()->active()->count();
            $cacheStatus = $active > 0 ? 'Cache aktif ' . $active . ' entri' : 'Cache kosong - refresh untuk sinkronisasi';
        }

        return [
            ['title' => 'CCTV Lobby stabil', 'detail' => 'Stream utama latency 1.2 detik.', 'tone' => 'emerald'],
            ['title' => 'Sync SIPP aktif', 'detail' => $cacheStatus, 'tone' => 'emerald'],
            ['title' => 'Interkom aktif', 'detail' => '4 alias online di kanal operasional.', 'tone' => 'blue'],
        ];
    }

    public static function dashboardPayload(): array
    {
        $waCount = Schema::hasTable('wa_caraka_logs') ? WaCarakaLog::whereDate('created_at', today())->count() : 0;

        return [
            'appMeta' => self::appMeta(),
            'navGroups' => self::navGroups(),
            'quickActions' => self::quickActions(),
            'metrics' => self::metrics(),
            'hearings' => self::hearings(),
            'modules' => self::modules(),
            'alerts' => self::alerts(),
            'systemHealth' => [
                ['label' => 'SSO', 'value' => 'Aktif — Google OAuth', 'tone' => 'emerald'],
                ['label' => 'Reverb', 'value' => env('VITE_REVERB_ENABLED', 'false') === 'true' ? 'Real-time aktif' : 'Fallback polling', 'tone' => env('VITE_REVERB_ENABLED', 'false') === 'true' ? 'emerald' : 'amber'],
                ['label' => 'CCTV', 'value' => sprintf('%d stream aktif', CctvCamera::query()->active()->count()), 'tone' => 'emerald'],
                ['label' => 'SIPP Cache', 'value' => Schema::hasTable('sipp_caches') ? sprintf('%d cache aktif', SippCache::query()->active()->count()) : 'Inisialisasi', 'tone' => 'emerald'],
                ['label' => 'WA Caraka', 'value' => $waCount > 0 ? $waCount . ' pesan hari ini' : 'Siap terhubung', 'tone' => 'emerald'],
            ],
            'cameras' => self::cameras(limit: 4, featuredOnly: true),
            'messages' => self::messages(limit: 4),
            'channels' => self::channels(),
        ];
    }

    public static function cctvPayload(): array
    {
        return [
            'appMeta' => self::appMeta(),
            'navGroups' => self::navGroups(),
            'alerts' => self::alerts(),
            'cameras' => self::cameras(),
            'networkSummary' => [
                'cameraCount' => CctvCamera::query()->active()->count(),
                'locationCount' => CctvCamera::query()->active()->distinct('zone')->count('zone'),
                'status' => 'Jaringan stabil',
                'latency' => '1.2 detik',
            ],
        ];
    }

    public static function chatPayload(): array
    {
        return [
            'appMeta' => self::appMeta(),
            'navGroups' => self::navGroups(),
            'channels' => self::channels(),
            'aliases' => self::aliases(),
            'messages' => self::messages(),
            'quickActions' => [
                ['label' => 'Buka CCTV', 'href' => route('lawangsewu.cctv')],
                ['label' => 'Alias publik', 'href' => '#'],
                ['label' => 'Simpan template jawaban', 'href' => '#'],
            ],
        ];
    }

    public static function pilarPayload(): array
    {
        $ptspToday = Schema::hasTable('ptsp_queue_tickets')
            ? PtspQueueTicket::query()->today()->count()
            : 0;

        $sidangToday = Schema::hasTable('sidang_queue_tickets')
            ? SidangQueueTicket::query()->today()->count()
            : 0;

        $ptspWaiting = Schema::hasTable('ptsp_queue_tickets')
            ? PtspQueueTicket::query()->today()->where('status', 'waiting')->count()
            : 0;

        $sidangWaiting = Schema::hasTable('sidang_queue_tickets')
            ? SidangQueueTicket::query()->today()->where('status', 'waiting')->count()
            : 0;

        // Catalog loket PTSP dari DB (Phase 2)
        $ptspCounters = Schema::hasTable('service_counters')
            ? ServiceCounter::query()
                ->whereHas('service', fn ($q) => $q->where('code', 'ptsp_frontdesk'))
                ->orderBy('sort_order')
                ->get(['code', 'name', 'call_label', 'display_label', 'location_type', 'is_active'])
                ->map(fn ($c) => [
                    'code'         => $c->code,
                    'name'         => $c->name,
                    'callLabel'    => $c->call_label,
                    'displayLabel' => $c->display_label,
                    'locationType' => $c->location_type,
                    'isActive'     => (bool) $c->is_active,
                ])
                ->all()
            : [];

        // Catalog ruang sidang dari DB (Phase 2)
        $sidangCounters = Schema::hasTable('service_counters')
            ? ServiceCounter::query()
                ->whereHas('service', fn ($q) => $q->where('code', 'sidang'))
                ->orderBy('sort_order')
                ->get(['code', 'name', 'call_label', 'display_label', 'location_type', 'is_active'])
                ->map(fn ($c) => [
                    'code'         => $c->code,
                    'name'         => $c->name,
                    'callLabel'    => $c->call_label,
                    'displayLabel' => $c->display_label,
                    'locationType' => $c->location_type,
                    'isActive'     => (bool) $c->is_active,
                ])
                ->all()
            : [];

        return [
            'appMeta'   => self::appMeta(),
            'navGroups' => self::navGroups(),
            'overview'  => [
                'title'       => 'Pilar Antrian PASMG sebagai hub antrean terpadu',
                'description' => 'Modul ini menjadi pintu konsolidasi antrean PTSP dan sidang di dalam Lawangsewu, sesuai blueprint modular monolith untuk pelayanan satu komando.',
                'status'      => 'Phase 2 aktif — service catalog dan unified queue authority berjalan.',
            ],
            'stats' => [
                ['label' => 'Tiket PTSP hari ini',   'value' => (string) $ptspToday,     'detail' => 'Tercatat dari modul antrean PTSP Lawangsewu'],
                ['label' => 'Tiket Sidang hari ini', 'value' => (string) $sidangToday,   'detail' => 'Tercatat dari modul antrean sidang Lawangsewu'],
                ['label' => 'Menunggu PTSP',         'value' => (string) $ptspWaiting,   'detail' => 'Snapshot antrean aktif saat ini'],
                ['label' => 'Menunggu Sidang',       'value' => (string) $sidangWaiting, 'detail' => 'Snapshot antrean aktif saat ini'],
            ],
            'pillars' => [
                ['title' => 'Identity & Access',  'description' => 'Memakai SSO Lawangsewu, role, dan persetujuan akses yang sudah aktif.'],
                ['title' => 'Queue Core',         'description' => 'Menjadi otoritas tunggal status antrean untuk PTSP dan sidang — queue_tickets sebagai sumber kebenaran.'],
                ['title' => 'PTSP Services',      'description' => (count($ptspCounters) > 0 ? count($ptspCounters) . ' loket terdaftar di katalog resmi.' : 'Menaungi katalog layanan loket.') . ' Nomor antre A-xxx.'],
                ['title' => 'Hearing Services',   'description' => (count($sidangCounters) > 0 ? count($sidangCounters) . ' ruang terdaftar di katalog resmi.' : 'Mengelola antrean ruang sidang.') . ' Nomor antre S-xxx.'],
                ['title' => 'Display & Calling',  'description' => 'Menjadi rumah untuk TV publik, audio panggilan, dan printer tiket. (Phase 3)'],
                ['title' => 'Integration Layer',  'description' => 'Menjembatani sinkronisasi ke SIPP dan legacy transition tanpa query liar dari frontend. (Phase 4)'],
            ],
            'ptspCounters'   => $ptspCounters,
            'sidangCounters' => $sidangCounters,
            'legacySources'  => [
                ['name' => 'Legacy PTSP',                   'path' => '/var/www/pilarpasmg/ptsp',                                       'summary' => 'Sumber modul loket, pencetakan, kamera, dan display publik.'],
                ['name' => 'Legacy Antrian Sidang',         'path' => '/var/www/pilarpasmg/antrianpasmg',                              'summary' => 'Sumber login, flow sidang, dan tampilan antrean warisan.'],
                ['name' => 'Blueprint Pilar Antrian PASMG', 'path' => '/var/www/pilarpasmg/docs/pilarpasmg-architecture-blueprint.md', 'summary' => 'Dokumen arsitektur target modular monolith antrean.'],
            ],
            'launchers' => [
                ['label' => 'Buka Antrian PTSP',   'href' => route('lawangsewu.ptsp.index'),   'caption' => 'Gunakan modul aktif yang sudah berjalan di Lawangsewu'],
                ['label' => 'Buka Antrian Sidang', 'href' => route('lawangsewu.sidang.index'), 'caption' => 'Gunakan modul aktif yang sudah berjalan di Lawangsewu'],
                ['label' => 'Buka SIPP Hub',       'href' => route('lawangsewu.sipp.index'),   'caption' => 'Lapis integrasi data menuju sinkronisasi antrean dan jadwal'],
            ],
            'phases' => [
                ['step' => 'Phase 1 ✓', 'title' => 'Masuk sebagai hub modul',    'description' => 'Pilar Antrian PASMG tampil di Lawangsewu sebagai modul resmi dan launcher untuk fondasi antrean yang sudah ada.'],
                ['step' => 'Phase 2 ✓', 'title' => 'Samakan domain data',        'description' => 'Service catalog (loket & ruang sidang) terdaftar di DB. Queue authority aktif menyinkronkan semua tiket ke queue_tickets.'],
                ['step' => 'Phase 3',   'title' => 'Migrasi display dan calling', 'description' => 'Pindahkan TV antrean, audio panggil, kiosk, dan printer payload dari legacy ke modul inti.'],
                ['step' => 'Phase 4',   'title' => 'Integrasi SIPP terkendali',  'description' => 'Semua akses jadwal sidang dan referensi perkara masuk lewat adapter resmi, bukan query langsung dari UI.'],
            ],
        ];
    }

    // ──────────────────────────────────────────────
    // WA Caraka Module Payload
    // ──────────────────────────────────────────────

    public static function waCarakaPayload(): array
    {
        $stats = Schema::hasTable('wa_caraka_logs') ? [
            'total'    => WaCarakaLog::count(),
            'sent'     => WaCarakaLog::where('status', 'sent')->count(),
            'failed'   => WaCarakaLog::where('status', 'failed')->count(),
            'today'    => WaCarakaLog::whereDate('created_at', today())->count(),
            'lastSent' => optional(WaCarakaLog::latest()->first())?->created_at?->diffForHumans() ?? 'Belum ada',
        ] : ['total' => 0, 'sent' => 0, 'failed' => 0, 'today' => 0, 'lastSent' => 'Belum ada'];

        return [
            'appMeta'   => self::appMeta(),
            'navGroups' => self::navGroups(),
            'stats'     => $stats,
        ];
    }

    public static function cameras(?int $limit = null, bool $featuredOnly = false): array
    {
        $query = CctvCamera::query()->active()->orderBy('sort_order');

        if ($featuredOnly) {
            $query->featured();
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->map(fn (CctvCamera $camera) => self::transformCamera($camera))->all();
    }

    public static function aliases(): array
    {
        return ChatAlias::query()
            ->orderByRaw("case when status = 'online' then 0 when status = 'busy' then 1 else 2 end")
            ->orderBy('alias')
            ->get()
            ->map(fn (ChatAlias $alias) => [
                'id' => $alias->id,
                'alias' => $alias->alias,
                'department' => $alias->department,
                'status' => $alias->status,
                'accentColor' => $alias->accent_color ?: '#238636',
            ])->all();
    }

    public static function channels(): array
    {
        $catalog = collect([
            ['key' => 'interkom-umum', 'label' => 'Interkom Umum', 'description' => 'Koordinasi cepat lintas bagian.'],
            ['key' => 'ptsp', 'label' => 'PTSP', 'description' => 'Layanan front office dan loket.'],
            ['key' => 'sidang', 'label' => 'Sidang', 'description' => 'Status ruang dan jadwal sidang.'],
            ['key' => 'ptip', 'label' => 'PTIP & Server', 'description' => 'Infra, perangkat, dan koneksi.'],
        ]);

        $globalCount = ChatMessage::query()->where('type', 'global')->count();

        return $catalog->map(fn (array $channel) => [
            ...$channel,
            'count' => $channel['key'] === 'interkom-umum' ? $globalCount : 0,
        ])->all();
    }

    public static function messages(?int $limit = null): array
    {
        $query = ChatMessage::query()->with('user')->orderByDesc('created_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $messages = $query->get();

        if ($limit !== null) {
            $messages = $messages->reverse()->values();
        }

        return $messages->map(fn (ChatMessage $message) => self::transformMessage($message))->all();
    }

    public static function transformCamera(CctvCamera $camera): array
    {
        return [
            'id' => $camera->id,
            'key' => $camera->key,
            'name' => $camera->name,
            'zone' => $camera->zone,
            'iframeSrc' => $camera->iframe_src,
            'status' => $camera->is_active ? 'LIVE' : 'OFFLINE',
            'featured' => $camera->is_featured,
            'resolution' => $camera->sort_order <= 4 ? 'HD 1080p' : 'HD 720p',
            'updatedAt' => now()->setTimezone('Asia/Jakarta')->format('H:i').' WIB',
            'signal' => $camera->sort_order <= 6 ? 'Stabil' : 'Normal',
            'sortOrder' => $camera->sort_order,
        ];
    }

    public static function transformMessage(ChatMessage $message): array
    {
        $senderName = $message->user?->name ?? 'Operator';
        $senderAlias = $message->user?->alias ?: $senderName;

        return [
            'id' => $message->id,
            'type' => $message->type,
            'senderName' => $senderName,
            'alias' => $senderAlias,
            'body' => $message->content,
            'attachment' => ChatAttachment::present($message->metadata['attachment'] ?? null, $message),
            'avatar' => $message->user?->avatar,
            'sentAt' => optional($message->created_at)->setTimezone('Asia/Jakarta')->format('H:i').' WIB',
            'isOwn' => request()->user()?->id === $message->user_id,
        ];
    }
}
