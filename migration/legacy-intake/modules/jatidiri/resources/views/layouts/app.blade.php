<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Jatidiri')</title>
    <style>
        :root {
            --bg: #f6f0e8;
            --ink: #1f2d2a;
            --muted: #566764;
            --panel: rgba(255,255,255,0.82);
            --line: rgba(59, 95, 86, 0.16);
            --accent: #0f766e;
            --accent-strong: #115e59;
            --warm: #cb8b55;
            --shadow: 0 22px 60px rgba(31,45,42,0.10);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: "Trebuchet MS", Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(203,139,85,0.16), transparent 22%),
                radial-gradient(circle at right top, rgba(15,118,110,0.18), transparent 24%),
                linear-gradient(180deg, #fcfaf7 0%, var(--bg) 100%);
        }
        h1, h2, h3 { font-family: Georgia, "Times New Roman", serif; letter-spacing: -0.02em; }
        a { color: var(--accent-strong); text-decoration: none; }
        .shell { display: grid; grid-template-columns: 280px minmax(0, 1fr); min-height: 100vh; }
        .sidebar {
            padding: 24px 20px;
            border-right: 1px solid rgba(59,95,86,0.12);
            background: rgba(249,244,238,0.78);
            backdrop-filter: blur(12px);
        }
        .brand { margin-bottom: 24px; }
        .brand-kicker { display: inline-flex; padding: 6px 10px; border-radius: 999px; background: rgba(203,139,85,0.18); color: #8a4c1d; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .brand h1 { margin: 12px 0 8px; font-size: 34px; }
        .brand p { margin: 0; color: var(--muted); line-height: 1.6; }
        .nav { display: grid; gap: 10px; }
        .nav a { padding: 12px 14px; border-radius: 14px; background: rgba(255,255,255,0.58); border: 1px solid transparent; font-weight: 700; }
        .nav a:hover { border-color: var(--line); background: rgba(255,255,255,0.95); }
        .main { padding: 28px; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 18px; flex-wrap: wrap; }
        .topbar h2 { margin: 0 0 8px; font-size: 38px; }
        .topbar p { margin: 0; color: var(--muted); max-width: 760px; line-height: 1.7; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .button, button.button {
            border: 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 16px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(15,118,110,0.18);
        }
        .button.secondary { background: rgba(255,255,255,0.8); color: var(--accent-strong); border: 1px solid var(--line); box-shadow: none; }
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 22px; box-shadow: var(--shadow); padding: 20px; backdrop-filter: blur(10px); }
        .hero-grid, .content-grid, .stats-grid { display: grid; gap: 16px; }
        .hero-grid { grid-template-columns: 1.4fr 0.9fr; margin-bottom: 16px; }
        .summary-boxes { display: grid; gap: 12px; }
        .summary-box { padding: 16px; border-radius: 18px; background: rgba(255,255,255,0.64); border: 1px solid rgba(59,95,86,0.10); }
        .summary-label { display: block; color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 8px; }
        .summary-note { display: block; margin-top: 6px; color: var(--muted); font-size: 13px; }
        .eyebrow { display: inline-flex; padding: 6px 10px; border-radius: 999px; background: rgba(15,118,110,0.10); color: var(--accent-strong); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .hero-title { margin: 14px 0 10px; font-size: 34px; }
        .hero-copy, .intro-copy { margin: 0; color: var(--muted); line-height: 1.8; }
        .stats-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 16px; }
        .stat-card span { display: block; color: var(--muted); margin-bottom: 6px; }
        .stat-card strong { font-size: 34px; }
        .two-col { grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 16px; }
        .section-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; }
        .section-head h3 { margin: 0; font-size: 24px; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 10px 8px; text-align: left; border-bottom: 1px solid rgba(59,95,86,0.10); vertical-align: top; }
        .data-table th { color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }
        .badge { display: inline-flex; padding: 4px 8px; border-radius: 999px; background: rgba(15,118,110,0.10); color: var(--accent-strong); border: 1px solid rgba(15,118,110,0.14); margin: 0 6px 6px 0; font-size: 12px; font-weight: 700; }
        .cell-note { color: var(--muted); font-size: 13px; margin-top: 4px; }
        .pagination-wrap { margin-top: 14px; }
        .flash { padding: 12px 14px; border-radius: 14px; margin-bottom: 14px; }
        .flash-ok { background: #ebfbf6; border: 1px solid #bfe8da; }
        .flash-err { background: #fff0ef; border: 1px solid #efc8c3; }
        .action-stack { display: grid; gap: 10px; }
        .action-card { display: block; padding: 14px; border-radius: 16px; background: rgba(255,255,255,0.72); border: 1px solid rgba(59,95,86,0.10); }
        .action-card strong { display: block; margin-bottom: 6px; }
        .action-card span { color: var(--muted); line-height: 1.6; }
        .service-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .service-tile { padding: 18px; border-radius: 20px; background: rgba(255,255,255,0.74); border: 1px solid rgba(59,95,86,0.10); }
        .service-tile strong { display: block; font-size: 20px; margin-bottom: 8px; }
        .service-tile p { color: var(--muted); line-height: 1.7; margin: 0 0 14px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field { display: grid; gap: 6px; }
        .field label { font-size: 13px; color: var(--muted); font-weight: 700; }
        .field input, .field select, .field textarea {
            width: 100%; padding: 12px 14px; border: 1px solid rgba(59,95,86,0.18); border-radius: 12px; background: rgba(255,255,255,0.9); color: var(--ink); font: inherit;
        }
        .field textarea { min-height: 120px; resize: vertical; }
        .field.full { grid-column: 1 / -1; }
        .inline-form { display: inline-flex; }
        .empty-note { color: var(--muted); }
        @media (max-width: 1100px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { border-right: 0; border-bottom: 1px solid rgba(59,95,86,0.12); }
            .hero-grid, .two-col, .stats-grid, .service-grid, .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-kicker">Jatidiri Baru</span>
            <h1>Jatidiri</h1>
            <p>Workspace administrasi kepegawaian yang dibangun baru di Laravel 12 untuk alur kerja yang lebih rapi dan modern.</p>
        </div>
        <nav class="nav">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('employees.index') }}">Direktori Pegawai</a>
            <a href="{{ route('services.index') }}">Layanan Kepegawaian</a>
            <a href="{{ route('leave-requests.index') }}">Pengajuan Cuti</a>
            <a href="{{ route('study-permits.index') }}">Izin Belajar</a>
            <a href="{{ route('duty-letters.index') }}">Surat Tugas</a>
        </nav>
    </aside>
    <main class="main">
        <div class="topbar">
            <div>
                <h2>@yield('page_title', 'Jatidiri')</h2>
                <p>@yield('page_subtitle', 'Workspace baru berbasis Laravel untuk kebutuhan kepegawaian internal.')</p>
            </div>
            <div class="actions">@yield('actions')</div>
        </div>
        @yield('content')
    </main>
</div>
</body>
</html>