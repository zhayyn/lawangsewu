@extends('guestbook.layout')

@section('title', 'Tailscale Dashboard')

@push('styles')
<style>
    .tailscale-shell {
        min-height: calc(100dvh - 52px);
        padding: 1.25rem 0 1.6rem;
        background: radial-gradient(circle at top left, rgba(209, 250, 229, 0.45), transparent),
            linear-gradient(135deg, rgba(239, 246, 255, 0.62), rgba(236, 253, 245, 0.58));
    }

    .tailscale-panel {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.25);
        border-radius: 1rem;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.1);
    }

    .chip-up {
        color: #166534;
        background: rgba(34, 197, 94, 0.12);
        border: 1px solid rgba(34, 197, 94, 0.3);
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.3rem 0.7rem;
    }

    .chip-down {
        color: #991b1b;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.28);
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.3rem 0.7rem;
    }
</style>
@endpush

@section('content')
<div class="tailscale-shell">
    <div class="container">
        <section class="batik-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <div class="batik-kicker">Network Control</div>
                    <h1 class="batik-hero-title">Tailscale Dashboard</h1>
                    <p class="batik-hero-subtitle">Monitoring konektivitas server PA Semarang melalui jalur Tailscale.</p>
                </div>
                <div class="d-flex gap-2 align-items-start flex-wrap">
                    <a class="btn btn-outline-primary" href="{{ route('lawangsewu.dashboard') }}">Kembali ke Dashboard</a>
                    <button id="refreshStatus" class="btn btn-success">Refresh Status</button>
                </div>
            </div>
        </section>

        <div class="tailscale-panel p-3 p-lg-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Ringkasan Jaringan</h5>
                <small class="text-muted" id="checkedAt">Belum dicek</small>
            </div>
            <div class="row g-3" id="summaryCards">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted">Total Device</small>
                        <div class="h3 mb-0" id="totalDevices">{{ count($devices) }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted">Reachable</small>
                        <div class="h3 mb-0 text-success" id="reachableDevices">-</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <small class="text-muted">Unreachable</small>
                        <div class="h3 mb-0 text-danger" id="unreachableDevices">-</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tailscale-panel p-3 p-lg-4">
            <h5 class="mb-3">Daftar Perangkat</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>IP</th>
                            <th>Port</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="deviceRows">
                    @foreach ($devices as $key => $device)
                        <tr data-key="{{ $key }}">
                            <td>{{ $device['label'] }}</td>
                            <td>{{ $device['ip'] }}</td>
                            <td>{{ $device['port'] }}</td>
                            <td><span class="badge text-bg-secondary">Belum dicek</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const statusUrl = "{{ route('lawangsewu.tailscale.network-status') }}";

    function statusChip(reachable) {
        if (reachable) {
            return '<span class="chip-up">Reachable</span>';
        }

        return '<span class="chip-down">Unreachable</span>';
    }

    async function refreshNetworkStatus() {
        const response = await fetch(statusUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('Gagal mengambil status jaringan');
        }

        const payload = await response.json();
        const rows = document.querySelectorAll('#deviceRows tr');

        payload.devices.forEach((device) => {
            const row = Array.from(rows).find((item) => item.dataset.key === device.key);

            if (!row) {
                return;
            }

            row.cells[3].innerHTML = statusChip(device.reachable);
            document.getElementById('checkedAt').innerText = `Terakhir cek: ${device.checked_at}`;
        });

        document.getElementById('totalDevices').innerText = String(payload.summary.total);
        document.getElementById('reachableDevices').innerText = String(payload.summary.reachable);
        document.getElementById('unreachableDevices').innerText = String(payload.summary.unreachable);
    }

    document.getElementById('refreshStatus').addEventListener('click', async () => {
        try {
            await refreshNetworkStatus();
        } catch (error) {
            alert(error.message);
        }
    });

    refreshNetworkStatus().catch(() => {
        // no-op
    });
</script>
@endpush
