@extends('layouts.app')

@section('title', 'Manajemen Sisa Cuti')
@section('page_title', 'Manajemen Sisa Cuti')
@section('page_subtitle', 'Tracking kuota cuti dan sisa cuti per pegawai')

@section('actions')
    <a class="button" href="{{ route('leave-requests.index') }}">Kembali ke Cuti</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="flash flash-ok">{{ session('success') }}</div>
    @endif

    <section class="panel">
        <h3>Manajemen Sisa Cuti</h3>
        <form method="get" class="filter-form">
            <div class="field">
                <label for="year">Tahun</label>
                <select id="year" name="year">
                    @for($y = 2024; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="field">
                <label for="employee_nip">Pegawai</label>
                <select id="employee_nip" name="employee_nip">
                    <option value="">-- Semua Pegawai --</option>
                    @foreach($employees as $nip => $name)
                        <option value="{{ $nip }}" {{ request('employee_nip') === $nip ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="button">Filter</button>
        </form>
    </section>

    <section class="panel">
        <h3>Daftar Sisa Cuti</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIP</th>
                    <th>Nama</th>
                    <th>Tahun</th>
                    <th>Kuota</th>
                    <th>Carryover</th>
                    <th>Total</th>
                    <th>Terpakai</th>
                    <th>Sisa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $balance)
                    <tr>
                        <td>{{ $balance->employee_nip }}</td>
                        <td>{{ $employees[$balance->employee_nip] ?? $balance->employee_nip }}</td>
                        <td>{{ $balance->year }}</td>
                        <td>{{ $balance->annual_quota }}</td>
                        <td>{{ $balance->carryover_from_previous }}</td>
                        <td>{{ $balance->annual_quota + $balance->carryover_from_previous }}</td>
                        <td>{{ $balance->used }}</td>
                        <td>
                            <strong class="text-{{ $balance->remaining > 0 ? 'success' : 'danger' }}">
                                {{ $balance->remaining }}
                            </strong>
                        </td>
                        <td>
                            <a href="{{ route('leave-balances.show', $balance->employee_nip) }}?year={{ $balance->year }}" class="button-small">Lihat</a>
                            <a href="{{ route('leave-balances.edit', $balance->id) }}" class="button-small">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center;">Belum ada data sisa cuti</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination-wrap">{{ $balances->links() }}</div>
    </section>
@endsection
