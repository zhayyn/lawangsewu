<?php
/* developed by zhayyn™ */
$cacheFile = dirname(__DIR__, 2) . '/data/statistik-perkara-source.json';
$cacheTTL  = 1200;
$cacheOk   = false;
$payload   = null;

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    $raw = @file_get_contents($cacheFile);
    if ($raw) { $payload = json_decode($raw, true); if (isset($payload['ok']) && $payload['ok']) $cacheOk = true; }
}
if (!$cacheOk) {
    $apiScript = dirname(__DIR__, 2) . '/php/api/statistik-data.php';
    if (is_file($apiScript)) {
        $_GET['hal'] = 'perkara'; $_GET['tahun'] = (string)date('Y');
        ob_start(); include $apiScript; $raw = ob_get_clean();
        $payload = json_decode($raw, true);
        if (isset($payload['ok']) && $payload['ok']) @file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
$rows    = $payload['rows']   ?? [];
$totals  = $payload['totals'] ?? ['th4'=>0,'th3'=>0,'th2'=>0,'th1'=>0,'th0'=>0];
$tahun   = (int)date('Y');
$jsRows   = json_encode($rows,   JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
$jsTotals = json_encode($totals, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
$cachedAt = ($cacheOk && is_file($cacheFile)) ? date('H:i', filemtime($cacheFile)) : date('H:i');
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Statistik Perkara – PA Semarang</title>
<script src="/widgets/assets/chart.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden;font-family:'Segoe UI',Arial,sans-serif;background:transparent}
.wsm{height:100vh;display:flex;flex-direction:column;padding:14px 16px 12px;gap:10px;background:#fff;border-top:4px solid #1e5631;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.06)}
.wsm-stamp{font-size:10.5px;color:#9ca3af;text-align:right;flex-shrink:0}
.wsm-grid{flex:1;display:grid;grid-template-columns:1fr 1fr;gap:12px;min-height:0}
.wsm-box{background:#f9fbf9;border-radius:10px;border:1px solid #eef2e6;padding:10px;display:flex;flex-direction:column;min-height:0;overflow:hidden}
.wsm-box-title{font-size:12px;font-weight:600;color:#0f5132;text-align:center;margin-bottom:6px;flex-shrink:0}
.wsm-chart-wrap{flex:1;position:relative;min-height:0}
.wsm-chart-wrap canvas{width:100%!important;height:100%!important}
.wsm-pie-legend{flex-shrink:0;display:flex;flex-wrap:wrap;gap:3px 8px;justify-content:center;padding-top:5px;font-size:10px;color:#374151;line-height:1.4}
.wsm-pie-legend span{display:inline-flex;align-items:center;gap:3px}
.wsm-pie-legend b{display:inline-block;width:8px;height:8px;border-radius:50%;flex-shrink:0}
.wsm-btn{flex-shrink:0;display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;background:linear-gradient(135deg,#1e5631,#27ae60);color:#fff;text-decoration:none;border-radius:7px;font-weight:700;font-size:.88rem;transition:background .25s,transform .2s,box-shadow .2s}
.wsm-btn:hover{background:linear-gradient(135deg,#f39c12,#e67e22);transform:translateY(-2px);box-shadow:0 5px 14px rgba(243,156,18,.32);color:#fff}
</style>
</head>
<body>
<div class="wsm">
  <div class="wsm-stamp">Data per pukul <?=htmlspecialchars($cachedAt)?> WIB</div>
  <div class="wsm-grid">
    <div class="wsm-box">
      <div class="wsm-box-title">Perkara 5 Tahun Terakhir</div>
      <div class="wsm-chart-wrap"><canvas id="cvTren"></canvas></div>
    </div>
    <div class="wsm-box">
      <div class="wsm-box-title">Komposisi Tahun Berjalan</div>
      <div class="wsm-chart-wrap"><canvas id="cvPie"></canvas></div>
      <div class="wsm-pie-legend" id="pieLegend"></div>
    </div>
  </div>
  <a class="wsm-btn" href="https://lawangsewu.pa-semarang.go.id/statistik-perkara" target="_blank" rel="noopener">📊 Lihat Selengkapnya di Portal Lawangsewu</a>
</div>
<script>
(function(){
const ROWS=<?=$jsRows?>,TOTALS=<?=$jsTotals?>,YR=<?=$tahun?>;
const WARNA={'Cerai Gugat':'#27ae60','Cerai Talak':'#00B8D9','Asal Usul Anak':'#3498db','Dispensasi Kawin':'#9b59b6','Izin Poligami':'#e67e22','Kewarisan':'#e74c3c','Lain-Lain':'#95a5a6','Perwalian':'#1abc9c','P3HP/Penetapan Ahli Waris':'#f39c12','Pengesahan Perkawinan/Istbat Nikah':'#2ecc71','Penguasaan Anak':'#c0392b','Wali Adhol':'#d35400'};
const PAL=['#27ae60','#00B8D9','#9b59b6','#e67e22','#3498db','#e74c3c','#1abc9c','#f39c12','#c0392b'];
function warna(l){if(WARNA[l])return WARNA[l];let h=0;for(let i=0;i<l.length;i++){h=((h<<5)-h)+l.charCodeAt(i);h|=0}return PAL[Math.abs(h)%PAL.length]}
function fmt(n){return Number(n||0).toLocaleString('id-ID')}

// ── 3D Bar + Line Chart (Custom Canvas) ──────────────────────────────
(function(){
  const el=document.getElementById('cvTren');
  if(!el)return;
  const labels=[YR-4,YR-3,YR-2,YR-1,YR];
  const vals=[TOTALS.th4,TOTALS.th3,TOTALS.th2,TOTALS.th1,TOTALS.th0].map(Number);

  // Vibrant 3D color sets: [front-light, front-dark, right-face, top-face]
  const C=[
    {fl:'#42A5F5',fd:'#1565C0',rf:'#0D47A1',tf:'#90CAF9'},  // Blue
    {fl:'#66BB6A',fd:'#2E7D32',rf:'#1B5E20',tf:'#A5D6A7'},  // Green
    {fl:'#FFF176',fd:'#F9A825',rf:'#E65100',tf:'#FFEE58'},   // Yellow
    {fl:'#FFAB40',fd:'#E65100',rf:'#BF360C',tf:'#FFCC80'},   // Orange
    {fl:'#EF9A9A',fd:'#C62828',rf:'#7F0000',tf:'#EF9A9A'},   // Red
  ];

  function hexRgb(h){h=h.replace('#','');return[parseInt(h.slice(0,2),16),parseInt(h.slice(2,4),16),parseInt(h.slice(4,6),16)]}
  function lighter(h,t){const[r,g,b]=hexRgb(h);return`rgb(${Math.min(255,r+Math.round((255-r)*t))},${Math.min(255,g+Math.round((255-g)*t))},${Math.min(255,b+Math.round((255-b)*t))})` }

  let prog=0,hov=-1,hovTip={x:0,y:0},rafId=null;

  function draw(p){
    const dpr=devicePixelRatio||1;
    const wrap=el.parentElement;
    const W=wrap.clientWidth,H=wrap.clientHeight;
    el.width=W*dpr;el.height=H*dpr;
    el.style.width=W+'px';el.style.height=H+'px';
    const ctx=el.getContext('2d');ctx.scale(dpr,dpr);
    ctx.clearRect(0,0,W,H);

    const n=vals.length,maxV=Math.max(...vals,1);
    const padL=10,padR=8,padT=22,padB=28;
    const cW=W-padL-padR,cH=H-padT-padB;
    const slot=cW/n;
    const bW=slot*0.52;
    const dX=bW*0.30,dY=bW*0.15;  // 3D depth offsets
    const baseY=padT+cH;

    // Subtle grid lines
    ctx.save();
    const tks=4;
    for(let t=0;t<=tks;t++){
      const gy=padT+(cH/tks)*t;
      const gv=Math.round(maxV*(1-t/tks));
      ctx.strokeStyle='rgba(0,0,0,0.06)';ctx.lineWidth=1;
      ctx.setLineDash([3,4]);
      ctx.beginPath();ctx.moveTo(padL,gy);ctx.lineTo(W-padR,gy);ctx.stroke();
      if(t<tks){
        ctx.setLineDash([]);
        ctx.fillStyle='#9ca3af';ctx.font='8px Segoe UI';ctx.textAlign='right';
        ctx.fillText(gv>=1000?(gv/1000).toFixed(1)+'k':gv,padL-2,gy+3);
      }
    }
    ctx.setLineDash([]);ctx.restore();

    // Compute bar screen coords
    const bars=vals.map((v,i)=>{
      const bH=((v/maxV)*cH)*p;
      const cx=padL+i*slot+slot/2;
      const x=cx-bW/2,y=baseY-bH;
      const hovScale=(i===hov)?1.06:1.0;
      const bWs=bW*hovScale,bHs=bH*hovScale;
      const xs=cx-bWs/2,ys=baseY-bHs;
      return{cx,x,y,bH,v,xs,ys,bWs,bHs};
    });

    // Draw 3D bars (back-to-front)
    bars.forEach(({cx,xs,ys,bWs,bHs},i)=>{
      const c=C[i];
      const dXs=dX*(i===hov?1.06:1),dYs=dY*(i===hov?1.06:1);

      // ── Right side face ──
      ctx.beginPath();
      ctx.moveTo(xs+bWs,ys);
      ctx.lineTo(xs+bWs+dXs,ys-dYs);
      ctx.lineTo(xs+bWs+dXs,baseY-dYs);
      ctx.lineTo(xs+bWs,baseY);
      ctx.closePath();
      const sg=ctx.createLinearGradient(xs+bWs,ys,xs+bWs+dXs,ys-dYs);
      sg.addColorStop(0,c.rf);sg.addColorStop(1,lighter(c.rf,0.15));
      ctx.fillStyle=sg;ctx.fill();

      // ── Top face ──
      ctx.beginPath();
      ctx.moveTo(xs,ys);
      ctx.lineTo(xs+dXs,ys-dYs);
      ctx.lineTo(xs+bWs+dXs,ys-dYs);
      ctx.lineTo(xs+bWs,ys);
      ctx.closePath();
      const tg=ctx.createLinearGradient(xs,ys,xs+bWs+dXs,ys-dYs);
      tg.addColorStop(0,c.tf);tg.addColorStop(1,lighter(c.tf,0.35));
      ctx.fillStyle=tg;ctx.fill();
      // Top face border line
      ctx.strokeStyle='rgba(255,255,255,0.5)';ctx.lineWidth=0.8;ctx.stroke();

      // ── Front face with vertical gradient ──
      const fg=ctx.createLinearGradient(xs,ys,xs,baseY);
      fg.addColorStop(0,c.fl);fg.addColorStop(0.5,c.fl);fg.addColorStop(1,c.fd);
      ctx.beginPath();
      if(ctx.roundRect){ctx.roundRect(xs,ys,bWs,bHs,[4,4,0,0]);}
      else{ctx.rect(xs,ys,bWs,bHs);}
      ctx.fillStyle=fg;ctx.fill();

      // ── Gloss shine (left strip) ──
      const gl=ctx.createLinearGradient(xs,ys,xs,ys+bHs*0.5);
      gl.addColorStop(0,'rgba(255,255,255,0.42)');
      gl.addColorStop(1,'rgba(255,255,255,0)');
      ctx.beginPath();
      if(ctx.roundRect){ctx.roundRect(xs+bWs*0.05,ys,bWs*0.38,bHs*0.55,[4,4,0,0]);}
      else{ctx.rect(xs+bWs*0.05,ys,bWs*0.38,bHs*0.55);}
      ctx.fillStyle=gl;ctx.fill();

      // ── Front border highlight ──
      ctx.strokeStyle='rgba(255,255,255,0.28)';ctx.lineWidth=1;
      ctx.beginPath();
      if(ctx.roundRect){ctx.roundRect(xs,ys,bWs,bHs,[4,4,0,0]);}
      else{ctx.rect(xs,ys,bWs,bHs);}
      ctx.stroke();
    });

    // ── Trend line + dots + arrows (fade in after bars 60%) ──
    if(p>0.55){
      const lp=Math.min((p-0.55)/0.45,1);
      const pts=bars.map(({cx,ys})=>({px:cx,py:ys}));

      // Line (draw progressively)
      ctx.save();
      ctx.strokeStyle='rgba(255,255,255,0.92)';
      ctx.lineWidth=2.2;
      ctx.shadowColor='rgba(255,255,255,0.7)';ctx.shadowBlur=5;
      ctx.beginPath();
      ctx.moveTo(pts[0].px,pts[0].py);
      const maxSeg=(pts.length-1)*lp;
      for(let i=1;i<pts.length;i++){
        if(i>maxSeg) break;
        // Smooth bezier between pts
        const prev=pts[i-1],cur=pts[i];
        const cpx=(prev.px+cur.px)/2;
        ctx.quadraticCurveTo(cpx,prev.py,cur.px,cur.py);
      }
      // Partial last segment
      const fullSegs=Math.floor(maxSeg);
      if(fullSegs<pts.length-1){
        const frac=maxSeg-fullSegs;
        const pA=pts[fullSegs],pB=pts[fullSegs+1];
        const cpx=(pA.px+pB.px)/2;
        const tx=pA.px+(cpx-pA.px)*frac*2;
        const ty=pA.py+(pB.py-pA.py)*(frac*frac);
        ctx.quadraticCurveTo(Math.min(cpx,tx),pA.py,tx,ty);
      }
      ctx.stroke();
      ctx.shadowBlur=0;

      // Dot markers at each point
      pts.forEach((pt,i)=>{
        if(i/(pts.length-1)>lp+0.01)return;
        ctx.beginPath();ctx.arc(pt.px,pt.py,4.5,0,Math.PI*2);
        ctx.fillStyle=C[i].fl;ctx.fill();
        ctx.strokeStyle='#fff';ctx.lineWidth=2;ctx.stroke();
      });

      // Arrows between consecutive bars
      for(let i=1;i<pts.length;i++){
        if(i/(pts.length-1)>lp+0.01)break;
        const A=pts[i-1],B=pts[i];
        const isUp=B.py<A.py;
        const arrowCol=isUp?'#69F0AE':'#FF5252';
        const midX=(A.px+B.px)/2;
        const midY=(A.py+B.py)/2-(isUp?10:4);
        // Compute angle of segment
        const ang=Math.atan2(B.py-A.py,B.px-A.px);
        ctx.save();
        ctx.translate(midX,midY);
        ctx.rotate(isUp?ang-Math.PI/2:ang+Math.PI/2);
        ctx.beginPath();
        ctx.moveTo(0,-7);ctx.lineTo(5.5,4);ctx.lineTo(-5.5,4);
        ctx.closePath();
        ctx.fillStyle=arrowCol;
        ctx.shadowColor=arrowCol;ctx.shadowBlur=8;
        ctx.fill();
        ctx.restore();
      }
      ctx.restore();
    }

    // ── Value labels above each bar ──
    if(p>0.75){
      const vp=Math.min((p-0.75)/0.25,1);
      ctx.save();ctx.globalAlpha=vp;ctx.textAlign='center';
      bars.forEach(({cx,ys,v},i)=>{
        const label=v>=1000?(v/1000).toFixed(1)+'k':fmt(v);
        ctx.font=`bold 8.5px Segoe UI`;
        ctx.fillStyle=C[i].fd;
        ctx.fillText(label,cx,ys-dY-4);
      });
      ctx.restore();
    }

    // ── X-axis year labels ──
    ctx.save();ctx.textAlign='center';
    bars.forEach(({cx},i)=>{
      ctx.font=`bold 10px Segoe UI`;
      ctx.fillStyle='#374151';
      ctx.fillText(String(labels[i]),cx,baseY+16);
    });
    ctx.restore();

    // ── Hover tooltip ──
    if(hov>=0&&p>0.9){
      const{cx,ys,v}=bars[hov];
      const tip=`${labels[hov]}: ${fmt(v)} perkara`;
      ctx.font='bold 10.5px Segoe UI';
      const tw=ctx.measureText(tip).width;
      const pad=8,th=26,tW=tw+pad*2;
      let tx=cx-tW/2,ty=ys-dY-30;
      if(tx<4)tx=4;if(tx+tW>W-4)tx=W-tW-4;
      if(ty<4)ty=4;
      ctx.save();
      ctx.shadowColor='rgba(0,0,0,0.25)';ctx.shadowBlur=8;
      ctx.beginPath();
      if(ctx.roundRect){ctx.roundRect(tx,ty,tW,th,6);}
      else{ctx.rect(tx,ty,tW,th);}
      ctx.fillStyle='rgba(10,15,30,0.88)';ctx.fill();
      ctx.restore();
      ctx.fillStyle=C[hov].fl;ctx.font='bold 10.5px Segoe UI';ctx.textAlign='left';
      ctx.fillText(tip,tx+pad,ty+17);
    }
  }

  // Entrance animation
  function animate(){
    prog=Math.min(prog+0.028,1);
    draw(prog);
    if(prog<1)rafId=requestAnimationFrame(animate);
    else rafId=null;
  }

  // Hover detection
  el.addEventListener('mousemove',e=>{
    const rect=el.getBoundingClientRect();
    const mx=e.clientX-rect.left;
    const wrap=el.parentElement;
    const W=wrap.clientWidth;
    const slot=(W-18)/vals.length;
    const bW=slot*0.52;
    let found=-1;
    for(let i=0;i<vals.length;i++){
      const cx=10+i*slot+slot/2;
      if(mx>=cx-bW/2&&mx<=cx+bW/2){found=i;break;}
    }
    if(found!==hov){hov=found;if(!rafId){rafId=requestAnimationFrame(()=>{rafId=null;draw(prog)});}}
  });
  el.addEventListener('mouseleave',()=>{hov=-1;if(!rafId){rafId=requestAnimationFrame(()=>{rafId=null;draw(prog)})}});

  new ResizeObserver(()=>{draw(prog)}).observe(el.parentElement);
  animate();
})();



// ── 3D Water-Bubble Pie ───────────────────────────────────────────────
(function(){
  const canvas=document.getElementById('cvPie');
  const legendEl=document.getElementById('pieLegend');
  if(!canvas)return;

  const sorted=[...ROWS].filter(r=>Number(r.th0||0)>0).sort((a,b)=>Number(b.th0||0)-Number(a.th0||0)).slice(0,8);
  if(!sorted.length){canvas.parentElement.innerHTML='<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#9ca3af;font-size:12px">Data belum tersedia</div>';return;}

  const labels=sorted.map(r=>r.jenis||'-');
  const vals=sorted.map(r=>Number(r.th0||0));
  const colors=labels.map(warna);
  const total=vals.reduce((a,b)=>a+b,0);

  // Legend
  if(legendEl)legendEl.innerHTML=labels.map((l,i)=>`<span><b style="background:${colors[i]}"></b>${l.length>15?l.slice(0,13)+'…':l} (${fmt(vals[i])})</span>`).join('');

  // Helpers
  function hexRgb(h){h=h.replace('#','');return[parseInt(h.slice(0,2),16),parseInt(h.slice(2,4),16),parseInt(h.slice(4,6),16)]}
  function lighten(h,t){const[r,g,b]=hexRgb(h);return`rgba(${Math.min(255,r+Math.round((255-r)*t))},${Math.min(255,g+Math.round((255-g)*t))},${Math.min(255,b+Math.round((255-b)*t))},1)`}
  function darken(h,t){const[r,g,b]=hexRgb(h);return`rgba(${Math.round(r*(1-t))},${Math.round(g*(1-t))},${Math.round(b*(1-t))},1)`}
  function rrect(ctx,x,y,w,h,r){ctx.beginPath();ctx.moveTo(x+r,y);ctx.lineTo(x+w-r,y);ctx.arcTo(x+w,y,x+w,y+r,r);ctx.lineTo(x+w,y+h-r);ctx.arcTo(x+w,y+h,x+w-r,y+h,r);ctx.lineTo(x+r,y+h);ctx.arcTo(x,y+h,x,y+h-r,r);ctx.lineTo(x,y+r);ctx.arcTo(x,y,x+r,y,r);ctx.closePath()}

  let hovered=-1,hovProg=new Array(vals.length).fill(0),rafId=null,mouse={x:0,y:0};

  function buildAngles(){let s=-Math.PI/2;return vals.map(v=>{const sw=(v/total)*Math.PI*2,e=s+sw,mid=s+sw/2,res={s,e,mid};s=e;return res})}

  function draw(){
    const dpr=devicePixelRatio||1,wrap=canvas.parentElement;
    const W=wrap.clientWidth,H=wrap.clientHeight;
    canvas.width=W*dpr;canvas.height=H*dpr;
    canvas.style.width=W+'px';canvas.style.height=H+'px';
    const ctx=canvas.getContext('2d');ctx.scale(dpr,dpr);

    const cx=W/2,cy=H*0.46,r=Math.min(W*0.34,H*0.52),depth=r*0.18;
    const angles=buildAngles();

    // Shadow
    ctx.save();ctx.beginPath();ctx.ellipse(cx,cy+depth+4,r*.88,r*.17,0,0,Math.PI*2);
    ctx.fillStyle='rgba(0,0,0,.16)';ctx.filter='blur(5px)';ctx.fill();ctx.filter='none';ctx.restore();

    // Side faces
    for(let i=0;i<angles.length;i++){
      const p=hovProg[i],{s,e}=angles[i];
      const ox=Math.cos(angles[i].mid)*10*p,oy=Math.sin(angles[i].mid)*8*p-3*p;
      const vs=Math.max(s,0),ve=Math.min(e,Math.PI);if(ve<=vs)continue;
      ctx.save();ctx.translate(ox,oy);
      ctx.beginPath();ctx.moveTo(cx+r*Math.cos(vs),cy+r*Math.sin(vs));ctx.arc(cx,cy,r,vs,ve);
      ctx.lineTo(cx+r*Math.cos(ve),cy+depth+r*Math.sin(ve));ctx.arc(cx,cy+depth,r,ve,vs,true);ctx.closePath();
      ctx.fillStyle=darken(colors[i],.42);ctx.fill();
      ctx.restore();
    }

    // Top faces – water bubble effect
    for(let i=0;i<angles.length;i++){
      const p=hovProg[i],{s,e,mid}=angles[i];
      const ox=Math.cos(mid)*10*p,oy=Math.sin(mid)*8*p-3*p;
      ctx.save();ctx.translate(ox,oy);
      ctx.beginPath();ctx.moveTo(cx,cy);ctx.arc(cx,cy,r,s,e);ctx.closePath();

      // Water-bubble radial gradient: near-white → rich color → darker edge
      const hx=cx+r*.24*Math.cos(mid-.65),hy=cy+r*.24*Math.sin(mid-.65);
      const g=ctx.createRadialGradient(hx,hy,0,cx,cy,r*1.02);
      g.addColorStop(0,'rgba(255,255,255,.88)');
      g.addColorStop(.1,lighten(colors[i],.62));
      g.addColorStop(.42,colors[i]);
      g.addColorStop(.85,darken(colors[i],.18));
      g.addColorStop(1,darken(colors[i],.4));
      ctx.fillStyle=g;ctx.fill();

      ctx.strokeStyle='rgba(255,255,255,.92)';ctx.lineWidth=p>.1?2.5:1.5;ctx.stroke();
      ctx.restore();
    }

    // Bubble glass gloss: two-layer highlight
    const g1=ctx.createRadialGradient(cx-r*.3,cy-r*.4,0,cx,cy,r);
    g1.addColorStop(0,'rgba(255,255,255,.62)');g1.addColorStop(.28,'rgba(255,255,255,.18)');g1.addColorStop(.55,'rgba(255,255,255,.04)');g1.addColorStop(1,'rgba(255,255,255,0)');
    ctx.beginPath();ctx.arc(cx,cy,r,0,Math.PI*2);ctx.fillStyle=g1;ctx.fill();
    // Secondary cool shimmer at bottom-right (refractive)
    const g2=ctx.createRadialGradient(cx+r*.35,cy+r*.35,0,cx+r*.2,cy+r*.2,r*.55);
    g2.addColorStop(0,'rgba(180,230,255,.28)');g2.addColorStop(1,'rgba(180,230,255,0)');
    ctx.beginPath();ctx.arc(cx,cy,r,0,Math.PI*2);ctx.fillStyle=g2;ctx.fill();

    // Tooltip
    if(hovered>=0&&hovProg[hovered]>.5){
      const pct=((vals[hovered]/total)*100).toFixed(1);
      const l1=labels[hovered].length>24?labels[hovered].slice(0,22)+'…':labels[hovered];
      const l2=`${fmt(vals[hovered])} perkara  ·  ${pct}%`;
      ctx.font='bold 11.5px Segoe UI,sans-serif';
      const tw=Math.max(ctx.measureText(l1).width,ctx.measureText(l2).width);
      const pad=10,th=46,tW=tw+pad*2;
      let tx=mouse.x+14,ty=mouse.y-th/2;
      if(tx+tW>W-4)tx=mouse.x-tW-14;
      if(ty<4)ty=4;if(ty+th>H-4)ty=H-th-4;
      ctx.save();ctx.shadowColor='rgba(0,0,0,.25)';ctx.shadowBlur=10;
      rrect(ctx,tx,ty,tW,th,8);ctx.fillStyle='rgba(10,20,35,.9)';ctx.fill();ctx.restore();
      ctx.fillStyle=colors[hovered];rrect(ctx,tx,ty,4,th,4);ctx.fill();
      ctx.fillStyle='#f0f4f8';ctx.font='bold 11px Segoe UI,sans-serif';ctx.fillText(l1,tx+pad+2,ty+17);
      ctx.fillStyle=lighten(colors[hovered],.38);ctx.font='500 10.5px Segoe UI,sans-serif';ctx.fillText(l2,tx+pad+2,ty+33);
    }
  }

  // Smooth animation
  function tick(){
    let dirty=false;
    for(let i=0;i<hovProg.length;i++){
      const tgt=i===hovered?1:0,d=tgt-hovProg[i];
      if(Math.abs(d)>.004){hovProg[i]+=d*.14;dirty=true}else hovProg[i]=tgt;
    }
    draw();
    if(dirty||hovered>=0)rafId=requestAnimationFrame(tick);else rafId=null;
  }
  function kick(){if(!rafId)rafId=requestAnimationFrame(tick)}

  function hitTest(mx,my){
    const wrap=canvas.parentElement,W=wrap.clientWidth,H=wrap.clientHeight;
    const cx=W/2,cy=H*.46,r=Math.min(W*.34,H*.52);
    const dx=mx-cx,dy=my-cy;if(Math.sqrt(dx*dx+dy*dy)>r)return -1;
    let ang=Math.atan2(dy,dx);
    const angles=buildAngles();
    for(let i=0;i<angles.length;i++){
      let{s,e}=angles[i];
      while(s>Math.PI)s-=Math.PI*2;while(s<-Math.PI)s+=Math.PI*2;
      while(e>Math.PI)e-=Math.PI*2;while(e<-Math.PI)e+=Math.PI*2;
      if(s<=e){if(ang>=s&&ang<=e)return i}else{if(ang>=s||ang<=e)return i}
    }
    return -1;
  }

  canvas.addEventListener('mousemove',e=>{
    const rect=canvas.getBoundingClientRect();mouse.x=e.clientX-rect.left;mouse.y=e.clientY-rect.top;
    const h=hitTest(mouse.x,mouse.y);if(h!==hovered){hovered=h;kick()}else if(hovered>=0)kick();
  });
  canvas.addEventListener('mouseleave',()=>{if(hovered!==-1){hovered=-1;kick()}});

  draw();
  new ResizeObserver(()=>{draw()}).observe(canvas.parentElement);
})();
})();
/* developed by zhayyn™ */
</script>
</body>
</html>
