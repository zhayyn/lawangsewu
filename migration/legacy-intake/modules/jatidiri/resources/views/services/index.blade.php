@extends('layouts.app')

@section('title', 'Layanan Kepegawaian')
@section('page_title', 'Layanan Kepegawaian')
@section('page_subtitle', 'Buka layanan administratif yang akan terus dilengkapi dari draft internal sampai format final resmi.')

@section('actions')
    <a class="button" href="{{ route('leave-requests.create') }}">Buat Cuti</a>
    <a class="button" href="{{ route('duty-letters.create') }}">Buat Surat Tugas</a>
@endsection

@section('content')
    <section class="service-grid">
        <div class="service-tile panel">
            <strong>Pengajuan Cuti</strong>
            <p>Draft pengajuan cuti berbasis data pegawai internal, siap disesuaikan setelah format resmi diberikan.</p>
            <div class="cell-note">Total saat ini: {{ number_format($leaveCount) }}</div>
            <div style="margin-top:14px"><a class="button" href="{{ route('leave-requests.index') }}">Buka Cuti</a></div>
        </div>
        <div class="service-tile panel">
            <strong>Izin Belajar</strong>
            <p>Kelola pengajuan izin belajar dalam format draft yang bersih, dengan data pegawai yang sudah tersinkron.</p>
            <div class="cell-note">Total saat ini: {{ number_format($studyCount) }}</div>
            <div style="margin-top:14px"><a class="button" href="{{ route('study-permits.index') }}">Buka Izin Belajar</a></div>
        </div>
        <div class="service-tile panel">
            <strong>Surat Tugas</strong>
            <p>Siapkan surat tugas dari data terstruktur, lalu format akhir dapat disesuaikan ketika template resmi tersedia.</p>
            <div class="cell-note">Total saat ini: {{ number_format($dutyCount) }}</div>
            <div style="margin-top:14px"><a class="button" href="{{ route('duty-letters.index') }}">Buka Surat Tugas</a></div>
        </div>
    </section>
@endsection