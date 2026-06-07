<?php
/* TV Media Widget: Daftar Pegawai SIKEP & Statistik */
$employees = \App\Models\Employee::orderBy('rank', 'desc')->orderBy('name', 'asc')->get();
$total = $employees->count();

$statsFile = storage_path('app/sikep_stats.json');
$statsData = file_exists($statsFile) ? json_decode(file_get_contents($statsFile), true) : [];

$pages = [];
$perPage = 12;
$empChunks = array_chunk($employees->toArray(), $perPage);

// Tambahkan profil pegawai ke pages
foreach ($empChunks as $chunk) {
    $pages[] = ['type' => 'employees', 'data' => $chunk];
}

// Tambahkan statistik SIKEP ke pages (setiap tab jadi 1 halaman presentasi)
if (is_array($statsData)) {
    foreach ($statsData as $stat) {
        if (!empty($stat['rows'])) {
            $pages[] = ['type' => 'statistic', 'data' => $stat];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil & Statistik Aparatur</title>
    <!-- Modern Typography: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #050505;
            --surface: rgba(255, 255, 255, 0.03);
            --surface-hover: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.08);
            --accent: #3b82f6;
            --accent-glow: rgba(59, 130, 246, 0.5);
            --accent-alt: #10b981;
            --text-1: #ffffff;
            --text-2: #94a3b8;
            --text-3: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-1);
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
            position: relative;
        }

        /* Animated Gradient Blob Background */
        .bg-blob {
            position: absolute;
            filter: blur(120px);
            opacity: 0.4;
            z-index: -1;
            border-radius: 50%;
            animation: float 20s infinite ease-in-out alternate;
        }
        .blob-1 {
            width: 60vw; height: 60vh;
            background: linear-gradient(135deg, #1d4ed8, #4338ca);
            top: -20vh; left: -10vw;
        }
        .blob-2 {
            width: 50vw; height: 50vh;
            background: linear-gradient(135deg, #047857, #065f46);
            bottom: -20vh; right: -10vw;
            animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(5vw, 10vh) scale(1.1); }
            100% { transform: translate(-5vw, -5vh) scale(0.9); }
        }

        /* Header Premium */
        .header {
            padding: 30px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(180deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0) 100%);
            border-bottom: 1px solid var(--border);
            position: relative;
            z-index: 10;
        }
        .header-content h1 {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(90deg, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px;
        }
        .header-content .subtitle {
            font-size: 18px;
            color: var(--text-2);
            font-weight: 400;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .header-stats {
            display: flex;
            gap: 16px;
        }
        .stat-badge {
            background: var(--surface);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            padding: 12px 24px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .stat-badge-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--accent);
            line-height: 1;
        }
        .stat-badge-label {
            font-size: 12px;
            color: var(--text-2);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        /* Slider Engine */
        .slider-container {
            width: 100vw;
            height: calc(100vh - 125px); /* Header minus */
            overflow: hidden;
            position: relative;
            z-index: 5;
        }
        .slides {
            display: flex;
            height: 100%;
            transition: transform 1.2s cubic-bezier(0.77, 0, 0.175, 1);
        }
        .slide {
            min-width: 100vw;
            height: 100%;
            padding: 40px 50px;
            box-sizing: border-box;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        .slide.active-slide {
            opacity: 1;
        }

        /* --- Tampilan Mode: Pegawai --- */
        .emp-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            height: 100%;
            align-content: start;
        }
        .emp-card {
            background: rgba(20, 20, 25, 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, border-color 0.3s ease;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.02), 0 20px 40px rgba(0,0,0,0.3);
        }
        .emp-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--accent-alt));
            opacity: 0.5;
        }
        .emp-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.15);
        }
        .emp-name {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
            line-height: 1.2;
        }
        .emp-pos {
            font-size: 14px;
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .emp-meta {
            margin-top: auto;
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }
        .emp-meta span {
            color: var(--text-2);
        }
        .emp-meta strong {
            color: #fff;
            font-weight: 600;
        }

        /* --- Tampilan Mode: Statistik SIKEP --- */
        .stat-view {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: rgba(20, 20, 25, 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.4);
            animation: float-stat 10s infinite alternate ease-in-out;
        }
        @keyframes float-stat {
            0% { transform: translateY(0); }
            100% { transform: translateY(-10px); }
        }
        .stat-title {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 32px;
            display: inline-flex;
            align-items: center;
            gap: 16px;
        }
        .stat-title::before {
            content: '';
            display: block;
            width: 12px;
            height: 40px;
            background: var(--accent);
            border-radius: 6px;
        }
        .stat-table-wrapper {
            flex: 1;
            overflow: hidden;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: rgba(0,0,0,0.2);
        }
        .stat-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .stat-table th {
            background: rgba(255,255,255,0.05);
            padding: 20px 24px;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-2);
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--border);
        }
        .stat-table td {
            padding: 20px 24px;
            font-size: 18px;
            font-weight: 500;
            border-bottom: 1px solid rgba(255,255,255,0.02);
            color: #fff;
        }
        .stat-table tr:last-child td {
            border-bottom: none;
        }
        .stat-table tbody tr:hover {
            background: rgba(255,255,255,0.02);
        }
        /* Highlight the 'Jumlah' column */
        .stat-table td:last-child {
            color: var(--accent-alt);
            font-weight: 800;
            font-size: 22px;
        }

        /* Empty state */
        .empty-state {
            display: flex;
            width: 100vw;
            height: 100vh;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 24px;
        }
        
        /* Staggered Entrance Animation for Grid Items */
        .slide.active-slide .emp-card {
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }
        @keyframes slideUp {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="bg-blob blob-1"></div>
<div class="bg-blob blob-2"></div>

<div class="header">
    <div class="header-content">
        <h1>Profil & Statistik Aparatur</h1>
        <div class="subtitle">Pengadilan Agama Semarang</div>
    </div>
    <div class="header-stats">
        <div class="stat-badge">
            <div class="stat-badge-value"><?= $total ?></div>
            <div class="stat-badge-label">Total Pegawai</div>
        </div>
        <div class="stat-badge">
            <div class="stat-badge-value"><?= date('Y') ?></div>
            <div class="stat-badge-label">Data SIKEP</div>
        </div>
    </div>
</div>

<?php if (empty($pages)): ?>
    <div class="empty-state">
        <svg style="width:80px;height:80px;color:#3b82f6;opacity:0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        <h2 style="font-size: 28px; font-weight: 300;">Belum ada data pegawai</h2>
        <p style="color: var(--text-2);">Gunakan menu Admin &gt; Sinkronisasi SIKEP untuk memuat data.</p>
    </div>
<?php else: ?>
    <div class="slider-container">
        <div class="slides" id="slides-track">
            <?php foreach ($pages as $pIndex => $page): ?>
                <div class="slide <?= $pIndex === 0 ? 'active-slide' : '' ?>" id="slide-<?= $pIndex ?>">
                    
                    <?php if ($page['type'] === 'employees'): ?>
                        <div class="emp-grid">
                            <?php foreach ($page['data'] as $idx => $emp): ?>
                                <div class="emp-card" style="animation-delay: <?= $idx * 0.05 ?>s;">
                                    <div class="emp-name"><?= htmlspecialchars($emp['name']) ?></div>
                                    <div class="emp-pos"><?= htmlspecialchars($emp['position'] ?: 'Staf') ?></div>
                                    <div class="emp-meta">
                                        <div>
                                            <span>NIP</span><br>
                                            <strong><?= htmlspecialchars($emp['nip']) ?></strong>
                                        </div>
                                        <div style="text-align: right;">
                                            <span>Gol/Ruang</span><br>
                                            <strong><?= htmlspecialchars($emp['rank'] ?: '-') ?></strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($page['type'] === 'statistic'): ?>
                        <div class="stat-view">
                            <h2 class="stat-title"><?= htmlspecialchars($page['data']['title']) ?></h2>
                            <div class="stat-table-wrapper">
                                <table class="stat-table">
                                    <thead>
                                        <tr>
                                            <?php foreach ($page['data']['headers'] as $th): ?>
                                                <th><?= htmlspecialchars($th) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($page['data']['rows'] as $tr): ?>
                                            <tr>
                                                <?php foreach ($tr as $td): ?>
                                                    <td><?= htmlspecialchars($td) ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Auto slide logic
        var totalPages = <?= count($pages) ?>;
        var currentPage = 0;
        var track = document.getElementById('slides-track');
        var slides = document.querySelectorAll('.slide');
        
        if (totalPages > 1) {
            setInterval(function() {
                // Remove active class from old slide to reset animations
                slides[currentPage].classList.remove('active-slide');
                
                currentPage = (currentPage + 1) % totalPages;
                
                // Add active class to new slide to trigger stagger animations
                slides[currentPage].classList.add('active-slide');
                
                // Move track
                track.style.transform = 'translateX(-' + (currentPage * 100) + 'vw)';
            }, 10000); // 10 seconds per page for better readability
        }
    </script>
<?php endif; ?>

</body>
</html>
