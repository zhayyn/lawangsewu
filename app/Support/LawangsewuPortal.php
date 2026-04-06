<?php

namespace App\Support;

use App\Models\ChatAlias;
use App\Models\ChatMessage;
use App\Models\CctvCamera;

class LawangsewuPortal
{
    public static function appMeta(): array
    {
        return [
            'name' => 'Lawangsewu V2',
            'tagline' => 'Ekosistem digital internal PA Semarang',
            'sprint' => 'Sprint 1: Chatroom, CCTV, dan dashboard integrasi.',
            'status' => 'Semua layanan inti berjalan normal.',
        ];
    }

    public static function navGroups(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'items' => [
                    [
                        'label' => 'Dashboard Utama',
                        'short' => 'DB',
                        'routeKey' => 'dashboard',
                        'href' => route('lawangsewu.dashboard'),
                        'badge' => 'Live',
                    ],
                ],
            ],
            [
                'label' => 'Pelayanan',
                'items' => [
                    ['label' => 'Buku Tamu', 'short' => 'BT', 'routeKey' => 'satellite.pendopo', 'href' => route('lawangsewu.satellite.pendopo'), 'badge' => 'Pendopo'],
                    ['label' => 'Antrian PTSP', 'short' => 'PT', 'routeKey' => 'ptsp', 'href' => null, 'badge' => 'Soon'],
                    ['label' => 'Antrian Sidang', 'short' => 'SD', 'routeKey' => 'sidang', 'href' => null, 'badge' => 'Soon'],
                ],
            ],
            [
                'label' => 'SIPP Hub & Data',
                'items' => [
                    ['label' => 'Monitoring CCTV', 'short' => 'CV', 'routeKey' => 'cctv', 'href' => route('lawangsewu.cctv'), 'badge' => '19'],
                    ['label' => 'Chat Internal', 'short' => 'CH', 'routeKey' => 'chat', 'href' => route('lawangsewu.chat'), 'badge' => 'Live'],
                    ['label' => 'SIPP Hub', 'short' => 'SP', 'routeKey' => 'sipp', 'href' => null, 'badge' => 'Next'],
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
    }

    public static function quickActions(): array
    {
        return [
            ['label' => 'Buka CCTV', 'href' => route('lawangsewu.cctv'), 'tone' => 'accent'],
            ['label' => 'Buka Chat', 'href' => route('lawangsewu.chat'), 'tone' => 'neutral'],
            ['label' => 'Sinkronisasi SIPP', 'href' => '#', 'tone' => 'neutral'],
        ];
    }

    public static function metrics(): array
    {
        return [
            ['title' => 'Antrian PTSP', 'value' => '34', 'trend' => '-5 dari jam 09:30', 'detail' => '3 loket aktif, 1 loket cadangan', 'tone' => 'blue'],
            ['title' => 'Sidang Hari Ini', 'value' => '12', 'trend' => '3 sedang berlangsung', 'detail' => 'Perdata 7, Pidana 5', 'tone' => 'violet'],
            ['title' => 'Buku Tamu', 'value' => '28', 'trend' => '8 tamu internal', 'detail' => 'Puncak kunjungan pukul 10:00', 'tone' => 'amber'],
            ['title' => 'Sinkronisasi SIPP', 'value' => '98%', 'trend' => 'Terakhir 08:12 WIB', 'detail' => 'Widget cache siap dimuat', 'tone' => 'emerald'],
        ];
    }

    public static function modules(): array
    {
        return [
            ['title' => 'Buku Tamu', 'description' => 'Registrasi tamu dan kehadiran harian.', 'owner' => 'Pelayanan', 'badge' => 'Sprint 2', 'href' => null],
            ['title' => 'Antrian PTSP', 'description' => 'Manajemen loket dan nomor antre.', 'owner' => 'PTSP', 'badge' => 'Sprint 2', 'href' => null],
            ['title' => 'Antrian Sidang', 'description' => 'Panggilan sidang dan status ruang.', 'owner' => 'Kepaniteraan', 'badge' => 'Sprint 2', 'href' => null],
            ['title' => 'SIPP Hub', 'description' => 'Widget statistik dan cache sinkron.', 'owner' => 'Data', 'badge' => 'Sprint 3', 'href' => null],
            ['title' => 'Kepegawaian', 'description' => 'Jatidiri, identitas pegawai, dan SDM.', 'owner' => 'Organisasi', 'badge' => 'Sprint 4', 'href' => null],
            ['title' => 'PTIP', 'description' => 'Monitoring server, perangkat, dan SLA.', 'owner' => 'PTIP', 'badge' => 'Sprint 4', 'href' => null],
            ['title' => 'Umum / Keuangan', 'description' => 'Inventaris, kas, dan layanan umum.', 'owner' => 'Sekretariat', 'badge' => 'Sprint 4', 'href' => null],
            ['title' => 'Pandanaran AI', 'description' => 'Asisten internal untuk tanya jawab cepat.', 'owner' => 'AI', 'badge' => 'Beta', 'href' => null],
        ];
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
        return [
            ['title' => 'CCTV Lobby stabil', 'detail' => 'Stream utama latency 1.2 detik.', 'tone' => 'emerald'],
            ['title' => 'Sync SIPP tertunda ringan', 'detail' => 'Cache statistik akan diperbarui 15 menit lagi.', 'tone' => 'amber'],
            ['title' => 'Interkom aktif', 'detail' => '4 alias online di kanal operasional.', 'tone' => 'blue'],
        ];
    }

    public static function dashboardPayload(): array
    {
        return [
            'appMeta' => self::appMeta(),
            'navGroups' => self::navGroups(),
            'quickActions' => self::quickActions(),
            'metrics' => self::metrics(),
            'hearings' => self::hearings(),
            'modules' => self::modules(),
            'alerts' => self::alerts(),
            'systemHealth' => [
                ['label' => 'SSO', 'value' => 'Siap integrasi', 'tone' => 'emerald'],
                ['label' => 'Reverb', 'value' => 'Ready untuk real-time', 'tone' => 'blue'],
                ['label' => 'CCTV', 'value' => sprintf('%d stream aktif', CctvCamera::query()->active()->count()), 'tone' => 'emerald'],
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
            'avatar' => $message->user?->avatar,
            'sentAt' => optional($message->created_at)->setTimezone('Asia/Jakarta')->format('H:i').' WIB',
            'isOwn' => request()->user()?->id === $message->user_id,
        ];
    }
}
