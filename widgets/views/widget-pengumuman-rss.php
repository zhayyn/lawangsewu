<?php
/* developed by dubes favour-it */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widget Pengumuman MA & Badilag</title>
    <style>
        :root { --line:#e5e7eb; --muted:#6b7280; --bg:#f4f8f6; --pri:#0d6b41; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Inter","Segoe UI",Arial,sans-serif; background:var(--bg); color:#1f2937; }
        .widget { max-width: 900px; margin: 16px auto; background:#fff; border:1px solid var(--line); border-radius:14px; overflow:hidden; }
        .head { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:12px 14px; border-bottom:1px solid #edf0f4; flex-wrap:wrap; }
        .head h3 { margin:0; font-size:16px; color:var(--pri); }
        .meta { color:var(--muted); font-size:12px; }
        .tools { display:flex; gap:8px; }
        .btn { border:0; border-radius:8px; background:linear-gradient(135deg,#0d6b41,#12714a); color:#fff; font-weight:700; padding:7px 10px; cursor:pointer; }
        .btn:hover { opacity:.95; }
        .list { list-style:none; margin:0; padding:10px 14px 14px; }
        .item { border-bottom:1px dashed #e2e8f0; padding:10px 0; }
        .item:last-child { border-bottom:0; }
        .src { display:inline-block; font-size:11px; padding:3px 8px; border-radius:999px; background:#ecfdf5; color:#065f46; margin-bottom:6px; font-weight:700; }
        .item a { color:#0f172a; text-decoration:none; font-weight:600; line-height:1.4; }
        .item a:hover { color:var(--pri); text-decoration:underline; }
        .empty { color:var(--muted); font-size:13px; padding:12px 0; }
        .error { background:#fff3f2; border:1px solid #fecdca; color:#b42318; margin:12px 14px; padding:10px; border-radius:8px; display:none; }
        @media (max-width: 640px) { .widget { margin: 0; border-radius: 0; } }
    </style>
</head>
<body>
<div class="widget" id="pengumumanWidget" data-endpoint="/lawangsewu/api/pengumuman-rss?source=all&limit=5">
    <div class="head">
        <h3>Pengumuman MA & Badilag</h3>
        <div class="tools">
            <button class="btn" id="btnReload" type="button">Refresh</button>
        </div>
        <div class="meta" id="metaText">Memuat data...</div>
    </div>
    <div class="error" id="errText"></div>
    <ul class="list" id="listPengumuman"></ul>
</div>

<script>
const widgetEl = document.getElementById('pengumumanWidget');
const listEl = document.getElementById('listPengumuman');
const metaText = document.getElementById('metaText');
const errText = document.getElementById('errText');
const btnReload = document.getElementById('btnReload');

function escHtml(text) {
    return String(text || '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[char]));
}

function flattenItems(payload) {
    if (payload && payload.bySource) {
        const ma = (payload.bySource.ma && payload.bySource.ma.items) ? payload.bySource.ma.items : [];
        const badilag = (payload.bySource.badilag && payload.bySource.badilag.items) ? payload.bySource.badilag.items : [];
        return [...ma, ...badilag];
    }
    return (payload && payload.items) ? payload.items : [];
}

function renderList(items) {
    if (!Array.isArray(items) || items.length === 0) {
        listEl.innerHTML = '<li class="empty">Belum ada data pengumuman.</li>';
        return;
    }

    const html = items.slice(0, 5).map((item) => {
        const source = String(item.source || '').toLowerCase() === 'badilag' ? 'Badilag' : 'Mahkamah Agung';
        return `<li class="item"><div class="src">${source}</div><br><a href="${escHtml(item.url)}" target="_blank" rel="noopener noreferrer">${escHtml(item.title)}</a></li>`;
    }).join('');

    listEl.innerHTML = html;
}

async function loadPengumuman() {
    errText.style.display = 'none';
    metaText.textContent = 'Memuat data...';

    try {
        const base = widgetEl.getAttribute('data-endpoint') || '/lawangsewu/api/pengumuman-rss?source=all&limit=5';
        const connector = base.includes('?') ? '&' : '?';
        const endpoint = `${base}${connector}t=${Date.now()}`;
        const res = await fetch(endpoint, { cache: 'no-store' });
        const json = await res.json();

        if (!json.ok) {
            throw new Error(json.message || 'Gagal mengambil data pengumuman');
        }

        const items = flattenItems(json);
        renderList(items);
        metaText.textContent = `Menampilkan ${Math.min(items.length, 5)} item terbaru`;
    } catch (err) {
        errText.textContent = err.message;
        errText.style.display = 'block';
        listEl.innerHTML = '<li class="empty">Data tidak tersedia.</li>';
        metaText.textContent = 'Gagal memuat';
    }
}

btnReload.addEventListener('click', loadPengumuman);
document.addEventListener('DOMContentLoaded', loadPengumuman);
</script>
</body>
</html>
