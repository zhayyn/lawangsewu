@extends('guestbook.layout')

@section('title', 'Detail Tamu')

@push('styles')
<style>
    .detail-shell {
        min-height: calc(100dvh - 52px);
        padding: clamp(1rem, 2vw, 1.5rem) 0 1.8rem;
        background: linear-gradient(120deg, rgba(255, 246, 228, 0.56), rgba(238, 247, 255, 0.58)), url('{{ asset('guestbook/foto-bg.png') }}') center center / cover no-repeat, #f6f9ff;
        overflow-x: clip;
    }

    .detail-panel {
        background: rgba(255, 255, 255, 0.82);
        border-radius: 1.25rem;
        box-shadow: 0 18px 38px rgba(16, 24, 40, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(12px);
    }

    .detail-photo {
        width: 100%;
        max-height: 420px;
        object-fit: cover;
        border-radius: 0.8rem;
        border: 2px solid #e9efff;
        background: #eef3fa;
    }

    .field-label {
        color: #5a6980;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .btn-glass {
        border: 1px solid rgba(255, 255, 255, 0.45);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.36), rgba(255, 255, 255, 0.1));
        color: #0f2747;
        border-radius: 999px;
    }
</style>
@endpush

@section('content')
@php
    $photo = asset('guestbook/tanpafoto.jpg');
    foreach (['jpg', 'jpeg', 'png'] as $ext) {
        $storageRelative = 'guestbook/photos/' . $entry->id . '.' . $ext;
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($storageRelative)) {
            $photo = asset('storage/' . $storageRelative);
            break;
        }

        $candidate = public_path('guestbook/photos/' . $entry->id . '.' . $ext);
        if (is_file($candidate)) {
            $photo = asset('guestbook/photos/' . $entry->id . '.' . $ext);
            break;
        }
    }
@endphp

<div class="detail-shell">
    <div class="container mt-4 pb-5">
        <section class="batik-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
                <div>
                    <div class="batik-kicker">Pengadilan Agama Semarang</div>
                    <h1 class="batik-hero-title">Detail Tamu</h1>
                    <p class="batik-hero-subtitle">Riwayat lengkap kunjungan untuk {{ $entry->name }}, termasuk identitas, foto, dan waktu check-in.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="batik-chip"><i class="bi bi-clock-history"></i> {{ \Illuminate\Support\Carbon::parse($entry->checkin)->format('d M Y, H:i') }}</span>
                    <a href="{{ route('lawangsewu.guestbook.list', ['period' => 'all']) }}" class="btn btn-glass"><i class="bi bi-arrow-left-circle"></i> Kembali</a>
                    <a href="{{ route('lawangsewu.guestbook.cetak', $entry->id) }}" target="_blank" class="btn btn-glass"><i class="bi bi-printer"></i> Print Card</a>
                </div>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="detail-panel p-3">
                    <h6 class="mb-3">Foto Tamu</h6>
                    <img src="{{ $photo }}" alt="Foto {{ $entry->name }}" class="detail-photo">
                </div>
            </div>
            <div class="col-lg-8">
                <div class="detail-panel p-3 p-lg-4">
                    <h6 class="mb-3">Data Tamu</h6>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="field-label">ID Tamu</div><div>{{ $entry->id }}</div></div>
                        <div class="col-md-6"><div class="field-label">Nama Lengkap</div><div>{{ $entry->name }}</div></div>
                        <div class="col-md-6"><div class="field-label">Jabatan</div><div>{{ $entry->position }}</div></div>
                        <div class="col-md-6"><div class="field-label">Instansi</div><div>{{ $entry->institution }}</div></div>
                        <div class="col-md-6"><div class="field-label">Nomor HP</div><div>{{ $entry->phone ?: '-' }}</div></div>
                        <div class="col-md-12"><div class="field-label">Keperluan</div><div>{{ $entry->purpose ?: '-' }}</div></div>
                        <div class="col-md-6"><div class="field-label">Tanggal Kunjungan</div><div>{{ \Illuminate\Support\Carbon::parse($entry->checkin)->format('d/m/Y') }}</div></div>
                        <div class="col-md-6"><div class="field-label">Jam Check In</div><div>{{ \Illuminate\Support\Carbon::parse($entry->checkin)->format('H:i:s') }}</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
