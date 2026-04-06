<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $judul }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #f6f8fc; padding: 1rem; }
        .report-shell { max-width: 1200px; margin: 0 auto; background: #fff; border-radius: 1rem; box-shadow: 0 16px 38px rgba(16, 24, 40, 0.12); padding: 1.2rem; }
        @media print {
            body { background: #fff; padding: 0; }
            .report-action { display: none !important; }
            .report-shell { box-shadow: none; border-radius: 0; max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
<div class="report-shell">
    <div class="report-action d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">{{ $judul }}</h5>
            <small class="text-muted">Bulan {{ $namaBulan }} Tahun {{ $tahun }}</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="window.print()">Print</button>
            <a class="btn btn-success" href="{{ route('lawangsewu.guestbook.report', ['bulan' => $bulan, 'tahun' => $tahun, 'export' => 'xls']) }}">Export XLS</a>
        </div>
    </div>

    <table class="table table-bordered table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th width="4%" class="text-center">No</th>
                <th width="12%" class="text-center">Tanggal</th>
                <th width="12%" class="text-center">Jam</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Instansi</th>
                <th>Keperluan</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($entries as $row)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center">{{ \Illuminate\Support\Carbon::parse($row->checkin)->format('d/m/Y') }}</td>
                <td class="text-center">{{ \Illuminate\Support\Carbon::parse($row->checkin)->format('H:i:s') }}</td>
                <td>{{ $row->name }}</td>
                <td>{{ $row->position }}</td>
                <td>{{ $row->institution }}</td>
                <td>{{ $row->purpose ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center">Tidak ada data pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
