@extends('layouts.app')

@section('title', 'Buat Surat Tugas')
@section('page_title', 'Buat Surat Tugas')
@section('page_subtitle', 'Siapkan draft surat tugas baru. Format final dan nomor surat bisa disempurnakan setelah template resmi diberikan.')

@section('content')
    <section class="panel">
        <form method="post" action="{{ route('duty-letters.store') }}" class="form-grid">
            @csrf
            <div class="field">
                <label for="letter_number">Nomor Surat</label>
                <input id="letter_number" name="letter_number" value="{{ old('letter_number') }}">
            </div>
            <div class="field">
                <label for="letter_date">Tanggal Surat</label>
                <input id="letter_date" type="date" name="letter_date" value="{{ old('letter_date') }}" required>
            </div>
            <div class="field full">
                <label for="purpose">Maksud / Tujuan Penugasan</label>
                <textarea id="purpose" name="purpose" required>{{ old('purpose') }}</textarea>
            </div>
            <div class="field">
                <label for="destination_agency">Instansi Tujuan</label>
                <input id="destination_agency" name="destination_agency" value="{{ old('destination_agency') }}" required>
            </div>
            <div class="field">
                <label for="destination_city">Kota Tujuan</label>
                <input id="destination_city" name="destination_city" value="{{ old('destination_city') }}" required>
            </div>
            <div class="field">
                <label for="start_date">Tanggal Berangkat</label>
                <input id="start_date" type="date" name="start_date" value="{{ old('start_date') }}" required>
            </div>
            <div class="field">
                <label for="end_date">Tanggal Selesai</label>
                <input id="end_date" type="date" name="end_date" value="{{ old('end_date') }}" required>
            </div>
            <div class="field">
                <label for="signing_officer">Pejabat Penandatangan</label>
                <input id="signing_officer" name="signing_officer" value="{{ old('signing_officer') }}">
            </div>
            <div class="field">
                <label for="dipa_code">Kode DIPA</label>
                <input id="dipa_code" name="dipa_code" value="{{ old('dipa_code') }}">
            </div>
            <div class="field" style="align-self:end;">
                <button type="submit" class="button">Simpan Draft Surat Tugas</button>
            </div>
        </form>
    </section>
@endsection