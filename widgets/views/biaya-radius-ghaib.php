<style>
    :root {
        --lr-green: #1e5631;
        --lr-soft: #edf8f0;
        --lr-accent: #f39c12;
        --lr-border: #dce9df;
        --lr-ink: #1f2937;
        --lr-muted: #64748b;
    }

    .lr-wrap {
        font-family: 'Inter', 'Segoe UI', sans-serif;
        background: #fff;
        border: 1px solid var(--lr-border);
        border-radius: 18px;
        padding: 16px;
        box-shadow: 0 10px 26px rgba(15, 23, 42, .05);
    }

    .lr-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }

    .lr-title {
        margin: 0;
        font-size: 18px;
        font-weight: 800;
        color: var(--lr-green);
    }

    .lr-sub {
        margin: 4px 0 0;
        font-size: 12px;
        color: var(--lr-muted);
        font-weight: 600;
    }

    .lr-badge {
        font-size: 11px;
        font-weight: 800;
        color: #0b5138;
        border: 1px solid #bde7cc;
        background: #ecfbf1;
        border-radius: 999px;
        padding: 6px 10px;
        white-space: nowrap;
    }

    .lr-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .lr-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .lr-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--lr-ink);
    }

    .lr-input,
    .lr-select {
        border: 1px solid #cfd9d2;
        border-radius: 10px;
        padding: 9px 10px;
        font-size: 13px;
        color: #111827;
        background: #fff;
        width: 100%;
    }

    .lr-actions {
        margin-top: 12px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .lr-btn {
        border: 0;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
    }

    .lr-btn-main {
        color: #fff;
        background: linear-gradient(135deg, #256d3b, #1e5631);
    }

    .lr-btn-soft {
        color: #21412d;
        background: #f1f7f3;
        border: 1px solid #d3e2d8;
    }

    .lr-note {
        margin-top: 12px;
        border-left: 4px solid var(--lr-accent);
        background: #fffaf0;
        color: #6b4e0e;
        border-radius: 0 10px 10px 0;
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .lr-result {
        margin-top: 14px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
        padding: 12px;
        min-height: 80px;
    }

    .lr-loading {
        color: var(--lr-muted);
        font-size: 12px;
        font-weight: 700;
    }

    @media (max-width: 900px) {
        .lr-grid {
            grid-template-columns: 1fr;
        }

        .lr-head {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="lr-wrap">
    <div class="lr-head">
        <div>
            <h3 class="lr-title">Modul Panjar Perkara Ghaib (Lawangsewu)</h3>
            <p class="lr-sub">Versi bridge Lawangsewu ke Server 10 • tanpa ketergantungan file luar</p>
        </div>
        <span class="lr-badge" id="lr-health">Cek koneksi...</span>
    </div>

    <div class="lr-grid">
        <div class="lr-field">
            <label class="lr-label" for="lr-nama-p">Nama Penggugat</label>
            <input id="lr-nama-p" class="lr-input" type="text" value="PENGGUGAT">
        </div>
        <div class="lr-field">
            <label class="lr-label" for="lr-nama-t">Nama Tergugat</label>
            <input id="lr-nama-t" class="lr-input" type="text" value="TERGUGAT">
        </div>
        <div class="lr-field">
            <label class="lr-label" for="lr-kota">Kota/Kabupaten</label>
            <select id="lr-kota" class="lr-select"><option value="">Memuat kota...</option></select>
        </div>
        <div class="lr-field">
            <label class="lr-label" for="lr-kecamatan">Kecamatan</label>
            <select id="lr-kecamatan" class="lr-select"><option value="">Pilih kota dulu</option></select>
        </div>
        <div class="lr-field">
            <label class="lr-label" for="lr-kelurahan">Kelurahan (Radius)</label>
            <select id="lr-kelurahan" class="lr-select"><option value="">Pilih kecamatan dulu</option></select>
        </div>
        <div class="lr-field">
            <label class="lr-label" for="lr-alamat">Alamat Ringkas</label>
            <input id="lr-alamat" class="lr-input" type="text" value="SEMARANG">
        </div>
    </div>

    <div class="lr-actions">
        <button type="button" class="lr-btn lr-btn-main" id="lr-hitung">Hitung Panjar Ghaib</button>
        <button type="button" class="lr-btn lr-btn-soft" id="lr-reset">Reset</button>
    </div>

    <div class="lr-note">
        Endpoint preset otomatis: <strong>source=modul_panjar_perkara_ghaib</strong>. Parameter teknis satker/radius diisi otomatis dari data wilayah.
    </div>

    <div class="lr-result" id="lr-result">
        <div class="lr-loading">Menyiapkan modul...</div>
    </div>
</div>

<script>
(function () {
    const bridge = window.location.pathname.includes('/lawangsewu')
        ? `${window.location.origin}/lawangsewu/api/server10`
        : '/lawangsewu/api/server10';

    const node = {
        health: document.getElementById('lr-health'),
        namaP: document.getElementById('lr-nama-p'),
        namaT: document.getElementById('lr-nama-t'),
        kota: document.getElementById('lr-kota'),
        kecamatan: document.getElementById('lr-kecamatan'),
        kelurahan: document.getElementById('lr-kelurahan'),
        alamat: document.getElementById('lr-alamat'),
        hitung: document.getElementById('lr-hitung'),
        reset: document.getElementById('lr-reset'),
        result: document.getElementById('lr-result')
    };

    function setOptions(select, options, placeholder) {
        select.innerHTML = '';
        const first = document.createElement('option');
        first.value = '';
        first.textContent = placeholder;
        select.appendChild(first);
        options.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.label;
            if (item.meta) option.dataset.meta = item.meta;
            select.appendChild(option);
        });
    }

    function parseOptions(html) {
        const doc = new DOMParser().parseFromString(`<select>${html}</select>`, 'text/html');
        return Array.from(doc.querySelectorAll('option'))
            .map((option) => ({ value: option.value, label: option.textContent.trim() }))
            .filter((option) => option.value && option.label);
    }

    async function callBridge(params, body) {
        const url = `${bridge}?${new URLSearchParams(params).toString()}`;
        const response = await fetch(url, {
            method: 'POST',
            cache: 'no-store',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: new URLSearchParams(body || {}).toString()
        });
        return response.json();
    }

    async function loadHealth() {
        try {
            const response = await fetch(`${bridge}/health`, { cache: 'no-store' });
            const payload = await response.json();
            if (payload.ok) {
                node.health.textContent = 'Bridge aktif';
                node.health.style.background = '#ecfbf1';
                node.health.style.borderColor = '#bde7cc';
                node.health.style.color = '#0b5138';
            } else {
                node.health.textContent = 'Bridge bermasalah';
            }
        } catch (_) {
            node.health.textContent = 'Bridge gagal dicek';
        }
    }

    async function loadKota() {
        const payload = await callBridge(
            { source: 'panjar_wilayah' },
            { jenis: 'kota', id_provinces: 'JAWA TENGAH' }
        );
        const options = parseOptions(payload.raw || '');
        setOptions(node.kota, options, 'Pilih kota/kabupaten');
    }

    async function loadKecamatan(kota) {
        const payload = await callBridge(
            { path: '/lumpiapasar/panjar/_panjar_data_wilayah.php', method: 'POST' },
            { jenis: 'kecamatan', id_regencies: kota }
        );
        const options = parseOptions(payload.raw || '');
        setOptions(node.kecamatan, options, 'Pilih kecamatan');
        setOptions(node.kelurahan, [], 'Pilih kecamatan dulu');
    }

    async function loadKelurahan(kecamatan) {
        const payload = await callBridge(
            { path: '/lumpiapasar/panjar/_panjar_data_wilayah.php', method: 'POST' },
            { jenis: 'kelurahan', id_district: kecamatan }
        );
        const options = parseOptions(payload.raw || '').map((item) => ({
            value: item.value,
            label: item.label,
            meta: item.value
        }));
        setOptions(node.kelurahan, options, 'Pilih kelurahan');
    }

    function extractKelMeta(value) {
        const parts = String(value || '').split('^');
        return {
            satkerCode: parts[1] || '3322',
            satkerName: parts[2] || 'PA SEMARANG',
            nilai: parts[3] || '0',
            alamat: parts[4] || node.alamat.value || 'SEMARANG'
        };
    }

    async function hitung() {
        node.result.innerHTML = '<div class="lr-loading">Menghitung panjar perkara ghaib...</div>';
        try {
            const kelMeta = extractKelMeta(node.kelurahan.value);
            node.alamat.value = kelMeta.alamat;

            const payload = await callBridge(
                { source: 'modul_panjar_perkara_ghaib' },
                {
                    nama_p: node.namaP.value || 'PENGGUGAT',
                    nama_t: node.namaT.value || 'TERGUGAT',
                    satker_code: kelMeta.satkerCode,
                    nilai: kelMeta.nilai,
                    alamat: kelMeta.alamat,
                    ghoib: '1'
                }
            );

            if (!payload.ok) {
                node.result.textContent = `Gagal (${payload.status || 'n/a'}): ${payload.error || 'Unknown error'}`;
                return;
            }

            node.result.innerHTML = payload.raw || '<div class="lr-loading">Respons kosong.</div>';
        } catch (error) {
            node.result.textContent = `Error: ${String(error && error.message ? error.message : error)}`;
        }
    }

    function resetForm() {
        node.namaP.value = 'PENGGUGAT';
        node.namaT.value = 'TERGUGAT';
        node.alamat.value = 'SEMARANG';
        node.result.innerHTML = '<div class="lr-loading">Form direset. Klik Hitung Panjar Ghaib.</div>';
    }

    node.kota.addEventListener('change', () => {
        const value = node.kota.value;
        if (!value) {
            setOptions(node.kecamatan, [], 'Pilih kota dulu');
            setOptions(node.kelurahan, [], 'Pilih kecamatan dulu');
            return;
        }
        loadKecamatan(value);
    });

    node.kecamatan.addEventListener('change', () => {
        const value = node.kecamatan.value;
        if (!value) {
            setOptions(node.kelurahan, [], 'Pilih kecamatan dulu');
            return;
        }
        loadKelurahan(value);
    });

    node.kelurahan.addEventListener('change', () => {
        const meta = extractKelMeta(node.kelurahan.value);
        if (meta.alamat) {
            node.alamat.value = meta.alamat;
        }
    });

    node.hitung.addEventListener('click', hitung);
    node.reset.addEventListener('click', resetForm);

    (async () => {
        await loadHealth();
        await loadKota();
        node.result.innerHTML = '<div class="lr-loading">Siap digunakan. Lengkapi data lalu klik Hitung Panjar Ghaib.</div>';
    })();
})();
</script>
