@extends('guestbook.layout')

@section('title', 'Riwayat Buku Tamu')

@push('styles')
<style>
    .guest-list-shell {
        min-height: calc(100dvh - 52px);
        padding: clamp(1rem, 2vw, 1.5rem) 0 1.8rem;
        background: linear-gradient(120deg, rgba(255, 246, 228, 0.54), rgba(238, 247, 255, 0.54)), url('{{ asset('guestbook/foto-bg.png') }}') center center / cover no-repeat, #f6f9ff;
        overflow-x: clip;
    }

    .guest-list-panel {
        background: rgba(255, 255, 255, 0.8);
        border-radius: 1.3rem;
        box-shadow: 0 18px 38px rgba(16, 24, 40, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(12px);
    }

    .btn-glass {
        border: 1px solid rgba(255, 255, 255, 0.45);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.36), rgba(255, 255, 255, 0.1));
        color: #0f2747;
        border-radius: 999px;
    }

    .btn-filter-active {
        background: linear-gradient(145deg, rgba(72, 142, 255, 0.92), rgba(34, 116, 236, 0.92));
        color: #fff;
        border-color: rgba(72, 142, 255, 0.95);
    }

    .history-photo {
        width: 72px;
        height: 72px;
        border-radius: 0.75rem;
        object-fit: cover;
        border: 2px solid #edf2ff;
        background: #eef3fa;
    }

    .history-row:hover {
        background: #f8fbff;
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $canInspectGuestbook = $user && in_array($user->role, ['operator', 'admin'], true);
    $photoUrl = static function (string $id): string {
        $candidates = [
            public_path('guestbook/photos/' . $id . '.jpg'),
            public_path('guestbook/photos/' . $id . '.jpeg'),
            public_path('guestbook/photos/' . $id . '.png'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return asset('guestbook/photos/' . basename($path));
            }
        }

        return asset('guestbook/tanpafoto.jpg');
    };
@endphp

<div class="guest-list-shell">
<div class="container mt-4 pb-5">
    <section class="batik-hero mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
            <div>
                <div class="batik-kicker">Pengadilan Agama Semarang</div>
                <h1 class="batik-hero-title">Riwayat Buku Tamu</h1>
                <p class="batik-hero-subtitle">{{ $periodTitle }} untuk {{ $settings->event_name ?? 'Pendopo Pengadilan Agama Semarang' }} dengan tampilan responsif untuk pemantauan cepat dan detail data tamu.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <span class="batik-chip"><i class="bi bi-people"></i> {{ (int) ($stats['all'] ?? 0) }} total tamu</span>
                @if ($canInspectGuestbook)
                    <button type="button" class="btn btn-glass" data-bs-toggle="modal" data-bs-target="#laporanModal">
                        <i class="bi bi-printer"></i> Cetak Laporan
                    </button>
                @endif
                <a href="{{ route('lawangsewu.guestbook.form') }}" class="btn btn-glass">
                    <i class="bi bi-plus-circle"></i> Tambah Tamu
                </a>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl">
            <div class="guest-list-panel p-3 h-100">
                <div class="text-uppercase small text-muted fw-bold">Hari Ini</div>
                <div class="fs-3 fw-bold mt-1">{{ (int) ($stats['day'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="guest-list-panel p-3 h-100">
                <div class="text-uppercase small text-muted fw-bold">Minggu Ini</div>
                <div class="fs-3 fw-bold mt-1">{{ (int) ($stats['week'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="guest-list-panel p-3 h-100">
                <div class="text-uppercase small text-muted fw-bold">Bulan Ini</div>
                <div class="fs-3 fw-bold mt-1">{{ (int) ($stats['month'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="guest-list-panel p-3 h-100">
                <div class="text-uppercase small text-muted fw-bold">Tahun Ini</div>
                <div class="fs-3 fw-bold mt-1">{{ (int) ($stats['year'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-12 col-xl">
            <div class="guest-list-panel p-3 h-100">
                <div class="text-uppercase small text-muted fw-bold">Total Seluruh Tamu</div>
                <div class="fs-3 fw-bold mt-1">{{ (int) ($stats['all'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="guest-list-panel p-3 p-lg-4 mb-3">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('lawangsewu.guestbook.list', ['period' => 'day']) }}" class="btn btn-glass {{ ($period ?? 'all') === 'day' ? 'btn-filter-active' : '' }}">
                Hari Ini : {{ (int) ($stats['day'] ?? 0) }}
            </a>
            <span class="btn btn-glass disabled" aria-disabled="true">
                Minggu Ini : {{ (int) ($stats['week'] ?? 0) }}
            </span>
            <a href="{{ route('lawangsewu.guestbook.list', ['period' => 'month']) }}" class="btn btn-glass {{ ($period ?? 'all') === 'month' ? 'btn-filter-active' : '' }}">
                Bulan Ini : {{ (int) ($stats['month'] ?? 0) }}
            </a>
            <a href="{{ route('lawangsewu.guestbook.list', ['period' => 'year']) }}" class="btn btn-glass {{ ($period ?? 'all') === 'year' ? 'btn-filter-active' : '' }}">
                Tahun Ini : {{ (int) ($stats['year'] ?? 0) }}
            </a>
            <a href="{{ route('lawangsewu.guestbook.list', ['period' => 'all']) }}" class="btn btn-glass {{ ($period ?? 'all') === 'all' ? 'btn-filter-active' : '' }}">
                Semua Tamu : {{ (int) ($stats['all'] ?? 0) }}
            </a>
        </div>
    </div>

    <div class="table-responsive guest-list-panel p-3">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th width="5%">No</th>
                    <th width="14%" class="text-center">Tanggal</th>
                    <th width="12%" class="text-center">Foto</th>
                    <th>Data Tamu</th>
                    <th width="17%" class="text-end">Waktu Kunjungan</th>
                    <th width="14%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr class="history-row">
                        <td>{{ ($entries->currentPage() - 1) * $entries->perPage() + $loop->iteration }}.</td>
                        <td class="text-center">{{ \Illuminate\Support\Carbon::parse($entry->checkin)->format('d/m/Y') }}</td>
                        <td class="text-center">
                            @if ($canInspectGuestbook)
                                <a href="{{ route('lawangsewu.guestbook.detail', $entry->id) }}">
                                    <img src="{{ $photoUrl((string) $entry->id) }}" alt="Foto {{ $entry->name }}" class="history-photo">
                                </a>
                            @else
                                <img src="{{ $photoUrl((string) $entry->id) }}" alt="Foto {{ $entry->name }}" class="history-photo">
                            @endif
                        </td>
                        <td>
                            <strong>{{ $entry->name }}</strong><br>
                            <small class="text-muted">{{ $entry->position }} - {{ $entry->institution }}</small><br>
                            <small class="text-secondary">Keperluan: {{ $entry->purpose ?: '-' }}</small>
                        </td>
                        <td class="text-end">{{ \Illuminate\Support\Carbon::parse($entry->checkin)->format('d/m/Y H:i') }}</td>
                        <td class="text-center">
                            @if ($canInspectGuestbook)
                                <a href="{{ route('lawangsewu.guestbook.detail', $entry->id) }}" class="btn btn-sm btn-glass mb-1"><i class="bi bi-person-vcard"></i> Detail</a>
                                <a href="{{ route('lawangsewu.guestbook.cetak', ['id' => $entry->id, 'row' => ($entries->currentPage() - 1) * $entries->perPage() + $loop->iteration]) }}" target="_blank" class="btn btn-sm btn-glass"><i class="bi bi-printer"></i> Card</a>
                            @else
                                <span class="text-muted small">Lihat data ringkas</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center"><em>Belum Ada Data</em></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $entries->links() }}
    </div>
</div>
</div>

@if ($canInspectGuestbook)
    <div class="modal fade" id="laporanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('lawangsewu.guestbook.report') }}" method="post" target="_blank" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Cetak Laporan Bulanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Bulan</label>
                        <select name="bulan" class="form-select" required>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}" {{ now()->month === $m ? 'selected' : '' }}>{{ \Illuminate\Support\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Tahun</label>
                        <select name="tahun" class="form-select" required>
                            @for ($y = (int) date('Y'); $y >= 2019; $y--)
                                <option value="{{ $y }}" {{ (int) date('Y') === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-glass"><i class="bi bi-file-earmark-text"></i> Preview</button>
                    <button type="submit" formaction="{{ route('lawangsewu.guestbook.report', ['export' => 'xls']) }}" class="btn btn-glass"><i class="bi bi-file-earmark-spreadsheet"></i> Export XLS</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
