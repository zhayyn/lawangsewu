<?php
/* developed by dubes favour-it */
$y = (int)date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Perkara</title>

    <meta property="og:title" content="Statistik Komposisi Perkara - Lawangsewu">
    <meta property="og:description" content="Visualisasi data real-time komposisi jenis perkara tahun berjalan di Pengadilan Agama Semarang.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://lawangsewu.pa-semarang.go.id/statistik-perkara">
    
    <!-- Warning: Ensure the route is registered in routes/web.php -->
    <meta property="og:image" content="https://lawangsewu.pa-semarang.go.id/og-image/case-statistics.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="800">
    <meta property="og:image:height" content="400">
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Statistik Komposisi Perkara - Lawangsewu">
    <meta name="twitter:image" content="https://lawangsewu.pa-semarang.go.id/og-image/case-statistics.png">
    
    <?php if ($ga_id = config('services.google.analytics_id')): ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga_id) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= htmlspecialchars($ga_id) ?>');
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: #eef4f1;
            --panel: #ffffff;
            --text: #17212f;
            --muted: #6b7280;
            --line: #e5e7eb;
            --primary: #0d6b41;
            --primary-2: #12714a;
            --shadow: 0 10px 24px rgba(7, 39, 24, .09);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Inter", "Segoe UI", Arial, sans-serif; background: radial-gradient(circle at top right, #f4fbf7 0%, var(--bg) 35%); color: var(--text); }
        .container { max-width: 1280px; margin: 18px auto 28px; padding: 0 14px; }
        .head { background: linear-gradient(120deg, #073d26, #0d6b41 58%, #16975c); color: #fff; border-radius: 16px; padding: 20px; box-shadow: var(--shadow); }
        .head h1 { margin: 0; font-size: 26px; letter-spacing: .2px; }
        .head p { margin: 6px 0 0; opacity: .94; }
        .cards { display: grid; grid-template-columns: repeat(5, minmax(120px, 1fr)); gap: 10px; margin-top: 14px; }
        .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 12px; text-align: center; box-shadow: 0 2px 8px rgba(15, 23, 42, .03); transition: transform .16s ease, box-shadow .16s ease; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 10px 16px rgba(15, 23, 42, .07); }
        .card .label { font-size: 12px; color: var(--muted); }
        .card .value { font-size: 24px; font-weight: 800; color: var(--primary); margin-top: 4px; }
        .kpi-strip { display: grid; grid-template-columns: repeat(5, minmax(120px, 1fr)); gap: 10px; margin-top: 10px; }
        .kpi { background: linear-gradient(180deg, #ffffff, #f7fbf8); border: 1px solid #dbe7df; border-radius: 12px; padding: 10px 12px; }
        .kpi .k { font-size: 11px; color: #5f6b7a; text-transform: uppercase; letter-spacing: .35px; }
        .kpi .v { font-size: 20px; margin-top: 6px; color: #0f5132; font-weight: 800; }
        .kpi.warn { border-color: #f8d6b3; background: linear-gradient(180deg, #fffaf3, #fff); }
        .panel { margin-top: 14px; background: var(--panel); border-radius: 14px; border: 1px solid var(--line); overflow: hidden; box-shadow: var(--shadow); }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 12px; border-bottom: 1px solid #edf0f4; flex-wrap: wrap; }
        .tools { display: flex; gap: 8px; align-items: center; }
        .input { border: 1px solid #d6dde6; background: #fff; border-radius: 9px; padding: 8px 10px; min-width: 240px; outline: none; }
        .input:focus { border-color: #0d6b41; box-shadow: 0 0 0 3px rgba(13,107,65,.12); }
        .btn { border: 0; background: linear-gradient(135deg, var(--primary), var(--primary-2)); color: #fff; border-radius: 9px; padding: 8px 12px; cursor: pointer; font-weight: 700; }
        .btn:hover { opacity: .95; }
        .btn-tv { background: #111827; }
        .select { border: 1px solid #d6dde6; background: #fff; border-radius: 9px; padding: 8px 10px; min-width: 86px; }
        .status { font-size: 13px; color: var(--muted); }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 12px; }
        .chart-box { border: 1px solid var(--line); border-radius: 12px; padding: 10px; background: #fff; }
        .chart-box h3 { margin: 2px 0 8px; font-size: 14px; color: #0f5132; }
        .legend-wrap { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .legend-item { display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 999px; padding: 4px 9px; font-size: 12px; color: #334155; }
        .legend-dot { width: 10px; height: 10px; border-radius: 999px; box-shadow: 0 1px 3px rgba(0,0,0,.18); }
        .table-wrap { max-height: 58vh; overflow: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid var(--line); padding: 10px; font-size: 13px; }
        thead th { background: #f0f6f2; color: #0f5132; position: sticky; top: 0; z-index: 1; }
        tbody tr:hover { background: #f8fbf9; }
        td.right { text-align: right; }
        td.center { text-align: center; }
        tr.total { background: #f7faf8; font-weight: 800; }
        .footnote { text-align: center; margin: 14px 0 20px; color: var(--muted); font-size: 13px; }
        .meta { display: inline-flex; align-items: center; gap: 8px; margin-left: 8px; color: #4b5563; font-size: 12px; }
        .badge { background: #f3f6f4; border: 1px solid #dde5df; color: #274a36; border-radius: 999px; padding: 2px 8px; font-weight: 700; }
        .error { background: #fff3f2; color: #b42318; border: 1px solid #fecdca; padding: 10px; border-radius: 8px; margin: 12px; display: none; }
        .tv-mode .tv-slide { display: none; }
        .tv-mode .tv-slide.active { display: block; }
        .tv-mode .table-wrap.active { max-height: 72vh; }
        @media (max-width: 980px) { .cards, .kpi-strip { grid-template-columns: repeat(2, 1fr);} .grid { grid-template-columns: 1fr; } .input{min-width:180px;} }
        @media (max-width: 640px) { .container { padding: 0 10px; } .card .value { font-size: 20px; } .tools{ width:100%; } .input{ min-width: 0; flex:1; } }
    </style>
</head>
<body>
<div class="container">
    <div class="head">
        <h1>Statistik Perkara</h1>
        <p>Perbandingan Jumlah Penerimaan Perkara (5 Tahun Terakhir)</p>
    </div>

    <div class="cards" id="summaryCards">
        <div class="card"><div class="label"><?php echo $y-4; ?></div><div class="value" id="sum4">0</div></div>
        <div class="card"><div class="label"><?php echo $y-3; ?></div><div class="value" id="sum3">0</div></div>
        <div class="card"><div class="label"><?php echo $y-2; ?></div><div class="value" id="sum2">0</div></div>
        <div class="card"><div class="label"><?php echo $y-1; ?></div><div class="value" id="sum1">0</div></div>
        <div class="card"><div class="label"><?php echo $y; ?></div><div class="value" id="sum0">0</div></div>
    </div>
    <div class="kpi-strip" id="kpiStrip">
        <div class="kpi"><div class="k">Sisa Tahun Lalu</div><div class="v" id="kpiSisaLalu">0</div></div>
        <div class="kpi"><div class="k">Masuk Tahun Ini</div><div class="v" id="kpiMasuk">0</div></div>
        <div class="kpi"><div class="k">Diputus Tahun Ini</div><div class="v" id="kpiPutus">0</div></div>
        <div class="kpi"><div class="k">Belum Putus</div><div class="v" id="kpiBelumPutus">0</div></div>
        <div class="kpi warn"><div class="k">Clearance Rate</div><div class="v" id="kpiCR">0%</div></div>
    </div>

    <div class="panel">
        <div class="toolbar">
            <div class="status" id="statusText">Memuat data... <span class="meta" id="dataMeta"></span></div>
            <div class="tools">
                <input class="input" id="searchJenis" type="text" placeholder="Cari jenis perkara...">
                <button class="btn" id="btnRefresh" type="button">Refresh</button>
                <select class="select" id="tvDuration" title="Durasi rotate">
                    <option value="5000">5 dtk</option>
                    <option value="10000" selected>10 dtk</option>
                    <option value="15000">15 dtk</option>
                </select>
                <button class="btn btn-tv" id="btnTvMode" type="button">Mode TV: OFF</button>
            </div>
        </div>
        <div class="grid tv-slide" data-tv-slide>
            <div class="chart-box">
                <h3>Tren Total Penerimaan 5 Tahun</h3>
                <canvas id="chartTrend" height="120"></canvas>
            </div>
            <div class="chart-box">
                <h3>Komposisi Jenis Perkara Tahun Berjalan</h3>
                <canvas id="chartType" height="120"></canvas>
                <div class="legend-wrap" id="legendType"></div>
            </div>
        </div>
        <div class="error" id="errorBox"></div>
        <div class="table-wrap tv-slide" data-tv-slide>
            <table>
                <thead>
                    <tr>
                        <th rowspan="2" style="width:50px;">NO</th>
                        <th rowspan="2">JENIS PERKARA</th>
                        <th colspan="5">Perbandingan Tahun</th>
                    </tr>
                    <tr>
                        <th><?php echo $y-4; ?></th>
                        <th><?php echo $y-3; ?></th>
                        <th><?php echo $y-2; ?></th>
                        <th><?php echo $y-1; ?></th>
                        <th><?php echo $y; ?></th>
                    </tr>
                </thead>
                <tbody id="rowsBody"></tbody>
            </table>
        </div>
    </div>

    <div class="footnote">Sumber: SIPP (via adapter Lawangsewu)</div>
</div>

<script>
const rowsBody = document.getElementById('rowsBody');
const statusText = document.getElementById('statusText');
const errorBox = document.getElementById('errorBox');
const dataMeta = document.getElementById('dataMeta');
const btnRefresh = document.getElementById('btnRefresh');
const searchJenis = document.getElementById('searchJenis');
const btnTvMode = document.getElementById('btnTvMode');
const tvDuration = document.getElementById('tvDuration');
const API_CANDIDATES = [
    '/api/statistik-data',
    '/lawangsewu/api/statistik-data',
    '/lawangsewu/statistik-data',
    'statistik-data',
    '/statistik-data'
];
let trendChart = null;
let typeChart = null;
let cachedRows = [];
let cachedTotals = { th4:0, th3:0, th2:0, th1:0, th0:0 };
let cachedRingkasan = { sisa_lalu: 0, masuk: 0, putus: 0, belum_putus: 0 };
let tvMode = false;
let tvTimer = null;
let tvIndex = 0;
const tvSlides = Array.from(document.querySelectorAll('[data-tv-slide]'));
const palette = ['#00C2FF','#7ED321','#00B8D9','#FFB020','#7A5AF8','#EC4899','#F97316','#22C55E','#6366F1','#E11D48','#14B8A6','#A855F7'];
const fixedLabelColors = {
    'Cerai Gugat': '#7ED321',
    'Cerai Talak': '#00B8D9',
    'Asal Usul Anak': '#00C2FF',
    'Dispensasi Kawin': '#6366F1',
    'Izin Poligami': '#F97316',
    'Kewarisan': '#EC4899',
    'Lain-Lain': '#A855F7',
    'Perwalian': '#14B8A6',
    'P3HP/Penetapan Ahli Waris': '#7A5AF8',
    'Pengesahan Perkawinan/Istbat Nikah': '#22C55E',
    'Penguasaan Anak': '#E11D48',
    'Wali Adhol': '#FFB020'
};

const chartShadowPlugin = {
    id: 'chartShadowPlugin',
    beforeDatasetDraw(chart) {
        const { ctx } = chart;
        ctx.save();
        ctx.shadowColor = 'rgba(17,24,39,0.22)';
        ctx.shadowBlur = 10;
        ctx.shadowOffsetY = 5;
    },
    afterDatasetDraw(chart) {
        chart.ctx.restore();
    }
};

function colorByLabel(label) {
    const key = String(label || '').trim();
    if (fixedLabelColors[key]) {
        return fixedLabelColors[key];
    }
    let hash = 0;
    for (let i = 0; i < key.length; i += 1) {
        hash = ((hash << 5) - hash) + key.charCodeAt(i);
        hash |= 0;
    }
    const idx = Math.abs(hash) % palette.length;
    return palette[idx];
}

function fmt(n) {
    return new Intl.NumberFormat('id-ID').format(Number(n || 0));
}

function setSums(t) {
    document.getElementById('sum4').textContent = fmt(t.th4);
    document.getElementById('sum3').textContent = fmt(t.th3);
    document.getElementById('sum2').textContent = fmt(t.th2);
    document.getElementById('sum1').textContent = fmt(t.th1);
    document.getElementById('sum0').textContent = fmt(t.th0);
}

function setKpi(ringkasan) {
    document.getElementById('kpiSisaLalu').textContent = fmt(ringkasan.sisa_lalu);
    document.getElementById('kpiMasuk').textContent = fmt(ringkasan.masuk);
    document.getElementById('kpiPutus').textContent = fmt(ringkasan.putus);
    document.getElementById('kpiBelumPutus').textContent = fmt(ringkasan.belum_putus);
    const masuk = Number(ringkasan.masuk || 0);
    const putus = Number(ringkasan.putus || 0);
    const cr = masuk > 0 ? (putus / masuk) * 100 : 0;
    document.getElementById('kpiCR').textContent = `${cr.toFixed(1).replace('.', ',')}%`;
}

function totalsFromRows(rows) {
    return rows.reduce((acc, row) => {
        acc.th4 += Number(row.th4 || 0);
        acc.th3 += Number(row.th3 || 0);
        acc.th2 += Number(row.th2 || 0);
        acc.th1 += Number(row.th1 || 0);
        acc.th0 += Number(row.th0 || 0);
        return acc;
    }, { th4: 0, th3: 0, th2: 0, th1: 0, th0: 0 });
}

function normalizeRows(rows) {
    return rows.map((row) => ({
        jenis: String(row.jenis || '-'),
        th4: Number(row.th4 || 0),
        th3: Number(row.th3 || 0),
        th2: Number(row.th2 || 0),
        th1: Number(row.th1 || 0),
        th0: Number(row.th0 || 0)
    }));
}

function applyDataMeta(source, totalsTrusted) {
    const now = new Date();
    const stamp = now.toLocaleString('id-ID', { hour12: false });
    const sourceBadge = `<span class="badge">${source}</span>`;
    const trustBadge = totalsTrusted
        ? '<span class="badge">totals: sinkron</span>'
        : '<span class="badge">totals: rekalkulasi</span>';
    dataMeta.innerHTML = `${sourceBadge}${trustBadge}<span>update ${stamp}</span>`;
}

function renderColorLegend(targetId, labels, colors) {
    const el = document.getElementById(targetId);
    if (!el) {
        return;
    }
    const html = labels.map((label, index) => (
        `<span class="legend-item"><span class="legend-dot" style="background:${colors[index]}"></span>${label}</span>`
    )).join('');
    el.innerHTML = html;
}

function renderCharts(year, totals, rows) {
    if (!window.Chart) {
        return;
    }

    const trendEl = document.getElementById('chartTrend');
    const typeEl = document.getElementById('chartType');

    const labels = [year-4, year-3, year-2, year-1, year];
    const values = [totals.th4, totals.th3, totals.th2, totals.th1, totals.th0].map(v => Number(v || 0));

    if (trendChart) trendChart.destroy();
    trendChart = new Chart(trendEl, {
        type: 'line',
        plugins: [chartShadowPlugin],
        data: {
            labels,
            datasets: [{
                label: 'Total Perkara',
                data: values,
                tension: .35,
                borderColor: '#00B8D9',
                backgroundColor: 'rgba(0,184,217,.20)',
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            interaction: { mode: 'index', intersect: false },
            scales: { 
                y: { 
                    min: 300,
                    title: {
                        display: true,
                        text: 'Jumlah Perkara',
                        font: { size: 12 },
                        color: '#6b7280'
                    }
                } 
            }
        }
    });

    const sorted = [...rows].sort((a,b) => Number(b.th0||0) - Number(a.th0||0)).slice(0, 12);
    const rowColors = sorted.map(r => colorByLabel(r.jenis));
    const rowLabels = sorted.map(r => r.jenis || '-');
    if (typeChart) typeChart.destroy();
    typeChart = new Chart(typeEl, {
        type: 'bar',
        plugins: [chartShadowPlugin],
        data: {
            labels: rowLabels,
            datasets: [{
                label: 'Jumlah Perkara',
                data: sorted.map(r => Number(r.th0 || 0)),
                backgroundColor: rowColors,
                borderColor: rowColors,
                borderWidth: 1.5,
                borderRadius: 8,
                maxBarThickness: 44
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { type: 'logarithmic' },
                x: { ticks: { maxRotation: 45, minRotation: 30 } }
            }
        }
    });

    renderColorLegend('legendType', rowLabels, rowColors);
}

function renderRows() {
    const keyword = (searchJenis.value || '').toLowerCase().trim();
    const rows = keyword
        ? cachedRows.filter(r => String(r.jenis || '').toLowerCase().includes(keyword))
        : cachedRows;

    let i = 0;
    let html = '';
    rows.forEach((r) => {
        i += 1;
        html += `\n<tr>\n<td class="center">${i}</td>\n<td>${r.jenis}</td>\n<td class="right">${fmt(r.th4)}</td>\n<td class="right">${fmt(r.th3)}</td>\n<td class="right">${fmt(r.th2)}</td>\n<td class="right">${fmt(r.th1)}</td>\n<td class="right">${fmt(r.th0)}</td>\n</tr>`;
    });

    html += `\n<tr class="total">\n<td colspan="2" class="right">TOTAL</td>\n<td class="right">${fmt(cachedTotals.th4)}</td>\n<td class="right">${fmt(cachedTotals.th3)}</td>\n<td class="right">${fmt(cachedTotals.th2)}</td>\n<td class="right">${fmt(cachedTotals.th1)}</td>\n<td class="right">${fmt(cachedTotals.th0)}</td>\n</tr>`;
    rowsBody.innerHTML = html;
}

function showTvSlide(index) {
    tvSlides.forEach((slide, i) => slide.classList.toggle('active', i === index));
}

function startTvMode() {
    if (tvSlides.length === 0) return;
    tvMode = true;
    tvIndex = 0;
    document.body.classList.add('tv-mode');
    showTvSlide(tvIndex);
    btnTvMode.textContent = 'Mode TV: ON';
    tvTimer = window.setInterval(() => {
        tvIndex = (tvIndex + 1) % tvSlides.length;
        showTvSlide(tvIndex);
    }, Number(tvDuration.value || 10000));
}

function stopTvMode() {
    tvMode = false;
    document.body.classList.remove('tv-mode');
    tvSlides.forEach(slide => slide.classList.add('active'));
    btnTvMode.textContent = 'Mode TV: OFF';
    if (tvTimer) {
        window.clearInterval(tvTimer);
        tvTimer = null;
    }
}

function toggleTvMode() {
    if (tvMode) {
        stopTvMode();
    } else {
        startTvMode();
    }
}

async function fetchStatistikPerkara(year) {
    let lastError = 'endpoint tidak ditemukan';
    for (const base of API_CANDIDATES) {
        const joiner = base.includes('?') ? '&' : '?';
        const url = `${base}${joiner}hal=perkara&tahun=${year}&t=${Date.now()}`;
        try {
            const res = await fetch(url, { cache: 'no-store' });
            if (!res.ok) {
                lastError = `HTTP ${res.status} di ${base}`;
                continue;
            }
            const json = await res.json();
            if (json && json.ok) {
                json._source = base;
                return json;
            }
            lastError = (json && json.message) ? json.message : `Respons tidak valid dari ${base}`;
        } catch (e) {
            lastError = `${base}: ${e.message}`;
        }
    }
    throw new Error('Endpoint statistik tidak terjangkau (' + lastError + ')');
}

async function loadData() {
    statusText.textContent = 'Mengambil data statistik dari Server 10...';
    dataMeta.innerHTML = '';
    errorBox.style.display = 'none';
    rowsBody.innerHTML = '';

    try {
        const year = new Date().getFullYear();
        const json = await fetchStatistikPerkara(year);

        cachedRows = normalizeRows(Array.isArray(json.rows) ? json.rows : []);
        const apiTotals = {
            th4: Number(json?.totals?.th4 || 0),
            th3: Number(json?.totals?.th3 || 0),
            th2: Number(json?.totals?.th2 || 0),
            th1: Number(json?.totals?.th1 || 0),
            th0: Number(json?.totals?.th0 || 0),
        };
        const calcTotals = totalsFromRows(cachedRows);
        const totalsTrusted = JSON.stringify(apiTotals) === JSON.stringify(calcTotals);
        cachedTotals = totalsTrusted ? apiTotals : calcTotals;
        cachedRingkasan = {
            sisa_lalu: Number(json?.ringkasan?.sisa_lalu || 0),
            masuk: Number(json?.ringkasan?.masuk || 0),
            putus: Number(json?.ringkasan?.putus || 0),
            belum_putus: Number(json?.ringkasan?.belum_putus || 0),
        };
        setSums(cachedTotals);
        setKpi(cachedRingkasan);
        renderRows();
        renderCharts(year, cachedTotals, cachedRows);
        applyDataMeta(json._source || 'unknown', totalsTrusted);
        statusText.textContent = 'Data berhasil dimuat.';
    } catch (err) {
        errorBox.textContent = err.message;
        errorBox.style.display = 'block';
        statusText.textContent = 'Gagal memuat data.';
    }
}

btnRefresh.addEventListener('click', loadData);
searchJenis.addEventListener('input', renderRows);
btnTvMode.addEventListener('click', toggleTvMode);
tvDuration.addEventListener('change', () => {
    if (tvMode) {
        stopTvMode();
        startTvMode();
    }
});

// Auto-refresh data secara background setiap 2 menit
let dataRefreshTimer = null;
function startDataAutoRefresh() {
    if (dataRefreshTimer) clearInterval(dataRefreshTimer);
    dataRefreshTimer = window.setInterval(() => {
        const year = new Date().getFullYear();
        fetchStatistikPerkara(year).then(json => {
            if(json && json.ok) {
                cachedRows = normalizeRows(Array.isArray(json.rows) ? json.rows : []);
                const apiTotals = {
                    th4: Number(json?.totals?.th4 || 0),
                    th3: Number(json?.totals?.th3 || 0),
                    th2: Number(json?.totals?.th2 || 0),
                    th1: Number(json?.totals?.th1 || 0),
                    th0: Number(json?.totals?.th0 || 0),
                };
                const calcTotals = totalsFromRows(cachedRows);
                const totalsTrusted = JSON.stringify(apiTotals) === JSON.stringify(calcTotals);
                cachedTotals = totalsTrusted ? apiTotals : calcTotals;
                cachedRingkasan = {
                    sisa_lalu: Number(json?.ringkasan?.sisa_lalu || 0),
                    masuk: Number(json?.ringkasan?.masuk || 0),
                    putus: Number(json?.ringkasan?.putus || 0),
                    belum_putus: Number(json?.ringkasan?.belum_putus || 0),
                };
                setSums(cachedTotals);
                setKpi(cachedRingkasan);
                renderRows();
                renderCharts(year, cachedTotals, cachedRows);
                applyDataMeta(json._source || 'unknown', totalsTrusted);
            }
        }).catch(err => console.error("Auto-refresh gagal:", err));
    }, 120000); // 120,000 ms = 2 menit
}

document.addEventListener('DOMContentLoaded', () => {
    tvSlides.forEach(slide => slide.classList.add('active'));
    loadData().then(startDataAutoRefresh);
});
</script>
</body>
</html>
<!-- developed by zhayyn™ -->
