@extends('layouts.app')

@section('title', 'Izin Belajar')
@section('page_title', 'Izin Belajar')
@section('page_subtitle', 'Kelola draft izin belajar berbasis data pegawai yang sudah tersusun rapi.')

@section('actions')
    <a class="button" href="{{ route('study-permits.create') }}">Buat Izin Belajar</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="flash flash-ok">{{ session('success') }}</div>
    @endif

    <section class="panel">
        <table class="data-table">
            <thead><tr><th>NIP</th><th>Nama</th><th>Program</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($requests as $item)
                <tr>
                    <td>{{ $item->nip }}</td>
                    <td>{{ $item->employee_name }}</td>
                    <td>{{ $item->study_program }}</td>
                    <td><span class="badge">{{ $item->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada draft izin belajar.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $requests->links() }}</div>
    </section>
@endsection