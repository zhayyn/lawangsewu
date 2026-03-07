<?php
/* developed by dubes favour-it */

$allowedSource = ['all', 'ma', 'badilag'];
$source = strtolower(trim((string)($_GET['source'] ?? 'all')));
if (!in_array($source, $allowedSource, true)) {
    $source = 'all';
}
$uiSource = in_array($source, ['ma', 'badilag'], true) ? $source : 'ma';

$limit = (int)($_GET['limit'] ?? 5);
if ($limit < 1) {
    $limit = 1;
}
if ($limit > 20) {
    $limit = 20;
}

$apiUrl = '/lawangsewu/api/pengumuman-rss?source=' . rawurlencode($source) . '&limit=' . $limit;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Embed Pengumuman MA & Badilag</title>
    <style>
        :root { --line:#e4e4e7; --muted:#6b7280; --badge:#ecfdf5; --badgeText:#065f46; --deep:#0f172a; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Inter","Segoe UI",Arial,sans-serif; background:transparent; color:#1f2937; }
        .feed { width:100%; }
        .toolbar { display:flex; justify-content:flex-start; align-items:center; margin:0 0 10px; }
        .tabs { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .tab-btn {
            border:1px solid #d1d5db;
            background:#fff;
            color:#334155;
            border-radius:999px;
            padding:7px 11px;
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
        .err { display:none; border:1px solid #fecaca; background:#fff1f2; color:#9f1239; border-radius:8px; padding:8px 10px; margin:0 0 10px; font-size:12px; }
        .list { list-style:none; margin:0; padding:0; }
        .item {
            display:grid;
            grid-template-columns:58px 1fr auto;
            align-items:center;
            gap:10px;
            border:1px solid var(--line);
            border-radius:12px;
            padding:9px;
            margin-bottom:8px;
            background:#fff;
            box-shadow:0 2px 8px rgba(15,23,42,.05);
        }
        .thumb { width:58px; height:58px; border-radius:9px; object-fit:cover; border:1px solid #d6dbe3; background:#e2e8f0; }
        .rss-icon { width:16px; height:16px; color:#c6a15a; margin-bottom:auto; }
        .badge { display:inline-block; font-size:10px; font-weight:700; color:var(--badgeText); background:var(--badge); border-radius:999px; padding:3px 8px; margin-top:6px; }
        .item a { color:var(--deep); text-decoration:none; font-weight:600; line-height:1.4; font-size:14px; }
        .item a:hover { color:#0d6b41; text-decoration:underline; }
        .meta { display:flex; align-items:center; gap:10px; color:var(--muted); font-size:11px; margin-top:6px; flex-wrap:wrap; }
        .chip { display:inline-flex; align-items:center; gap:4px; }
        .chip svg { width:12px; height:12px; }
        .empty { color:var(--muted); font-size:13px; padding:8px 0; }
        @media (max-width:640px) { .tabs{ width:100%; } .tab-btn{ flex:1; text-align:center; } .item { grid-template-columns:52px 1fr 15px; gap:8px; padding:8px; } .thumb { width:52px; height:52px; } .item a { font-size:13px; } }
    </style>
</head>
<body>
<div class="feed" id="feed" data-api="<?php echo htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="toolbar">
        <div class="tabs" id="sourceTabs">
            <button class="tab-btn <?php echo $uiSource === 'ma' ? 'active' : ''; ?>" type="button" data-source="ma">Mahkamah Agung</button>
            <button class="tab-btn <?php echo $uiSource === 'badilag' ? 'active' : ''; ?>" type="button" data-source="badilag">Badilag</button>
        </div>
    </div>
    <div id="feedContent"></div>
</div>

<script>
const feed = document.getElementById('feed');
const feedContent = document.getElementById('feedContent');
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
    const bulan = { januari:0, februari:1, maret:2, april:3, mei:4, juni:5, juli:6, agustus:7, september:8, oktober:9, november:10, desember:11 };
    const m2 = text.match(/(\d{1,2})\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember)\s+(\d{4})/i);
    if (m2) {
        return new Date(Number(m2[3]), bulan[m2[2].toLowerCase()], Number(m2[1]));
    }
    return null;
}

function formatDateId(dateObj) {
    if (!(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) return '-';
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(dateObj);
}

function relativeFromDate(dateObj) {
    if (!(dateObj instanceof Date) || Number.isNaN(dateObj.getTime())) return '-';
    const diff = Date.now() - dateObj.getTime();
    const hours = Math.max(Math.floor(diff / (1000 * 60 * 60)), 0);
    if (hours < 24) return `${hours} jam lalu`;
    return `${Math.floor(hours / 24)} hari lalu`;
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

function renderError(message) {
    feedContent.innerHTML = `<div class="err" style="display:block;">${escHtml(message)}</div><div class="empty">Data pengumuman tidak tersedia.</div>`;
    postFrameHeight();
}

function renderItems(items) {
    if (!Array.isArray(items) || items.length === 0) {
        feedContent.innerHTML = '<div class="empty">Belum ada pengumuman.</div>';
        return;
    }

    const html = items.map((item) => {
        const source = String(item.source || '').toLowerCase() === 'badilag' ? 'Badilag' : 'Mahkamah Agung';
        const thumb = source === 'Badilag' ? THUMB_BADILAG : THUMB_MA;
        const dateObj = normalizeDateFromTitle(item.title);
        const dateText = formatDateId(dateObj);
        const relText = relativeFromDate(dateObj);
        const titleText = cleanTitle(item.title) || item.title;

        return `<li class="item">
            <img class="thumb" src="${escHtml(thumb)}" data-fallback="${escHtml(THUMB_MA_FALLBACK)}" data-fallback-local="${escHtml(THUMB_MA_LOCAL)}" alt="${escHtml(source)}" loading="lazy" onerror="if(!this.dataset.fallbackDone&&this.dataset.fallback&&this.src!==this.dataset.fallback){this.dataset.fallbackDone='1';this.src=this.dataset.fallback;return;}if(!this.dataset.fallbackLocalDone&&this.dataset.fallbackLocal&&this.src!==this.dataset.fallbackLocal){this.dataset.fallbackLocalDone='1';this.src=this.dataset.fallbackLocal;return;}this.style.opacity='0.35'">
            <div>
                <a href="${escHtml(item.url)}" target="_blank" rel="noopener noreferrer">${escHtml(titleText)}</a>
                <div class="meta">
                    <span class="chip">${calendarSvg()} ${escHtml(dateText)}</span>
                    <span class="chip">${clockSvg()} ${escHtml(relText)}</span>
                </div>
                <span class="badge">${source}</span>
            </div>
            <span class="rss-icon">${rssIconSvg()}</span>
        </li>`;
    }).join('');

    feedContent.innerHTML = `<ul class="list">${html}</ul>`;
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
        frame: 'pa-semarang-pengumuman-embed'
    }, '*');
}

async function loadEmbedFeed() {
    try {
        const seed = feed.getAttribute('data-api') || '/lawangsewu/api/pengumuman-rss?source=all&limit=5';
        const url = new URL(seed, window.location.origin);
        url.searchParams.set('source', activeSource);
        url.searchParams.set('t', Date.now().toString());

        const res = await fetch(url.toString(), { cache: 'no-store' });
        const payload = await res.json();
        if (!payload.ok) {
            throw new Error(payload.message || 'Gagal mengambil pengumuman');
        }

        const items = flatten(payload).slice(0, <?php echo $limit; ?>);
        renderItems(items);
    } catch (err) {
        renderError(err.message);
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
    loadEmbedFeed();
});

document.addEventListener('DOMContentLoaded', loadEmbedFeed);
window.addEventListener('resize', postFrameHeight);
setInterval(postFrameHeight, 2000);
</script>
</body>
</html>
