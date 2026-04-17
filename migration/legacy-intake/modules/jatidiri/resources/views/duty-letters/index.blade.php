@extends('layouts.app')

@section('title', 'Surat Tugas')
@section('page_title', 'Surat Tugas')
@section('page_subtitle', 'Daftar draft surat tugas yang disiapkan dari workflow baru Jatidiri.')

@section('actions')
    <a class="button" href="{{ route('duty-letters.create') }}">Buat Surat Tugas</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="flash flash-ok">{{ session('success') }}</div>
    @endif

    <section class="panel">
        <table class="data-table">
            <thead><tr><th>Nomor</th><th>Tujuan</th><th>Kota</th><th>Periode</th></tr></thead>
            <tbody>
            @forelse($letters as $item)
                <tr>
                    <td>{{ $item->letter_number ?: '-' }}</td>
                    <td>{{ $item->destination_agency }}</td>
                    <td>{{ $item->destination_city }}</td>
                    <td>{{ $item->start_date }} s.d. {{ $item->end_date }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada draft surat tugas.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $letters->links() }}</div>
    </section>
@endsection