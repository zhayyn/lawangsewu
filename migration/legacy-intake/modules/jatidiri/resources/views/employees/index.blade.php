@extends('layouts.app')

@section('title', 'Direktori Pegawai')
@section('page_title', 'Direktori Pegawai')
@section('page_subtitle', 'Satu daftar pegawai yang bersih, terkonsolidasi, dan siap dipakai untuk proses administrasi.')

@section('actions')
    <form method="post" action="{{ route('employees.sync') }}" class="inline-form">
        @csrf
        <input type="hidden" name="source" value="sikep-portal">
        <button type="submit" class="button">Segarkan dari SIKEP</button>
    </form>
@endsection

@section('content')
    @if (session('sync_success'))
        <div class="flash flash-ok">{{ session('sync_success') }}</div>
    @endif

    @if (session('sync_error'))
        <div class="flash flash-err">{{ session('sync_error') }}</div>
    @endif

    <section class="panel intro-panel">
        <p class="intro-copy">Data pegawai dipakai ulang untuk menyiapkan pengajuan cuti, izin belajar, dan surat tugas. Sumber SIKEP menjadi prioritas utama, sementara data arsip awal hanya melengkapi bila ada field yang kosong.</p>
    </section>

    <section class="panel">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIP</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Satker</th>
                    <th>Sumber</th>
                    <th>Terakhir Sinkron</th>
                </tr>
            </thead>
            <tbody>
            @forelse($employees as $emp)
                <tr>
                    <td>{{ $emp->nip }}</td>
                    <td>
                        <strong>{{ $emp->name }}</strong>
                        <div class="cell-note">{{ $emp->email ?: 'Email belum tersedia' }}</div>
                    </td>
                    <td>{{ $emp->position ?: '-' }}</td>
                    <td>{{ $emp->satker_name ?: ($emp->satker_code ?: '-') }}</td>
                    <td>
                        @php($sources = $emp->merged_sources ?: [$emp->source_system])
                        @foreach($sources as $source)
                            <span class="badge">{{ $sourceLabels[$source] ?? $source }}</span>
                        @endforeach
                    </td>
                    <td>{{ $emp->last_synced_at ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada data pegawai.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">{{ $employees->links() }}</div>
    </section>
@endsection
