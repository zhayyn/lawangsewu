<?php
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Jadwal Persidangan</title>
    <style>
        :root { --green: #084228; --orange: #ff6600; --bg: #f4f7f6; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg); color: #1f2a22; }
        .wrap { max-width: 1100px; margin: 20px auto; padding: 0 16px 24px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); margin-bottom: 16px; overflow: hidden; }
        .head { background: linear-gradient(135deg, var(--green), #0d6b41); color: #fff; padding: 14px 16px; font-weight: 700; }
        .body { padding: 14px 16px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .grid .full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; }
        input, textarea { width: 100%; border: 1px solid #ccd5d0; border-radius: 8px; padding: 10px; font-size: 14px; }
        textarea { min-height: 70px; resize: vertical; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        button { border: 0; border-radius: 8px; padding: 10px 14px; color: #fff; background: var(--green); cursor: pointer; font-weight: 600; }
        button.secondary { background: #4d5f56; }
        button.warn { background: var(--orange); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e6ece8; text-align: left; vertical-align: top; font-size: 13px; }
        th { background: #f0f5f2; font-size: 12px; text-transform: uppercase; letter-spacing: .3px; }
        .small { font-size: 12px; color: #5c6862; }
        .tools { display: flex; gap: 6px; }
        .tools button { padding: 6px 8px; font-size: 12px; }
        .status { margin-top: 8px; font-size: 13px; color: #184b31; }
        @media (max-width: 800px) {
            .grid { grid-template-columns: 1fr; }
            th, td { font-size: 12px; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="head">Admin Jadwal Persidangan Mandiri</div>
        <div class="body">
            <div class="grid">
                <div>
                    <label for="tanggalFilter">Tanggal Sidang</label>
                    <input type="date" id="tanggalFilter">
                </div>
                <div>
                    <label for="urutan">Urutan Tampil</label>
                    <input type="number" id="urutan" placeholder="10, 20, 30...">
                </div>
                <div>
                    <label for="nomorPerkara">Nomor Perkara</label>
                    <input type="text" id="nomorPerkara" placeholder="contoh: 545/Pdt.G/2026/PA.Smg">
                </div>
                <div>
                    <label for="ruangSidang">Ruang Sidang</label>
                    <input type="text" id="ruangSidang" placeholder="contoh: Ruang Sidang Utama">
                </div>
                <div class="full">
                    <label for="agenda">Agenda</label>
                    <input type="text" id="agenda" placeholder="contoh: Pembuktian">
                </div>
                <div class="full">
                    <label for="keterangan">Keterangan</label>
                    <textarea id="keterangan" placeholder="Opsional, dibuat singkat"></textarea>
                </div>
            </div>
            <input type="hidden" id="rowId" value="0">
            <div class="actions">
                <button id="btnSimpan">Simpan</button>
                <button class="secondary" id="btnReset">Reset Form</button>
                <button class="warn" id="btnImport">Import dari slide_sidang.html</button>
                <button class="secondary" id="btnReload">Reload Daftar</button>
            </div>
            <div class="status" id="statusBox"></div>
        </div>
    </div>

    <div class="card">
        <div class="head">Daftar Jadwal Tanggal <span id="tanggalLabel"></span></div>
        <div class="body">
            <table>
                <thead>
                <tr>
                    <th style="width:70px;">Urut</th>
                    <th style="width:210px;">Nomor Perkara</th>
                    <th>Agenda</th>
                    <th style="width:180px;">Ruang</th>
                    <th style="width:220px;">Keterangan</th>
                    <th style="width:120px;">Aksi</th>
                </tr>
                </thead>
                <tbody id="rowsBody">
                <tr><td colspan="6" class="small">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const api = '../api/jadwal-persidangan-api.php';

function today() {
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + mm + '-' + dd;
}

function el(id) { return document.getElementById(id); }

function setStatus(msg, isError = false) {
    const box = el('statusBox');
    box.textContent = msg;
    box.style.color = isError ? '#a32222' : '#184b31';
}

function resetForm() {
    el('rowId').value = '0';
    el('urutan').value = '';
    el('nomorPerkara').value = '';
    el('agenda').value = '';
    el('ruangSidang').value = '';
    el('keterangan').value = '';
}

async function loadRows() {
    const tanggal = el('tanggalFilter').value;
    el('tanggalLabel').textContent = tanggal;
    const res = await fetch(api + '?action=list&tanggal=' + encodeURIComponent(tanggal), { cache: 'no-store' });
    const json = await res.json();
    const body = el('rowsBody');

    if (!json.ok) {
        body.innerHTML = '<tr><td colspan="6" class="small">Gagal memuat data</td></tr>';
        setStatus(json.message || 'Gagal memuat data', true);
        return;
    }

    const rows = Array.isArray(json.rows) ? json.rows : [];
    if (rows.length === 0) {
        body.innerHTML = '<tr><td colspan="6" class="small">Belum ada jadwal untuk tanggal ini.</td></tr>';
        return;
    }

    body.innerHTML = rows.map((r) => {
        const data = encodeURIComponent(JSON.stringify(r));
        return '<tr>' +
            '<td>' + (r.urutan ?? 0) + '</td>' +
            '<td>' + (r.nomor_perkara ?? '') + '</td>' +
            '<td>' + (r.agenda ?? '') + '</td>' +
            '<td>' + (r.ruang_sidang ?? '') + '</td>' +
            '<td>' + (r.keterangan ?? '') + '</td>' +
            '<td><div class="tools">' +
                '<button class="secondary" onclick="editRow(\'' + data + '\')">Edit</button>' +
                '<button class="warn" onclick="deleteRow(' + r.id + ')">Hapus</button>' +
            '</div></td>' +
            '</tr>';
    }).join('');
}

window.editRow = function(dataEnc) {
    const row = JSON.parse(decodeURIComponent(dataEnc));
    el('rowId').value = String(row.id || 0);
    el('urutan').value = String(row.urutan || 0);
    el('nomorPerkara').value = row.nomor_perkara || '';
    el('agenda').value = row.agenda || '';
    el('ruangSidang').value = row.ruang_sidang || '';
    el('keterangan').value = row.keterangan || '';
    setStatus('Mode edit ID ' + row.id);
};

window.deleteRow = async function(id) {
    if (!confirm('Hapus data ini?')) {
        return;
    }
    const form = new FormData();
    form.append('id', String(id));
    const res = await fetch(api + '?action=delete', { method: 'POST', body: form });
    const json = await res.json();
    if (!json.ok) {
        setStatus(json.message || 'Gagal menghapus data', true);
        return;
    }
    setStatus('Data berhasil dihapus');
    await loadRows();
};

async function saveRow() {
    const form = new FormData();
    form.append('id', el('rowId').value);
    form.append('tanggal_sidang', el('tanggalFilter').value);
    form.append('urutan', el('urutan').value);
    form.append('nomor_perkara', el('nomorPerkara').value);
    form.append('agenda', el('agenda').value);
    form.append('ruang_sidang', el('ruangSidang').value);
    form.append('keterangan', el('keterangan').value);

    const res = await fetch(api + '?action=save', { method: 'POST', body: form });
    const json = await res.json();
    if (!json.ok) {
        setStatus(json.message || 'Gagal menyimpan data', true);
        return;
    }

    setStatus('Data tersimpan');
    resetForm();
    await loadRows();
}

async function importCache() {
    if (!confirm('Import dari slide_sidang.html akan mengganti semua data pada tanggal ini. Lanjutkan?')) {
        return;
    }

    const form = new FormData();
    form.append('tanggal_sidang', el('tanggalFilter').value);
    const res = await fetch(api + '?action=import_cache', { method: 'POST', body: form });
    const json = await res.json();
    if (!json.ok) {
        setStatus(json.message || 'Gagal import cache', true);
        return;
    }
    setStatus('Import selesai. Total baris: ' + (json.total || 0));
    await loadRows();
}

el('tanggalFilter').value = today();
el('btnSimpan').addEventListener('click', saveRow);
el('btnReset').addEventListener('click', resetForm);
el('btnReload').addEventListener('click', loadRows);
el('btnImport').addEventListener('click', importCache);
el('tanggalFilter').addEventListener('change', loadRows);

loadRows();
</script>
</body>
</html>
