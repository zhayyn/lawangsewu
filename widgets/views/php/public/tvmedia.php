<?php
/* tvmedia.php — TV Media Slideshow — developed by zhayyn™
 * Dioptimasi untuk Smart TV: tanpa Google Fonts, tanpa backdrop-filter,
 * CSS minimal, JS vanilla, tidak ada library eksternal.
 * Layout 16:9 — cocok untuk monitor TV 1080p/4K.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <!-- Lebar viewport tetap 1280 agar kalkulasi px konsisten di semua TV -->
    <meta name="viewport" content="width=1280, initial-scale=1.0">
    <title>TV Media — Pengadilan Agama Semarang</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        /* ─── Reset ringan ─────────────────────────────────────── */
        *{box-sizing:border-box;margin:0;padding:0}

        :root{
            --bg:#040b14;
            --bar:#0a1628;
            --accent:#00d4aa;
            --accent2:#3b82f6;
            /* Bar lebih tipis agar konten slide punya ruang maksimal di 16:9 */
            --bar-h:52px;
            --trans:400ms ease;
        }

        html,body{
            width:100%;height:100%;
            overflow:hidden;
            background:var(--bg);
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
            color:#fff;
        }

        /* ─── Stage ────────────────────────────────────────────── */
        #tv-stage{
            position:fixed;
            top:0;left:0;right:0;
            bottom:var(--bar-h);
            background:var(--bg);
        }

        .tv-slide{
            position:absolute;
            inset:0;
            opacity:0;
            transition:opacity var(--trans);
            pointer-events:none;
            z-index:1;
        }
        .tv-slide.active{
            opacity:1;
            pointer-events:all;
            z-index:2;
        }
        .tv-slide.leaving{
            opacity:0;
            z-index:1;
        }
        .tv-slide iframe{
            width:100%;height:100%;
            border:none;display:block;
            background:#000;
        }

        /* ─── Fallback ─────────────────────────────────────────── */
        .slide-fallback{
            display:none;
            flex-direction:column;
            align-items:center;justify-content:center;
            height:100%;gap:16px;
            color:#64748b;text-align:center;padding:40px;
            background:var(--bg);
        }
        .slide-fallback svg{width:56px;height:56px;opacity:.4}
        .slide-fallback p{font-size:15px;max-width:480px;line-height:1.6}

        /* ─── Label ────────────────────────────────────────────── */
        .slide-label{
            position:absolute;
            top:12px;left:16px;
            background:rgba(4,11,20,.82);
            border:1px solid rgba(255,255,255,.1);
            border-radius:20px;
            padding:4px 12px;
            font-size:11px;font-weight:600;
            color:rgba(255,255,255,.65);
            letter-spacing:.4px;
            z-index:10;pointer-events:none;
        }

        /* ─── Progress bar ─────────────────────────────────────── */
        #tv-progress{
            position:fixed;
            bottom:var(--bar-h);left:0;right:0;
            height:3px;
            background:rgba(255,255,255,.08);
            z-index:99;
        }
        #tv-progress-fill{
            height:100%;width:0%;
            background:linear-gradient(90deg,var(--accent),var(--accent2));
            transition:width linear;
        }

        /* ─── Bottom Bar ───────────────────────────────────────── */
        #tv-bar{
            position:fixed;
            bottom:0;left:0;right:0;
            height:var(--bar-h);
            background:var(--bar);
            border-top:1px solid rgba(255,255,255,.07);
            display:flex;align-items:center;
            padding:0 18px;gap:12px;
            z-index:100;
        }

        #bar-brand{display:flex;align-items:center;gap:8px;flex-shrink:0}
        .logo-wrap{
            width:30px;height:30px;
            background:linear-gradient(135deg,var(--accent),var(--accent2));
            border-radius:7px;
            display:flex;align-items:center;justify-content:center;
            font-size:16px;
        }
        .brand-name{font-size:12px;font-weight:700;color:#e2e8f0;line-height:1.2}
        .brand-sub{font-size:9px;color:#475569;margin-top:1px}

        .bar-sep{width:1px;height:28px;background:rgba(255,255,255,.09);flex-shrink:0}

        #bar-indicators{display:flex;gap:5px;align-items:center;flex:1}
        .dot{
            width:7px;height:7px;border-radius:50%;
            background:rgba(255,255,255,.18);
            cursor:pointer;
            transition:all .2s;flex-shrink:0;
        }
        .dot.on{width:20px;border-radius:4px;background:var(--accent)}
        .dot:hover:not(.on){background:rgba(255,255,255,.4)}

        #bar-title{
            font-size:11px;color:#64748b;font-weight:500;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
            max-width:180px;
        }

        /* ─── Jam ──────────────────────────────────────────────── */
        #bar-clock{flex-shrink:0;text-align:right}
        #clk-time{
            font-size:18px;font-weight:800;
            font-family:monospace;
            color:var(--accent);letter-spacing:1px;line-height:1;
        }
        #clk-date{font-size:9px;color:#475569;margin-top:2px}

        /* ─── Admin btn ────────────────────────────────────────── */
        #btn-admin{
            display:none;
            background:rgba(255,255,255,.07);
            border:1px solid rgba(255,255,255,.1);
            color:#94a3b8;border-radius:7px;
            padding:5px 11px;font-size:11px;font-weight:600;
            cursor:pointer;flex-shrink:0;
        }
        #btn-admin:hover{background:rgba(255,255,255,.14);color:#f1f5f9}

        /* ─── Admin Panel ──────────────────────────────────────── */
        #adm{
            display:none;
            position:fixed;inset:0 0 var(--bar-h) 0;
            background:rgba(4,11,20,.97);
            z-index:200;overflow-y:auto;padding:28px 24px;
        }
        #adm.open{display:block}
        .adm-h{font-size:20px;font-weight:800;color:#e2e8f0;margin-bottom:3px}
        .adm-s{font-size:12px;color:#475569;margin-bottom:20px}

        .adm-card{
            background:rgba(255,255,255,.04);
            border:1px solid rgba(255,255,255,.07);
            border-radius:10px;padding:14px 16px;
            margin-bottom:8px;
            display:flex;align-items:center;gap:12px;
        }
        .adm-body{flex:1;min-width:0}
        .adm-lbl{font-size:13px;font-weight:700;color:#e2e8f0;margin-bottom:2px}
        .adm-src{font-size:10px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .adm-badge{
            padding:2px 9px;border-radius:20px;
            font-size:9px;font-weight:700;letter-spacing:.5px;flex-shrink:0;
        }
        .adm-badge.iframe{background:rgba(59,130,246,.18);color:#93c5fd;border:1px solid rgba(59,130,246,.28)}
        .adm-badge.widget{background:rgba(0,212,170,.14);color:#6ee7d4;border:1px solid rgba(0,212,170,.22)}
        .adm-badge.image{background:rgba(245,158,11,.14);color:#fcd34d;border:1px solid rgba(245,158,11,.22)}
        .adm-dur{display:flex;align-items:center;gap:5px;flex-shrink:0}
        .adm-dur input{
            width:55px;
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.1);
            border-radius:6px;color:#f1f5f9;
            font-size:12px;padding:3px 6px;text-align:center;
        }
        .adm-dur span{font-size:10px;color:#475569}
        .adm-del{
            background:rgba(239,68,68,.14);
            border:1px solid rgba(239,68,68,.22);
            color:#fca5a5;border-radius:6px;
            padding:5px 9px;cursor:pointer;font-size:11px;flex-shrink:0;
        }
        .adm-del:hover{background:rgba(239,68,68,.28)}

        .adm-add{
            background:rgba(0,212,170,.05);
            border:1px solid rgba(0,212,170,.12);
            border-radius:10px;padding:16px;margin-top:16px;
        }
        .adm-add-h{font-size:13px;font-weight:700;color:#6ee7d4;margin-bottom:12px}
        .adm-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px}
        .adm-f{flex:1;min-width:130px}
        .adm-f label{display:block;font-size:10px;color:#475569;margin-bottom:3px}
        .adm-f input,.adm-f select{
            width:100%;
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.09);
            border-radius:7px;color:#f1f5f9;
            font-size:12px;padding:7px 9px;
        }
        .adm-f select option{background:#0f172a}
        .adm-btns{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
        .btn-ok{
            background:var(--accent);color:#040b14;
            border:none;border-radius:7px;
            padding:8px 16px;font-weight:700;font-size:12px;cursor:pointer;
        }
        .btn-ok:hover{opacity:.85}
        .btn-sec{
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.09);
            color:#94a3b8;border-radius:7px;
            padding:8px 16px;font-weight:600;font-size:12px;cursor:pointer;
        }
        .btn-sec:hover{background:rgba(255,255,255,.12);color:#f1f5f9}
        .btn-del{
            background:rgba(239,68,68,.12);
            border:1px solid rgba(239,68,68,.22);
            color:#fca5a5;border-radius:7px;
            padding:8px 16px;font-weight:600;font-size:12px;cursor:pointer;
        }
        .btn-del:hover{background:rgba(239,68,68,.26)}
        .adm-x{
            position:fixed;top:16px;right:18px;
            background:rgba(255,255,255,.07);
            border:1px solid rgba(255,255,255,.1);
            color:#94a3b8;border-radius:50%;
            width:34px;height:34px;
            display:flex;align-items:center;justify-content:center;
            cursor:pointer;font-size:18px;z-index:210;
        }
        .adm-x:hover{background:rgba(255,255,255,.14);color:#fff}

        /* ─── Hint ─────────────────────────────────────────────── */
        #hint{
            position:fixed;top:10px;right:14px;
            font-size:10px;color:rgba(255,255,255,.15);
            z-index:50;pointer-events:none;
        }
    </style>
</head>
<body>

<div id="hint">Space/→: next &nbsp; F: layar penuh &nbsp; A: admin</div>

<div id="tv-stage"></div>

<div id="tv-progress"><div id="tv-progress-fill"></div></div>

<div id="tv-bar">
    <div id="bar-brand">
        <div class="logo-wrap">⚖️</div>
        <div>
            <div class="brand-name">Pengadilan Agama Semarang</div>
            <div class="brand-sub">lawangsewu.pa-semarang.go.id/tvmedia</div>
        </div>
    </div>
    <div class="bar-sep"></div>
    <div id="bar-indicators"></div>
    <div class="bar-sep"></div>
    <div id="bar-title">Memuat...</div>
    <div style="flex:1"></div>
    <button id="btn-admin" onclick="tgAdmin()">⚙ Kelola Slide</button>
    <div class="bar-sep"></div>
    <div id="bar-clock">
        <div id="clk-time">--:--:--</div>
        <div id="clk-date">...</div>
    </div>
</div>

<!-- Admin Panel -->
<div id="adm">
    <button class="adm-x" onclick="tgAdmin()" title="Tutup">✕</button>
    <div class="adm-h">⚙ Kelola Slide TV Media</div>
    <div class="adm-s">Perubahan disimpan di browser ini (localStorage). Klik "Terapkan" untuk memperbarui slideshow.</div>
    <div id="adm-list"></div>
    <div class="adm-add">
        <div class="adm-add-h">+ Tambah Slide Baru</div>
        <div class="adm-row">
            <div class="adm-f">
                <label>Label Slide</label>
                <input type="text" id="add-lbl" placeholder="Nama slide">
            </div>
            <div class="adm-f">
                <label>Tipe</label>
                <select id="add-type">
                    <option value="iframe">iframe (URL eksternal)</option>
                    <option value="widget">widget (halaman internal)</option>
                    <option value="image">image (URL gambar)</option>
                </select>
            </div>
            <div class="adm-f">
                <label>Durasi (detik)</label>
                <input type="number" id="add-dur" value="15" min="5" max="300">
            </div>
        </div>
        <div class="adm-row">
            <div class="adm-f" style="min-width:100%">
                <label>URL / Sumber</label>
                <input type="text" id="add-src" placeholder="https://... atau /path/internal">
            </div>
        </div>
        <div class="adm-btns">
            <button class="btn-ok" onclick="addSlide()">+ Tambah</button>
            <button class="btn-sec" onclick="applyPL()">✓ Terapkan &amp; Mulai Ulang</button>
            <button class="btn-del" onclick="resetPL()">↺ Reset ke Default</button>
        </div>
    </div>
</div>

<script>
/* TV Media Slideshow Engine — developed by zhayyn™
 * Ringan: tanpa library eksternal, ES5-compatible untuk Smart TV lama */

var SK = 'tvmedia_playlist_v7'; /* v7: revert to embed link */
var DEF = [
    {
        id:'canva-1', type:'iframe', label:'Laporan Kesekretariatan',
        /* autoplay=1: Canva mungkin mengaktifkan auto-advance native jika diset dari dalam Canva */
        src:'https://www.canva.com/design/DAHLiKEH7OE/dBR2-4mnDt47h0bHEs7Piw/view?embed&autoplay=1',
        /* slideCount x slideIntervalSec = durasi otomatis (30 x 4 = 120 dtk) */
        slideCount: 30,
        slideIntervalSec: 4,
        /* duration dihitung otomatis dari slideCount x slideIntervalSec */
        duration: 120000,
        fallback:'Konten Canva tidak dapat dimuat. Pastikan design diset Publik di Canva.'
    },
    {id:'statistik-perkara', type:'widget', label:'Statistik Perkara',
     src:'/statistik-perkara?tvmode=1', duration:10000, fallback:null},
    {id:'info-persidangan', type:'widget', label:'Info Persidangan',
     src:'/info-persidangan', duration:10000, fallback:null}
];

var pl=[], cur=0, tmr=null, paused=false, isAdm=false;
/* ── Iframe slide-advance state ────────────────────────────── */
var ifrAdvTmr=null;   /* timer postMessage per-interval ke iframe */
var curIfrEl=null;    /* referensi elemen iframe yang sedang aktif */

/* ── Init ───────────────────────────────────────────────────── */
function init(){
    var p=window.location.search;
    if(p.indexOf('admin=1')!==-1){
        isAdm=true;
        document.getElementById('btn-admin').style.display='inline-block';
    }
    var sv=null;
    try{ sv=JSON.parse(localStorage.getItem(SK)); }catch(e){}
    pl=(sv&&sv.length)?sv:cloneDef();
    buildSlides();
    buildDots();
    goTo(0);
    startClock();
}

function cloneDef(){ return JSON.parse(JSON.stringify(DEF)); }

/* ── Build slides ───────────────────────────────────────────── */
function buildSlides(){
    var stage=document.getElementById('tv-stage');
    stage.innerHTML='';
    for(var i=0;i<pl.length;i++) stage.appendChild(makeSlide(pl[i],i));
}

function makeSlide(s,idx){
    var div=document.createElement('div');
    div.className='tv-slide';
    div.id='sl-'+idx;

    var lbl=document.createElement('div');
    lbl.className='slide-label';
    lbl.textContent=s.label||s.type;
    div.appendChild(lbl);

    if(s.type==='iframe'||s.type==='widget'){
        var fr=document.createElement('iframe');
        fr.src=s.src||'';
        fr.setAttribute('loading','lazy');
        fr.setAttribute('allow','fullscreen; autoplay');
        fr.setAttribute('allowfullscreen','allowfullscreen');
        /* Canva & URL eksternal tidak boleh di-sandbox — akan memblokir scriptnya.
           Widget internal (same-origin) tetap diberi sandbox ringan. */
        if(s.type==='widget'){
            fr.setAttribute('sandbox','allow-scripts allow-same-origin allow-forms allow-popups');
        }
        /* onload/onerror via dataset agar tidak eval */
        fr.dataset.idx=idx;
        fr.addEventListener('load',frLoad);
        fr.addEventListener('error',frError);
        div.appendChild(fr);

        var fb=document.createElement('div');
        fb.className='slide-fallback';
        fb.id='fb-'+idx;
        fb.innerHTML='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">'
            +'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" '
            +'d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 '
            +'1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 '
            +'0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
            +'<p>'+esc(s.fallback||'Konten tidak dapat dimuat.')+'</p>';
        div.appendChild(fb);

    } else if(s.type==='image'){
        var wrap=document.createElement('div');
        wrap.style.cssText='display:flex;align-items:center;justify-content:center;height:100%;background:#000';
        var img=document.createElement('img');
        img.src=s.src||'';
        img.alt=s.label||'';
        img.style.cssText='max-width:100%;max-height:100%;object-fit:contain';
        wrap.appendChild(img);
        div.appendChild(wrap);

    } else if(s.type==='html'){
        var box=document.createElement('div');
        box.style.cssText='display:flex;align-items:center;justify-content:center;height:100%;padding:40px';
        var inner=document.createElement('div');
        inner.style.cssText='max-width:800px;font-size:32px;line-height:1.6;text-align:center;color:#e2e8f0';
        inner.innerHTML=s.src||'';
        box.appendChild(inner);
        div.appendChild(box);
    }

    return div;
}

function frLoad(e){
    var fr=e.target;
    try{
        /* same-origin: cek href */
        var h=fr.contentWindow&&fr.contentWindow.location.href;
        if(h==='about:blank') return;
    }catch(ex){/* cross-origin — anggap berhasil */}
    var fb=document.getElementById('fb-'+fr.dataset.idx);
    if(fb) fb.style.display='none';
    fr.style.display='block';
}

function frError(e){
    var fr=e.target;
    var fb=document.getElementById('fb-'+fr.dataset.idx);
    if(fb) fb.style.display='flex';
    fr.style.display='none';
}

/* ── Dots / Indicators ──────────────────────────────────────── */
function buildDots(){
    var bar=document.getElementById('bar-indicators');
    bar.innerHTML='';
    for(var i=0;i<pl.length;i++){
        var d=document.createElement('div');
        d.className='dot'+(i===cur?' on':'');
        d.id='dot-'+i;
        d.title=pl[i].label||'';
        (function(n){d.onclick=function(){goTo(n)};})(i);
        bar.appendChild(d);
    }
}

function updDots(){
    for(var i=0;i<pl.length;i++){
        var d=document.getElementById('dot-'+i);
        if(d){ d.className='dot'+(i===cur?' on':''); }
    }
    var t=document.getElementById('bar-title');
    if(t&&pl[cur]) t.textContent=pl[cur].label||'';
}

/* ── Duration helper ─────────────────────────────────────────── */
/* Hitung durasi: jika slide punya slideCount + slideIntervalSec,
   gunakan slideCount × slideIntervalSec (dalam ms). */
function calcDur(s){
    if(s&&s.slideCount&&s.slideIntervalSec){
        return Math.max(1000, s.slideCount * s.slideIntervalSec * 1000);
    }
    return (s&&s.duration)||15000;
}

/* ── Iframe advance (best-effort postMessage) ─────────────────── */
/* Canva dan beberapa iframe eksternal mungkin merespons postMessage
   atau keyboard event. Ini upaya terbaik — cross-origin memang
   membatasi kontrol penuh dari luar. */
function stopIfrAdv(){
    if(ifrAdvTmr){ clearInterval(ifrAdvTmr); ifrAdvTmr=null; }
    curIfrEl=null;
}

function tryAdvCanva(fr){
    if(!fr||!fr.contentWindow) return;
    /* Format 1 — Arrow key via postMessage */
    try{ fr.contentWindow.postMessage({type:'keydown',key:'ArrowRight',keyCode:39,which:39},'*'); }catch(e){}
    /* Format 2 — Canva presentation next */
    try{ fr.contentWindow.postMessage(JSON.stringify({type:'next'}),'*'); }catch(e){}
    /* Format 3 — generic */
    try{ fr.contentWindow.postMessage({action:'next'},'https://www.canva.com'); }catch(e){}
}

function startIfrAdv(fr, intervalMs){
    stopIfrAdv();
    curIfrEl=fr;
    /* Tembak pertama kali setelah iframe sedikit settled */
    setTimeout(function(){ tryAdvCanva(fr); }, 2000);
    ifrAdvTmr=setInterval(function(){ tryAdvCanva(fr); }, intervalMs);
}

/* ── Navigation ─────────────────────────────────────────────── */
function goTo(idx){
    if(!pl.length) return;
    idx=((idx%pl.length)+pl.length)%pl.length;

    /* Hentikan iframe advance dari slide sebelumnya */
    stopIfrAdv();

    var prev=document.getElementById('sl-'+cur);
    if(prev&&idx!==cur){
        prev.classList.remove('active');
        prev.classList.add('leaving');
        (function(el){ setTimeout(function(){el.classList.remove('leaving');},500); })(prev);
    }
    cur=idx;
    var next=document.getElementById('sl-'+cur);
    if(next) next.classList.add('active');

    /* Jika slide baru adalah iframe dengan slideCount, mulai advance timer */
    var s=pl[cur];
    if(s&&s.type==='iframe'&&s.slideCount&&s.slideIntervalSec){
        var fr=next&&next.querySelector('iframe');
        if(fr) startIfrAdv(fr, s.slideIntervalSec*1000);
    }

    updDots();
    schedule();
}

function nextSlide(){ goTo((cur+1)%pl.length); }

function schedule(){
    if(tmr){ clearTimeout(tmr); tmr=null; }
    if(paused) return;
    /* Gunakan calcDur agar slideCount × intervalSec dipakai jika ada */
    var dur=calcDur(pl[cur]);
    var fill=document.getElementById('tv-progress-fill');
    fill.style.transition='none';
    fill.style.width='0%';
    fill.getBoundingClientRect(); /* reflow */
    fill.style.transition='width '+dur+'ms linear';
    fill.style.width='100%';
    tmr=setTimeout(nextSlide,dur);
}

/* ── Clock ──────────────────────────────────────────────────── */
var HR=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
var BL=['Januari','Februari','Maret','April','Mei','Juni','Juli',
        'Agustus','September','Oktober','November','Desember'];
function startClock(){
    function tick(){
        var n=new Date();
        var hh=pad(n.getHours()),mm=pad(n.getMinutes()),ss=pad(n.getSeconds());
        document.getElementById('clk-time').textContent=hh+':'+mm+':'+ss;
        document.getElementById('clk-date').textContent=
            HR[n.getDay()]+', '+n.getDate()+' '+BL[n.getMonth()]+' '+n.getFullYear();
    }
    tick();
    setInterval(tick,1000);
}
function pad(n){ return n<10?'0'+n:String(n); }

/* ── Admin ──────────────────────────────────────────────────── */
function tgAdmin(){
    var panel=document.getElementById('adm');
    var opening=!panel.classList.contains('open');
    panel.classList.toggle('open');
    paused=opening;
    if(opening){
        if(tmr){clearTimeout(tmr);tmr=null;}
        stopIfrAdv(); /* hentikan iframe advance saat admin panel dibuka */
        document.getElementById('tv-progress-fill').style.width='0%';
        renderAdmList();
    } else { schedule(); }
}

function renderAdmList(){
    var list=document.getElementById('adm-list');
    if(!list) return;
    var html='';
    for(var i=0;i<pl.length;i++){
        var s=pl[i];
        var effDur=Math.round(calcDur(s)/1000);
        /* Untuk iframe dengan slideCount: tampilkan field jumlah slide + detik/slide */
        var slideFields='';
        if(s.type==='iframe'){
            var sc=s.slideCount||'';
            var si=s.slideIntervalSec||'';
            slideFields='<div style="display:flex;gap:6px;align-items:center;flex-shrink:0">'
                +'<div style="font-size:9px;color:#475569;text-align:center">Slide<br>'
                +'<input type="number" value="'+sc+'" min="1" max="500" placeholder="jml"'
                +' style="width:48px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);'
                +'border-radius:5px;color:#f1f5f9;font-size:11px;padding:3px 4px;text-align:center"'
                +' onchange="updSlideCount('+i+',this.value)">'
                +'</div>'
                +'<div style="font-size:9px;color:#475569;text-align:center">Dtk/slide<br>'
                +'<input type="number" value="'+si+'" min="1" max="60" placeholder="dtk"'
                +' style="width:44px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);'
                +'border-radius:5px;color:#f1f5f9;font-size:11px;padding:3px 4px;text-align:center"'
                +' onchange="updSlideInterval('+i+',this.value)">'
                +'</div>'
                +'</div>';
        }
        html+='<div class="adm-card">'
            +'<span class="adm-badge '+esc(s.type)+'">'+esc(s.type).toUpperCase()+'</span>'
            +'<div class="adm-body">'
            +'<div class="adm-lbl">'+esc(s.label||'(tanpa judul)')+'</div>'
            +'<div class="adm-src">'+esc(s.src||'')+'</div>'
            +'</div>'
            +slideFields
            +'<div class="adm-dur">'
            +'<input type="number" id="dur-inp-'+i+'" value="'+effDur+'"'
            +' min="3" max="3600" '
            +(s.slideCount&&s.slideIntervalSec?'readonly style="opacity:.5;cursor:not-allowed" title="Dihitung otomatis dari jumlah slide × detik/slide"':'')
            +' onchange="updDur('+i+',this.value)">'
            +'<span>dtk</span></div>'
            +'<button class="adm-del" onclick="delSlide('+i+')">✕</button>'
            +'</div>';
    }
    list.innerHTML=html;
}

function updDur(i,v){
    if(!pl[i]) return;
    /* Hanya update manual jika tidak ada slideCount (auto-calc) */
    if(!pl[i].slideCount){
        pl[i].duration=Math.max(3,parseInt(v)||15)*1000;
    }
}

function updSlideCount(i,v){
    if(!pl[i]) return;
    pl[i].slideCount=Math.max(1,parseInt(v)||1);
    /* Recalc duration otomatis */
    if(pl[i].slideIntervalSec){
        pl[i].duration=pl[i].slideCount*pl[i].slideIntervalSec*1000;
        var inp=document.getElementById('dur-inp-'+i);
        if(inp) inp.value=Math.round(pl[i].duration/1000);
    }
}

function updSlideInterval(i,v){
    if(!pl[i]) return;
    pl[i].slideIntervalSec=Math.max(1,parseInt(v)||4);
    /* Recalc duration otomatis */
    if(pl[i].slideCount){
        pl[i].duration=pl[i].slideCount*pl[i].slideIntervalSec*1000;
        var inp=document.getElementById('dur-inp-'+i);
        if(inp) inp.value=Math.round(pl[i].duration/1000);
    }
}

function delSlide(i){
    if(!confirm('Hapus slide ini?')) return;
    pl.splice(i,1);
    renderAdmList();
}

function addSlide(){
    var lbl=document.getElementById('add-lbl').value.trim();
    var type=document.getElementById('add-type').value;
    var src=document.getElementById('add-src').value.trim();
    var dur=parseInt(document.getElementById('add-dur').value)||15;
    if(!src){alert('URL tidak boleh kosong.');return;}
    pl.push({id:'sl-'+Date.now(),type:type,label:lbl||src,src:src,duration:dur*1000,fallback:null});
    document.getElementById('add-lbl').value='';
    document.getElementById('add-src').value='';
    document.getElementById('add-dur').value='15';
    renderAdmList();
}

function applyPL(){
    try{ localStorage.setItem(SK,JSON.stringify(pl)); }catch(e){}
    document.getElementById('adm').classList.remove('open');
    paused=false;cur=0;
    buildSlides();buildDots();goTo(0);
}

function resetPL(){
    if(!confirm('Reset ke playlist default?')) return;
    try{ localStorage.removeItem(SK); }catch(e){}
    pl=cloneDef();
    renderAdmList();
}

/* ── Keyboard ───────────────────────────────────────────────── */
document.addEventListener('keydown',function(e){
    if(e.target.tagName==='INPUT'||e.target.tagName==='TEXTAREA') return;
    var admOpen=document.getElementById('adm').classList.contains('open');

    if((e.key===' '||e.key==='ArrowRight'||e.key==='ArrowDown')&&!admOpen){
        e.preventDefault(); nextSlide();
    }
    if((e.key==='ArrowLeft'||e.key==='ArrowUp')&&!admOpen){
        e.preventDefault(); goTo(cur-1);
    }
    if((e.key==='f'||e.key==='F')&&!admOpen){
        if(!document.fullscreenElement){
            document.documentElement.requestFullscreen&&document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen&&document.exitFullscreen();
        }
    }
    if((e.key==='a'||e.key==='A')&&isAdm) tgAdmin();
    if(e.key==='Escape'&&admOpen) tgAdmin();
});

/* ── Helper ─────────────────────────────────────────────────── */
function esc(s){
    return String(s||'')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
}

/* ── Boot ───────────────────────────────────────────────────── */
if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded',init);
} else {
    init();
}

/* Auto-fullscreen saat pertama kali diklik (cocok untuk Smart TV) */
var _fsReq=false;
document.body.addEventListener('click',function(){
    if(!_fsReq&&!document.fullscreenElement){
        _fsReq=true;
        document.documentElement.requestFullscreen&&
            document.documentElement.requestFullscreen().catch(function(){});
    }
},{once:true});
</script>
</body>
</html>
<?php /* developed by zhayyn™ */ ?>
