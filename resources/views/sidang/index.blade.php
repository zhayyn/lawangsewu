@extends('guestbook.layout')

@section('title', 'Antrian Sidang')

@push('styles')
<style>
    .sidang-shell {
        min-height: calc(100dvh - 52px);
        padding: 1.25rem 0 1.6rem;
        background: radial-gradient(circle at top right, rgba(255, 232, 232, 0.52), transparent 42%),
            linear-gradient(120deg, rgba(232, 244, 255, 0.62), rgba(255, 249, 236, 0.55));
    }

    .sidang-panel {
        background: rgba(255, 255, 255, 0.86);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 1.2rem;
        box-shadow: 0 20px 36px rgba(17, 24, 39, 0.11);
        backdrop-filter: blur(12px);
    }

    .summary-card {
        border-radius: 1rem;
        border: 1px solid rgba(56, 34, 16, 0.1);
        background: rgba(56, 34, 16, 0.04);
        padding: 0.9rem 1rem;
    }

    .summary-card .value {
        font-size: 1.5rem;
        font-weight: 800;
    }

    .badge-status {
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 0.3rem 0.62rem;
    }

    .badge-status.waiting { background: rgba(59, 130, 246, 0.14); color: #1f5aa8; }
    .badge-status.called { background: rgba(16, 185, 129, 0.16); color: #087a55; }
    .badge-status.completed { background: rgba(99, 102, 241, 0.14); color: #3730a3; }
    .badge-status.postponed { background: rgba(245, 158, 11, 0.18); color: #9a5a02; }

    .btn-glass {
        border: 1px solid rgba(255, 255, 255, 0.45);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.36), rgba(255, 255, 255, 0.1));
        color: #0f2747;
        border-radius: 999px;
    }

    .active-call {
        border-radius: 1rem;
        border: 1px solid rgba(147, 51, 234, 0.24);
        background: linear-gradient(135deg, rgba(129, 140, 248, 0.14), rgba(244, 114, 182, 0.1));
        padding: 1rem;
    }
</style>
@endpush

@section('content')
<div class="sidang-shell">
    <div class="container">
        <section class="batik-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <div class="batik-kicker">Kepaniteraan Persidangan</div>
                    <h1 class="batik-hero-title">Antrian Sidang</h1>
                    <p class="batik-hero-subtitle">Kelola antrean pemanggilan sidang per ruang sidang secara cepat dan terstruktur.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-start">
                    <a class="btn btn-glass" href="{{ route('lawangsewu.ptsp.index') }}"><i class="bi bi-people"></i> Antrian PTSP</a>
                    <a class="btn btn-glass" href="{{ route('lawangsewu.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </div>
            </div>
        </section>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="summary-card"><div class="text-muted small">Menunggu</div><div class="value">{{ $summary['waiting'] }}</div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="summary-card"><div class="text-muted small">Dipanggil</div><div class="value">{{ $summary['called'] }}</div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="summary-card"><div class="text-muted small">Selesai</div><div class="value">{{ $summary['completed'] }}</div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="summary-card"><div class="text-muted small">Ditunda</div><div class="value">{{ $summary['postponed'] }}</div></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="sidang-panel p-3 p-lg-4 mb-3">
                    <h5 class="mb-3">Tambah Antrian Sidang</h5>
                    <form action="{{ route('lawangsewu.sidang.store') }}" method="post" class="vstack gap-3">
                        @csrf
                        <div>
                            <label class="form-label">Nomor Perkara</label>
                            <input class="form-control" name="hearing_number" maxlength="80" required placeholder="Contoh: 112/Pdt.G/2026/PA.Smg">
                        </div>
                        <div>
                            <label class="form-label">Ruang Sidang</label>
                            <select class="form-select" name="courtroom" required>
                                <option value="Ruang Sidang 1">Ruang Sidang 1</option>
                                <option value="Ruang Sidang 2">Ruang Sidang 2</option>
                                <option value="Ruang Sidang 3">Ruang Sidang 3</option>
                                <option value="Ruang Mediasi">Ruang Mediasi</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Pihak (opsional)</label>
                            <input class="form-control" name="parties" maxlength="255" placeholder="Penggugat vs Tergugat">
                        </div>
                        <div>
                            <label class="form-label">Jam Sidang (opsional)</label>
                            <input class="form-control" type="time" name="hearing_time">
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle"></i> Tambah Antrian</button>
                    </form>
                </div>

                <div class="sidang-panel p-3 p-lg-4">
                    <h6 class="mb-2">Panggilan Aktif</h6>
                    @if ($activeCall)
                        <div class="active-call">
                            <div class="text-muted small mb-1">Sedang dipanggil</div>
                            <div class="h4 mb-0">{{ $activeCall->ticket_number }}</div>
                            <div class="small text-muted">{{ $activeCall->courtroom }} · {{ optional($activeCall->called_at)->format('H:i:s') }}</div>
                            <div class="small mt-2">{{ $activeCall->hearing_number }}</div>
                        </div>
                    @else
                        <div class="text-muted small">Belum ada perkara yang sedang dipanggil.</div>
                    @endif
                </div>
            </div>

            <div class="col-lg-8">
                <div class="sidang-panel p-3 p-lg-4">
                    <h5 class="mb-3">Daftar Antrian Sidang Hari Ini</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Perkara</th>
                                    <th>Ruang / Jam</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($todayTickets as $ticket)
                                    <tr>
                                        <td><strong>{{ $ticket->ticket_number }}</strong></td>
                                        <td>
                                            <div>{{ $ticket->hearing_number }}</div>
                                            <small class="text-muted">{{ $ticket->parties ?: '-' }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $ticket->courtroom }}</div>
                                            <small class="text-muted">{{ optional($ticket->hearing_time)->format('H:i') ?: '-' }} WIB</small>
                                        </td>
                                        <td><span class="badge-status {{ $ticket->status }}">{{ $ticket->status }}</span></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                @if (in_array($ticket->status, ['waiting', 'postponed'], true))
                                                    <form action="{{ route('lawangsewu.sidang.call', $ticket) }}" method="post">@csrf <button class="btn btn-sm btn-outline-primary">Panggil</button></form>
                                                @endif
                                                @if ($ticket->status === 'called')
                                                    <form action="{{ route('lawangsewu.sidang.complete', $ticket) }}" method="post">@csrf <button class="btn btn-sm btn-outline-success">Selesai</button></form>
                                                    <form action="{{ route('lawangsewu.sidang.postpone', $ticket) }}" method="post">@csrf <button class="btn btn-sm btn-outline-warning">Tunda</button></form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Belum ada antrean sidang hari ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
