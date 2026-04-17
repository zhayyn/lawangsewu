@extends('layouts.app')

@section('title', 'Edit Sisa Cuti')
@section('page_title', 'Edit Sisa Cuti')

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

        <form method="post" action="{{ route('leave-balances.update', $balance->id) }}" class="form-grid">
            @csrf
            @method('PUT')

            <div class="field full">
                <h3>{{ $employee->name }} ({{ $employee->nip }}) - Tahun {{ $balance->year }}</h3>
            </div>

            <div class="field">
                <label for="annual_quota">Kuota Tahunan</label>
                <input type="number" id="annual_quota" name="annual_quota" value="{{ old('annual_quota', $balance->annual_quota) }}" required min="0">
                @error('annual_quota')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="carryover_from_previous">Carryover dari Tahun Lalu</label>
                <input type="number" id="carryover_from_previous" name="carryover_from_previous" value="{{ old('carryover_from_previous', $balance->carryover_from_previous) }}" required min="0">
                @error('carryover_from_previous')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="used">Sudah Dipakai</label>
                <input type="number" id="used" name="used" value="{{ old('used', $balance->used) }}" required min="0">
                @error('used')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field full">
                <label for="notes">Catatan</label>
                <textarea id="notes" name="notes" rows="4">{{ old('notes', $balance->notes) }}</textarea>
                @error('notes')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="info-box">
                <p>
                    <strong>Total Tersedia:</strong> 
                    <span id="total-available">{{ $balance->annual_quota + $balance->carryover_from_previous }}</span> hari
                </p>
                <p>
                    <strong>Sisa Cuti:</strong> 
                    <span id="remaining-calc">{{ $balance->remaining }}</span> hari
                </p>
            </div>

            <div class="field" style="align-self:end; grid-column: span 2;">
                <button type="submit" class="button">Simpan Perubahan</button>
                <a href="{{ route('leave-balances.show', $balance->employee_nip) }}" class="button-outline">Batal</a>
            </div>
        </form>
    </section>

    <script>
        function updateCalculation() {
            const quota = parseInt(document.getElementById('annual_quota').value) || 0;
            const carryover = parseInt(document.getElementById('carryover_from_previous').value) || 0;
            const used = parseInt(document.getElementById('used').value) || 0;
            const total = quota + carryover;
            const remaining = total - used;

            document.getElementById('total-available').textContent = total;
            document.getElementById('remaining-calc').textContent = remaining;
        }

        document.getElementById('annual_quota').addEventListener('change', updateCalculation);
        document.getElementById('carryover_from_previous').addEventListener('change', updateCalculation);
        document.getElementById('used').addEventListener('change', updateCalculation);
    </script>
@endsection
