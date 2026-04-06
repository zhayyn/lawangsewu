@extends('layouts.app')

@section('title', 'Buat Pengajuan Cuti')
@section('page_title', 'Buat Pengajuan Cuti')
@section('page_subtitle', 'Formulir permintaan dan pemberian cuti')

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

        <form method="post" action="{{ route('leave-requests.store') }}" class="form-grid">
            @csrf
            
            <h3>I. DATA PEGAWAI</h3>
            
            <div class="field">
                <label for="employee_nip">NIP Pegawai</label>
                <select id="employee_nip" name="employee_nip" required onchange="updateEmployeeData()">
                    <option value="">-- Pilih Pegawai --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->nip }}" data-name="{{ $employee->name }}" data-position="{{ $employee->position }}" data-rank="{{ $employee->rank }}">
                            {{ $employee->nip }} - {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
                @error('employee_nip')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="supervisor_nip">NIP Atasan Langsung</label>
                <select id="supervisor_nip" name="supervisor_nip" required>
                    <option value="">-- Pilih Atasan --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->nip }}">
                            {{ $employee->nip }} - {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
                @error('supervisor_nip')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <h3>II. JENIS CUTI</h3>
            
            <div class="field full">
                <label for="leave_category">Kategori Cuti</label>
                <select id="leave_category" name="leave_category" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($leaveCategories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('leave_category')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field full">
                <label for="leave_type">Jenis Cuti</label>
                <input id="leave_type" name="leave_type" value="{{ old('leave_type') }}" placeholder="Cuti Tahunan / Cuti Besar / dll" required>
                @error('leave_type')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <h3>III. ALASAN CUTI</h3>
            
            <div class="field full">
                <label for="reason">Alasan Cuti</label>
                <textarea id="reason" name="reason" rows="4" required>{{ old('reason') }}</textarea>
                @error('reason')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <h3>IV. LAMANYA CUTI</h3>
            
            <div class="field">
                <label for="start_date">Tanggal Mulai</label>
                <input id="start_date" type="date" name="start_date" value="{{ old('start_date') }}" required>
                @error('start_date')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="end_date">Tanggal Selesai</label>
                <input id="end_date" type="date" name="end_date" value="{{ old('end_date') }}" required>
                @error('end_date')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <h3>VI. ALAMAT SELAMA MENJALANKAN CUTI</h3>
            
            <div class="field full">
                <label for="address">Alamat</label>
                <textarea id="address" name="address" rows="3">{{ old('address') }}</textarea>
                @error('address')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="phone">Telepon</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}">
                @error('phone')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field" style="align-self:end; grid-column: span 2;">
                <button type="submit" class="button">Ajukan Permohonan Cuti</button>
                <a href="{{ route('leave-requests.index') }}" class="button-outline">Kembali</a>
            </div>
        </form>
    </section>

    <script>
        function updateEmployeeData() {
            const select = document.getElementById('employee_nip');
            const option = select.options[select.selectedIndex];
            // Data akan tersedia dari atribut data-*
        }
    </script>
@endsection