@extends('layouts.app')

@section('title', 'Detail Sisa Cuti - ' . $employee->name)
@section('page_title', 'Detail Sisa Cuti')
@section('page_subtitle', $employee->name . ' (' . $employee->nip . ')')

@section('content')
    @if (session('success'))
        <div class="flash flash-ok">{{ session('success') }}</div>
    @endif

    <section class="panel">
        <div class="info-grid">
            <div class="info-item">
                <label>NIP</label>
                <span>{{ $employee->nip }}</span>
            </div>
            <div class="info-item">
                <label>Nama</label>
                <span>{{ $employee->name }}</span>
            </div>
            <div class="info-item">
                <label>Jabatan</label>
                <span>{{ $employee->position ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Golongan</label>
                <span>{{ $employee->rank ?? '-' }}</span>
            </div>
        </div>

        <form method="get" class="filter-form">
            <div class="field">
                <label for="year">Pilih Tahun</label>
                <select id="year" name="year" onchange="this.form.submit()">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </section>

    <section class="panel">
        <h3>Sisa Cuti Tahun {{ $year }}</h3>
        
        <div class="balance-summary">
            <div class="balance-item">
                <label>Kuota Tahunan</label>
                <span class="value">{{ $balance->annual_quota }} hari</span>
            </div>
            <div class="balance-item">
                <label>Carryover Tahun Lalu</label>
                <span class="value">{{ $balance->carryover_from_previous }} hari</span>
            </div>
            <div class="balance-item">
                <label>Total Tersedia</label>
                <span class="value">{{ $balance->annual_quota + $balance->carryover_from_previous }} hari</span>
            </div>
            <div class="balance-item">
                <label>Sudah Dipakai</label>
                <span class="value">{{ $balance->used }} hari</span>
            </div>
            <div class="balance-item highlight">
                <label>Sisa Cuti</label>
                <span class="value">{{ $balance->remaining }} hari</span>
            </div>
        </div>

        @if($balance->notes)
            <p><strong>Catatan:</strong> {{ $balance->notes }}</p>
        @endif

        <div class="actions">
            <a href="{{ route('leave-balances.edit', $balance->id) }}" class="button">Edit</a>
            <a href="{{ route('leave-balances.index') }}" class="button-outline">Kembali</a>
        </div>
    </section>
@endsection
