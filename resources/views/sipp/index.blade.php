@extends('guestbook.layout')

@section('title', 'SIPP Hub & Data')

@push('styles')
<style>
    .sipp-shell {
        min-height: calc(100dvh - 52px);
        padding: 1.25rem 0 1.6rem;
        background: radial-gradient(circle at top left, rgba(186, 230, 253, 0.48), transparent),
            linear-gradient(135deg, rgba(240, 253, 250, 0.62), rgba(248, 240, 255, 0.58));
    }

    .sipp-panel {
        background: rgba(255, 255, 255, 0.88);
        border: 1px solid rgba(226, 232, 240, 0.6);
        border-radius: 1.2rem;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.1);
        backdrop-filter: blur(12px);
    }

    .metric-card {
        border-radius: 1rem;
        border: 1px solid rgba(14, 165, 233, 0.15);
        background: linear-gradient(135deg, rgba(240, 249, 255, 0.7), rgba(225, 243, 254, 0.5));
        padding: 1.1rem;
    }

    .metric-card .label {
        font-size: 0.85rem;
        color: #475569;
        font-weight: 600;
    }

    .metric-card .value {
        font-size: 1.8rem;
        font-weight: 800;
        color: #0c4a6e;
        margin-top: 0.4rem;
    }

    .sync-status {
        border-radius: 1rem;
        border: 1px solid rgba(34, 197, 94, 0.2);
        background: linear-gradient(135deg, rgba(240, 253, 244, 0.8), rgba(220, 252, 231, 0.6));
        padding: 1rem;
    }

    .badge-cached {
        display: inline-block;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background: rgba(34, 197, 94, 0.15);
        color: #166534;
        padding: 0.4rem 0.7rem;
    }

    .btn-glass {
        border: 1px solid rgba(255, 255, 255, 0.5);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.36), rgba(255, 255, 255, 0.1));
        color: #0f2747;
        border-radius: 999px;
    }

    .stat-block {
        border-radius: 1rem;
        border: 1px solid rgba(59, 130, 246, 0.1);
        background: rgba(59, 130, 246, 0.04);
        padding: 1rem;
        margin-bottom: 0.8rem;
    }

    .stat-block .title {
        font-size: 0.9rem;
        color: #0c4a6e;
        font-weight: 600;
    }

    .stat-block .content {
        margin-top: 0.5rem;
        font-size: 1.3rem;
        font-weight: 700;
        color: #0f2747;
    }
</style>
@endpush

@section('content')
<div class="sipp-shell">
    <div class="container">
        <section class="batik-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <div class="batik-kicker">Sistem Informasi Persidangan</div>
                    <h1 class="batik-hero-title">SIPP Hub & Data</h1>
                    <p class="batik-hero-subtitle">Dashboard terpadu untuk statistik perkara, e-court, dan hakim dengan sinkronisasi widget real-time.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-start">
                    <form action="{{ route('lawangsewu.sipp.refresh') }}" method="post">@csrf <button class="btn btn-glass"><i class="bi bi-arrow-clockwise"></i> Sinkronisasi Ulang</button></form>
                    <a class="btn btn-glass" href="{{ route('lawangsewu.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard Utama</a>
                </div>
            </div>
        </section>

        @if (session('status'))
            <div class="alert alert-success"><i class="bi bi-check-circle"></i> {{ session('status') }}</div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="sipp-panel p-3 p-lg-4">
                    <h6 class="mb-3"><i class="bi bi-cloud-check"></i> Status Sinkronisasi</h6>
                    <div class="sync-status">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small text-muted">Terakhir Sinkronisasi</div>
                                <div class="h6 mb-0">{{ $syncInfo['lastSync'] }}</div>
                            </div>
                            <span class="badge-cached">{{ $syncInfo['cacheStatus'] }}</span>
                        </div>
                        <div class="mt-3 pt-3 border-top">
                            <div class="small text-muted">Status</div>
                            <div class="text-success fw-bold">{{ $syncInfo['status'] }}</div>
                        </div>
                        <div class="mt-2 small text-muted">
                            Sinkronisasi berikutnya: {{ $syncInfo['nextSync'] }} WIB
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="row g-3">
                    @foreach ($metrics as $metric)
                        <div class="col-md-6 col-lg-4">
                            <div class="metric-card">
                                <div class="label">{{ $metric['label'] }}</div>
                                <div class="value">{{ $metric['value'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="sipp-panel p-3 p-lg-4">
                    <h5 class="mb-3"><i class="bi bi-file-earmark-text"></i> Statistik Perkara</h5>
                    @if ($caseStats && isset($caseStats ['perdata'], $caseStats['pidana']))
                        <div class="stat-block">
                            <div class="title">Perkara Perdata</div>
                            <div class="content">{{ $caseStats['perdata'] ?? '-' }} perkara</div>
                        </div>
                        <div class="stat-block">
                            <div class="title">Perkara Pidana</div>
                            <div class="content">{{ $caseStats['pidana'] ?? '-' }} perkara</div>
                        </div>
                        <div class="stat-block">
                            <div class="title">Total Perkara Bulan Ini</div>
                            <div class="content">{{ ($caseStats['perdata'] ?? 0) + ($caseStats['pidana'] ?? 0) }} perkara</div>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">Cache widget statistik perkara sedang dimuat. Silakan coba lagi dalam beberapa saat.</div>
                    @endif
                </div>
            </div>

            <div class="col-lg-6">
                <div class="sipp-panel p-3 p-lg-4">
                    <h5 class="mb-3"><i class="bi bi-pc-display"></i> Statistik E-Court</h5>
                    @if ($ecourtStats && isset($ecourtStats['submission'], $ecourtStats['processed']))
                        <div class="stat-block">
                            <div class="title">Pengajuan Elektronik</div>
                            <div class="content">{{ $ecourtStats['submission'] ?? '-' }}</div>
                        </div>
                        <div class="stat-block">
                            <div class="title">Permohonan Terproses</div>
                            <div class="content">{{ $ecourtStats['processed'] ?? '-' }}</div>
                        </div>
                        <div class="stat-block">
                            <div class="title">Persentase Digitalisasi</div>
                            <div class="content">
                                @php
                                    $total = ($ecourtStats['submission'] ?? 0) + ($ecourtStats['processed'] ?? 0);
                                    $pct = $total > 0 ? round((($ecourtStats['processed'] ?? 0) / $total) * 100) : 0;
                                @endphp
                                {{ $pct }}%
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">Cache widget statistik e-court sedang dimuat. Silakan coba lagi dalam beberapa saat.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3 mt-3">
            <div class="col-lg-12">
                <div class="sipp-panel p-3 p-lg-4">
                    <h5 class="mb-3"><i class="bi bi-person-badge"></i> Statistik Hakim</h5>
                    @if ($judgeStats && isset($judgeStats['active'], $judgeStats['total']))
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="stat-block">
                                    <div class="title">Hakim Aktif</div>
                                    <div class="content">{{ $judgeStats['active'] ?? '-' }} hakim</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stat-block">
                                    <div class="title">Total Hakim</div>
                                    <div class="content">{{ $judgeStats['total'] ?? '-' }} hakim</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 p-3 border rounded-3 bg-light">
                            <small class="text-muted">Data ini diprovisikan dari SIPP dan di-cache untuk performa optimal. Tekan tombol "Sinkronisasi Ulang" untuk memperbarui data secara instan.</small>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">Cache widget statistik hakim sedang dimuat. Silakan coba lagi dalam beberapa saat.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
