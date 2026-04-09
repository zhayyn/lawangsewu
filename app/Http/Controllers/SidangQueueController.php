<?php

namespace App\Http\Controllers;

use App\Models\ServiceCounter;
use App\Models\SidangQueueTicket;
use App\Services\PilarQueueAuthority;
use App\Support\LawangsewuPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SidangQueueController extends Controller
{
    public function __construct(
        private readonly PilarQueueAuthority $queueAuthority,
    ) {
    }

    public function index(Request $request): Response
    {
        $todayTickets = SidangQueueTicket::query()
            ->today()
            ->orderByRaw("case when status = 'called' then 0 when status = 'waiting' then 1 when status = 'completed' then 2 else 3 end")
            ->orderByRaw('coalesce(hearing_time, created_at) asc')
            ->get()
            ->map(fn (SidangQueueTicket $t) => [
                'id'              => $t->id,
                'ticket_number'   => $t->ticket_number,
                'hearing_number'  => $t->hearing_number,
                'courtroom'       => $t->courtroom,
                'parties'         => $t->parties,
                'hearing_time_label' => optional($t->hearing_time)?->setTimezone('Asia/Jakarta')->format('H:i'),
                'status'          => $t->status,
                'called_at'       => optional($t->called_at)?->setTimezone('Asia/Jakarta')->format('H:i:s'),
            ])
            ->values()
            ->all();

        $activeCall = SidangQueueTicket::query()->today()->where('status', 'called')->first();
        $counters   = ServiceCounter::query()
            ->whereHas('service', fn ($q) => $q->where('code', 'sidang'))
            ->orderBy('sort_order')
            ->get(['id', 'name', 'call_label', 'display_label', 'is_active'])
            ->all();

        $user = $request->user();
        $canOperate = $user && in_array($user->role ?? '', ['operator', 'admin']) || ($user?->is_superadmin ?? false);

        return Inertia::render('Lawangsewu/SidangQueue', [
            'appMeta'      => LawangsewuPortal::appMeta(),
            'navGroups'    => LawangsewuPortal::navGroups(),
            'todayTickets' => $todayTickets,
            'activeCall'   => $activeCall ? [
                'id'             => $activeCall->id,
                'ticket_number'  => $activeCall->ticket_number,
                'courtroom'      => $activeCall->courtroom,
                'hearing_number' => $activeCall->hearing_number,
                'called_at'      => optional($activeCall->called_at)?->setTimezone('Asia/Jakarta')->format('H:i:s'),
            ] : null,
            'summary' => [
                'waiting'   => collect($todayTickets)->where('status', 'waiting')->count(),
                'called'    => collect($todayTickets)->where('status', 'called')->count(),
                'completed' => collect($todayTickets)->where('status', 'completed')->count(),
                'postponed' => collect($todayTickets)->where('status', 'postponed')->count(),
            ],
            'counters'   => $counters,
            'canOperate' => $canOperate,
            'flash'      => ['status' => $request->session()->get('status')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'hearing_number' => ['required', 'string', 'max:80'],
            'courtroom'      => ['required', 'string', 'max:50'],
            'parties'        => ['nullable', 'string', 'max:255'],
            'hearing_time'   => ['nullable', 'date_format:H:i'],
        ]);

        $today      = today();
        $todayCount = SidangQueueTicket::query()->whereDate('queue_date', $today)->count() + 1;
        $ticketNumber = 'S-' . str_pad((string) $todayCount, 3, '0', STR_PAD_LEFT);

        $ticket = SidangQueueTicket::query()->create([
            'ticket_number'  => $ticketNumber,
            'queue_date'     => $today,
            'hearing_number' => $payload['hearing_number'],
            'courtroom'      => $payload['courtroom'],
            'parties'        => $payload['parties'] ?: null,
            'hearing_time'   => isset($payload['hearing_time']) && $payload['hearing_time'] !== null
                ? now()->setTimeFromTimeString($payload['hearing_time'])
                : null,
            'status'         => 'waiting',
        ]);

        $this->queueAuthority->syncSidangTicket($ticket, $request->user()?->id);

        return redirect()
            ->route('lawangsewu.sidang.index')
            ->with('status', 'Antrian sidang baru berhasil dibuat.');
    }

    public function call(SidangQueueTicket $ticket): RedirectResponse
    {
        SidangQueueTicket::query()
            ->whereDate('queue_date', $ticket->queue_date)
            ->where('status', 'called')
            ->update(['status' => 'waiting', 'called_at' => null]);

        $ticket->update([
            'status'    => 'called',
            'called_at' => now(),
        ]);

        $this->queueAuthority->syncSidangTicket($ticket->fresh(), request()->user()?->id);

        return redirect()
            ->route('lawangsewu.sidang.index')
            ->with('status', 'Nomor ' . $ticket->ticket_number . ' dipanggil ke ' . $ticket->courtroom . '.');
    }

    public function complete(SidangQueueTicket $ticket): RedirectResponse
    {
        $ticket->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        $this->queueAuthority->syncSidangTicket($ticket->fresh(), request()->user()?->id);

        return redirect()
            ->route('lawangsewu.sidang.index')
            ->with('status', 'Nomor ' . $ticket->ticket_number . ' selesai disidangkan.');
    }

    public function postpone(SidangQueueTicket $ticket): RedirectResponse
    {
        $ticket->update([
            'status' => 'postponed',
            'notes'  => 'Ditunda untuk pemanggilan berikutnya.',
        ]);

        $this->queueAuthority->syncSidangTicket($ticket->fresh(), request()->user()?->id);

        return redirect()
            ->route('lawangsewu.sidang.index')
            ->with('status', 'Nomor ' . $ticket->ticket_number . ' ditunda.');
    }
}
