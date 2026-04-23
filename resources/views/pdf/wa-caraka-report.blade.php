<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan WA Caraka</title>
    <style>
        @page { margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.45;
            margin: 0;
            background: #ffffff;
        }
        .hero {
            padding: 18px 20px;
            border-radius: 18px;
            color: #fff;
            background: linear-gradient(135deg, #0f172a 0%, #111827 50%, #1d4ed8 100%);
        }
        .eyebrow {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #bfdbfe;
        }
        h1 {
            margin: 10px 0 4px;
            font-size: 24px;
            line-height: 1.2;
        }
        .muted { color: #cbd5e1; }
        .meta-grid, .metric-grid, .duo-grid, .triple-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
            margin-top: 12px;
        }
        .metric-card, .panel, .snapshot-card {
            vertical-align: top;
            border-radius: 16px;
            padding: 14px;
        }
        .metric-card {
            color: #fff;
        }
        .metric-cyan { background: linear-gradient(135deg, #0891b2, #38bdf8); }
        .metric-emerald { background: linear-gradient(135deg, #059669, #34d399); }
        .metric-violet { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
        .metric-amber { background: linear-gradient(135deg, #d97706, #fbbf24); }
        .label {
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
            opacity: .9;
        }
        .value {
            margin-top: 8px;
            font-size: 22px;
            font-weight: 900;
            line-height: 1;
        }
        .note {
            margin-top: 6px;
            font-size: 9px;
            opacity: .92;
        }
        .panel {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        h2 {
            margin: 0 0 10px;
            font-size: 14px;
        }
        .chip {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        .chip-dark { background: #e0f2fe; color: #075985; }
        .chip-emerald { background: #dcfce7; color: #166534; }
        .bar-row { margin-bottom: 8px; }
        .bar-top {
            width: 100%;
            margin-bottom: 4px;
            font-size: 9px;
        }
        .bar-track {
            height: 12px;
            border-radius: 999px;
            background: #dbeafe;
            overflow: hidden;
        }
        .bar-fill {
            height: 12px;
            border-radius: 999px;
            background: linear-gradient(90deg, #06b6d4, #2563eb);
        }
        .stack-track {
            height: 14px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            margin-top: 6px;
        }
        .stack-inbound, .stack-outbound, .stack-history {
            height: 14px;
            float: left;
        }
        .stack-inbound { background: #0ea5e9; }
        .stack-outbound { background: #10b981; }
        .stack-history { background: #8b5cf6; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            padding: 8px 9px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        th {
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .14em;
        }
        .right { text-align: right; }
        .small { font-size: 9px; color: #475569; }
        .footer {
            margin-top: 16px;
            font-size: 8px;
            color: #64748b;
            text-align: right;
        }
        .snapshot-card {
            background: linear-gradient(180deg, #ffffff, #eff6ff);
            border: 1px solid #dbeafe;
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="eyebrow">WA Caraka Executive Report</div>
        <h1>Laporan Bulanan dan Status Sinkronisasi</h1>
        <div class="muted">Dibuat untuk {{ $authUser->alias ?: $authUser->name }} • {{ $generatedAtLabel }}</div>
    </div>

    <table class="metric-grid">
        <tr>
            <td class="metric-card metric-cyan">
                <div class="label">Inbound Hari Ini</div>
                <div class="value">{{ data_get($report, 'summary.inboundToday', 0) }}</div>
                <div class="note">Pesan yang masuk hari ini.</div>
            </td>
            <td class="metric-card metric-emerald">
                <div class="label">Inbound Minggu Ini</div>
                <div class="value">{{ data_get($report, 'summary.inboundWeek', 0) }}</div>
                <div class="note">Akumulasi 7 hari terakhir.</div>
            </td>
            <td class="metric-card metric-violet">
                <div class="label">Inbound Bulan Ini</div>
                <div class="value">{{ data_get($report, 'summary.inboundMonth', 0) }}</div>
                <div class="note">Modal utama laporan bulanan.</div>
            </td>
            <td class="metric-card metric-amber">
                <div class="label">Percakapan Aktif</div>
                <div class="value">{{ data_get($report, 'summary.activeConversations', 0) }}</div>
                <div class="note">Thread yang masih hidup.</div>
            </td>
        </tr>
    </table>

    <table class="duo-grid">
        <tr>
            <td class="panel" style="width:55%;">
                <span class="chip chip-dark">Puncak Inbound Bulanan</span>
                <h2>Skyline 12 Bulan</h2>
                @php
                    $monthly = collect(data_get($report, 'monthlyInbound', []));
                    $peak = max(1, (int) $monthly->max('count'));
                @endphp
                @foreach ($monthly as $point)
                    <div class="bar-row">
                        <table class="bar-top"><tr><td>{{ $point['label'] }}</td><td class="right"><strong>{{ $point['count'] }}</strong></td></tr></table>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ max(4, (($point['count'] ?? 0) / $peak) * 100) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </td>
            <td class="panel" style="width:45%;">
                <span class="chip chip-emerald">Sinkron Riwayat</span>
                <h2>Health Engine</h2>
                <table>
                    <tr><td>Status</td><td class="right"><strong>{{ data_get($report, 'historySync.latest.status', 'belum ada sync') }}</strong></td></tr>
                    <tr><td>Pesan diterima</td><td class="right"><strong>{{ data_get($report, 'historySync.latest.messagesReceived', 0) }}</strong></td></tr>
                    <tr><td>Pesan diimpor</td><td class="right"><strong>{{ data_get($report, 'historySync.latest.messagesImported', 0) }}</strong></td></tr>
                    <tr><td>Duplikat</td><td class="right"><strong>{{ data_get($report, 'historySync.latest.messagesDuplicate', 0) }}</strong></td></tr>
                    <tr><td>Progress</td><td class="right"><strong>{{ data_get($report, 'historySync.latest.progress', 0) }}%</strong></td></tr>
                </table>

                <h2 style="margin-top:14px;">Komposisi Jenis Pesan</h2>
                @php
                    $types = collect(data_get($report, 'messageTypes', []));
                    $typePeak = max(1, (int) $types->max('count'));
                @endphp
                @foreach ($types as $type)
                    <div class="bar-row">
                        <table class="bar-top"><tr><td>{{ $type['label'] }}</td><td class="right"><strong>{{ $type['count'] }}</strong></td></tr></table>
                        <div class="bar-track" style="background:#ede9fe;">
                            <div class="bar-fill" style="width: {{ max(6, (($type['count'] ?? 0) / $typePeak) * 100) }}%; background:linear-gradient(90deg,#8b5cf6,#c084fc);"></div>
                        </div>
                    </div>
                @endforeach
            </td>
        </tr>
    </table>

    <table class="triple-grid">
        <tr>
            <td class="panel" style="width:34%;">
                <h2>Operator Teratas</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Operator</th>
                            <th class="right">Thread</th>
                            <th class="right">Outbound</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse (collect(data_get($report, 'operatorStats', []))->take(6) as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="right">{{ $row['conversations'] }}</td>
                            <td class="right">{{ $row['outboundMessages'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="small">Belum ada data operator.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </td>
            <td class="panel" style="width:33%;">
                <h2>Kontak Tersibuk</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Kontak</th>
                            <th class="right">Pesan</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse (collect(data_get($report, 'topContacts', []))->take(6) as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="right">{{ $row['messages'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="small">Belum ada data kontak.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </td>
            <td class="panel" style="width:33%;">
                <h2>Snapshot Bulanan</h2>
                @foreach (collect(data_get($report, 'snapshots.timeline', []))->take(4) as $snapshot)
                    @php
                        $total = max(1, (int) ($snapshot['totalMessages'] ?? 0));
                        $inboundWidth = (($snapshot['inboundMessages'] ?? 0) / $total) * 100;
                        $outboundWidth = (($snapshot['outboundMessages'] ?? 0) / $total) * 100;
                        $historyWidth = (($snapshot['historyMessages'] ?? 0) / $total) * 100;
                    @endphp
                    <div class="snapshot-card" style="margin-bottom:10px;">
                        <div><strong>{{ $snapshot['label'] }}</strong></div>
                        <div class="small">Kontak unik {{ $snapshot['uniqueContacts'] }} • Top operator {{ $snapshot['topOperatorName'] ?: '-' }}</div>
                        <div class="stack-track">
                            <div class="stack-inbound" style="width: {{ $inboundWidth }}%;"></div>
                            <div class="stack-outbound" style="width: {{ $outboundWidth }}%;"></div>
                            <div class="stack-history" style="width: {{ $historyWidth }}%;"></div>
                        </div>
                        <table style="margin-top:6px;">
                            <tr><td>Total</td><td class="right"><strong>{{ $snapshot['totalMessages'] }}</strong></td></tr>
                            <tr><td>Inbound</td><td class="right">{{ $snapshot['inboundMessages'] }}</td></tr>
                            <tr><td>Outbound</td><td class="right">{{ $snapshot['outboundMessages'] }}</td></tr>
                        </table>
                    </div>
                @endforeach
            </td>
        </tr>
    </table>

    <div class="footer">
        WA Caraka • Snapshot lokal menjaga laporan tetap hidup meski device terputus atau nomor diganti.
    </div>
</body>
</html>
