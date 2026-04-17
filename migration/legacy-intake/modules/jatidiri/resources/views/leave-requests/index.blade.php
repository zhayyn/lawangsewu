@extends('layouts.app')

@section('title', 'Pengajuan Cuti')
@section('page_title', 'Pengajuan Cuti')
@section('page_subtitle', 'Manajemen permohonan dan pemberian cuti')

@section('actions')
    <a class="button" href="{{ route('leave-requests.create') }}">Buat Pengajuan</a>
    <a class="button button-info" href="{{ route('leave-balances.index') }}">Manajemen Sisa Cuti</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="flash flash-ok">{{ session('success') }}</div>
    @endif

    <section class="panel">
        <form method="get" class="filter-form">
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">-- Semua Status --</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <button type="submit" class="button">Filter</button>
        </form>
    </section>

    <section class="panel">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIP</th>
                    <th>Nama Pegawai</th>
                    <th>Kategori</th>
                    <th>Periode</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $item)
                    <tr>
                        <td>{{ $item->employee_nip }}</td>
                        <td>{{ $item->employee_name }}</td>
                        <td>{{ $item->leave_category }}</td>
                        <td>{{ $item->start_date->format('d-m-Y') }} s/d {{ $item->end_date->format('d-m-Y') }}</td>
                        <td>{{ $item->duration_days }} hari</td>
                        <td>
                            <span class="badge badge-{{ strtolower($item->status) }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('leave-requests.show', $item->id) }}" class="button-small">Lihat</a>
                            @if($item->status === 'pending')
                                <a href="{{ route('leave-requests.edit', $item->id) }}" class="button-small">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center;">Belum ada pengajuan cuti</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $requests->links() }}</div>
    </section>
@endsection