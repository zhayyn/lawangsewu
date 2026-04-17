@extends('guestbook.layout')

@section('title', 'Antrian PTSP')

@push('styles')
<style>
    .ptsp-shell {
        min-height: calc(100dvh - 52px);
        padding: 1.25rem 0 1.6rem;
        background: linear-gradient(125deg, rgba(236, 249, 255, 0.62), rgba(255, 248, 232, 0.55));
    }

    .ptsp-panel {
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid rgba(255, 255, 255, 0.58);
        border-radius: 1.25rem;
        box-shadow: 0 18px 38px rgba(16, 24, 40, 0.11);
        backdrop-filter: blur(12px);
    }

    .summary-card {
        border-radius: 1rem;
        border: 1px solid rgba(18, 51, 79, 0.08);
        background: rgba(18, 51, 79, 0.04);
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
    .badge-status.served { background: rgba(99, 102, 241, 0.14); color: #3730a3; }
    .badge-status.skipped { background: rgba(245, 158, 11, 0.18); color: #9a5a02; }

    .btn-glass {
        border: 1px solid rgba(255, 255, 255, 0.45);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.36), rgba(255, 255, 255, 0.1));
        color: #0f2747;
        border-radius: 999px;
    }

    .active-call {
        border-radius: 1rem;
        border: 1px solid rgba(20, 184, 166, 0.24);
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(56, 189, 248, 0.08));
        padding: 1rem;
    }
</style>
@endpush

@section('content')
<div class="ptsp-shell">
    <div class="container">
        <section class="batik-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <div class="batik-kicker">Pelayanan Terpadu Satu Pintu</div>
                    <h1 class="batik-hero-title">Antrian PTSP</h1>
                    <p class="batik-hero-subtitle">Kelola nomor antrian, panggil tiket, dan tandai selesai layanan secara real-time operasional.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-start">
                    <a class="btn btn-glass" href="{{ route('lawangsewu.guestbook.form') }}"><i class="bi bi-book-half"></i> Buku Tamu</a>
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
                <div class="summary-card"><div class="text-muted small">Selesai</div><div class="value">{{ $summary['served'] }}</div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="summary-card"><div class="text-muted small">Dilewati</div><div class="value">{{ $summary['skipped'] }}</div></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="ptsp-panel p-3 p-lg-4 mb-3">
                    <h5 class="mb-3">Tambah Nomor Antrian</h5>
                    <form action="{{ route('lawangsewu.ptsp.store') }}" method="post" class="vstack gap-3">
                        @csrf
                        <div>
                            <label class="form-label">Loket</label>
                            <select class="form-select" name="service_desk" required>
                                <option value="PTSP-1">PTSP-1</option>
                                <option value="PTSP-2">PTSP-2</option>
                                <option value="PTSP-3">PTSP-3</option>
                                <option value="PTSP-4">PTSP-4</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Nama Tamu (opsional)</label>
                            <input class="form-control" name="visitor_name" maxlength="120" placeholder="Nama pengunjung">
                        </div>
                        <div>
                            <label class="form-label">Keperluan (opsional)</label>
                            <input class="form-control" name="purpose" maxlength="255" placeholder="Contoh: Legalisir dokumen">
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle"></i> Ambil Nomor Baru</button>
                    </form>
                </div>

                <div class="ptsp-panel p-3 p-lg-4">
                    <h6 class="mb-2">Panggilan Aktif</h6>
                    @if ($activeCall)
                        <div class="active-call">
                            <div class="text-muted small mb-1">Sedang dipanggil</div>
                            <div class="h4 mb-0">{{ $activeCall->ticket_number }}</div>
                            <div class="small text-muted">{{ $activeCall->service_desk }} · {{ optional($activeCall->called_at)->format('H:i:s') }}</div>
                        </div>
                    @else
                        <div class="text-muted small">Belum ada nomor yang sedang dipanggil.</div>
                    @endif
                </div>
            </div>

            <div class="col-lg-8">
                <div class="ptsp-panel p-3 p-lg-4">
                    <h5 class="mb-3">Daftar Antrian Hari Ini</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Loket</th>
                                    <th>Nama / Keperluan</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($todayTickets as $ticket)
                                    <tr>
                                        <td><strong>{{ $ticket->ticket_number }}</strong></td>
                                        <td>{{ $ticket->service_desk }}</td>
                                        <td>
                                            <div>{{ $ticket->visitor_name ?: '-' }}</div>
                                            <small class="text-muted">{{ $ticket->purpose ?: '-' }}</small>
                                        </td>
                                        <td><span class="badge-status {{ $ticket->status }}">{{ $ticket->status }}</span></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                @if (in_array($ticket->status, ['waiting', 'skipped'], true))
                                                    <form action="{{ route('lawangsewu.ptsp.call', $ticket) }}" method="post">@csrf <button class="btn btn-sm btn-outline-primary">Panggil</button></form>
                                                @endif
                                                @if ($ticket->status === 'called')
                                                    <form action="{{ route('lawangsewu.ptsp.serve', $ticket) }}" method="post">@csrf <button class="btn btn-sm btn-outline-success">Layani</button></form>
                                                    <form action="{{ route('lawangsewu.ptsp.skip', $ticket) }}" method="post">@csrf <button class="btn btn-sm btn-outline-warning">Lewati</button></form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">Belum ada antrian hari ini.</td></tr>
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
