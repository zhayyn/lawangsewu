<style>
    :root {
        --tr-green: #1e5631;
        --tr-accent: #f39c12;
        --tr-border: #dce9df;
        --tr-soft: #f5faf7;
        --tr-muted: #64748b;
    }

    .tr-wrap {
        font-family: 'Inter', 'Segoe UI', sans-serif;
        background: #fff;
        border: 1px solid var(--tr-border);
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
        width: 100%;
        box-sizing: border-box;
    }

    .tr-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e7efe9;
    }

    .tr-title {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        color: var(--tr-green);
    }

    .tr-controls-detail {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        margin-bottom: 0;
    }

    .tr-search-top {
        margin: 0 0 12px;
    }

    .tr-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .tr-label {
        font-size: 12px;
        font-weight: 700;
        color: #334155;
    }

    .tr-select {
        border-radius: 10px;
        font-size: 13px;
    }

    .tr-select {
        border: 1px solid #cfd9d2;
        padding: 9px 10px;
        width: 100%;
        background: #fff;
        outline: none;
        transition: border-color .16s ease, box-shadow .16s ease;
    }

    .tr-select:focus {
        border-color: #7bbf91;
        box-shadow: 0 0 0 3px rgba(37, 109, 59, .12);
    }

    .tr-note {
        border-left: 4px solid var(--tr-accent);
        border-radius: 0 10px 10px 0;
        background: #fffaf0;
        color: #6b4e0e;
        font-size: 12px;
        font-weight: 600;
        padding: 10px 12px;
        margin-bottom: 12px;
    }

    .tr-table-wrap {
        border: 1px solid #e3e8ef;
        border-radius: 12px;
        overflow: auto;
        max-height: 72vh;
    }

    .tr-table {
        width: 100%;
        min-width: 560px;
        border-collapse: collapse;
        background: #fff;
    }

    .tr-table th,
    .tr-table td {
        border-bottom: 1px solid #edf2f7;
        padding: 10px;
        font-size: 13px;
        text-align: left;
        vertical-align: top;
    }

    .tr-table th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #f8fafc;
        color: #334155;
        font-weight: 800;
    }

    .tr-kec {
        font-weight: 700;
        color: #1f2937;
    }

    .tr-money {
        font-weight: 800;
        color: var(--tr-green);
        white-space: nowrap;
    }

    .tr-status {
        font-size: 12px;
        font-weight: 700;
        color: var(--tr-muted);
        margin-bottom: 8px;
    }

    .tr-empty {
        padding: 16px;
        text-align: center;
        color: var(--tr-muted);
        font-size: 13px;
    }

    .tr-detail-note {
        font-size: 12px;
        color: var(--tr-muted);
        font-weight: 700;
        margin-bottom: 10px;
    }

    .tr-kecamatan-grid {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) minmax(280px, 1.2fr);
        gap: 12px;
    }

    .tr-card {
        border: 1px solid #e3ebe6;
        border-radius: 12px;
        background: #ffffff;
        padding: 10px;
    }

    .tr-card-title {
        margin: 0 0 8px;
        font-size: 12px;
        font-weight: 800;
        color: #334155;
    }

    .tr-kec-list {
        max-height: 420px;
        overflow: auto;
        display: grid;
        gap: 8px;
    }

    .tr-kel-list {
        max-height: 420px;
        overflow: auto;
        display: grid;
        gap: 8px;
    }

    .tr-kel-item {
        border: 1px solid #e3ebe6;
        border-radius: 10px;
        background: #fbfdfb;
        padding: 9px 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }

    .tr-kel-name {
        font-size: 13px;
        font-weight: 700;
        color: #1f2937;
    }

    .tr-kel-fee {
        font-size: 12px;
        font-weight: 800;
        color: #0f5132;
        white-space: nowrap;
    }

    .tr-search {
        margin-bottom: 8px;
    }

    .tr-kec-btn {
        border: 1px solid #d9e4dc;
        background: #f8fbf9;
        border-radius: 10px;
        padding: 9px 10px;
        text-align: left;
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
        color: #1f2937;
        transition: all .16s ease;
    }

    .tr-kec-btn:hover {
        border-color: #9cc9ab;
        background: #f0f8f2;
    }

    .tr-kec-btn.active {
        border-color: #2f7b4a;
        background: #eaf7ef;
        color: #0f5132;
    }

    .tr-kec-note {
        display: block;
        margin-top: 3px;
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
    }

    .tr-controls-panel {
        background: #f8fcf9;
        border: 1px solid #e4efe8;
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 12px;
    }

    @media (max-width: 900px) {
        .tr-wrap {
            padding: 14px;
            border-radius: 14px;
        }

        .tr-controls-detail {
            grid-template-columns: 1fr;
        }

        .tr-kecamatan-grid {
            grid-template-columns: 1fr;
        }

        .tr-head {
            flex-direction: column;
            align-items: flex-start;
        }

        .tr-kec-list {
            max-height: 240px;
        }

        .tr-kel-list {
            max-height: 240px;
        }

    }

    @media (max-width: 640px) {
        .tr-title {
            font-size: 17px;
        }

        .tr-note,
        .tr-status,
        .tr-detail-note {
            font-size: 11px;
        }
    }
</style>

<div class="tr-wrap">
    <div class="tr-head">
        <div>
            <h3 class="tr-title">Tabel Radius Biaya Panggilan per Kecamatan</h3>
        </div>
    </div>

    <div class="tr-note">
        Panjar biaya setiap kelurahan di satu kecamatan ada kemungkinan berbeda.
    </div>

    <div class="tr-search-top">
        <input id="tr-kel-search" class="tr-select tr-search" type="text" placeholder="Pencarian global kelurahan (lintas kecamatan Kota Semarang)...">
    </div>

    <div class="tr-controls-panel tr-kecamatan-grid">
        <div class="tr-card">
            <p class="tr-card-title">Daftar Kecamatan</p>
            <div id="tr-kec-list" class="tr-kec-list">
                <div class="tr-empty">Data kecamatan akan tampil setelah proses dimuat.</div>
            </div>
        </div>
        <div class="tr-card">
            <div class="tr-field">
                <label class="tr-label" id="tr-kel-label">Kelurahan (dengan biaya panggilan)</label>
                <div id="tr-kel-list" class="tr-kel-list">
                    <div class="tr-empty">Klik kecamatan untuk menampilkan daftar kelurahan.</div>
                </div>
            </div>
        </div>
    </div>
    <div id="tr-detail-note" class="tr-detail-note">Klik salah satu kecamatan untuk menampilkan daftar kelurahan.</div>

    <div id="tr-status" class="tr-status">Sistem siap digunakan.</div>
</div>

<script>
(function () {
    const bridgeBase = window.location.pathname.includes('/lawangsewu')
        ? `${window.location.origin}/lawangsewu/api/server10`
        : '/lawangsewu/api/server10';

    const AUTO_CITY = 'KOTA SEMARANG';
    const MIN_EXPECTED_KECAMATAN = 17;
    const AUTO_RETRY_MAX = 8;
    const AUTO_RETRY_DELAY = 3500;

    const node = {
        kecList: document.getElementById('tr-kec-list'),
        kelLabel: document.getElementById('tr-kel-label'),
        kelSearch: document.getElementById('tr-kel-search'),
        kelList: document.getElementById('tr-kel-list'),
        detailNote: document.getElementById('tr-detail-note'),
        status: document.getElementById('tr-status')
    };

    const state = {
        kecamatanList: [],
        kelurahanByKecamatan: new Map(),
        selectedKecamatan: ''
    };

    function parseOptions(html) {
        const doc = new DOMParser().parseFromString(`<select>${html}</select>`, 'text/html');
        return Array.from(doc.querySelectorAll('option'))
            .map((option) => ({ value: option.value, label: option.textContent.trim() }))
            .filter((option) => option.value && option.label);
    }

    function sortByLabel(items) {
        return [...items].sort((a, b) => String(a.label || '').localeCompare(String(b.label || ''), 'id', { sensitivity: 'base' }));
    }

    function isMassMediaLabel(label) {
        return String(label || '').trim().toUpperCase() === 'MASS MEDIA';
    }

    function orderKecamatanForDisplay(items) {
        const sorted = sortByLabel(items);
        const regular = sorted.filter((item) => !isMassMediaLabel(item.label));
        const massMedia = sorted.filter((item) => isMassMediaLabel(item.label));
        return [...regular, ...massMedia];
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function money(value) {
        const parsed = Number(String(value || '0').replace(/[^0-9.-]/g, ''));
        const amount = Number.isFinite(parsed) ? parsed : 0;
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
    }

    function normalizeNominal(value) {
        const standards = [140000, 160000, 180000];
        const parsed = Number(String(value || '0').replace(/[^0-9.-]/g, ''));
        if (!Number.isFinite(parsed) || parsed <= 0) {
            return 160000;
        }

        let best = standards[0];
        let bestDiff = Math.abs(parsed - best);
        for (let i = 1; i < standards.length; i++) {
            const diff = Math.abs(parsed - standards[i]);
            if (diff < bestDiff) {
                best = standards[i];
                bestDiff = diff;
            }
        }
        return best;
    }

    function forcedNominalByKecamatan(kecamatanLabel) {
        const name = String(kecamatanLabel || '').trim().toUpperCase();
        if (name === 'TUGU') return 140000;
        if (name === 'MIJEN') return 180000;
        if (name === 'MASS MEDIA') return 160000;
        return null;
    }

    async function callBridge(params, body) {
        const response = await fetch(`${bridgeBase}?${new URLSearchParams(params).toString()}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            cache: 'no-store',
            body: new URLSearchParams(body || {}).toString()
        });
        return response.json();
    }

    async function getKecamatan(kota) {
        const payload = await callBridge(
            { path: '/lumpiapasar/panjar/_panjar_data_wilayah.php', method: 'POST' },
            { jenis: 'kecamatan', id_regencies: kota }
        );
        return sortByLabel(parseOptions(payload.raw || ''));
    }

    async function getKelurahan(kecamatan) {
        const payload = await callBridge(
            { path: '/lumpiapasar/panjar/_panjar_data_wilayah.php', method: 'POST' },
            { jenis: 'kelurahan', id_district: kecamatan }
        );
        return sortByLabel(parseOptions(payload.raw || ''));
    }

    function buildKelurahanDetails(kecamatanLabel, kelurahanList) {
        const forced = forcedNominalByKecamatan(kecamatanLabel);
        return sortByLabel(kelurahanList).map((kelurahan) => {
            const parts = String(kelurahan.value || '').split('^');
            const rawNominal = parts[3] || '0';
            const nominal = forced !== null ? forced : normalizeNominal(rawNominal);
            return {
                label: kelurahan.label,
                nominal
            };
        });
    }

    function renderKecamatanList(kecamatanList) {
        if (!kecamatanList.length) {
            node.kecList.innerHTML = '<div class="tr-empty">Data kecamatan tidak tersedia.</div>';
            return;
        }

        const orderedList = orderKecamatanForDisplay(kecamatanList);

        node.kecList.innerHTML = orderedList.map((item) => {
            const isActive = item.value === state.selectedKecamatan;
            const note = isMassMediaLabel(item.label)
                ? '<span class="tr-kec-note">Khusus untuk panggilan melalui media massa.</span>'
                : '';
            return `
                <button type="button" class="tr-kec-btn ${isActive ? 'active' : ''}" data-kec="${escapeHtml(item.value)}">
                    ${escapeHtml(item.label)}
                    ${note}
                </button>
            `;
        }).join('');

        node.kecList.querySelectorAll('.tr-kec-btn').forEach((button) => {
            button.addEventListener('click', () => {
                const value = button.getAttribute('data-kec') || '';
                if (!value) return;
                state.selectedKecamatan = value;
                renderKecamatanList(state.kecamatanList);
                renderKelurahanList(value);
            });
        });
    }

    function renderKelurahanList(kecamatanValue) {
        const kecamatan = state.kecamatanList.find((item) => item.value === kecamatanValue);
        const keyword = String(node.kelSearch.value || '').trim().toLowerCase();

        if (keyword !== '') {
            const globalMatches = [];
            state.kecamatanList.forEach((kec) => {
                const details = state.kelurahanByKecamatan.get(kec.value) || [];
                details.forEach((item) => {
                    if (item.label.toLowerCase().includes(keyword)) {
                        globalMatches.push({
                            kecamatan: kec.label,
                            kelurahan: item.label,
                            nominal: item.nominal,
                        });
                    }
                });
            });

            node.kelLabel.textContent = 'Hasil pencarian kelurahan lintas kecamatan';

            if (!globalMatches.length) {
                node.kelList.innerHTML = '<div class="tr-empty">Data kelurahan tidak ditemukan.</div>';
                node.detailNote.textContent = 'Tidak ada hasil pencarian kelurahan pada Kota Semarang.';
                return;
            }

            node.kelList.innerHTML = globalMatches.map((item) => `
                <div class="tr-kel-item">
                    <span class="tr-kel-name">${escapeHtml(item.kelurahan)}<br><small>${escapeHtml(item.kecamatan)}</small></span>
                    <span class="tr-kel-fee">${escapeHtml(money(item.nominal))}</span>
                </div>
            `).join('');

            node.detailNote.textContent = `Menampilkan ${globalMatches.length} hasil pencarian lintas kecamatan.`;
            return;
        }

        const allDetails = state.kelurahanByKecamatan.get(kecamatanValue) || [];
        const details = allDetails;

        node.kelLabel.textContent = kecamatan
            ? `Kelurahan di ${kecamatan.label} (dengan biaya panggilan)`
            : 'Kelurahan (dengan biaya panggilan)';

        if (!details.length) {
            node.kelList.innerHTML = '<div class="tr-empty">Data kelurahan tidak ditemukan.</div>';
            node.detailNote.textContent = keyword
                ? 'Tidak ada hasil pencarian kelurahan pada kecamatan ini.'
                : 'Data kelurahan pada kecamatan ini tidak tersedia.';
            return;
        }

        node.kelList.innerHTML = details.map((item) => `
            <div class="tr-kel-item">
                <span class="tr-kel-name">${escapeHtml(item.label)}</span>
                <span class="tr-kel-fee">${escapeHtml(money(item.nominal))}</span>
            </div>
        `).join('');

        node.detailNote.textContent = `Total kelurahan pada kecamatan ini: ${allDetails.length}`;
    }

    async function loadTableOnce() {
        node.status.textContent = 'Memuat data kecamatan...';
        node.kecList.innerHTML = '<div class="tr-empty">Memproses data kecamatan, mohon tunggu...</div>';
        node.kelList.innerHTML = '<div class="tr-empty">Klik kecamatan untuk menampilkan daftar kelurahan.</div>';

        const kecamatanList = await getKecamatan(AUTO_CITY);
        const kelurahanByKecamatan = new Map();

        for (let i = 0; i < kecamatanList.length; i++) {
            const kecamatan = kecamatanList[i];
            node.status.textContent = `Memuat data ${i + 1}/${kecamatanList.length}: ${kecamatan.label}`;
            const kelurahanList = await getKelurahan(kecamatan.value);
            const kelurahanDetails = buildKelurahanDetails(kecamatan.label, kelurahanList);
            kelurahanByKecamatan.set(kecamatan.value, kelurahanDetails);
        }

        state.kecamatanList = kecamatanList;
        state.kelurahanByKecamatan = kelurahanByKecamatan;
        if (!state.selectedKecamatan || !state.kecamatanList.some((item) => item.value === state.selectedKecamatan)) {
            state.selectedKecamatan = kecamatanList.length ? kecamatanList[0].value : '';
        }

        renderKecamatanList(kecamatanList);
        if (state.selectedKecamatan) {
            renderKelurahanList(state.selectedKecamatan);
        }

        const allHaveKelurahan = kecamatanList.every((item) => (kelurahanByKecamatan.get(item.value) || []).length > 0);
        return kecamatanList.length >= MIN_EXPECTED_KECAMATAN && allHaveKelurahan;
    }

    async function autoReloadInBackground() {
        for (let attempt = 1; attempt <= AUTO_RETRY_MAX; attempt++) {
            try {
                const isComplete = await loadTableOnce();
                if (isComplete) {
                    node.status.textContent = `Data tampil lengkap. Total ${state.kecamatanList.length} kecamatan.`;
                    return;
                }

                if (attempt < AUTO_RETRY_MAX) {
                    node.status.textContent = `Data belum lengkap, pembaruan otomatis di background (${attempt}/${AUTO_RETRY_MAX})...`;
                    await new Promise((resolve) => setTimeout(resolve, AUTO_RETRY_DELAY));
                }
            } catch (error) {
                if (attempt >= AUTO_RETRY_MAX) {
                    node.status.textContent = `Terjadi kendala saat memuat data: ${String(error && error.message ? error.message : error)}`;
                    node.kecList.innerHTML = '<div class="tr-empty">Terjadi kendala saat memuat daftar kecamatan.</div>';
                    return;
                }
                node.status.textContent = `Memuat ulang otomatis di background (${attempt}/${AUTO_RETRY_MAX})...`;
                await new Promise((resolve) => setTimeout(resolve, AUTO_RETRY_DELAY));
            }
        }

        node.status.textContent = `Data ditampilkan dengan kondisi terbaru. Total ${state.kecamatanList.length} kecamatan.`;
    }

    node.kelSearch.addEventListener('input', () => {
        if (state.selectedKecamatan) {
            renderKelurahanList(state.selectedKecamatan);
        }
    });

    (async function init() {
        await autoReloadInBackground();
    })();
})();
</script>