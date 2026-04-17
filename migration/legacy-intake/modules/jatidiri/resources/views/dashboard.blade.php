@extends('layouts.app')

@section('title', 'Dashboard Jatidiri')
@section('page_title', 'Jatidiri Workspace')
@section('page_subtitle', 'Pusat kerja kepegawaian modern untuk data pegawai, cuti, izin belajar, dan surat tugas.')

@section('actions')
    <a class="button" href="{{ route('employees.index') }}">Pegawai</a>
    <a class="button" href="{{ route('services.index') }}">Layanan</a>
    <a class="button secondary" href="{{ config('lawangsewu.portal_url') }}">Portal</a>
@endsection

@section('content')
    <section class="panel hero-grid">
        <div>
            <div class="eyebrow">Laravel 12 Native</div>
            <h2 class="hero-title">Satu ruang kerja untuk data pegawai dan naskah administrasi internal.</h2>
            <p class="hero-copy">Aplikasi ini dibangun ulang di atas Laravel baru, dengan tampilan yang bersih, alur kerja sederhana, dan sinkronisasi SIKEP untuk menjaga data pegawai tetap akurat.</p>
        </div>
        <div class="summary-boxes">
            <div class="summary-box">
                <span class="summary-label">Login</span>
                <strong>{{ $user->name }}</strong>
                <span class="summary-note">Role: {{ $user->role }}</span>
            </div>
            <div class="summary-box">
                <span class="summary-label">Sinkronisasi Terakhir</span>
                <strong>{{ $lastSync ?: '-' }}</strong>
                <span class="summary-note">Mode SSO {{ config('lawangsewu.sso_mode') }}</span>
            </div>
        </div>
    </section>

    <section class="stats-grid">
        <div class="panel stat-card"><span>Pegawai</span><strong>{{ number_format($employeeCount) }}</strong></div>
        <div class="panel stat-card"><span>Pengajuan Cuti</span><strong>{{ number_format($leaveCount) }}</strong></div>
        <div class="panel stat-card"><span>Izin Belajar</span><strong>{{ number_format($studyCount) }}</strong></div>
        <div class="panel stat-card"><span>Surat Tugas</span><strong>{{ number_format($dutyCount) }}</strong></div>
    </section>

    <section class="content-grid two-col">
        <div class="panel">
            <div class="section-head">
                <h3>Peta Sumber Pegawai</h3>
                <a href="{{ route('employees.index') }}">Buka Direktori</a>
            </div>
            <table class="data-table">
                <thead><tr><th>Sumber</th><th>Total</th></tr></thead>
                <tbody>
                @forelse($sourceBreakdown as $row)
                    <tr><td>{{ $row->source_system }}</td><td>{{ number_format((int) $row->total) }}</td></tr>
                @empty
                    <tr><td colspan="2">Belum ada data sinkronisasi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="panel">
            <div class="section-head">
                <h3>Layanan Cepat</h3>
                <a href="{{ route('services.index') }}">Buka Semua</a>
            </div>
            <div class="action-stack">
                <a class="action-card" href="{{ route('leave-requests.create') }}"><strong>Buat Pengajuan Cuti</strong><span>Susun draft pengajuan cuti berbasis data pegawai.</span></a>
                <a class="action-card" href="{{ route('study-permits.create') }}"><strong>Buat Izin Belajar</strong><span>Kelola izin belajar dengan form native Jatidiri.</span></a>
                <a class="action-card" href="{{ route('duty-letters.create') }}"><strong>Buat Surat Tugas</strong><span>Siapkan surat tugas internal dari data terstruktur.</span></a>
            </div>
        </div>
    </section>

    <section class="content-grid two-col">
        <div class="panel">
            <div class="section-head"><h3>Cuti Terbaru</h3><a href="{{ route('leave-requests.index') }}">Lihat Semua</a></div>
            <table class="data-table">
                <thead><tr><th>Nama</th><th>Jenis</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($recentLeaves as $item)
                    <tr><td>{{ $item->employee_name }}</td><td>{{ $item->leave_type }}</td><td>{{ $item->status }}</td></tr>
                @empty
                    <tr><td colspan="3">Belum ada pengajuan cuti.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="panel">
            <div class="section-head"><h3>Surat Tugas Terbaru</h3><a href="{{ route('duty-letters.index') }}">Lihat Semua</a></div>
            <table class="data-table">
                <thead><tr><th>Nomor</th><th>Tujuan</th><th>Tanggal</th></tr></thead>
                <tbody>
                @forelse($recentDuties as $item)
                    <tr><td>{{ $item->letter_number ?: '-' }}</td><td>{{ $item->destination_agency ?: '-' }}</td><td>{{ $item->letter_date ?: '-' }}</td></tr>
                @empty
                    <tr><td colspan="3">Belum ada surat tugas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
