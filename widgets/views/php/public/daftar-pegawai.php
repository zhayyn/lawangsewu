<?php
/* TV Media Widget: Daftar Pegawai SIKEP */
$employees = \App\Models\Employee::orderBy('rank', 'desc')->orderBy('name', 'asc')->get();
$total = $employees->count();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pegawai</title>
    <style>
        body {
            margin: 0; padding: 0;
            background: #0f172a;
            color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            overflow: hidden; /* No scrollbar on TV */
        }
        .header {
            padding: 24px 40px;
            background: linear-gradient(90deg, #1e293b, #0f172a);
            border-bottom: 2px solid #3b82f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 600;
        }
        .header .subtitle {
            color: #94a3b8;
            font-size: 18px;
        }
        .total-badge {
            background: #2563eb;
            color: white;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 20px;
            font-weight: bold;
        }
        
        .slider-container {
            width: 100vw;
            height: calc(100vh - 100px); /* Minus header approx height */
            overflow: hidden;
            position: relative;
        }
        
        .slides {
            display: flex;
            transition: transform 1s ease-in-out;
            height: 100%;
        }

        .slide {
            min-width: 100vw;
            height: 100%;
            padding: 40px;
            box-sizing: border-box;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-auto-rows: min-content;
            gap: 24px;
            align-content: start;
        }

        .card {
            background: #1e293b;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            border: 1px solid #334155;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .card-name {
            font-size: 20px;
            font-weight: bold;
            color: #60a5fa;
            line-height: 1.3;
        }

        .card-pos {
            font-size: 16px;
            color: #cbd5e1;
            font-weight: 500;
        }

        .card-meta {
            font-size: 14px;
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 8px;
        }

        .empty-state {
            display: flex;
            width: 100vw;
            height: calc(100vh - 100px);
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 16px;
            color: #64748b;
        }
        
        .empty-state h2 {
            font-size: 24px;
            margin: 0;
        }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1>Profil Aparatur</h1>
        <div class="subtitle">Pengadilan Agama Semarang</div>
    </div>
    <div class="total-badge"><?= $total ?> Pegawai</div>
</div>

<?php if ($total === 0): ?>
    <div class="empty-state">
        <svg style="width:64px;height:64px;color:#475569" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        <h2>Belum ada data pegawai</h2>
        <p>Gunakan menu Admin &gt; Sinkronisasi SIKEP untuk memuat data.</p>
    </div>
<?php else: ?>
    <div class="slider-container">
        <div class="slides" id="slides-track">
            <?php 
            $perPage = 12; // 3 cols x 4 rows
            $pages = array_chunk($employees->toArray(), $perPage);
            foreach ($pages as $page): ?>
                <div class="slide">
                    <?php foreach ($page as $emp): ?>
                        <div class="card">
                            <div class="card-name"><?= htmlspecialchars($emp['name']) ?></div>
                            <div class="card-pos"><?= htmlspecialchars($emp['position'] ?: '-') ?></div>
                            <div class="card-meta">
                                <div>NIP: <?= htmlspecialchars($emp['nip']) ?></div>
                                <div>Gol/Ruang: <?= htmlspecialchars($emp['rank'] ?: '-') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Auto slide logic
        var totalPages = <?= count($pages) ?>;
        var currentPage = 0;
        var track = document.getElementById('slides-track');
        
        if (totalPages > 1) {
            setInterval(function() {
                currentPage = (currentPage + 1) % totalPages;
                track.style.transform = 'translateX(-' + (currentPage * 100) + 'vw)';
            }, 8000); // 8 seconds per page
        }
    </script>
<?php endif; ?>

</body>
</html>
