@extends('layouts.app')

@section('title', 'Buat Izin Belajar')
@section('page_title', 'Buat Izin Belajar')
@section('page_subtitle', 'Susun draft izin belajar baru dengan data pegawai yang sudah tersinkron.')

@section('content')
    <section class="panel">
        <form method="post" action="{{ route('study-permits.store') }}" class="form-grid">
            @csrf
            <div class="field">
                <label for="nip">NIP</label>
                <input id="nip" name="nip" list="study-employee-nips" value="{{ old('nip') }}" required>
                <datalist id="study-employee-nips">
                    @foreach($employees as $employee)
                        <option value="{{ $employee->nip }}">{{ $employee->name }}</option>
                    @endforeach
                </datalist>
            </div>
            <div class="field">
                <label for="employee_name">Nama Pegawai</label>
                <input id="employee_name" name="employee_name" value="{{ old('employee_name') }}" required>
            </div>
            <div class="field">
                <label for="position">Jabatan</label>
                <input id="position" name="position" value="{{ old('position') }}">
            </div>
            <div class="field">
                <label for="rank">Golongan</label>
                <input id="rank" name="rank" value="{{ old('rank') }}">
            </div>
            <div class="field">
                <label for="study_type">Jenis Studi</label>
                <input id="study_type" name="study_type" value="{{ old('study_type') }}" placeholder="Izin Belajar / Tugas Belajar" required>
            </div>
            <div class="field">
                <label for="study_program">Program Studi</label>
                <input id="study_program" name="study_program" value="{{ old('study_program') }}" required>
            </div>
            <div class="field full">
                <label for="notes">Catatan</label>
                <textarea id="notes" name="notes">{{ old('notes') }}</textarea>
            </div>
            <div class="field" style="align-self:end;">
                <button type="submit" class="button">Simpan Draft Izin Belajar</button>
            </div>
        </form>
    </section>
@endsection