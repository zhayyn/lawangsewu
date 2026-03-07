<?php /* developed by dubes favour-it */ ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik eCourt</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --line:#e5e7eb; --muted:#6b7280; --p:#0d6b41; --shadow:0 10px 22px rgba(9,46,29,.08); }
        * { box-sizing: border-box; }
        body { margin:0; font-family: "Inter", "Segoe UI", Arial, sans-serif; background:radial-gradient(circle at top right,#f4fbf7 0,#eef4f1 38%); color:#1f2937; }
        .container { max-width: 1280px; margin: 18px auto; padding: 0 14px; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:10px; flex-wrap:wrap; }
        .header h2 { margin:0; }
        .tools { display:flex; align-items:center; gap:8px; }
        #tahun { border:1px solid #d6dde6; border-radius:10px; padding:8px 10px; min-width:120px; }
        .select { border:1px solid #d6dde6; border-radius:10px; padding:8px 10px; min-width:86px; }
        .btn { border: 0; background: linear-gradient(135deg, #0d6b41, #12714a); color: #fff; border-radius: 9px; padding: 8px 12px; cursor: pointer; font-weight: 700; }
        .btn-tv { background: #111827; }
        .cards { display:grid; grid-template-columns: repeat(5, minmax(120px,1fr)); gap:10px; margin-bottom:14px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:12px; padding:12px; text-align:center; box-shadow:0 2px 8px rgba(15,23,42,.03); transition:transform .16s ease, box-shadow .16s ease; }
        .card:hover { transform:translateY(-2px); box-shadow:0 8px 14px rgba(15,23,42,.08); }
        .card small { color:var(--muted); }
        .card h4 { margin: 8px 0 0; color:var(--p); font-size:19px; }
        .panel { background:#fff; border:1px solid var(--line); border-radius:14px; overflow:hidden; margin-bottom:14px; box-shadow:var(--shadow); }
        .panel h3 { margin:0; padding:10px 12px; font-size:15px; background:#f0f6f2; color:#0f5132; }
        .panel .body { padding:12px; }
        .grid { display:grid; grid-template-columns: 360px 1fr; gap:12px; }
        .table-wrap { max-height: 310px; overflow:auto; border:1px solid var(--line); border-radius:10px; }
        .legend-wrap { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .legend-item { display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 999px; padding: 4px 9px; font-size: 12px; color: #334155; }
        .legend-dot { width: 10px; height: 10px; border-radius: 999px; box-shadow: 0 1px 3px rgba(0,0,0,.18); }
        table { width:100%; border-collapse:collapse; }
        th, td { border:1px solid var(--line); padding:8px; font-size:13px; }
        th { background:#fafafa; position:sticky; top:0; z-index:1; }
        tbody tr:hover { background:#f8fbf9; }
        .right { text-align:right; }
        .error { background:#fff3f2; border:1px solid #fecdca; color:#b42318; padding:10px; border-radius:8px; display:none; margin-bottom:10px; }
        .tv-mode [data-tv-slide] { display:none; }
        .tv-mode [data-tv-slide].active { display:block; }
        .tv-mode .table-wrap { max-height: 70vh; }
        @media(max-width: 960px){ .cards{grid-template-columns: repeat(2,1fr);} .grid{grid-template-columns:1fr;} }
        @media(max-width: 640px){ .container{padding:0 10px;} .tools{width:100%;} #tahun{flex:1;} .btn{white-space:nowrap;} }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Dashboard Perkara eCourt</h2>
        <div class="tools">
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

    <div class="cards">
        <div class="card"><small>Sisa</small><h4 id="sisa_lalu">0</h4></div>
        <div class="card"><small>Masuk</small><h4 id="masuk">0</h4></div>
        <div class="card"><small>Beban</small><h4 id="beban">0</h4></div>
        <div class="card"><small>Putus</small><h4 id="putus">0</h4></div>
        <div class="card"><small>Belum Putus</small><h4 id="belum_putus">0</h4></div>
    </div>

    <div class="panel" data-tv-slide>
        <h3>eCourt Masuk per Bulan</h3>
        <div class="body grid">
            <div class="table-wrap"><table><thead><tr><th>Bulan</th><th class="right">Jumlah</th></tr></thead><tbody id="tblBulanan"></tbody></table></div>
            <div>
                <canvas id="chartBulanan"></canvas>
                <div class="legend-wrap" id="legendBulanan"></div>
            </div>
        </div>
    </div>

    <div class="panel" data-tv-slide>
        <h3>eCourt Berdasarkan Alur Perkara</h3>
        <div class="body grid">
            <div class="table-wrap"><table><thead><tr><th>Alur</th><th class="right">Jumlah</th></tr></thead><tbody id="tblAlur"></tbody></table></div>
            <div>
                <canvas id="chartAlur"></canvas>
                <div class="legend-wrap" id="legendAlur"></div>
            </div>
        </div>
    </div>

    <div class="panel" data-tv-slide>
        <h3>eCourt Berdasarkan Jenis Perkara</h3>
        <div class="body grid">
            <div class="table-wrap"><table><thead><tr><th>Jenis</th><th class="right">Jumlah</th></tr></thead><tbody id="tblJenis"></tbody></table></div>
            <div>
                <canvas id="chartJenis"></canvas>
                <div class="legend-wrap" id="legendJenis"></div>
            </div>
        </div>
    </div>
</div>

<script>
const namaBulan=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const colors=['#0d6efd','#198754','#dc3545','#ffc107','#6f42c1','#20c997','#fd7e14','#6610f2','#0dcaf0','#adb5bd'];
let cBulanan,cAlur,cJenis;
const API_CANDIDATES = ['statistik-data', '/lawangsewu/statistik-data', '/statistik-data'];
const btnTvMode = document.getElementById('btnTvMode');
const tvDuration = document.getElementById('tvDuration');
let tvMode = false;
let tvTimer = null;
let tvIndex = 0;
const tvSlides = Array.from(document.querySelectorAll('[data-tv-slide]'));
const palette = ['#00C2FF','#7ED321','#00B8D9','#FFB020','#7A5AF8','#EC4899','#F97316','#22C55E','#6366F1','#E11D48','#14B8A6','#A855F7'];
const fixedLabelColors = {
    'Cerai Gugat': '#7ED321',
    'Cerai Talak': '#00B8D9',
    'Perdata Gugatan': '#00C2FF',
    'Perdata Permohonan': '#7A5AF8',
    'Asal Usul Anak': '#14B8A6',
    'Dispensasi Kawin': '#6366F1',
    'P3HP/Penetapan Ahli Waris': '#A855F7',
    'Perwalian': '#22C55E',
    'Penguasaan Anak': '#E11D48'
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
function bil(n){return Number(n||0).toLocaleString('id-ID');}
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
function pieCols(n){const arr=[];for(let i=0;i<n;i++)arr.push(colors[i%colors.length]);return arr;}
function colorsByLabels(labels){ return labels.map(label => colorByLabel(label)); }
function renderColorLegend(targetId, labels, colorList){
    const el = document.getElementById(targetId);
    if (!el) {
        return;
    }
    el.innerHTML = labels.map((label, idx) => (
        `<span class="legend-item"><span class="legend-dot" style="background:${colorList[idx]}"></span>${label}</span>`
    )).join('');
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
async function fetchStatistikEcourt(tahun){
    let lastError = 'endpoint tidak ditemukan';
    for (const base of API_CANDIDATES) {
        const joiner = base.includes('?') ? '&' : '?';
        const url = `${base}${joiner}hal=ecourt&tahun=${tahun}&t=${Date.now()}`;
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
async function loadData(){
    const errorBox = document.getElementById('errorBox');
    const tahun = document.getElementById('tahun').value;
    errorBox.style.display = 'none';
    try{
        const json = await fetchStatistikEcourt(tahun);

        const p = json.ringkasan || {sisa_lalu:0,masuk:0,putus:0,belum_putus:0};
        const e = json.summary || {sisa_lalu:0,masuk:0,putus:0,belum_putus:0};
        const pBeban = Number(p.sisa_lalu)+Number(p.masuk);
        const eBeban = Number(e.sisa_lalu)+Number(e.masuk);

        const pct = (a,b)=> b>0 ? `${((a/b)*100).toFixed(2)}%` : '0.00%';
        document.getElementById('sisa_lalu').textContent = `${bil(e.sisa_lalu)} (${pct(e.sisa_lalu,p.sisa_lalu)})`;
        document.getElementById('masuk').textContent = `${bil(e.masuk)} (${pct(e.masuk,p.masuk)})`;
        document.getElementById('beban').textContent = `${bil(eBeban)} (${pct(eBeban,pBeban)})`;
        document.getElementById('putus').textContent = `${bil(e.putus)} (${pct(e.putus,p.putus)})`;
        document.getElementById('belum_putus').textContent = `${bil(e.belum_putus)} (${pct(e.belum_putus,p.belum_putus)})`;

        const bul=json.bulanan||[];
        let blLbl=[],blVal=[],tot=0,html='';
        bul.forEach(v=>{blLbl.push(namaBulan[Number(v.bln)||0]); blVal.push(Number(v.jml)||0); tot+=Number(v.jml)||0; html+=`<tr><td>${namaBulan[Number(v.bln)||0]}</td><td class="right">${bil(v.jml)}</td></tr>`;});
        html += `<tr><th class="right">TOTAL</th><th class="right">${bil(tot)}</th></tr>`;
        document.getElementById('tblBulanan').innerHTML = html;
        if (window.Chart) {
            if(cBulanan) cBulanan.destroy();
            const bulananColors = blVal.map((_,i)=>palette[i%palette.length]);
            cBulanan = new Chart(document.getElementById('chartBulanan'), {
                type:'bar',
                plugins:[chartShadowPlugin],
                data:{labels:blLbl,datasets:[{label:'Perkara',data:blVal,backgroundColor:bulananColors,borderColor:bulananColors,borderWidth:1.5,borderRadius:8,maxBarThickness:36}]},
                options:{plugins:{legend:{display:false}},interaction:{mode:'index',intersect:false},scales:{y:{beginAtZero:true}}}
            });
            renderColorLegend('legendBulanan', blLbl, bulananColors);
        }

        const al=json.alur||[];
        let aLbl=[],aVal=[],aTot=0,aHtml='';
        al.forEach(v=>{aLbl.push(v.label); aVal.push(Number(v.jml)||0); aTot+=Number(v.jml)||0; aHtml+=`<tr><td>${v.label}</td><td class="right">${bil(v.jml)}</td></tr>`;});
        aHtml += `<tr><th class="right">TOTAL</th><th class="right">${bil(aTot)}</th></tr>`;
        document.getElementById('tblAlur').innerHTML = aHtml;
        if (window.Chart) {
            if(cAlur) cAlur.destroy();
            const alurColors = colorsByLabels(aLbl);
            cAlur = new Chart(document.getElementById('chartAlur'), {
                type:'pie',
                plugins:[chartShadowPlugin],
                data:{labels:aLbl,datasets:[{data:aVal,backgroundColor:alurColors}]},
                options:{plugins:{legend:{position:'bottom'}}}
            });
            renderColorLegend('legendAlur', aLbl, alurColors);
        }

        const je=json.jenis||[];
        let jLbl=[],jVal=[],jTot=0,jHtml='';
        je.forEach(v=>{jLbl.push(v.label); jVal.push(Number(v.jml)||0); jTot+=Number(v.jml)||0; jHtml+=`<tr><td>${v.label}</td><td class="right">${bil(v.jml)}</td></tr>`;});
        jHtml += `<tr><th class="right">TOTAL</th><th class="right">${bil(jTot)}</th></tr>`;
        document.getElementById('tblJenis').innerHTML = jHtml;
        if (window.Chart) {
            if(cJenis) cJenis.destroy();
            const jenisColors = colorsByLabels(jLbl);
            cJenis = new Chart(document.getElementById('chartJenis'), {
                type:'doughnut',
                plugins:[chartShadowPlugin],
                data:{labels:jLbl,datasets:[{data:jVal,backgroundColor:jenisColors}]},
                options:{plugins:{legend:{position:'bottom'}}}
            });
            renderColorLegend('legendJenis', jLbl, jenisColors);
        }
    }catch(err){
        errorBox.textContent = err.message;
        errorBox.style.display = 'block';
    }
}
fillYear();
document.getElementById('tahun').addEventListener('change', loadData);
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
