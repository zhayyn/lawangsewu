@extends('layouts.app')

@section('title', 'Edit Permohonan Cuti')
@section('page_title', 'Edit Permohonan Cuti')

@section('content')
    <section class="panel">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('leave-requests.update', $leaveRequest->id) }}" class="form-grid">
            @csrf
            @method('PUT')
            
            <h3>I. DATA PEGAWAI</h3>
            
            <div class="field">
                <label for="employee_nip">NIP Pegawai</label>
                <select id="employee_nip" name="employee_nip" required>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->nip }}" {{ $leaveRequest->employee_nip === $employee->nip ? 'selected' : '' }}>
                            {{ $employee->nip }} - {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="supervisor_nip">NIP Atasan Langsung</label>
                <select id="supervisor_nip" name="supervisor_nip">
                    <option value="">-- Pilih Atasan --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->nip }}" {{ $leaveRequest->supervisor_nip === $employee->nip ? 'selected' : '' }}>
                            {{ $employee->nip }} - {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <h3>II. JENIS CUTI</h3>
            
            <div class="field full">
                <label for="leave_category">Kategori Cuti</label>
                <select id="leave_category" name="leave_category" required>
                    @foreach($leaveCategories as $key => $label)
                        <option value="{{ $key }}" {{ $leaveRequest->leave_category === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field full">
                <label for="leave_type">Jenis Cuti</label>
                <input id="leave_type" name="leave_type" value="{{ old('leave_type', $leaveRequest->leave_type) }}" required>
            </div>

            <h3>III. ALASAN CUTI</h3>
            
            <div class="field full">
                <label for="reason">Alasan Cuti</label>
                <textarea id="reason" name="reason" rows="4" required>{{ old('reason', $leaveRequest->reason) }}</textarea>
            </div>

            <h3>IV. LAMANYA CUTI</h3>
            
            <div class="field">
                <label for="start_date">Tanggal Mulai</label>
                <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $leaveRequest->start_date->format('Y-m-d')) }}" required>
            </div>

            <div class="field">
                <label for="end_date">Tanggal Selesai</label>
                <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $leaveRequest->end_date->format('Y-m-d')) }}" required>
            </div>

            <h3>VI. ALAMAT SELAMA MENJALANKAN CUTI</h3>
            
            <div class="field full">
                <label for="address">Alamat</label>
                <textarea id="address" name="address" rows="3">{{ old('address', $leaveRequest->address) }}</textarea>
            </div>

            <div class="field">
                <label for="phone">Telepon</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone', $leaveRequest->phone) }}">
            </div>

            <div class="field" style="align-self:end; grid-column: span 2;">
                <button type="submit" class="button">Simpan Perubahan</button>
                <a href="{{ route('leave-requests.show', $leaveRequest->id) }}" class="button-outline">Batal</a>
            </div>
        </form>
    </section>
@endsection
