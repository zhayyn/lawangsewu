<?php /* developed by dubes favour-it */ ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Hakim</title>
    <style>
        :root { --line:#e5e7eb; --muted:#6b7280; --p:#0d6b41; --shadow:0 10px 22px rgba(9,46,29,.08); }
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Inter", "Segoe UI", Arial, sans-serif; background:radial-gradient(circle at top right,#f4fbf7 0,#eef4f1 38%); color:#1f2937; }
        .container { max-width:1280px; margin:18px auto; padding:0 14px; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:10px; flex-wrap:wrap; }
        .tools { display:flex; gap:8px; align-items:center; }
        #tahun, #qHakim { border:1px solid #d6dde6; border-radius:10px; padding:8px 10px; }
        #qHakim { min-width:220px; }
        .select { border:1px solid #d6dde6; border-radius:10px; padding:8px 10px; min-width:86px; }
        .btn { border: 0; background: linear-gradient(135deg, #0d6b41, #12714a); color: #fff; border-radius: 9px; padding: 8px 12px; cursor: pointer; font-weight: 700; }
        .btn-tv { background: #111827; }
        .panel { background:#fff; border:1px solid var(--line); border-radius:14px; overflow:hidden; margin-bottom:14px; box-shadow:var(--shadow); }
        .panel h3 { margin:0; padding:10px 12px; font-size:15px; background:#f0f6f2; color:#0f5132; }
        .body { padding:12px; }
        .table-wrap { max-height: 56vh; overflow:auto; border:1px solid var(--line); border-radius:10px; }
        table { width:100%; border-collapse:collapse; }
        th, td { border:1px solid var(--line); padding:8px; font-size:13px; }
        th { background:#fafafa; position:sticky; top:0; z-index:1; }
        tbody tr:hover { background:#f8fbf9; }
        .right { text-align:right; }
        .error { background:#fff3f2; border:1px solid #fecdca; color:#b42318; padding:10px; border-radius:8px; display:none; margin-bottom:10px; }
        .tv-mode [data-tv-slide] { display:none; }
        .tv-mode [data-tv-slide].active { display:block; }
        .tv-mode .table-wrap { max-height: 72vh; }
        @media (max-width: 640px) { .container{padding:0 10px;} .tools{width:100%;} #qHakim{min-width:0;flex:1;} #tahun{flex:1;} .select{flex:1;} }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Performansi Hakim</h2>
        <div class="tools">
            <input id="qHakim" type="text" placeholder="Cari nama hakim...">
            <select id="tahun"></select>
            <select class="select" id="tvDuration" title="Durasi rotate">
                <option value="5000">5 dtk</option>
                <option value="10000" selected>10 dtk</option>
                <option value="15000">15 dtk</option>
            </select>
            <button class="btn btn-tv" id="btnTvMode" type="button">Mode TV: OFF</button>
        </div>
    </div>
    <div class="error" id="errorBox"></div>

    <div class="panel" data-tv-slide>
        <h3>Kinerja Hakim</h3>
        <div class="body">
            <div class="table-wrap"><table>
                <thead>
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Nama Hakim</th>
                    <th rowspan="2" class="right">Sisa Lalu</th>
                    <th rowspan="2" class="right">Diterima</th>
                    <th rowspan="2" class="right">Beban</th>
                    <th rowspan="2" class="right">Pros%</th>
                    <th colspan="6" class="right">Lama Penyelesaian (bulan)</th>
                    <th rowspan="2" class="right">Sisa Sekarang</th>
                </tr>
                <tr>
                    <th class="right">0</th>
                    <th class="right">1</th>
                    <th class="right">2</th>
                    <th class="right">3</th>
                    <th class="right">4</th>
                    <th class="right">>=5</th>
                </tr>
                </thead>
                <tbody id="tblSumHakim"></tbody>
            </table></div>
        </div>
    </div>

    <div class="panel" data-tv-slide>
        <h3>Alur Perkara dan Penyelesaiannya</h3>
        <div class="body">
            <div class="table-wrap"><table>
                <thead>
                <tr>
                    <th>No</th>
                    <th>Alur</th>
                    <th class="right">Putus <=120 hari</th>
                    <th class="right">Putus >120 hari</th>
                    <th class="right">Jumlah Putusan</th>
                    <th class="right">Rerata Putus (hari)</th>
                    <th class="right">Tercepat</th>
                    <th class="right">Terlama</th>
                </tr>
                </thead>
                <tbody id="tblSumAlur"></tbody>
            </table></div>
        </div>
    </div>
</div>

<script>
function bil(n){return Number(n||0).toLocaleString('id-ID');}
const API_CANDIDATES = ['statistik-data', '/lawangsewu/statistik-data', '/statistik-data'];
const qHakim = document.getElementById('qHakim');
const btnTvMode = document.getElementById('btnTvMode');
const tvDuration = document.getElementById('tvDuration');
let hakimRowsCache = [];
let tvMode = false;
let tvTimer = null;
let tvIndex = 0;
const tvSlides = Array.from(document.querySelectorAll('[data-tv-slide]'));
function fillYear(){
    const y = new Date().getFullYear();
    const tahunEl = document.getElementById('tahun');
    for(let i=y;i>=y-5;i--){
        const opt = document.createElement('option');
        opt.value = String(i);
        opt.textContent = String(i);
        tahunEl.appendChild(opt);
    }
}
function showTvSlide(index){ tvSlides.forEach((slide,i)=>slide.classList.toggle('active', i===index)); }
function startTvMode(){
    if (tvSlides.length === 0) return;
    tvMode = true;
    tvIndex = 0;
    document.body.classList.add('tv-mode');
    showTvSlide(tvIndex);
    btnTvMode.textContent = 'Mode TV: ON';
    tvTimer = window.setInterval(()=>{
        tvIndex = (tvIndex + 1) % tvSlides.length;
        showTvSlide(tvIndex);
    }, Number(tvDuration.value || 10000));
}
function stopTvMode(){
    tvMode = false;
    document.body.classList.remove('tv-mode');
    tvSlides.forEach(slide => slide.classList.add('active'));
    btnTvMode.textContent = 'Mode TV: OFF';
    if (tvTimer) {
        window.clearInterval(tvTimer);
        tvTimer = null;
    }
}
function toggleTvMode(){ tvMode ? stopTvMode() : startTvMode(); }
async function fetchStatistikHakim(tahun){
    let lastError = 'endpoint tidak ditemukan';
    for (const base of API_CANDIDATES) {
        const joiner = base.includes('?') ? '&' : '?';
        const url = `${base}${joiner}hal=hakim&tahun=${tahun}&t=${Date.now()}`;
        try {
            const res = await fetch(url, { cache: 'no-store' });
            if (!res.ok) {
                lastError = `HTTP ${res.status} di ${base}`;
                continue;
            }
            const json = await res.json();
            if (json && json.ok) {
                return json;
            }
            lastError = (json && json.message) ? json.message : `Respons tidak valid dari ${base}`;
        } catch (e) {
            lastError = `${base}: ${e.message}`;
        }
    }
    throw new Error('Endpoint statistik tidak terjangkau (' + lastError + ')');
}
function renderHakimRows(list){
    const keyword = (qHakim.value || '').toLowerCase().trim();
    const rows = keyword
        ? list.filter(v => String(v.hakim_nama || '').toLowerCase().includes(keyword))
        : list;

    let html='',num=1,sisa=0,terima=0,bebanTot=0,a0=0,a1=0,a2=0,a3=0,a4=0,a5=0,sisaNow=0;
    rows.forEach(v=>{ const beban=Number(v.sisa||0)+Number(v.diterima||0); html += `<tr><td>${num++}</td><td>${v.hakim_nama||'-'}</td><td class='right'>${bil(v.sisa)}</td><td class='right'>${bil(v.diterima)}</td><td class='right'>${bil(beban)}</td><td class='right'>-</td><td class='right'>${bil(v.a0)}</td><td class='right'>${bil(v.a1)}</td><td class='right'>${bil(v.a2)}</td><td class='right'>${bil(v.a3)}</td><td class='right'>${bil(v.a4)}</td><td class='right'>${bil(v.a5)}</td><td class='right'>${bil(v.sisa_sekarang)}</td></tr>`; sisa+=Number(v.sisa||0); terima+=Number(v.diterima||0); bebanTot+=beban; a0+=Number(v.a0||0); a1+=Number(v.a1||0); a2+=Number(v.a2||0); a3+=Number(v.a3||0); a4+=Number(v.a4||0); a5+=Number(v.a5||0); sisaNow+=Number(v.sisa_sekarang||0); });
    html += `<tr><th colspan='2' class='right'>TOTAL</th><th class='right'>${bil(sisa)}</th><th class='right'>${bil(terima)}</th><th class='right'>${bil(bebanTot)}</th><th class='right'>100%</th><th class='right'>${bil(a0)}</th><th class='right'>${bil(a1)}</th><th class='right'>${bil(a2)}</th><th class='right'>${bil(a3)}</th><th class='right'>${bil(a4)}</th><th class='right'>${bil(a5)}</th><th class='right'>${bil(sisaNow)}</th></tr>`;
    document.getElementById('tblSumHakim').innerHTML = html;
}
async function loadData(){
    const errorBox = document.getElementById('errorBox');
    const tahun = document.getElementById('tahun').value;
    errorBox.style.display = 'none';
    try{
        const json = await fetchStatistikHakim(tahun);

        hakimRowsCache = json.summary_hakim||[];
        renderHakimRows(hakimRowsCache);

        const alur=json.penyelesaian_by_alur||[];
        let html2='',n2=1,j1=0,j2=0,j3=0,j4=0,j5=0,j6=0;
        alur.forEach(v=>{ html2 += `<tr><td>${n2++}</td><td>${v.alur||'-'}</td><td class='right'>${bil(v.j_ok)}</td><td class='right'>${bil(v.j5)}</td><td class='right'>${bil(v.jml_putusan)}</td><td class='right'>${bil(v.avg_putus_hari)}</td><td class='right'>${bil(v.putus_tercepat)}</td><td class='right'>${bil(v.putus_terlama)}</td></tr>`; j1+=Number(v.j_ok||0); j2+=Number(v.j5||0); j3+=Number(v.jml_putusan||0); j4+=Number(v.avg_putus_hari||0); j5+=Number(v.putus_tercepat||0); j6+=Number(v.putus_terlama||0); });
        html2 += `<tr><th colspan='2' class='right'>TOTAL</th><th class='right'>${bil(j1)}</th><th class='right'>${bil(j2)}</th><th class='right'>${bil(j3)}</th><th class='right'>${bil(j4)}</th><th class='right'>${bil(j5)}</th><th class='right'>${bil(j6)}</th></tr>`;
        document.getElementById('tblSumAlur').innerHTML = html2;
    }catch(err){
        errorBox.textContent = err.message;
        errorBox.style.display = 'block';
    }
}
fillYear();
document.getElementById('tahun').addEventListener('change', loadData);
qHakim.addEventListener('input', () => renderHakimRows(hakimRowsCache));
btnTvMode.addEventListener('click', toggleTvMode);
tvDuration.addEventListener('change', () => {
    if (tvMode) {
        stopTvMode();
        startTvMode();
    }
});
document.addEventListener('DOMContentLoaded', () => {
    tvSlides.forEach(slide => slide.classList.add('active'));
    loadData();
});
</script>
</body>
</html>
<?php /* developed by dubes favour-it */ ?>
