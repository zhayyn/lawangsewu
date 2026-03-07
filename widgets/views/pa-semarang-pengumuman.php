<?php
/* developed by dubes favour-it */

$allowedSource = ['all', 'ma', 'badilag'];
$source = strtolower(trim((string)($_GET['source'] ?? 'all')));
if (!in_array($source, $allowedSource, true)) {
    $source = 'all';
}
$uiSource = in_array($source, ['ma', 'badilag'], true) ? $source : 'ma';

$limit = (int)($_GET['limit'] ?? 8);
if ($limit < 1) {
    $limit = 1;
}
if ($limit > 20) {
    $limit = 20;
}

$apiBase = '/lawangsewu/api/pengumuman-rss';
$apiUrl = $apiBase . '?source=' . rawurlencode($source) . '&limit=' . $limit;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed Pengumuman MA & Badilag</title>
    <style>
        :root {
            --line:#e4e4e7;
            --muted:#6b7280;
            --bg:#f4f6f8;
            --card:#ffffff;
            --title:#055b52;
            --accent:#c9a35d;
            --deep:#0f172a;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            font-family:"Inter","Segoe UI",Arial,sans-serif;
            background:var(--bg);
            color:#1f2937;
        }
        .wrap {
            max-width:920px;
            margin:0 auto;
            padding:12px;
        }
        .head {
            display:flex;
            align-items:flex-start;
            gap:14px;
            margin-bottom:14px;
        }
        .head-icon {
            width:52px;
            height:52px;
            border-radius:8px;
            background:#065f46;
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:28px;
            font-weight:700;
            flex:0 0 52px;
            box-shadow:0 6px 14px rgba(6,95,70,.22);
        }
        .head-main { flex:1; }
        .head h2 {
            margin:0;
            font-size:40px;
            letter-spacing:.5px;
            line-height:1;
            color:#005f57;
        }
        .head-sub {
            margin:4px 0 0;
            font-size:28px;
            color:#0f172a;
            font-weight:500;
        }
        .head-line {
            width:220px;
            height:4px;
            background:var(--accent);
            border-radius:4px;
            margin-top:10px;
        }
        .toolbar {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
            flex-wrap:wrap;
            margin-bottom:12px;
        }
        .meta {
            font-size:12px;
            color:var(--muted);
        }
        .tools {
            display:flex;
            align-items:center;
            gap:8px;
            flex-wrap:wrap;
        }
        .btn {
            border:1px solid #d1d5db;
            border-radius:10px;
            padding:8px 11px;
            font-size:13px;
            background:#fff;
        }
        .btn {
            border:0;
            background:linear-gradient(135deg,#0d6b41,#12714a);
            color:#fff;
            font-weight:700;
            cursor:pointer;
        }
        .btn:hover { opacity:.95; }
        .tabs {
            display:flex;
            align-items:center;
            gap:8px;
            flex-wrap:wrap;
        }
        .tab-btn {
            border:1px solid #d1d5db;
            background:#fff;
            color:#334155;
            border-radius:999px;
            padding:8px 12px;
            font-size:12px;
            font-weight:700;
            cursor:pointer;
            transition:all .15s ease;
        }
        .tab-btn.active {
            background:linear-gradient(135deg,#0d6b41,#12714a);
            color:#fff;
            border-color:#0d6b41;
            box-shadow:0 5px 12px rgba(13,107,65,.22);
        }
        .err {
            display:none;
            margin:10px 0;
            border:1px solid #fecdca;
            border-radius:8px;
            background:#fff3f2;
            color:#b42318;
            padding:9px 10px;
            font-size:13px;
        }
        .list {
            list-style:none;
            margin:0;
            padding:0;
        }
        .item {
            position:relative;
            display:grid;
            grid-template-columns:72px 1fr auto;
            align-items:center;
            gap:14px;
            background:#fff;
            border:1px solid var(--line);
            border-radius:14px;
            padding:12px;
            margin-bottom:10px;
            box-shadow:0 3px 10px rgba(15,23,42,.05);
        }
        .thumb {
            width:72px;
            height:72px;
            border-radius:10px;
            object-fit:cover;
            border:1px solid #d6dbe3;
            background:#e2e8f0;
        }
        .rss-icon {
            width:18px;
            height:18px;
            color:#c6a15a;
            opacity:.95;
            margin-bottom:auto;
        }
        .title-link {
            text-decoration:none;
            color:var(--deep);
            font-weight:600;
            line-height:1.4;
            font-size:22px;
        }
        .title-link:hover {
            color:#0d6b41;
            text-decoration:underline;
        }
        .meta-row {
            display:flex;
            align-items:center;
            gap:14px;
            color:#6b7280;
            font-size:12px;
            margin-top:8px;
            flex-wrap:wrap;
        }
        .chip {
            display:inline-flex;
            align-items:center;
            gap:5px;
        }
        .chip svg { width:13px; height:13px; }
        .src-badge {
            display:inline-flex;
            align-items:center;
            border-radius:999px;
            background:#ecfdf5;
            color:#065f46;
            font-size:10px;
            font-weight:700;
            padding:3px 8px;
            margin-top:8px;
        }
        .empty {
            color:var(--muted);
            font-size:13px;
            padding:10px;
        }
        @media (max-width:860px) {
            .head h2 { font-size:32px; }
            .head-sub { font-size:22px; }
            .title-link { font-size:18px; }
        }
        @media (max-width:640px) {
            .head { gap:10px; }
            .head-icon { width:44px; height:44px; font-size:22px; flex-basis:44px; }
            .head h2 { font-size:28px; }
            .head-sub { font-size:17px; }
            .head-line { width:170px; }
            .tools { width:100%; }
            .tabs { width:100%; }
            .tab-btn { flex:1; text-align:center; }
            .item { grid-template-columns:58px 1fr 16px; gap:10px; padding:10px; }
            .thumb { width:58px; height:58px; }
            .title-link { font-size:15px; }
            .meta-row { gap:10px; font-size:11px; }
            .rss-icon { width:16px; height:16px; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <div class="head-icon">📢</div>
        <div class="head-main">
            <h2>PENGUMUMAN</h2>
            <p class="head-sub">Informasi Resmi Peradilan</p>
            <div class="head-line"></div>
        </div>
    </div>

    <div id="feedCard" data-api="<?php echo htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="toolbar">
            <div class="tools">
                <div class="tabs" id="sourceTabs">
                    <button class="tab-btn <?php echo $uiSource === 'ma' ? 'active' : ''; ?>" type="button" data-source="ma">Mahkamah Agung</button>
                    <button class="tab-btn <?php echo $uiSource === 'badilag' ? 'active' : ''; ?>" type="button" data-source="badilag">Badilag</button>
                </div>
            </div>
            <div class="meta" id="metaInfo">Memuat data...</div>
        </div>
        <div class="err" id="errBox"></div>
        <ul class="list" id="feedList"></ul>
    </div>
</div>

<script>
const card = document.getElementById('feedCard');
const list = document.getElementById('feedList');
const errBox = document.getElementById('errBox');
const metaInfo = document.getElementById('metaInfo');
const sourceTabs = document.getElementById('sourceTabs');
const tabButtons = Array.from(document.querySelectorAll('.tab-btn'));
let activeSource = '<?php echo $uiSource; ?>';

const THUMB_MA = 'https://www.mahkamahagung.go.id/assets/img/gedung.jpg';
const THUMB_MA_FALLBACK = 'https://www.mahkamahagung.go.id/files/templates/post_1.jpg';
const THUMB_MA_LOCAL = '/lawangsewu/widgets/assets/ma-fallback.jpg';
const THUMB_BADILAG = '/lawangsewu/widgets/assets/badilag-thumb.svg';

function escHtml(text) {
    return String(text || '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[char]));
}

function flatten(payload) {
    if (payload && payload.bySource) {
        const ma = payload.bySource.ma && Array.isArray(payload.bySource.ma.items) ? payload.bySource.ma.items : [];
        const badilag = payload.bySource.badilag && Array.isArray(payload.bySource.badilag.items) ? payload.bySource.badilag.items : [];
        return [...ma, ...badilag];
    }
    return payload && Array.isArray(payload.items) ? payload.items : [];
}

function normalizeDateFromTitle(title) {
    const text = String(title || '');
    const m1 = text.match(/(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?/);
    if (m1) {
        const year = m1[3] ? Number(m1[3].length === 2 ? ('20' + m1[3]) : m1[3]) : new Date().getFullYear();
        return new Date(year, Number(m1[2]) - 1, Number(m1[1]));
    }

    const bulan = {
        januari: 0, februari: 1, maret: 2, april: 3, mei: 4, juni: 5,
        juli: 6, agustus: 7, september: 8, oktober: 9, november: 10, desember: 11
    };
    const m2 = text.match(/(\d{1,2})\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\s+(\d{4})/i);
    if (m2) {
        return new Date(Number(m2[3]), bulan[m2[2].toLowerCase()], Number(m2[1]));
    }
    return null;
}

function formatDateId(dateObj) {
    if (!(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) {
        return '-';
    }
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(dateObj);
}

function relativeFromDate(dateObj) {
    if (!(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) {
        return '-';
    }
    const diff = Date.now() - dateObj.getTime();
    const hours = Math.max(Math.floor(diff / (1000 * 60 * 60)), 0);
    if (hours < 24) {
        return `${hours} jam lalu`;
    }
    const days = Math.floor(hours / 24);
    return `${days} hari lalu`;
}

function cleanTitle(rawTitle) {
    return String(rawTitle || '')
        .replace(/\|\s*\(\d{1,2}\/\d{1,2}(?:\/\d{2,4})?\)/gi, '')
        .replace(/(senin|selasa|rabu|kamis|jumat|sabtu|minggu),\s*\d{1,2}\s+[A-Za-z]+\s+\d{4}.*$/i, '')
        .replace(/\s{2,}/g, ' ')
        .trim();
}

function rssIconSvg() {
    return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4a2 2 0 1 0 0 4c6.627 0 12 5.373 12 12a2 2 0 1 0 4 0C20 11.163 12.837 4 4 4Zm0 8a2 2 0 1 0 0 4 4 4 0 0 1 4 4 2 2 0 1 0 4 0c0-4.418-3.582-8-8-8Zm0 8a2 2 0 1 0 0 .001A2 2 0 0 0 4 20Z"/></svg>';
}

function calendarSvg() {
    return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-9ZM5 6a1 1 0 0 0-1 1v1h16V7a1 1 0 0 0-1-1H5Z"/></svg>';
}

function clockSvg() {
    return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm1 5a1 1 0 0 0-2 0v5a1 1 0 0 0 .293.707l3 3a1 1 0 1 0 1.414-1.414L13 11.586V7Z"/></svg>';
}

function render(items) {
    if (!Array.isArray(items) || items.length === 0) {
        list.innerHTML = '<li class="empty">Belum ada pengumuman yang dapat ditampilkan.</li>';
        return;
    }

    const html = items.map((item) => {
        const src = String(item.source || '').toLowerCase() === 'badilag' ? 'Badilag' : 'Mahkamah Agung';
        const thumb = src === 'Badilag' ? THUMB_BADILAG : THUMB_MA;
        const parsedDate = normalizeDateFromTitle(item.title);
        const dateText = formatDateId(parsedDate);
        const relativeText = relativeFromDate(parsedDate);
        const titleText = cleanTitle(item.title) || item.title;

        return `<li class="item">
            <img class="thumb" src="${escHtml(thumb)}" data-fallback="${escHtml(THUMB_MA_FALLBACK)}" data-fallback-local="${escHtml(THUMB_MA_LOCAL)}" alt="${escHtml(src)}" loading="lazy" onerror="if(!this.dataset.fallbackDone&&this.dataset.fallback&&this.src!==this.dataset.fallback){this.dataset.fallbackDone='1';this.src=this.dataset.fallback;return;}if(!this.dataset.fallbackLocalDone&&this.dataset.fallbackLocal&&this.src!==this.dataset.fallbackLocal){this.dataset.fallbackLocalDone='1';this.src=this.dataset.fallbackLocal;return;}this.style.opacity='0.35'">
            <div>
                <a class="title-link" href="${escHtml(item.url)}" target="_blank" rel="noopener noreferrer">${escHtml(titleText)}</a>
                <div class="meta-row">
                    <span class="chip">${calendarSvg()} ${escHtml(dateText)}</span>
                    <span class="chip">${clockSvg()} ${escHtml(relativeText)}</span>
                </div>
                <span class="src-badge">${src}</span>
            </div>
            <span class="rss-icon">${rssIconSvg()}</span>
        </li>`;
    }).join('');
    list.innerHTML = html;
    postFrameHeight();
}

function postFrameHeight() {
    if (window.parent === window) {
        return;
    }
    const height = Math.max(
        document.body.scrollHeight,
        document.documentElement.scrollHeight,
        document.body.offsetHeight,
        document.documentElement.offsetHeight
    );
    window.parent.postMessage({
        type: 'LAWANGSEWU_IFRAME_RESIZE',
        height,
        frame: 'pa-semarang-pengumuman'
    }, '*');
}

async function loadFeed() {
    errBox.style.display = 'none';
    metaInfo.textContent = 'Memuat data...';

    try {
        const seed = card.getAttribute('data-api') || '/lawangsewu/api/pengumuman-rss?source=all&limit=8';
        const urlObj = new URL(seed, window.location.origin);
        urlObj.searchParams.set('source', activeSource);
        urlObj.searchParams.set('t', Date.now().toString());

        const res = await fetch(urlObj.toString(), { cache: 'no-store' });
        const payload = await res.json();
        if (!payload.ok) {
            throw new Error(payload.message || 'Gagal mengambil data pengumuman');
        }

        const rows = flatten(payload).slice(0, <?php echo $limit; ?>);
        render(rows);
        metaInfo.textContent = `Menampilkan ${rows.length} item • sumber: ${activeSource === 'ma' ? 'MAHKAMAH AGUNG' : 'BADILAG'}`;
    } catch (err) {
        list.innerHTML = '<li class="empty">Data pengumuman tidak tersedia.</li>';
        errBox.textContent = err.message;
        errBox.style.display = 'block';
        metaInfo.textContent = 'Gagal memuat data';
        postFrameHeight();
    }
}

sourceTabs.addEventListener('click', (event) => {
    const button = event.target.closest('.tab-btn');
    if (!button) {
        return;
    }
    const source = String(button.dataset.source || '').toLowerCase();
    if (source !== 'ma' && source !== 'badilag') {
        return;
    }
    activeSource = source;
    tabButtons.forEach((tab) => tab.classList.toggle('active', tab === button));
    loadFeed();
});
document.addEventListener('DOMContentLoaded', loadFeed);
window.addEventListener('resize', postFrameHeight);
setInterval(postFrameHeight, 2000);
</script>
</body>
</html>
