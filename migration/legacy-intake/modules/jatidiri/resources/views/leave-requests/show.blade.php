@extends('layouts.app')

@section('title', 'Detail Permohonan Cuti')
@section('page_title', 'Detail Permohonan Cuti')

@section('content')
    <section class="panel">
        <div class="request-header">
            <div>
                <h2>{{ $leaveRequest->employee_name }} ({{ $leaveRequest->employee_nip }})</h2>
                <p class="subtitle">{{ $leaveRequest->leave_category }} | {{ $leaveRequest->start_date->format('d-m-Y') }} s/d {{ $leaveRequest->end_date->format('d-m-Y') }}</p>
            </div>
            <div class="status-badge status-{{ strtolower($leaveRequest->status) }}">
                {{ ucfirst($leaveRequest->status) }}
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <label>NIP</label>
                <span>{{ $leaveRequest->employee_nip }}</span>
            </div>
            <div class="info-item">
                <label>Nama</label>
                <span>{{ $leaveRequest->employee_name }}</span>
            </div>
            <div class="info-item">
                <label>Jabatan</label>
                <span>{{ $employee?->position ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Golongan</label>
                <span>{{ $employee?->rank ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Kategori Cuti</label>
                <span>{{ $leaveRequest->leave_category }}</span>
            </div>
            <div class="info-item">
                <label>Jenis Cuti</label>
                <span>{{ $leaveRequest->leave_type }}</span>
            </div>
            <div class="info-item full">
                <label>Alasan</label>
                <span>{{ $leaveRequest->reason }}</span>
            </div>
            <div class="info-item">
                <label>Tanggal Mulai</label>
                <span>{{ $leaveRequest->start_date->format('d-m-Y') }}</span>
            </div>
            <div class="info-item">
                <label>Tanggal Selesai</label>
                <span>{{ $leaveRequest->end_date->format('d-m-Y') }}</span>
            </div>
            <div class="info-item">
                <label>Durasi (Hari Kerja)</label>
                <span>{{ $leaveRequest->duration_days }} hari</span>
            </div>
            <div class="info-item full">
                <label>Alamat Selama Cuti</label>
                <span>{{ $leaveRequest->address ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Telepon</label>
                <span>{{ $leaveRequest->phone ?? '-' }}</span>
            </div>
            @if($leaveRequest->supervisor_nip)
                <div class="info-item">
                    <label>Atasan Langsung</label>
                    <span>{{ $leaveRequest->supervisor_name }} ({{ $leaveRequest->supervisor_nip }})</span>
                </div>
            @endif
            @if($leaveRequest->decision_notes)
                <div class="info-item full alert alert-info">
                    <label>Catatan Keputusan</label>
                    <span>{{ $leaveRequest->decision_notes }}</span>
                </div>
            @endif
        </div>

        <div class="actions">
            @if($leaveRequest->status === 'pending')
                <form method="post" action="{{ route('leave-requests.approve', $leaveRequest->id) }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="button button-success">Setujui</button>
                </form>
                
                <button class="button button-danger" onclick="showRejectForm()">Tolak</button>
                
                <a href="{{ route('leave-requests.edit', $leaveRequest->id) }}" class="button">Edit</a>
            @endif

            <a href="{{ route('leave-requests.letter', $leaveRequest->id) }}" class="button button-info" download>
                Download Surat Cuti
            </a>
            
            <a href="{{ route('leave-requests.index') }}" class="button-outline">Kembali</a>
        </div>

        @if($leaveRequest->status === 'pending')
            <section class="panel" id="reject-form" style="display: none;">
                <h3>Tolak Permohonan Cuti</h3>
                <form method="post" action="{{ route('leave-requests.reject', $leaveRequest->id) }}">
                    @csrf
                    <div class="field full">
                        <label for="decision_notes">Alasan Penolakan</label>
                        <textarea id="decision_notes" name="decision_notes" rows="4" required></textarea>
                    </div>
                    <div class="field">
                        <button type="submit" class="button button-danger">Kirim Penolakan</button>
                        <button type="button" class="button-outline" onclick="hideRejectForm()">Batal</button>
                    </div>
                </form>
            </section>
        @endif
    </section>

    <script>
        function showRejectForm() {
            document.getElementById('reject-form').style.display = 'block';
        }
        function hideRejectForm() {
            document.getElementById('reject-form').style.display = 'none';
        }
    </script>
@endsection
