@extends('guestbook.layout')

@section('title', 'Riwayat Buku Tamu')

@push('styles')
<style>
    .guest-list-shell {
        position: relative;
        min-height: calc(100dvh - 52px);
        padding: clamp(1rem, 2vw, 1.5rem) 0 1.8rem;
        background: linear-gradient(120deg, rgba(255, 246, 228, 0.54), rgba(238, 247, 255, 0.54)), url('{{ asset('guestbook/foto-bg.png') }}') center center / cover no-repeat, #f6f9ff;
        overflow-x: clip;
    }

    .guest-list-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.24), rgba(255, 255, 255, 0.06));
        pointer-events: none;
    }

    .guest-list-shell > .container {
        position: relative;
        z-index: 1;
    }

    .guest-list-panel {
        background: rgba(255, 255, 255, 0.78);
        border-radius: 1.4rem;
        box-shadow: 0 18px 38px rgba(16, 24, 40, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.52);
        backdrop-filter: blur(14px);
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
        border-radius: 0.9rem;
        object-fit: cover;
        border: 2px solid #edf2ff;
        background: #eef3fa;
        box-shadow: 0 10px 24px rgba(18, 38, 63, 0.12);
    }

    .guest-history-table {
        --bs-table-bg: transparent;
        --bs-table-striped-bg: rgba(247, 251, 255, 0.74);
        --bs-table-hover-bg: rgba(238, 246, 255, 0.92);
        margin-bottom: 0;
    }

    .guest-history-table thead th {
        background: linear-gradient(135deg, rgba(15, 39, 71, 0.94), rgba(28, 90, 138, 0.9));
        color: #f4f8ff;
        border: 0;
        font-size: 0.77rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    .guest-history-table tbody td {
        padding-top: 1rem;
        padding-bottom: 1rem;
        border-color: rgba(18, 51, 79, 0.08);
        vertical-align: middle;
    }

    .history-row:hover {
        background: #f8fbff;
    }

    .history-primary {
        display: block;
        font-size: 1rem;
        font-weight: 700;
        color: #173754;
    }

    .history-secondary {
        display: block;
        margin-top: 0.2rem;
        color: #5f7489;
        font-size: 0.92rem;
    }

    .history-purpose {
        display: inline-flex;
        align-items: center;
        margin-top: 0.55rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: rgba(18, 51, 79, 0.06);
        color: #365874;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .guest-table-actions {
        display: inline-flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.45rem;
    }

    .guest-pagination-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.9rem;
        margin-top: 1rem;
        padding: 1rem 1.1rem;
    }

    .guest-pagination-meta {
        color: #49627d;
        font-size: 0.92rem;
        font-weight: 600;
    }

    .guest-pagination {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
    }

    .guest-page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 0.8rem;
        border-radius: 999px;
        border: 1px solid rgba(18, 51, 79, 0.12);
        background: rgba(255, 255, 255, 0.86);
        color: #12334f;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        box-shadow: 0 8px 18px rgba(18, 38, 63, 0.08);
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .guest-page-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 22px rgba(18, 38, 63, 0.12);
        background: #ffffff;
        color: #12334f;
    }

    .guest-page-link.is-active {
        background: linear-gradient(145deg, rgba(72, 142, 255, 0.96), rgba(34, 116, 236, 0.96));
        border-color: rgba(72, 142, 255, 1);
        color: #fff;
        box-shadow: 0 12px 24px rgba(34, 116, 236, 0.22);
    }

    .guest-page-link.is-disabled {
        opacity: 0.45;
        pointer-events: none;
        box-shadow: none;
    }

    .guest-page-link.is-nav {
        padding-left: 0.95rem;
        padding-right: 0.95rem;
    }

    .guest-page-ellipsis {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 40px;
        color: #6b8095;
        font-weight: 700;
    }

    @media (max-width: 991.98px) {
        .guest-history-table thead th:nth-child(2),
        .guest-history-table tbody td:nth-child(2),
        .guest-history-table thead th:nth-child(5),
        .guest-history-table tbody td:nth-child(5) {
            white-space: nowrap;
        }
    }

    @media (max-width: 767.98px) {
        .guest-list-shell {
            padding-bottom: 1.1rem;
        }

        .guest-pagination-wrap {
            justify-content: center;
            text-align: center;
            padding: 0.9rem;
        }

        .guest-pagination-meta {
            width: 100%;
            font-size: 0.85rem;
        }

        .guest-pagination {
            justify-content: center;
            gap: 0.28rem;
        }

        .guest-page-link {
            min-width: 34px;
            height: 34px;
            padding: 0 0.58rem;
            font-size: 0.82rem;
        }

        .guest-page-link.is-nav {
            font-size: 0.78rem;
            padding-left: 0.72rem;
            padding-right: 0.72rem;
        }

        .guest-page-ellipsis {
            min-width: 18px;
            height: 34px;
        }

        .history-photo {
            width: 58px;
            height: 58px;
        }

        .guest-table-actions {
            flex-direction: column;
            width: 100%;
        }

        .guest-table-actions .btn {
            width: 100%;
        }

        .history-primary {
            font-size: 0.94rem;
        }

        .history-secondary {
            font-size: 0.84rem;
        }

        .history-purpose {
            font-size: 0.76rem;
            line-height: 1.35;
        }
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $canInspectGuestbook = $user && in_array($user->role, ['operator', 'admin'], true);
    $photoUrl = static function (string $id): string {
        foreach (['jpg', 'jpeg', 'png'] as $ext) {
            $storageRelative = 'guestbook/photos/' . $id . '.' . $ext;
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($storageRelative)) {
                return asset('storage/' . $storageRelative);
            }

            $legacyPath = public_path('guestbook/photos/' . $id . '.' . $ext);
            if (is_file($legacyPath)) {
                return asset('guestbook/photos/' . $id . '.' . $ext);
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
        <table class="table guest-history-table align-middle">
            <thead>
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
                            <span class="history-primary">{{ $entry->name }}</span>
                            <span class="history-secondary">{{ $entry->position }} - {{ $entry->institution }}</span>
                            <span class="history-purpose">Keperluan: {{ $entry->purpose ?: '-' }}</span>
                        </td>
                        <td class="text-end">{{ \Illuminate\Support\Carbon::parse($entry->checkin)->format('d/m/Y H:i') }}</td>
                        <td class="text-center">
                            @if ($canInspectGuestbook)
                                <div class="guest-table-actions">
                                    <a href="{{ route('lawangsewu.guestbook.detail', $entry->id) }}" class="btn btn-sm btn-glass"><i class="bi bi-person-vcard"></i> Detail</a>
                                    <a href="{{ route('lawangsewu.guestbook.cetak', ['id' => $entry->id, 'row' => ($entries->currentPage() - 1) * $entries->perPage() + $loop->iteration]) }}" target="_blank" class="btn btn-sm btn-glass"><i class="bi bi-printer"></i> Card</a>
                                </div>
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

    @if ($entries->hasPages())
        @php
            $startPage = max(1, $entries->currentPage() - 1);
            $endPage = min($entries->lastPage(), $entries->currentPage() + 1);

            if ($entries->currentPage() <= 2) {
                $endPage = min($entries->lastPage(), 3);
            }

            if ($entries->currentPage() >= $entries->lastPage() - 1) {
                $startPage = max(1, $entries->lastPage() - 2);
            }
        @endphp
        <div class="guest-list-panel guest-pagination-wrap">
            <div class="guest-pagination-meta">
                Menampilkan {{ $entries->firstItem() ?? 0 }}-{{ $entries->lastItem() ?? 0 }} dari {{ $entries->total() }} tamu
            </div>
            <nav class="guest-pagination" aria-label="Navigasi halaman daftar tamu">
                <a href="{{ $entries->onFirstPage() ? '#' : $entries->previousPageUrl() }}" class="guest-page-link is-nav {{ $entries->onFirstPage() ? 'is-disabled' : '' }}" aria-label="Halaman sebelumnya">Sblm</a>

                @if ($startPage > 1)
                    <a href="{{ $entries->url(1) }}" class="guest-page-link">1</a>
                    @if ($startPage > 2)
                        <span class="guest-page-ellipsis">...</span>
                    @endif
                @endif

                @foreach ($entries->getUrlRange($startPage, $endPage) as $page => $url)
                    <a href="{{ $url }}" class="guest-page-link {{ $page === $entries->currentPage() ? 'is-active' : '' }}" aria-current="{{ $page === $entries->currentPage() ? 'page' : 'false' }}">{{ $page }}</a>
                @endforeach

                @if ($endPage < $entries->lastPage())
                    @if ($endPage < $entries->lastPage() - 1)
                        <span class="guest-page-ellipsis">...</span>
                    @endif
                    <a href="{{ $entries->url($entries->lastPage()) }}" class="guest-page-link">{{ $entries->lastPage() }}</a>
                @endif

                <a href="{{ $entries->hasMorePages() ? $entries->nextPageUrl() : '#' }}" class="guest-page-link is-nav {{ $entries->hasMorePages() ? '' : 'is-disabled' }}" aria-label="Halaman berikutnya">Brkt</a>
            </nav>
        </div>
    @endif
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
