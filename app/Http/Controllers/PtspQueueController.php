<?php

namespace App\Http\Controllers;

use App\Models\PtspQueueTicket;
use App\Models\ServiceCounter;
use App\Services\PilarQueueAuthority;
use App\Support\LawangsewuPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PtspQueueController extends Controller
{
    public function __construct(
        private readonly PilarQueueAuthority $queueAuthority,
    ) {
    }

    public function index(Request $request): Response
    {
        $todayTickets = PtspQueueTicket::query()
            ->today()
            ->orderByRaw("case when status = 'called' then 0 when status = 'waiting' then 1 when status = 'served' then 2 else 3 end")
            ->orderBy('id')
            ->get()
            ->map(fn (PtspQueueTicket $t) => [
                'id'           => $t->id,
                'ticket_number' => $t->ticket_number,
                'service_desk' => $t->service_desk,
                'visitor_name' => $t->visitor_name,
                'purpose'      => $t->purpose,
                'status'       => $t->status,
                'called_at'    => optional($t->called_at)?->setTimezone('Asia/Jakarta')->format('H:i:s'),
            ])
            ->values()
            ->all();

        $activeCall = PtspQueueTicket::query()->today()->where('status', 'called')->first();
        $counters   = ServiceCounter::query()
            ->whereHas('service', fn ($q) => $q->where('code', 'ptsp_frontdesk'))
            ->orderBy('sort_order')
            ->get(['id', 'name', 'call_label', 'display_label', 'is_active'])
            ->all();

        $user = $request->user();
        $canOperate = $user && in_array($user->role ?? '', ['operator', 'admin']) || ($user?->is_superadmin ?? false);

        return Inertia::render('Lawangsewu/PtspQueue', [
            'appMeta'      => LawangsewuPortal::appMeta(),
            'navGroups'    => LawangsewuPortal::navGroups(),
            'todayTickets' => $todayTickets,
            'activeCall'   => $activeCall ? [
                'id'           => $activeCall->id,
                'ticket_number' => $activeCall->ticket_number,
                'service_desk' => $activeCall->service_desk,
                'called_at'    => optional($activeCall->called_at)?->setTimezone('Asia/Jakarta')->format('H:i:s'),
            ] : null,
            'summary' => [
                'waiting'  => collect($todayTickets)->where('status', 'waiting')->count(),
                'called'   => collect($todayTickets)->where('status', 'called')->count(),
                'served'   => collect($todayTickets)->where('status', 'served')->count(),
                'skipped'  => collect($todayTickets)->where('status', 'skipped')->count(),
            ],
            'counters'   => $counters,
            'canOperate' => $canOperate,
            'flash'      => ['status' => $request->session()->get('status')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'service_desk' => ['required', 'string', 'max:30'],
            'visitor_name' => ['nullable', 'string', 'max:120'],
            'purpose'      => ['nullable', 'string', 'max:255'],
        ]);

        $today      = today();
        $todayCount = PtspQueueTicket::query()->whereDate('queue_date', $today)->count() + 1;
        $ticketNumber = 'A-' . str_pad((string) $todayCount, 3, '0', STR_PAD_LEFT);

        $ticket = PtspQueueTicket::query()->create([
            'ticket_number' => $ticketNumber,
            'queue_date'    => $today,
            'service_desk'  => $payload['service_desk'],
            'visitor_name'  => $payload['visitor_name'] ?: null,
            'purpose'       => $payload['purpose'] ?: null,
            'status'        => 'waiting',
        ]);

        $this->queueAuthority->syncPtspTicket($ticket, $request->user()?->id);

        return redirect()
            ->route('lawangsewu.ptsp.index')
            ->with('status', 'Nomor antrian baru berhasil dibuat.');
    }

    public function call(PtspQueueTicket $ticket): RedirectResponse
    {
        PtspQueueTicket::query()
            ->whereDate('queue_date', $ticket->queue_date)
            ->where('status', 'called')
            ->update(['status' => 'waiting', 'called_at' => null]);

        $ticket->update([
            'status'    => 'called',
            'called_at' => now(),
        ]);

        $this->queueAuthority->syncPtspTicket($ticket->fresh(), request()->user()?->id);

        return redirect()
            ->route('lawangsewu.ptsp.index')
            ->with('status', 'Nomor ' . $ticket->ticket_number . ' dipanggil ke loket ' . $ticket->service_desk . '.');
    }

    public function serve(PtspQueueTicket $ticket): RedirectResponse
    {
        $ticket->update([
            'status'    => 'served',
            'served_at' => now(),
        ]);

        $this->queueAuthority->syncPtspTicket($ticket->fresh(), request()->user()?->id);

        return redirect()
            ->route('lawangsewu.ptsp.index')
            ->with('status', 'Nomor ' . $ticket->ticket_number . ' telah dilayani.');
    }

    public function skip(PtspQueueTicket $ticket): RedirectResponse
    {
        $ticket->update([
            'status' => 'skipped',
            'notes'  => 'Tidak hadir saat dipanggil.',
        ]);

        $this->queueAuthority->syncPtspTicket($ticket->fresh(), request()->user()?->id);

        return redirect()
            ->route('lawangsewu.ptsp.index')
            ->with('status', 'Nomor ' . $ticket->ticket_number . ' dilewati.');
    }
}
