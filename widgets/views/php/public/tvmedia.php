<?php
/* tvmedia.php — TV Media Digital Signage — developed by zhayyn™
 * Versi SINEMATIK: Navigasi 2-Sumbu (Kategori Vertikal + Slide Horizontal)
 * Tanpa library eksternal — murni CSS3 + Vanilla JS ES5-compat untuk Smart TV
 * Layout 16:9 — Optimal untuk monitor 1080p/4K
 */
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1280, initial-scale=1.0">
    <title>TV Media — Pengadilan Agama Semarang</title>
    <meta name="robots" content="noindex, nofollow">
    <!-- Google Fonts: preconnect dulu agar cepat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
    /* ══════════════════════════════════════════════════════════════
       DESIGN TOKENS — Dark / Light Mode
    ══════════════════════════════════════════════════════════════ */
    :root {
        --font-main: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
        --font-mono: 'JetBrains Mono', 'Consolas', monospace;

        /* Animasi global */
        --ease-cinematic: cubic-bezier(0.77, 0, 0.175, 1);
        --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
        --dur-slide: 650ms;
        --dur-fast: 220ms;
        --dur-glow: 3s;
    }

    /* ── DARK THEME (default) ── */
    [data-theme="dark"] {
        --bg-base:        #080c14;
        --bg-panel:       #0d1220;
        --bg-glass:       rgba(13,18,32,0.88);
        --bg-glass-light: rgba(255,255,255,0.04);
        --bg-card:        rgba(255,255,255,0.05);

        --text-primary:   #f0f4ff;
        --text-secondary: #8b98b8;
        --text-muted:     rgba(139,152,184,0.55);

        --accent-gold:    #c9a84c;
        --accent-gold-glow: rgba(201,168,76,0.25);
        --accent-blue:    #4f8ef7;
        --accent-blue-glow: rgba(79,142,247,0.2);
        --accent-teal:    #00d4aa;
        --accent-teal-glow: rgba(0,212,170,0.18);

        --border:         rgba(255,255,255,0.08);
        --border-accent:  rgba(201,168,76,0.4);

        --sidebar-bg:     rgba(8,12,20,0.95);
        --sidebar-item:   rgba(255,255,255,0.04);
        --sidebar-active: rgba(201,168,76,0.12);
        --sidebar-w:      220px;

        --bar-bg:         rgba(8,12,20,0.96);
        --bar-h:          56px;

        --dot-inactive:   rgba(255,255,255,0.2);
        --dot-active:     var(--accent-gold);

        --progress-bg:    rgba(255,255,255,0.06);
        --progress-fill:  linear-gradient(90deg, var(--accent-gold), var(--accent-blue));

        --shadow-glow:    0 0 40px rgba(201,168,76,0.08);
        --shadow-card:    0 8px 32px rgba(0,0,0,0.4);
    }

    /* ── LIGHT THEME ── */
    [data-theme="light"] {
        --bg-base:        #f0f4fc;
        --bg-panel:       #ffffff;
        --bg-glass:       rgba(255,255,255,0.92);
        --bg-glass-light: rgba(0,0,0,0.03);
        --bg-card:        rgba(0,0,0,0.04);

        --text-primary:   #0d1220;
        --text-secondary: #4a5568;
        --text-muted:     rgba(74,85,104,0.6);

        --accent-gold:    #a07820;
        --accent-gold-glow: rgba(160,120,32,0.15);
        --accent-blue:    #2563eb;
        --accent-blue-glow: rgba(37,99,235,0.12);
        --accent-teal:    #0d9488;
        --accent-teal-glow: rgba(13,148,136,0.12);

        --border:         rgba(0,0,0,0.08);
        --border-accent:  rgba(160,120,32,0.35);

        --sidebar-bg:     rgba(240,244,252,0.97);
        --sidebar-item:   rgba(0,0,0,0.04);
        --sidebar-active: rgba(160,120,32,0.1);
        --sidebar-w:      220px;

        --bar-bg:         rgba(255,255,255,0.96);
        --bar-h:          56px;

        --dot-inactive:   rgba(0,0,0,0.2);
        --dot-active:     var(--accent-gold);

        --progress-bg:    rgba(0,0,0,0.06);
        --progress-fill:  linear-gradient(90deg, var(--accent-gold), var(--accent-blue));

        --shadow-glow:    0 0 40px rgba(160,120,32,0.06);
        --shadow-card:    0 8px 32px rgba(0,0,0,0.1);
    }

    /* ══════════════════════════════════════════════════════════════
       RESET & BASE
    ══════════════════════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
        width: 100%; height: 100%;
        overflow: hidden;
        background: var(--bg-base);
        font-family: var(--font-main);
        color: var(--text-primary);
        transition: background var(--dur-fast) var(--ease-smooth),
                    color var(--dur-fast) var(--ease-smooth);
        -webkit-font-smoothing: antialiased;
    }

    /* ══════════════════════════════════════════════════════════════
       AMBIENT BACKGROUND — Particle glow layer
    ══════════════════════════════════════════════════════════════ */
    #tv-ambient {
        position: fixed; inset: 0;
        pointer-events: none; z-index: 0;
        overflow: hidden;
    }
    .amb-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0;
        animation: orbFloat var(--dur-glow) ease-in-out infinite alternate;
    }
    .amb-orb-1 {
        width: 500px; height: 500px;
        top: -100px; left: -100px;
        background: radial-gradient(circle, rgba(79,142,247,0.12) 0%, transparent 70%);
        animation-delay: 0s; opacity: 0.8;
    }
    .amb-orb-2 {
        width: 400px; height: 400px;
        bottom: -80px; right: -80px;
        background: radial-gradient(circle, rgba(201,168,76,0.1) 0%, transparent 70%);
        animation-delay: 1.5s; opacity: 0.8;
    }
    .amb-orb-3 {
        width: 300px; height: 300px;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        background: radial-gradient(circle, rgba(0,212,170,0.06) 0%, transparent 70%);
        animation-delay: 0.75s; opacity: 0.6;
    }
    @keyframes orbFloat {
        0%   { transform: scale(1) translate(0, 0); }
        100% { transform: scale(1.15) translate(20px, -15px); }
    }
    /* Orb 3 override */
    .amb-orb-3 { animation-name: orbFloat3; }
    @keyframes orbFloat3 {
        0%   { transform: translate(-50%, -50%) scale(1); }
        100% { transform: translate(-50%, -50%) scale(1.2); }
    }

    /* ══════════════════════════════════════════════════════════════
       LAYOUT — Sidebar + Stage + Bottom Bar
    ══════════════════════════════════════════════════════════════ */

    /* ── Sidebar Kiri (Navigasi Kategori Vertikal) ── */
    #sidebar-hotzone {
        position: fixed; left: 0; top: 0; bottom: var(--bar-h); width: 40px;
        z-index: 99;
    }
    #tv-sidebar {
        position: fixed;
        top: 0; left: 0;
        width: 380px; /* Lebar timeline cukup lebar */
        bottom: var(--bar-h);
        background: linear-gradient(90deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 60%, transparent 100%);
        border: none;
        display: flex; flex-direction: column;
        z-index: 100;
        opacity: 0; pointer-events: none;
        transform: translateX(-40px);
        transition: all 0.5s var(--ease-cinematic);
    }
    #sidebar-hotzone:hover ~ #tv-sidebar,
    #tv-sidebar:hover,
    #tv-sidebar:focus-within,
    #tv-sidebar:active {
        opacity: 1; pointer-events: auto;
        transform: translateX(0);
    }

    /* Sidebar header / branding */
    #sidebar-header {
        padding: 20px 18px 16px;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }
    .sb-logo {
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 10px;
    }
    .sb-logo-icon {
        width: 36px; height: 36px; flex-shrink: 0;
        background: linear-gradient(135deg, var(--accent-gold), var(--accent-blue));
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 14px var(--accent-gold-glow);
    }
    .sb-brand-name {
        font-size: 11px; font-weight: 700;
        color: var(--text-primary);
        line-height: 1.3; letter-spacing: 0.3px;
    }
    .sb-brand-sub {
        font-size: 9px; color: var(--text-muted);
        margin-top: 2px; letter-spacing: 0.2px;
    }
    /* Date-time di sidebar header */
    #sb-clock {
        font-family: var(--font-mono);
        font-size: 18px; font-weight: 700;
        color: var(--accent-gold);
        letter-spacing: 1px; line-height: 1;
    }
    #sb-date {
        font-size: 10px; color: var(--text-secondary);
        margin-top: 3px;
    }

    /* Kategori Navigation - Timeline Style */
    #sidebar-nav {
        flex: 1; overflow-y: auto; padding: 20px 0 40px;
        scrollbar-width: none;
        position: relative;
    }
    #sidebar-nav::-webkit-scrollbar { display: none; }

    /* Garis vertikal timeline */
    #sidebar-nav::before {
        content: ''; position: absolute;
        top: 20px; bottom: 40px; left: 80px;
        width: 1px; background: rgba(255,255,255,0.2);
    }

    .nav-cat-btn {
        display: flex; align-items: center;
        width: 100%; background: transparent; border: none;
        padding: 16px 0; cursor: pointer; position: relative;
        color: rgba(255,255,255,0.5);
        font-family: var(--font-main); text-align: left;
        transition: all 0.3s ease;
    }
    .nav-cat-btn * { position: relative; z-index: 2; }
    .nav-cat-btn:hover { color: rgba(255,255,255,0.85); }

    .tl-num {
        width: 80px; text-align: center; font-family: var(--font-mono);
        font-size: 13px; font-weight: 700; flex-shrink: 0;
        transition: all 0.3s ease;
    }
    .tl-dot {
        width: 7px; height: 7px; background: rgba(255,255,255,0.4);
        border-radius: 50%; margin-left: -3px; flex-shrink: 0;
        transition: all 0.3s ease;
    }
    .tl-content { padding-left: 20px; flex: 1; }
    .tl-title { font-size: 13px; font-weight: 500; transition: all 0.3s ease; }

    /* Active state (Chronicle red splash) */
    .nav-cat-btn.active { color: #fff; }
    .nav-cat-btn.active .tl-num { font-size: 15px; color: #fff; }
    .nav-cat-btn.active .tl-dot { background: #fff; box-shadow: 0 0 10px #fff; transform: scale(1.3); }
    .nav-cat-btn.active .tl-title { font-size: 18px; font-weight: 800; color: #fff; }

    .nav-cat-btn.active::after {
        content: ''; position: absolute;
        left: 80px; right: -80px; top: 4px; bottom: 4px;
        background: linear-gradient(90deg, rgba(220,38,38,0.8) 0%, rgba(220,38,38,0.2) 60%, transparent 100%);
        transform: skewX(-15deg) scaleX(0);
        transform-origin: left;
        z-index: 0; opacity: 0;
        animation: splashIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes splashIn {
        100% { transform: skewX(-15deg) scaleX(1); opacity: 1; }
    }
    .nav-cat-icon {
        font-size: 16px; flex-shrink: 0;
        width: 28px; height: 28px;
        display: flex; align-items: center; justify-content: center;
        background: var(--bg-glass-light);
        border-radius: 7px;
        transition: transform var(--dur-fast) var(--ease-smooth);
    }
    .nav-cat-btn.active .nav-cat-icon { transform: scale(1.1); }
    .nav-cat-text { flex: 1; min-width: 0; }
    .nav-cat-title {
        font-size: 12px; font-weight: 600;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        display: block;
    }
    .nav-cat-count {
        font-size: 9px; color: var(--text-muted);
        display: block; margin-top: 1px;
    }
    .nav-cat-badge {
        font-size: 9px; font-weight: 700;
        background: var(--accent-gold);
        color: #080c14;
        border-radius: 10px;
        padding: 2px 6px;
        flex-shrink: 0;
        display: none;
    }
    .nav-cat-btn.active .nav-cat-badge { display: block; }

    /* Slide dots vertikal di sidebar (sub-nav) */
    .nav-sub-dots {
        display: none; flex-direction: column; gap: 4px;
        padding: 6px 12px 6px 50px;
        margin-bottom: 2px;
    }
    .nav-cat-btn.active + .nav-sub-dots { display: flex; }
    .sub-dot {
        display: flex; align-items: center; gap: 8px;
        cursor: pointer; padding: 4px 0;
        color: var(--text-muted);
        font-size: 10px; font-weight: 500;
        transition: color var(--dur-fast) var(--ease-smooth);
    }
    .sub-dot::before {
        content: '';
        width: 6px; height: 6px; flex-shrink: 0;
        border-radius: 50%;
        background: var(--dot-inactive);
        transition: all var(--dur-fast) var(--ease-smooth);
    }
    .sub-dot.active { color: var(--text-primary); }
    .sub-dot.active::before {
        background: var(--dot-active);
        box-shadow: 0 0 6px var(--accent-gold-glow);
        transform: scale(1.3);
    }

    /* ── Stage Area (Full Screen) ── */
    #tv-stage {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: var(--bar-h);
        background: var(--bg-base);
        overflow: hidden;
        z-index: 10;
    }

    /* ── Category Pane Container (horizontal slider per kategori) ── */
    .cat-pane {
        position: absolute; inset: 0;
        display: flex;
        will-change: transform;
        transition: transform var(--dur-slide) var(--ease-cinematic);
    }
    .cat-pane.hidden { display: none; }
    .cat-pane.entering-right { animation: paneEnterRight var(--dur-slide) var(--ease-cinematic) forwards; }
    .cat-pane.entering-left  { animation: paneEnterLeft  var(--dur-slide) var(--ease-cinematic) forwards; }
    .cat-pane.leaving-right  { animation: paneLeavRight  var(--dur-slide) var(--ease-cinematic) forwards; }
    .cat-pane.leaving-left   { animation: paneLeaveLeft  var(--dur-slide) var(--ease-cinematic) forwards; }

    @keyframes paneEnterRight {
        from { opacity: 0; transform: translateX(60px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes paneEnterLeft {
        from { opacity: 0; transform: translateX(-60px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes paneLeavRight {
        from { opacity: 1; transform: translateX(0); }
        to   { opacity: 0; transform: translateX(-60px); }
    }
    @keyframes paneLeaveLeft {
        from { opacity: 1; transform: translateX(0); }
        to   { opacity: 0; transform: translateX(60px); }
    }

    /* ── Slide (single view dalam pane) ── */
    .tv-slide {
        position: absolute; inset: 0;
        opacity: 0;
        pointer-events: none;
        transition: opacity var(--dur-slide) var(--ease-smooth);
    }
    .tv-slide.active {
        opacity: 1;
        pointer-events: all;
        z-index: 2;
    }
    .tv-slide.leaving {
        opacity: 0; z-index: 1;
    }
    .tv-slide iframe {
        width: 100%; height: 100%;
        border: none; display: block;
    }

    /* ── Slide Transition Direction Animations ── */
    .tv-slide.slide-in-right {
        animation: slideInRight var(--dur-slide) var(--ease-cinematic) forwards;
    }
    .tv-slide.slide-in-left {
        animation: slideInLeft var(--dur-slide) var(--ease-cinematic) forwards;
    }
    .tv-slide.slide-out-right {
        animation: slideOutRight var(--dur-slide) var(--ease-cinematic) forwards;
    }
    .tv-slide.slide-out-left {
        animation: slideOutLeft var(--dur-slide) var(--ease-cinematic) forwards;
    }
    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(80px) scale(0.97); }
        to   { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-80px) scale(0.97); }
        to   { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes slideOutRight {
        from { opacity: 1; transform: translateX(0) scale(1); }
        to   { opacity: 0; transform: translateX(-80px) scale(0.97); }
    }
    @keyframes slideOutLeft {
        from { opacity: 1; transform: translateX(0) scale(1); }
        to   { opacity: 0; transform: translateX(80px) scale(0.97); }
    }

    /* ── Fallback ── */
    .slide-fallback {
        display: none;
        flex-direction: column;
        align-items: center; justify-content: center;
        height: 100%; gap: 18px;
        color: var(--text-secondary); text-align: center; padding: 60px;
        background: var(--bg-base);
    }
    .slide-fallback svg { width: 64px; height: 64px; opacity: 0.3; }
    .slide-fallback p { font-size: 14px; max-width: 480px; line-height: 1.7; }

    /* ══════════════════════════════════════════════════════════════
       SLIDE LABEL — Cinematic badge
    ══════════════════════════════════════════════════════════════ */
    .slide-label {
        position: absolute;
        top: 16px; left: 16px;
        background: var(--bg-glass);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 5px 14px;
        font-size: 10px; font-weight: 600;
        color: var(--text-secondary);
        letter-spacing: 0.8px;
        z-index: 10; pointer-events: none;
        text-transform: uppercase;
    }

    /* ══════════════════════════════════════════════════════════════
       PROGRESS BAR — Slim cinematic line
    ══════════════════════════════════════════════════════════════ */
    #tv-progress {
        position: fixed;
        bottom: var(--bar-h); left: var(--sidebar-w); right: 0;
        height: 2px;
        background: var(--progress-bg);
        z-index: 90;
    }
    #tv-progress-fill {
        height: 100%; width: 0%;
        background: var(--progress-fill);
        box-shadow: 0 0 8px var(--accent-gold-glow);
        transition: width linear;
    }

    /* ══════════════════════════════════════════════════════════════
       BOTTOM BAR — Cinematic HUD
    ══════════════════════════════════════════════════════════════ */
    #tv-bar {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        height: var(--bar-h);
        background: var(--bar-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-top: 1px solid var(--border);
        display: flex; align-items: center;
        padding: 0 18px; gap: 14px;
        z-index: 100;
        transition: background var(--dur-fast) var(--ease-smooth);
    }
    /* Subtle top glow line on bar */
    #tv-bar::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(
            90deg,
            transparent 0%,
            var(--accent-gold) 30%,
            var(--accent-blue) 70%,
            transparent 100%
        );
        opacity: 0.4;
    }

    /* Running text ticker */
    #bar-ticker-wrap {
        flex: 1; overflow: hidden; position: relative;
        mask-image: linear-gradient(90deg, transparent 0%, black 5%, black 95%, transparent 100%);
        -webkit-mask-image: linear-gradient(90deg, transparent 0%, black 5%, black 95%, transparent 100%);
    }
    #bar-ticker {
        display: inline-block;
        white-space: nowrap;
        font-size: 11px; font-weight: 500;
        color: var(--text-secondary);
        animation: tickerScroll 30s linear infinite;
    }
    @keyframes tickerScroll {
        0%   { transform: translateX(100%); }
        100% { transform: translateX(-100%); }
    }

    /* Slide indicator dots (horizontal) */
    #bar-indicators {
        display: flex; gap: 5px; align-items: center;
        flex-shrink: 0; max-width: 200px; flex-wrap: wrap;
    }
    .bar-dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: var(--dot-inactive);
        cursor: pointer;
        transition: all var(--dur-fast) var(--ease-smooth);
        flex-shrink: 0;
    }
    .bar-dot.on {
        width: 18px; border-radius: 3px;
        background: var(--dot-active);
        box-shadow: 0 0 6px var(--accent-gold-glow);
    }
    .bar-dot:hover:not(.on) { background: var(--text-secondary); }

    /* Category label in bar */
    #bar-cat-label {
        font-size: 10px; font-weight: 700;
        color: var(--accent-gold);
        text-transform: uppercase; letter-spacing: 1px;
        flex-shrink: 0;
        white-space: nowrap;
    }

    #bar-sep { width: 1px; height: 24px; background: var(--border); flex-shrink: 0; }

    /* Theme toggle button */
    #btn-theme {
        flex-shrink: 0;
        width: 34px; height: 34px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 15px;
        transition: all var(--dur-fast) var(--ease-smooth);
        color: var(--text-secondary);
    }
    #btn-theme:hover {
        background: var(--accent-gold-glow);
        border-color: var(--border-accent);
        color: var(--accent-gold);
        transform: rotate(15deg);
    }

    /* Admin button */
    #btn-admin {
        display: none; flex-shrink: 0;
        background: var(--bg-card);
        border: 1px solid var(--border);
        color: var(--text-secondary);
        border-radius: 8px;
        padding: 6px 12px; font-size: 10px; font-weight: 700;
        cursor: pointer; letter-spacing: 0.5px;
        font-family: var(--font-main);
        transition: all var(--dur-fast) var(--ease-smooth);
    }
    #btn-admin:hover {
        background: var(--accent-gold-glow);
        border-color: var(--border-accent);
        color: var(--accent-gold);
    }

    /* Keyboard hint */
    #bar-hint {
        font-size: 9px; color: var(--text-muted);
        flex-shrink: 0;
        pointer-events: none; letter-spacing: 0.3px;
    }

    /* ══════════════════════════════════════════════════════════════
       ADMIN PANEL — Glassmorphic Overlay
    ══════════════════════════════════════════════════════════════ */
    #adm {
        display: none;
        position: fixed; inset: 0 0 var(--bar-h) 0;
        background: var(--bg-glass);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-right: 1px solid var(--border);
        z-index: 200; overflow-y: auto;
        padding: 32px 28px;
        animation: admSlideIn var(--dur-fast) var(--ease-smooth);
    }
    #adm.open { display: block; }
    @keyframes admSlideIn {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .adm-header {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 6px;
    }
    .adm-h {
        font-size: 22px; font-weight: 800;
        color: var(--text-primary); flex: 1;
    }
    .adm-h span { color: var(--accent-gold); }
    .adm-s {
        font-size: 11px; color: var(--text-muted);
        margin-bottom: 24px; max-width: 600px; line-height: 1.6;
    }
    .adm-x {
        position: fixed; top: 18px; right: 22px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        color: var(--text-secondary); border-radius: 50%;
        width: 36px; height: 36px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 18px; z-index: 210;
        transition: all var(--dur-fast) var(--ease-smooth);
    }
    .adm-x:hover { background: rgba(239,68,68,.15); color: #fca5a5; border-color: rgba(239,68,68,.3); }

    /* Section headers */
    .adm-section-title {
        font-size: 10px; font-weight: 700;
        color: var(--text-muted);
        letter-spacing: 1.5px; text-transform: uppercase;
        margin: 20px 0 10px;
        display: flex; align-items: center; gap: 8px;
    }
    .adm-section-title::after {
        content: ''; flex: 1; height: 1px;
        background: var(--border);
    }

    .adm-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px; padding: 14px 16px;
        margin-bottom: 8px;
        display: flex; align-items: center; gap: 12px;
        transition: border-color var(--dur-fast) var(--ease-smooth);
    }
    .adm-card:hover { border-color: var(--border-accent); }
    .adm-body { flex: 1; min-width: 0; }
    .adm-lbl { font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
    .adm-src { font-size: 10px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .adm-badge {
        padding: 2px 10px; border-radius: 20px;
        font-size: 9px; font-weight: 700; letter-spacing: 0.6px; flex-shrink: 0;
    }
    .adm-badge.iframe  { background: rgba(79,142,247,.15); color: #93c5fd; border: 1px solid rgba(79,142,247,.25); }
    .adm-badge.widget  { background: rgba(0,212,170,.12); color: #6ee7d4;  border: 1px solid rgba(0,212,170,.2); }
    .adm-badge.image   { background: rgba(201,168,76,.12); color: #f0cd7a; border: 1px solid rgba(201,168,76,.2); }

    .adm-dur { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
    .adm-dur input {
        width: 56px;
        background: var(--bg-card); border: 1px solid var(--border);
        border-radius: 7px; color: var(--text-primary);
        font-size: 12px; padding: 4px 6px; text-align: center;
        font-family: var(--font-mono);
    }
    .adm-dur input:focus { outline: none; border-color: var(--border-accent); }
    .adm-dur span { font-size: 10px; color: var(--text-muted); }

    .adm-del {
        background: rgba(239,68,68,.1);
        border: 1px solid rgba(239,68,68,.2);
        color: #fca5a5; border-radius: 7px;
        padding: 6px 10px; cursor: pointer; font-size: 12px; flex-shrink: 0;
        transition: all var(--dur-fast) var(--ease-smooth);
    }
    .adm-del:hover { background: rgba(239,68,68,.24); }

    /* Add slide form */
    .adm-add {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 14px; padding: 20px;
        margin-top: 16px;
    }
    .adm-add-h {
        font-size: 13px; font-weight: 700;
        color: var(--accent-teal); margin-bottom: 14px;
        display: flex; align-items: center; gap: 6px;
    }
    .adm-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
    .adm-f { flex: 1; min-width: 130px; }
    .adm-f label {
        display: block; font-size: 10px; font-weight: 600;
        color: var(--text-muted); margin-bottom: 5px;
        text-transform: uppercase; letter-spacing: 0.8px;
    }
    .adm-f input, .adm-f select {
        width: 100%;
        background: var(--bg-base); border: 1px solid var(--border);
        border-radius: 8px; color: var(--text-primary);
        font-size: 12px; padding: 8px 10px;
        font-family: var(--font-main);
        transition: border-color var(--dur-fast) var(--ease-smooth);
    }
    .adm-f input:focus, .adm-f select:focus {
        outline: none; border-color: var(--accent-teal);
    }
    .adm-f select option { background: var(--bg-panel); }
    .adm-btns { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
    .btn-ok {
        background: linear-gradient(135deg, var(--accent-gold), #e8b84b);
        color: #080c14; border: none; border-radius: 8px;
        padding: 9px 18px; font-weight: 800; font-size: 12px; cursor: pointer;
        font-family: var(--font-main); letter-spacing: 0.3px;
        transition: opacity var(--dur-fast) var(--ease-smooth), transform var(--dur-fast) var(--ease-smooth);
    }
    .btn-ok:hover { opacity: 0.88; transform: translateY(-1px); }
    .btn-sec {
        background: var(--bg-card); border: 1px solid var(--border);
        color: var(--text-secondary); border-radius: 8px;
        padding: 9px 18px; font-weight: 600; font-size: 12px; cursor: pointer;
        font-family: var(--font-main);
        transition: all var(--dur-fast) var(--ease-smooth);
    }
    .btn-sec:hover { border-color: var(--border-accent); color: var(--text-primary); }
    .btn-del {
        background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.2);
        color: #fca5a5; border-radius: 8px;
        padding: 9px 18px; font-weight: 600; font-size: 12px; cursor: pointer;
        font-family: var(--font-main);
        transition: all var(--dur-fast) var(--ease-smooth);
    }
    .btn-del:hover { background: rgba(239,68,68,.22); }

    /* Category group in admin */
    .adm-cat-section {
        background: var(--bg-glass-light);
        border: 1px solid var(--border);
        border-radius: 12px; padding: 14px;
        margin-bottom: 12px;
    }
    .adm-cat-title {
        font-size: 13px; font-weight: 800;
        color: var(--accent-gold);
        margin-bottom: 10px;
        display: flex; align-items: center; gap: 8px;
    }

    /* ══════════════════════════════════════════════════════════════
       UPLOAD ZONE — Drag & Drop
    ══════════════════════════════════════════════════════════════ */
    .upload-tabs {
        display: flex; gap: 0;
        border-bottom: 1px solid var(--border);
        margin-bottom: 14px;
    }
    .upload-tab {
        padding: 8px 16px;
        font-size: 11px; font-weight: 700;
        color: var(--text-muted);
        border: none; background: none;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all var(--dur-fast) var(--ease-smooth);
        font-family: var(--font-main);
        letter-spacing: 0.3px;
    }
    .upload-tab.active {
        color: var(--accent-gold);
        border-bottom-color: var(--accent-gold);
    }
    .upload-tab-pane { display: none; }
    .upload-tab-pane.active { display: block; }

    /* Drop zone */
    #upload-dropzone {
        border: 2px dashed var(--border);
        border-radius: 12px;
        padding: 28px 20px;
        text-align: center;
        cursor: pointer;
        transition: all var(--dur-fast) var(--ease-smooth);
        position: relative;
        background: var(--bg-glass-light);
    }
    #upload-dropzone:hover,
    #upload-dropzone.dragover {
        border-color: var(--accent-gold);
        background: var(--accent-gold-glow);
    }
    #upload-dropzone input[type=file] {
        position: absolute; inset: 0;
        opacity: 0; cursor: pointer;
        width: 100%; height: 100%;
    }
    .drop-icon { font-size: 32px; margin-bottom: 8px; }
    .drop-label {
        font-size: 13px; font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }
    .drop-hint {
        font-size: 10px; color: var(--text-muted);
    }

    /* Preview after pick */
    #upload-preview {
        display: none;
        margin-top: 12px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 12px;
        gap: 12px; align-items: center;
    }
    #upload-preview.show { display: flex; }
    #upload-preview-thumb {
        width: 72px; height: 72px;
        border-radius: 8px;
        object-fit: cover;
        flex-shrink: 0;
        background: var(--bg-base);
    }
    .upload-preview-info { flex: 1; min-width: 0; }
    .upload-preview-name {
        font-size: 12px; font-weight: 700;
        color: var(--text-primary);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .upload-preview-size {
        font-size: 10px; color: var(--text-muted); margin-top: 2px;
    }
    #upload-progress-wrap {
        margin-top: 10px;
        display: none;
    }
    #upload-progress-wrap.show { display: block; }
    .upload-progress-bar {
        height: 4px; border-radius: 2px;
        background: var(--bg-card);
        overflow: hidden;
    }
    .upload-progress-fill {
        height: 100%; width: 0%;
        background: linear-gradient(90deg, var(--accent-gold), var(--accent-blue));
        transition: width 200ms linear;
        border-radius: 2px;
    }
    .upload-progress-label {
        font-size: 10px; color: var(--text-muted); margin-top: 4px;
        text-align: right;
    }
    #btn-upload-file {
        background: linear-gradient(135deg, var(--accent-teal), #00a882);
        color: #fff; border: none; border-radius: 8px;
        padding: 8px 16px; font-weight: 700; font-size: 12px;
        cursor: pointer; font-family: var(--font-main);
        white-space: nowrap; flex-shrink: 0;
        transition: opacity var(--dur-fast) var(--ease-smooth);
    }
    #btn-upload-file:hover { opacity: 0.85; }
    #btn-upload-file:disabled { opacity: 0.4; cursor: not-allowed; }

    /* Media library */
    #media-library-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 8px;
        max-height: 260px;
        overflow-y: auto;
        padding-right: 4px;
        scrollbar-width: thin;
    }
    .media-thumb {
        position: relative; border-radius: 8px; overflow: hidden;
        border: 2px solid transparent;
        cursor: pointer;
        transition: border-color var(--dur-fast) var(--ease-smooth);
        background: var(--bg-card);
        aspect-ratio: 16/9;
    }
    .media-thumb:hover { border-color: var(--accent-gold); }
    .media-thumb.selected { border-color: var(--accent-gold); }
    .media-thumb img,
    .media-thumb video {
        width: 100%; height: 100%; object-fit: cover; display: block;
    }
    .media-thumb-label {
        position: absolute; bottom: 0; left: 0; right: 0;
        background: rgba(0,0,0,0.65);
        font-size: 8px; color: #fff;
        padding: 3px 5px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .media-thumb-del {
        position: absolute; top: 3px; right: 3px;
        background: rgba(239,68,68,0.85);
        color: #fff; border: none; border-radius: 4px;
        font-size: 10px; padding: 2px 5px;
        cursor: pointer; opacity: 0;
        transition: opacity var(--dur-fast) var(--ease-smooth);
        font-family: var(--font-main);
    }
    .media-thumb:hover .media-thumb-del { opacity: 1; }
    #media-lib-empty {
        grid-column: 1/-1;
        text-align: center; color: var(--text-muted);
        font-size: 11px; padding: 24px 0;
    }

    /* ══════════════════════════════════════════════════════════════
       CATEGORY TRANSITION OVERLAY
    ══════════════════════════════════════════════════════════════ */
    #cat-overlay {
        position: fixed;
        top: 0; left: var(--sidebar-w); right: 0; bottom: var(--bar-h);
        background: var(--bg-base);
        z-index: 50;
        opacity: 0; pointer-events: none;
        transition: opacity 300ms var(--ease-smooth);
    }
    #cat-overlay.flash {
        opacity: 0.6; pointer-events: all;
    }

    /* ══════════════════════════════════════════════════════════════
       SLIDE COUNTER CHIP
    ══════════════════════════════════════════════════════════════ */
    #slide-counter {
        position: fixed;
        top: 14px; right: 14px;
        background: var(--bg-glass);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 5px 13px;
        font-family: var(--font-mono);
        font-size: 11px; font-weight: 700;
        color: var(--text-muted);
        z-index: 70; pointer-events: none;
        letter-spacing: 1px;
        transition: color var(--dur-fast) var(--ease-smooth);
    }

    /* ══════════════════════════════════════════════════════════════
       TOAST NOTIFICATION
    ══════════════════════════════════════════════════════════════ */
    #tv-toast {
        position: fixed;
        bottom: calc(var(--bar-h) + 16px);
        right: 16px;
        background: var(--bg-glass);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--border-accent);
        border-radius: 10px;
        padding: 10px 16px;
        font-size: 12px; font-weight: 600;
        color: var(--accent-gold);
        z-index: 300;
        opacity: 0; pointer-events: none;
        transform: translateY(8px);
        transition: all 300ms var(--ease-smooth);
    }
    #tv-toast.show {
        opacity: 1; pointer-events: none;
        transform: translateY(0);
    }

    /* ══════════════════════════════════════════════════════════════
       RESPONSIVE — Ajustment minor
    ══════════════════════════════════════════════════════════════ */
    @media (max-width: 900px) {
        :root { --sidebar-w: 180px; }
    }
    </style>
</head>
<body>

<!-- Ambient background orbs -->
<div id="tv-ambient" aria-hidden="true">
    <div class="amb-orb amb-orb-1"></div>
    <div class="amb-orb amb-orb-2"></div>
    <div class="amb-orb amb-orb-3"></div>
</div>

<!-- ── Sidebar Hover Hotzone ── -->
<div id="sidebar-hotzone" aria-hidden="true"></div>

<!-- ── Sidebar Navigasi Kategori (Timeline) ── -->
<nav id="tv-sidebar" role="navigation" aria-label="Navigasi Kategori">
    <div id="sidebar-header">
        <div class="sb-logo">
            <div class="sb-logo-icon">⚖️</div>
            <div>
                <div class="sb-brand-name">Pengadilan Agama<br>Semarang</div>
            </div>
        </div>
        <div id="sb-clock">--:--:--</div>
        <div id="sb-date">...</div>
    </div>
    <div id="sidebar-nav">
        <div id="nav-cats"><!-- diisi JS --></div>
    </div>
</nav>

<!-- ── Stage ── -->
<main id="tv-stage" role="main" aria-live="polite">
    <!-- Category panes diisi JS -->
</main>

<!-- ── Slide counter chip ── -->
<div id="slide-counter" aria-label="Posisi slide">1 / 1</div>

<!-- Category flash overlay -->
<div id="cat-overlay" aria-hidden="true"></div>

<!-- ── Progress bar ── -->
<div id="tv-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
    <div id="tv-progress-fill"></div>
</div>

<!-- ── Bottom HUD Bar ── -->
<footer id="tv-bar">
    <div id="bar-cat-label">—</div>
    <div id="bar-sep" style="width:1px;height:24px;background:var(--border);flex-shrink:0;"></div>
    <div id="bar-indicators"></div>
    <div id="bar-sep2" style="width:1px;height:24px;background:var(--border);flex-shrink:0;"></div>
    <div id="bar-ticker-wrap" aria-live="off">
        <span id="bar-ticker">🏛️ Pengadilan Agama Semarang &nbsp;·&nbsp; lawangsewu.pa-semarang.go.id/tvmedia &nbsp;·&nbsp; Pelayanan Prima, Peradilan Modern &nbsp;·&nbsp; Selamat Datang</span>
    </div>
    <div id="bar-sep3" style="width:1px;height:24px;background:var(--border);flex-shrink:0;"></div>
    <button id="btn-theme" onclick="toggleTheme()" title="Ganti tema" aria-label="Toggle dark/light mode">🌙</button>
    <button id="btn-admin" onclick="tgAdmin()" aria-label="Buka panel admin">⚙ KELOLA</button>
    <div id="bar-hint" aria-hidden="true">F·full &nbsp; A·admin &nbsp; ←→·slide</div>
</footer>

<!-- ── Admin Panel ── -->
<div id="adm" role="dialog" aria-modal="true" aria-label="Panel Admin TV Media">
    <button class="adm-x" onclick="tgAdmin()" title="Tutup" aria-label="Tutup panel admin">✕</button>
    <div class="adm-header">
        <div class="adm-h">⚙ Kelola <span>TV Media</span></div>
    </div>
    <div class="adm-s">Perubahan disimpan di browser ini (localStorage). Klik "Terapkan &amp; Mulai Ulang" untuk memperbarui slideshow di layar TV.</div>
    <div id="adm-list"></div>

    <!-- ── Tambah Slide Baru ── -->
    <div class="adm-add">
        <div class="adm-add-h">＋ Tambah Slide Baru</div>

        <!-- Row 1: Meta -->
        <div class="adm-row">
            <div class="adm-f">
                <label>Kategori</label>
                <select id="add-cat">
                    <option value="0">Seputar Peradilan</option>
                    <option value="1">Kepaniteraan</option>
                    <option value="2">Kesekretariatan</option>
                </select>
            </div>
            <div class="adm-f">
                <label>Label Slide</label>
                <input type="text" id="add-lbl" placeholder="Nama slide">
            </div>
            <div class="adm-f">
                <label>Tipe</label>
                <select id="add-type" onchange="onTypeChange(this.value)">
                    <option value="image">🖼 Gambar (upload/URL)</option>
                    <option value="youtube">🎬 YouTube Video</option>
                    <option value="widget">⚙ Widget (halaman internal)</option>
                    <option value="iframe">🌐 iFrame (URL eksternal)</option>
                </select>
            </div>
            <div class="adm-f">
                <label>Durasi (detik)</label>
                <input type="number" id="add-dur" value="12" min="5" max="300">
            </div>
        </div>

        <!-- Row 2: Sumber konten (URL / Upload tergantung tipe) -->
        <div id="src-url-row" class="adm-row">
            <div class="adm-f" style="min-width:100%">
                <label id="src-url-label">URL / Sumber</label>
                <input type="text" id="add-src" placeholder="https://... atau /path/internal">
                <div id="src-url-hint" style="display:none;font-size:10px;color:var(--text-muted);margin-top:5px"></div>
            </div>
        </div>

        <!-- Row 3: Upload zone (muncul saat tipe = image) -->
        <div id="src-upload-row" style="display:none;margin-bottom:10px">

            <!-- Tab: Upload vs Media Library -->
            <div class="upload-tabs">
                <button class="upload-tab active" onclick="switchUploadTab('upload',this)">☁ Upload Baru</button>
                <button class="upload-tab" onclick="switchUploadTab('library',this)">🗂 Media Library</button>
            </div>

            <!-- Tab: Upload file -->
            <div id="up-tab-upload" class="upload-tab-pane active">
                <div id="upload-dropzone">
                    <input type="file" id="upload-file-input"
                           accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm"
                           onchange="onFileChosen(this)">
                    <div class="drop-icon">📁</div>
                    <div class="drop-label">Seret &amp; Lepas file di sini</div>
                    <div class="drop-hint">atau klik untuk pilih file · JPG, PNG, GIF, WebP · maks. 15 MB<br>Video: MP4, WebM · maks. 80 MB</div>
                </div>

                <!-- Preview setelah file dipilih -->
                <div id="upload-preview">
                    <img id="upload-preview-thumb" src="" alt="preview">
                    <div class="upload-preview-info">
                        <div class="upload-preview-name" id="upload-preview-name">-</div>
                        <div class="upload-preview-size" id="upload-preview-size">-</div>
                    </div>
                    <button id="btn-upload-file" onclick="doUpload()" disabled>⬆ Upload</button>
                </div>

                <!-- Progress bar -->
                <div id="upload-progress-wrap">
                    <div class="upload-progress-bar">
                        <div class="upload-progress-fill" id="upload-progress-fill"></div>
                    </div>
                    <div class="upload-progress-label" id="upload-progress-label">Mengunggah...</div>
                </div>
            </div>

            <!-- Tab: Media Library -->
            <div id="up-tab-library" class="upload-tab-pane">
                <div id="media-library-grid">
                    <div id="media-lib-empty">Belum ada file yang diupload.</div>
                </div>
            </div>

            <!-- URL hasil upload (hidden, auto-filled) -->
            <div style="margin-top:10px">
                <label style="font-size:10px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px">URL Gambar/Video</label>
                <input type="text" id="add-src" placeholder="Akan terisi otomatis setelah upload, atau isi manual" style="width:100%;margin-top:5px;background:var(--bg-base);border:1px solid var(--border);border-radius:8px;color:var(--text-primary);font-size:12px;padding:8px 10px;font-family:var(--font-main)">
            </div>
        </div>

        <div class="adm-btns">
            <button class="btn-ok" onclick="addSlide()">＋ Tambah Slide</button>
            <button class="btn-sec" onclick="applyPL()">✓ Terapkan &amp; Mulai Ulang</button>
            <button class="btn-del" onclick="resetPL()">↺ Reset ke Default</button>
        </div>
    </div>
</div>

<!-- ── Toast ── -->
<div id="tv-toast" role="status" aria-live="polite"></div>

<script>
/* ════════════════════════════════════════════════════════════════
   TV MEDIA SLIDESHOW ENGINE v14 — developed by zhayyn™
   Arsitektur 2-Sumbu: Navigasi Vertikal Kategori + Horizontal Slide
   Ringan: zero external dependencies, ES5-compatible untuk Smart TV
   ════════════════════════════════════════════════════════════════ */

/* ── Storage key ── */
var SK = 'tvmedia_playlist_v14';

/* ── Struktur default: 3 kategori, masing-masing punya slides ── */
var DEF_CATS = [
    {
        id: 'peradilan',
        label: 'Seputar Peradilan',
        icon: '⚖️',
        slides: [
            {id:'statistik-perkara', type:'widget', label:'Statistik Perkara',
             src:'/statistik-perkara?tvmode=1', duration:12000, fallback:null},
            {id:'info-persidangan', type:'widget', label:'Info Persidangan',
             src:'/info-persidangan', duration:12000, fallback:null}
        ]
    },
    {
        id: 'kepaniteraan',
        label: 'Kepaniteraan',
        icon: '📋',
        slides: [
            {id:'monitor-antrian', type:'widget', label:'Monitor Antrian Sidang',
             src:'/monitor-antrian-sidang', duration:15000, fallback:null},
            {id:'info-persidangan-kep', type:'widget', label:'Info Persidangan',
             src:'/info-persidangan', duration:12000, fallback:null}
        ]
    },
    {
        id: 'kesekretariatan',
        label: 'Kesekretariatan',
        icon: '🗂️',
        slides: [
            {id:'canva-1', type:'iframe', label:'Laporan Kesekretariatan',
             src:'https://www.canva.com/design/DAHLiKEH7OE/XcjP9DUcV1xZEI2cu2iNRw/view?embed',
             slideCount:5, slideIntervalSec:10,
             fallback:'Konten Canva tidak dapat dimuat. Pastikan design diset Publik di Canva.'},
            {id:'daftar-pegawai', type:'widget', label:'Daftar Pegawai',
             src:'/daftar-pegawai', duration:15000, fallback:null}
        ]
    }
];

/* ── State ── */
var cats = [];          /* array kategori aktif */
var curCat = 0;         /* index kategori aktif */
var curSlides = [];     /* array curSlide per kategori */
var tmr = null;
var paused = false;
var isAdm = false;
var ifrAdvTmr = null;
var curIfrEl = null;
var isTransitioning = false;

/* ── Init ── */
function init() {
    var p = window.location.search;
    if (p.indexOf('admin=1') !== -1) {
        isAdm = true;
        document.getElementById('btn-admin').style.display = 'inline-block';
    }

    var sv = null;
    try { sv = JSON.parse(localStorage.getItem(SK)); } catch(e) {}
    cats = (sv && sv.length) ? sv : cloneDef();

    /* curSlides: satu index per kategori, semua mulai dari 0 */
    curSlides = [];
    for (var i = 0; i < cats.length; i++) curSlides.push(0);

    buildSidebar();
    buildStage();
    goToCat(0, false);
    startClock();
    initTheme();
}

function cloneDef() { return JSON.parse(JSON.stringify(DEF_CATS)); }

/* ── Build Sidebar Nav ── */
function buildSidebar() {
    var nav = document.getElementById('nav-cats');
    nav.innerHTML = '';
    for (var i = 0; i < cats.length; i++) {
        (function(idx) {
            var cat = cats[idx];
            /* Button kategori */
            var btn = document.createElement('button');
            btn.className = 'nav-cat-btn' + (idx === curCat ? ' active' : '');
            btn.id = 'nav-cat-' + idx;
            btn.setAttribute('aria-pressed', idx === curCat ? 'true' : 'false');
            btn.innerHTML =
                '<div class="tl-num">CH ' + pad(idx + 1) + '</div>' +
                '<div class="tl-dot"></div>' +
                '<div class="tl-content">' +
                    '<div class="tl-title">' + esc(cat.label) + '</div>' +
                '</div>';
            btn.onclick = function() { goToCat(idx, true); };
            nav.appendChild(btn);

            /* Sub-dots untuk kategori ini */
            var subDots = document.createElement('div');
            subDots.className = 'nav-sub-dots';
            subDots.id = 'sub-dots-' + idx;
            for (var j = 0; j < cat.slides.length; j++) {
                (function(si) {
                    var sd = document.createElement('div');
                    sd.className = 'sub-dot' + (si === curSlides[idx] ? ' active' : '');
                    sd.id = 'sub-dot-' + idx + '-' + si;
                    sd.textContent = cat.slides[si].label || ('Slide ' + (si + 1));
                    sd.onclick = function() {
                        if (curCat !== idx) goToCat(idx, true, si);
                        else goToSlide(si, true);
                    };
                    subDots.appendChild(sd);
                })(j);
            }
            nav.appendChild(subDots);
        })(i);
    }
}

/* ── Build Stage: satu cat-pane per kategori ── */
function buildStage() {
    var stage = document.getElementById('tv-stage');
    stage.innerHTML = '';
    for (var i = 0; i < cats.length; i++) {
        (function(ci) {
            var pane = document.createElement('div');
            pane.className = 'cat-pane hidden';
            pane.id = 'pane-' + ci;

            var cat = cats[ci];
            for (var j = 0; j < cat.slides.length; j++) {
                pane.appendChild(makeSlide(cat.slides[j], ci, j));
            }
            stage.appendChild(pane);
        })(i);
    }
}

/* ── Make single slide element ── */
function makeSlide(s, ci, si) {
    var div = document.createElement('div');
    div.className = 'tv-slide';
    div.id = 'sl-' + ci + '-' + si;

    var lbl = document.createElement('div');
    lbl.className = 'slide-label';
    lbl.textContent = s.label || s.type;
    div.appendChild(lbl);

    if (s.type === 'iframe' || s.type === 'widget') {
        var fr = document.createElement('iframe');
        fr.src = s.src || '';
        fr.setAttribute('loading', 'lazy');
        fr.setAttribute('allow', 'fullscreen; autoplay');
        fr.setAttribute('allowfullscreen', 'allowfullscreen');
        if (s.type === 'widget') {
            fr.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-forms allow-popups');
        }
        fr.dataset.ci = ci;
        fr.dataset.si = si;
        fr.addEventListener('load', frLoad);
        fr.addEventListener('error', frError);
        div.appendChild(fr);

        var fb = document.createElement('div');
        fb.className = 'slide-fallback';
        fb.id = 'fb-' + ci + '-' + si;
        fb.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" ' +
            'd="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 ' +
            '1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 ' +
            '0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' +
            '<p>' + esc(s.fallback || 'Konten tidak dapat dimuat.') + '</p>';
        div.appendChild(fb);

    } else if (s.type === 'youtube') {
        /* YouTube embed — konversi URL apapun ke format embed dengan autoplay+mute */
        var ytId  = extractYouTubeId(s.src || '');
        var ytSrc = ytId
            ? 'https://www.youtube.com/embed/' + ytId +
              '?autoplay=1&mute=1&loop=1&playlist=' + ytId +
              '&controls=0&modestbranding=1&rel=0&iv_load_policy=3&playsinline=1'
            : '';

        if (ytId) {
            var ytFr = document.createElement('iframe');
            ytFr.src = ytSrc;
            ytFr.setAttribute('loading', 'lazy');
            ytFr.setAttribute('allow', 'autoplay; fullscreen; encrypted-media; picture-in-picture');
            ytFr.setAttribute('allowfullscreen', 'allowfullscreen');
            ytFr.style.cssText = 'border:none;width:100%;height:100%;display:block';
            ytFr.title = s.label || 'YouTube Video';
            div.appendChild(ytFr);
        } else {
            var ytErr = document.createElement('div');
            ytErr.className = 'slide-fallback';
            ytErr.style.display = 'flex';
            ytErr.innerHTML = '<p>⚠ URL YouTube tidak valid.<br><small>' + esc(s.src || '') + '</small></p>';
            div.appendChild(ytErr);
        }

    } else if (s.type === 'image') {
        var wrap = document.createElement('div');
        wrap.style.cssText = 'display:flex;align-items:center;justify-content:center;height:100%;background:#000';

        /* Deteksi apakah src adalah video (mp4/webm) atau gambar */
        var isVideoFile = /\.(mp4|webm)(\?.*)?$/i.test(s.src || '');
        if (isVideoFile) {
            var vid = document.createElement('video');
            vid.src = s.src || '';
            vid.autoplay = true;
            vid.muted    = true;
            vid.loop     = true;
            vid.setAttribute('playsinline', '');
            vid.style.cssText = 'max-width:100%;max-height:100%;object-fit:contain';
            wrap.appendChild(vid);
        } else {
            var img = document.createElement('img');
            img.src = s.src || '';
            img.alt = s.label || '';
            img.style.cssText = 'max-width:100%;max-height:100%;object-fit:contain';
            wrap.appendChild(img);
        }
        div.appendChild(wrap);

    } else if (s.type === 'html') {
        var box = document.createElement('div');
        box.style.cssText = 'display:flex;align-items:center;justify-content:center;height:100%;padding:60px';
        var inner = document.createElement('div');
        inner.style.cssText = 'max-width:900px;font-size:34px;line-height:1.7;text-align:center;color:var(--text-primary)';
        inner.innerHTML = s.src || '';
        box.appendChild(inner);
        div.appendChild(box);
    }

    return div;
}

function frLoad(e) {
    var fr = e.target;
    try {
        var h = fr.contentWindow && fr.contentWindow.location.href;
        if (h === 'about:blank') return;
    } catch(ex) { /* cross-origin */ }
    var fb = document.getElementById('fb-' + fr.dataset.ci + '-' + fr.dataset.si);
    if (fb) fb.style.display = 'none';
    fr.style.display = 'block';
}

function frError(e) {
    var fr = e.target;
    var fb = document.getElementById('fb-' + fr.dataset.ci + '-' + fr.dataset.si);
    if (fb) fb.style.display = 'flex';
    fr.style.display = 'none';
}

/**
 * extractYouTubeId — ekstrak video ID dari berbagai format URL YouTube:
 * - https://www.youtube.com/watch?v=VIDEO_ID
 * - https://youtu.be/VIDEO_ID
 * - https://www.youtube.com/embed/VIDEO_ID
 * - https://www.youtube.com/shorts/VIDEO_ID
 * - VIDEO_ID (langsung ID 11 karakter)
 */
function extractYouTubeId(url) {
    if (!url) return null;
    url = url.trim();

    /* Langsung ID 11 karakter */
    if (/^[a-zA-Z0-9_-]{11}$/.test(url)) return url;

    var patterns = [
        /[?&]v=([a-zA-Z0-9_-]{11})/,           /* watch?v= */
        /youtu\.be\/([a-zA-Z0-9_-]{11})/,       /* youtu.be/ */
        /\/embed\/([a-zA-Z0-9_-]{11})/,          /* /embed/ */
        /\/shorts\/([a-zA-Z0-9_-]{11})/,         /* /shorts/ */
        /\/v\/([a-zA-Z0-9_-]{11})/,              /* /v/ */
    ];

    for (var i = 0; i < patterns.length; i++) {
        var m = url.match(patterns[i]);
        if (m) return m[1];
    }

    return null;
}

/* ══════════════════════════════════════════════════════════════
   NAVIGATION — Kategori (Vertikal)
══════════════════════════════════════════════════════════════ */
function goToCat(idx, animated, forceSlide) {
    if (idx < 0 || idx >= cats.length) return;
    if (isTransitioning && animated) return;

    var prev = curCat;
    var prevPane = document.getElementById('pane-' + prev);
    var nextPane = document.getElementById('pane-' + idx);

    if (!nextPane || cats[idx].slides.length === 0) return;

    /* Flash overlay untuk transisi kategori */
    if (animated && prev !== idx) {
        isTransitioning = true;
        var overlay = document.getElementById('cat-overlay');
        overlay.classList.add('flash');
        setTimeout(function() {
            overlay.classList.remove('flash');
            isTransitioning = false;
        }, 400);
    }

    /* Hide prev pane */
    if (prevPane && prev !== idx) {
        prevPane.classList.add('hidden');
        /* Deactivate current slide in prev pane */
        var prevSlideEl = document.getElementById('sl-' + prev + '-' + curSlides[prev]);
        if (prevSlideEl) prevSlideEl.classList.remove('active');
    }

    curCat = idx;
    if (typeof forceSlide !== 'undefined') curSlides[curCat] = forceSlide;

    /* Show next pane */
    nextPane.classList.remove('hidden');
    /* Activate slide */
    var slideIdx = curSlides[curCat];
    var slideEl = document.getElementById('sl-' + curCat + '-' + slideIdx);
    if (slideEl) slideEl.classList.add('active');

    stopIfrAdv();
    tryStartIfrAdv(curCat, slideIdx);

    updSidebar();
    updBarDots();
    updCounter();
    updBarCatLabel();
    scheduleSlide();
}

/* ══════════════════════════════════════════════════════════════
   NAVIGATION — Slide dalam Kategori (Horizontal)
══════════════════════════════════════════════════════════════ */
function goToSlide(idx, forward) {
    var cat = cats[curCat];
    if (!cat || cat.slides.length === 0) return;

    idx = ((idx % cat.slides.length) + cat.slides.length) % cat.slides.length;
    var prev = curSlides[curCat];
    if (prev === idx) return;

    stopIfrAdv();

    /* Animasi arah */
    var goingForward = (forward === true || (typeof forward === 'undefined' && idx > prev));
    var inAnim  = goingForward ? 'slide-in-right'  : 'slide-in-left';
    var outAnim = goingForward ? 'slide-out-right' : 'slide-out-left';

    var prevEl = document.getElementById('sl-' + curCat + '-' + prev);
    var nextEl = document.getElementById('sl-' + curCat + '-' + idx);

    if (prevEl) {
        prevEl.classList.remove('active');
        prevEl.classList.add(outAnim);
        (function(el, anim) {
            setTimeout(function() {
                el.classList.remove(anim);
            }, 700);
        })(prevEl, outAnim);
    }
    if (nextEl) {
        nextEl.classList.add(inAnim);
        nextEl.classList.add('active');
        (function(el, anim) {
            setTimeout(function() {
                el.classList.remove(anim);
            }, 700);
        })(nextEl, inAnim);
    }

    curSlides[curCat] = idx;
    tryStartIfrAdv(curCat, idx);

    updSidebar();
    updBarDots();
    updCounter();
    scheduleSlide();
}

function nextSlide() {
    var cat = cats[curCat];
    if (!cat) return;
    var next = curSlides[curCat] + 1;
    if (next >= cat.slides.length) {
        /* Akhir slide kategori ini → pindah kategori berikutnya */
        var nextCat = (curCat + 1) % cats.length;
        curSlides[curCat] = 0; /* Reset ke slide 0 di kategori ini */
        goToCat(nextCat, true, 0);
    } else {
        goToSlide(next, true);
    }
}

function prevSlide() {
    var prev = curSlides[curCat] - 1;
    if (prev < 0) {
        /* Balik ke kategori sebelumnya, slide terakhir */
        var prevCat = (curCat - 1 + cats.length) % cats.length;
        var prevCatLastSlide = cats[prevCat].slides.length - 1;
        goToCat(prevCat, true, prevCatLastSlide);
    } else {
        goToSlide(prev, false);
    }
}

/* ── Duration ── */
function calcDur(s) {
    if (s && s.slideCount && s.slideIntervalSec) {
        return Math.max(1000, s.slideCount * s.slideIntervalSec * 1000);
    }
    return (s && s.duration) || 12000;
}

/* ── Iframe advance (Canva best-effort postMessage) ── */
function stopIfrAdv() {
    if (ifrAdvTmr) { clearInterval(ifrAdvTmr); ifrAdvTmr = null; }
    curIfrEl = null;
}

function tryAdvCanva(fr) {
    if (!fr || !fr.contentWindow) return;
    try { fr.contentWindow.postMessage({type:'keydown', key:'ArrowRight', keyCode:39, which:39}, '*'); } catch(e) {}
    try { fr.contentWindow.postMessage(JSON.stringify({type:'next'}), '*'); } catch(e) {}
    try { fr.contentWindow.postMessage({action:'next'}, 'https://www.canva.com'); } catch(e) {}
}

function tryStartIfrAdv(ci, si) {
    var s = cats[ci] && cats[ci].slides[si];
    if (s && s.type === 'iframe' && s.slideCount && s.slideIntervalSec) {
        var pane = document.getElementById('pane-' + ci);
        var fr = pane && pane.querySelector('#sl-' + ci + '-' + si + ' iframe');
        if (fr) {
            curIfrEl = fr;
            setTimeout(function() { tryAdvCanva(fr); }, 2000);
            ifrAdvTmr = setInterval(function() { tryAdvCanva(fr); }, s.slideIntervalSec * 1000);
        }
    }
}

/* ── Schedule auto-advance ── */
function scheduleSlide() {
    if (tmr) { clearTimeout(tmr); tmr = null; }
    if (paused) return;
    var cat = cats[curCat];
    var s = cat && cat.slides[curSlides[curCat]];
    var dur = calcDur(s);

    var fill = document.getElementById('tv-progress-fill');
    fill.style.transition = 'none';
    fill.style.width = '0%';
    fill.getBoundingClientRect(); /* force reflow */
    fill.style.transition = 'width ' + dur + 'ms linear';
    fill.style.width = '100%';

    tmr = setTimeout(nextSlide, dur);
}

/* ══════════════════════════════════════════════════════════════
   UI UPDATES
══════════════════════════════════════════════════════════════ */
function updSidebar() {
    for (var i = 0; i < cats.length; i++) {
        var btn = document.getElementById('nav-cat-' + i);
        if (btn) {
            btn.className = 'nav-cat-btn' + (i === curCat ? ' active' : '');
            btn.setAttribute('aria-pressed', i === curCat ? 'true' : 'false');
        }
        /* Update sub-dots */
        for (var j = 0; j < (cats[i] ? cats[i].slides.length : 0); j++) {
            var sd = document.getElementById('sub-dot-' + i + '-' + j);
            if (sd) {
                sd.className = 'sub-dot' + (i === curCat && j === curSlides[i] ? ' active' : '');
            }
        }
    }
}

function updBarDots() {
    var bar = document.getElementById('bar-indicators');
    bar.innerHTML = '';
    var cat = cats[curCat];
    if (!cat) return;
    for (var i = 0; i < cat.slides.length; i++) {
        (function(si) {
            var d = document.createElement('div');
            d.className = 'bar-dot' + (si === curSlides[curCat] ? ' on' : '');
            d.id = 'bdot-' + si;
            d.title = cat.slides[si].label || '';
            d.onclick = function() { goToSlide(si, si > curSlides[curCat]); };
            bar.appendChild(d);
        })(i);
    }
}

function updCounter() {
    var el = document.getElementById('slide-counter');
    if (!el) return;
    var cat = cats[curCat];
    if (!cat) return;
    el.textContent = (curSlides[curCat] + 1) + ' / ' + cat.slides.length;
}

function updBarCatLabel() {
    var el = document.getElementById('bar-cat-label');
    if (!el) return;
    var cat = cats[curCat];
    el.textContent = cat ? cat.label : '—';
}

/* ══════════════════════════════════════════════════════════════
   CLOCK
══════════════════════════════════════════════════════════════ */
var HR  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
var BL  = ['Januari','Februari','Maret','April','Mei','Juni','Juli',
           'Agustus','September','Oktober','November','Desember'];
function startClock() {
    function tick() {
        var n = new Date();
        var hh = pad(n.getHours()), mm = pad(n.getMinutes()), ss = pad(n.getSeconds());
        document.getElementById('sb-clock').textContent = hh + ':' + mm + ':' + ss;
        document.getElementById('sb-date').textContent =
            HR[n.getDay()] + ', ' + n.getDate() + ' ' + BL[n.getMonth()] + ' ' + n.getFullYear();
    }
    tick();
    setInterval(tick, 1000);
}
function pad(n) { return n < 10 ? '0' + n : String(n); }

/* ══════════════════════════════════════════════════════════════
   THEME TOGGLE
══════════════════════════════════════════════════════════════ */
function initTheme() {
    var saved = localStorage.getItem('tvmedia_theme') || 'dark';
    applyTheme(saved);
}

function toggleTheme() {
    var current = document.documentElement.getAttribute('data-theme') || 'dark';
    var next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    localStorage.setItem('tvmedia_theme', next);
}

function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    var btn = document.getElementById('btn-theme');
    if (btn) btn.textContent = t === 'dark' ? '☀️' : '🌙';
}

/* ══════════════════════════════════════════════════════════════
   ADMIN PANEL
══════════════════════════════════════════════════════════════ */
function tgAdmin() {
    var panel = document.getElementById('adm');
    var opening = !panel.classList.contains('open');
    panel.classList.toggle('open');
    paused = opening;
    if (opening) {
        if (tmr) { clearTimeout(tmr); tmr = null; }
        stopIfrAdv();
        document.getElementById('tv-progress-fill').style.width = '0%';
        renderAdmList();
    } else {
        scheduleSlide();
    }
}

function renderAdmList() {
    var list = document.getElementById('adm-list');
    if (!list) return;
    var html = '';

    for (var ci = 0; ci < cats.length; ci++) {
        var cat = cats[ci];
        html += '<div class="adm-cat-section">';
        html += '<div class="adm-cat-title">' + (cat.icon || '📄') + ' ' + esc(cat.label) + '</div>';

        for (var si = 0; si < cat.slides.length; si++) {
            var s = cat.slides[si];
            var effDur = Math.round(calcDur(s) / 1000);

            var slideFields = '';
            if (s.type === 'iframe') {
                var sc = s.slideCount || '';
                var sv2 = s.slideIntervalSec || '';
                slideFields = '<div style="display:flex;gap:6px;align-items:center;flex-shrink:0">' +
                    '<div style="font-size:9px;color:var(--text-muted);text-align:center">Slide<br>' +
                    '<input type="number" value="' + sc + '" min="1" max="500" placeholder="jml"' +
                    ' style="width:48px;background:var(--bg-card);border:1px solid var(--border);' +
                    'border-radius:5px;color:var(--text-primary);font-size:11px;padding:3px 4px;text-align:center"' +
                    ' onchange="updSlideCount(' + ci + ',' + si + ',this.value)">' +
                    '</div>' +
                    '<div style="font-size:9px;color:var(--text-muted);text-align:center">Dtk/slide<br>' +
                    '<input type="number" value="' + sv2 + '" min="1" max="60" placeholder="dtk"' +
                    ' style="width:44px;background:var(--bg-card);border:1px solid var(--border);' +
                    'border-radius:5px;color:var(--text-primary);font-size:11px;padding:3px 4px;text-align:center"' +
                    ' onchange="updSlideInterval(' + ci + ',' + si + ',this.value)">' +
                    '</div>' +
                    '</div>';
            }

            html += '<div class="adm-card">' +
                '<span class="adm-badge ' + esc(s.type) + '">' + esc(s.type).toUpperCase() + '</span>' +
                '<div class="adm-body">' +
                '<div class="adm-lbl">' + esc(s.label || '(tanpa judul)') + '</div>' +
                '<div class="adm-src">' + esc(s.src || '') + '</div>' +
                '</div>' +
                slideFields +
                '<div class="adm-dur">' +
                '<input type="number" id="dur-inp-' + ci + '-' + si + '" value="' + effDur + '"' +
                ' min="3" max="3600" ' +
                (s.slideCount && s.slideIntervalSec ? 'readonly style="opacity:.5;cursor:not-allowed" title="Dihitung otomatis"' : '') +
                ' onchange="updDur(' + ci + ',' + si + ',this.value)">' +
                '<span>dtk</span></div>' +
                '<button class="adm-del" onclick="delSlide(' + ci + ',' + si + ')">✕</button>' +
                '</div>';
        }
        html += '</div>';
    }
    list.innerHTML = html;
}

function updDur(ci, si, v) {
    if (!cats[ci] || !cats[ci].slides[si]) return;
    var s = cats[ci].slides[si];
    if (!s.slideCount) s.duration = Math.max(3, parseInt(v) || 12) * 1000;
}

function updSlideCount(ci, si, v) {
    if (!cats[ci] || !cats[ci].slides[si]) return;
    var s = cats[ci].slides[si];
    s.slideCount = Math.max(1, parseInt(v) || 1);
    if (s.slideIntervalSec) {
        s.duration = s.slideCount * s.slideIntervalSec * 1000;
        var inp = document.getElementById('dur-inp-' + ci + '-' + si);
        if (inp) inp.value = Math.round(s.duration / 1000);
    }
}

function updSlideInterval(ci, si, v) {
    if (!cats[ci] || !cats[ci].slides[si]) return;
    var s = cats[ci].slides[si];
    s.slideIntervalSec = Math.max(1, parseInt(v) || 5);
    if (s.slideCount) {
        s.duration = s.slideCount * s.slideIntervalSec * 1000;
        var inp = document.getElementById('dur-inp-' + ci + '-' + si);
        if (inp) inp.value = Math.round(s.duration / 1000);
    }
}

function delSlide(ci, si) {
    if (!confirm('Hapus slide ini?')) return;
    cats[ci].slides.splice(si, 1);
    renderAdmList();
}

function addSlide() {
    var ci  = parseInt(document.getElementById('add-cat').value) || 0;
    var lbl = document.getElementById('add-lbl').value.trim();
    var type= document.getElementById('add-type').value;
    var src = document.getElementById('add-src').value.trim();
    var dur = parseInt(document.getElementById('add-dur').value) || 12;
    if (!src) { showToast('⚠ URL tidak boleh kosong!'); return; }
    if (!cats[ci]) { showToast('⚠ Kategori tidak valid!'); return; }
    cats[ci].slides.push({
        id: 'sl-' + Date.now(), type: type,
        label: lbl || src, src: src,
        duration: dur * 1000, fallback: null
    });
    document.getElementById('add-lbl').value = '';
    document.getElementById('add-src').value = '';
    document.getElementById('add-dur').value = '12';
    renderAdmList();
    showToast('✓ Slide berhasil ditambahkan');
}

function applyPL() {
    try { localStorage.setItem(SK, JSON.stringify(cats)); } catch(e) {}
    document.getElementById('adm').classList.remove('open');
    paused = false;
    curSlides = [];
    for (var i = 0; i < cats.length; i++) curSlides.push(0);
    buildSidebar();
    buildStage();
    goToCat(0, false, 0);
    showToast('✓ Slideshow diperbarui!');
}

function resetPL() {
    if (!confirm('Reset ke playlist default?')) return;
    try { localStorage.removeItem(SK); } catch(e) {}
    cats = cloneDef();
    renderAdmList();
    showToast('↺ Reset ke default');
}

/* ══════════════════════════════════════════════════════════════
   UPLOAD — Drag & Drop + File Picker
══════════════════════════════════════════════════════════════ */

/* Upload endpoint — hanya aktif saat diakses via /tvmedia/prakom */
var UPLOAD_URL  = '/tvmedia/prakom/upload';
var UPLOADS_URL = '/tvmedia/prakom/uploads';
var pickedFile  = null;

/* Ambil CSRF token dari meta (diinjeksi oleh Laravel controller) */
function getCsrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
}

/* Show/hide URL vs Upload row saat tipe berubah */
function onTypeChange(type) {
    var urlRow  = document.getElementById('src-url-row');
    var upRow   = document.getElementById('src-upload-row');
    var srcInp  = document.getElementById('add-src');
    var hint    = document.getElementById('src-url-hint');
    var lbl     = document.getElementById('src-url-label');

    if (!urlRow || !upRow) return;

    /* Sembunyikan hint dulu */
    if (hint) hint.style.display = 'none';

    if (type === 'image') {
        urlRow.style.display = 'none';
        upRow.style.display  = 'block';
    } else if (type === 'youtube') {
        urlRow.style.display = '';
        upRow.style.display  = 'none';
        if (lbl) lbl.textContent = 'URL YouTube';
        if (srcInp) srcInp.placeholder = 'https://www.youtube.com/watch?v=... atau https://youtu.be/...';
        if (hint) {
            hint.style.display = 'block';
            hint.innerHTML = '📺 Format yang diterima: <code>youtube.com/watch?v=ID</code> &nbsp;·&nbsp; ' +
                '<code>youtu.be/ID</code> &nbsp;·&nbsp; <code>youtube.com/shorts/ID</code> &nbsp;·&nbsp; ' +
                'ID langsung (11 karakter) &nbsp;·&nbsp; <code>youtube.com/embed/ID</code>';
        }
    } else {
        urlRow.style.display = '';
        upRow.style.display  = 'none';
        if (lbl) lbl.textContent = 'URL / Sumber';
        if (srcInp) {
            srcInp.placeholder = type === 'widget'
                ? '/path/internal contoh: /statistik-perkara'
                : 'https://... (URL embed)';
        }
    }
}

/* Switch tab Upload / Media Library */
function switchUploadTab(tab, btnEl) {
    document.querySelectorAll('.upload-tab').forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.upload-tab-pane').forEach(function(p) { p.classList.remove('active'); });
    if (btnEl) btnEl.classList.add('active');
    var pane = document.getElementById('up-tab-' + tab);
    if (pane) pane.classList.add('active');
    if (tab === 'library') loadMediaLibrary();
}

/* Drag & Drop events */
(function() {
    function ready() {
        var dz = document.getElementById('upload-dropzone');
        if (!dz) return;

        dz.addEventListener('dragover', function(e) {
            e.preventDefault();
            dz.classList.add('dragover');
        });
        dz.addEventListener('dragleave', function() { dz.classList.remove('dragover'); });
        dz.addEventListener('drop', function(e) {
            e.preventDefault();
            dz.classList.remove('dragover');
            var files = e.dataTransfer && e.dataTransfer.files;
            if (files && files.length) {
                var inp = document.getElementById('upload-file-input');
                /* Simulasikan pilih file dari drop */
                try {
                    var dt = new DataTransfer();
                    dt.items.add(files[0]);
                    inp.files = dt.files;
                } catch(ex) { /* Safari fallback */ }
                previewFile(files[0]);
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
    } else { ready(); }
})();

/* Dipanggil saat file dipilih via input[type=file] */
function onFileChosen(inp) {
    if (inp.files && inp.files[0]) previewFile(inp.files[0]);
}

function fmtBytes(b) {
    if (b < 1024) return b + ' B';
    if (b < 1024*1024) return (b/1024).toFixed(1) + ' KB';
    return (b/(1024*1024)).toFixed(1) + ' MB';
}

function previewFile(file) {
    pickedFile = file;
    var name  = document.getElementById('upload-preview-name');
    var size  = document.getElementById('upload-preview-size');
    var thumb = document.getElementById('upload-preview-thumb');
    var prev  = document.getElementById('upload-preview');
    var btn   = document.getElementById('btn-upload-file');

    if (name) name.textContent  = file.name;
    if (size) size.textContent  = fmtBytes(file.size);
    if (prev) prev.classList.add('show');
    if (btn)  btn.disabled = false;

    /* Buat object URL untuk preview gambar */
    if (thumb) {
        if (file.type.indexOf('image') === 0) {
            var url = URL.createObjectURL(file);
            thumb.src = url;
            thumb.style.display = 'block';
        } else {
            thumb.src = '';
            thumb.style.display = 'none';
        }
    }
}

/* Lakukan upload ke server */
function doUpload() {
    if (!pickedFile) return;

    var btn   = document.getElementById('btn-upload-file');
    var wrap  = document.getElementById('upload-progress-wrap');
    var fill  = document.getElementById('upload-progress-fill');
    var lbl   = document.getElementById('upload-progress-label');

    if (btn)  { btn.disabled = true; btn.textContent = 'Mengunggah...'; }
    if (wrap) wrap.classList.add('show');
    if (fill) fill.style.width = '0%';
    if (lbl)  lbl.textContent = 'Mengunggah...';

    var fd = new FormData();
    fd.append('file', pickedFile);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', UPLOAD_URL, true);
    xhr.setRequestHeader('X-CSRF-TOKEN', getCsrf());
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.upload.onprogress = function(e) {
        if (!e.lengthComputable) return;
        var pct = Math.round((e.loaded / e.total) * 100);
        if (fill) fill.style.width = pct + '%';
        if (lbl)  lbl.textContent  = pct + '%';
    };

    xhr.onload = function() {
        var res;
        try { res = JSON.parse(xhr.responseText); } catch(ex) { res = { ok: false }; }

        if (xhr.status === 200 && res.ok) {
            /* Isi field URL secara otomatis */
            var srcInp = document.getElementById('add-src');
            if (srcInp) srcInp.value = res.url;
            showToast('✓ Upload berhasil: ' + res.name);
            if (fill) fill.style.width = '100%';
            if (lbl)  lbl.textContent  = 'Selesai!';
            if (btn)  { btn.disabled = false; btn.textContent = '⬆ Upload'; }
            /* Auto-isi label slide jika kosong */
            var lblInp = document.getElementById('add-lbl');
            if (lblInp && !lblInp.value) {
                var baseName = res.name.replace(/\.[^.]+$/, '');
                lblInp.value = baseName;
            }
            setTimeout(function() {
                if (wrap) wrap.classList.remove('show');
            }, 2000);
        } else {
            var errMsg = (res && res.error) || 'Upload gagal (HTTP ' + xhr.status + ')';
            showToast('⚠ ' + errMsg);
            if (btn) { btn.disabled = false; btn.textContent = '⬆ Coba Lagi'; }
            if (wrap) wrap.classList.remove('show');
        }
    };

    xhr.onerror = function() {
        showToast('⚠ Koneksi gagal saat upload.');
        if (btn) { btn.disabled = false; btn.textContent = '⬆ Coba Lagi'; }
        if (wrap) wrap.classList.remove('show');
    };

    xhr.send(fd);
}

/* Load media library dari server */
function loadMediaLibrary() {
    var grid = document.getElementById('media-library-grid');
    if (!grid) return;
    grid.innerHTML = '<div id="media-lib-empty">Memuat...</div>';

    fetch(UPLOADS_URL, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.ok || !data.files || !data.files.length) {
            grid.innerHTML = '<div id="media-lib-empty">Belum ada file yang diupload.</div>';
            return;
        }
        grid.innerHTML = '';
        data.files.forEach(function(f) {
            var thumb = document.createElement('div');
            thumb.className = 'media-thumb';
            thumb.title = f.filename;

            var media;
            if (f.type === 'video') {
                media = document.createElement('video');
                media.src = f.url;
                media.muted = true;
                media.preload = 'metadata';
            } else {
                media = document.createElement('img');
                media.src = f.url;
                media.alt = f.filename;
            }
            thumb.appendChild(media);

            var lbl = document.createElement('div');
            lbl.className = 'media-thumb-label';
            lbl.textContent = f.filename;
            thumb.appendChild(lbl);

            var del = document.createElement('button');
            del.className = 'media-thumb-del';
            del.textContent = '✕';
            del.title = 'Hapus file';
            del.onclick = function(e) {
                e.stopPropagation();
                deleteMediaFile(f.filename, thumb);
            };
            thumb.appendChild(del);

            /* Klik thumbnail → isi URL field */
            thumb.onclick = function() {
                document.querySelectorAll('.media-thumb').forEach(function(t) { t.classList.remove('selected'); });
                thumb.classList.add('selected');
                var srcInp = document.getElementById('add-src');
                if (srcInp) srcInp.value = f.url;
                var lblInp = document.getElementById('add-lbl');
                if (lblInp && !lblInp.value) {
                    lblInp.value = f.filename.replace(/\.[^.]+$/, '');
                }
                showToast('✓ Media dipilih: ' + f.filename);
            };

            grid.appendChild(thumb);
        });
    })
    .catch(function() {
        grid.innerHTML = '<div id="media-lib-empty">Gagal memuat media library.</div>';
    });
}

/* Hapus file dari media library */
function deleteMediaFile(filename, thumbEl) {
    if (!confirm('Hapus file "' + filename + '"? Slide yang menggunakan file ini akan menampilkan gambar rusak.')) return;

    fetch(UPLOADS_URL + '/' + encodeURIComponent(filename), {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            if (thumbEl) thumbEl.remove();
            showToast('✓ File dihapus: ' + filename);
        } else {
            showToast('⚠ ' + (data.error || 'Gagal menghapus file.'));
        }
    })
    .catch(function() {
        showToast('⚠ Koneksi gagal saat menghapus.');
    });
}

/* Init upload UI: tampilkan upload zone by default (tipe = image) */
(function() {
    function initUploadUI() {
        var sel = document.getElementById('add-type');
        if (sel) onTypeChange(sel.value);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUploadUI);
    } else { initUploadUI(); }
})();

/* ══════════════════════════════════════════════════════════════
   KEYBOARD SHORTCUTS
══════════════════════════════════════════════════════════════ */
document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
    var admOpen = document.getElementById('adm').classList.contains('open');

    if (!admOpen) {
        if (e.key === ' ' || e.key === 'ArrowRight') { e.preventDefault(); nextSlide(); }
        if (e.key === 'ArrowLeft')  { e.preventDefault(); prevSlide(); }
        if (e.key === 'ArrowDown') { e.preventDefault(); goToCat((curCat + 1) % cats.length, true, 0); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); goToCat((curCat - 1 + cats.length) % cats.length, true, 0); }
        if (e.key === 'f' || e.key === 'F') {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen && document.documentElement.requestFullscreen().catch(function(){});
            } else {
                document.exitFullscreen && document.exitFullscreen();
            }
        }
        if (e.key === 't' || e.key === 'T') toggleTheme();
    }
    if ((e.key === 'a' || e.key === 'A') && isAdm) tgAdmin();
    if (e.key === 'Escape' && admOpen) tgAdmin();
});

/* ══════════════════════════════════════════════════════════════
   TOAST
══════════════════════════════════════════════════════════════ */
var toastTmr = null;
function showToast(msg) {
    var el = document.getElementById('tv-toast');
    if (!el) return;
    el.textContent = msg;
    el.classList.add('show');
    if (toastTmr) clearTimeout(toastTmr);
    toastTmr = setTimeout(function() { el.classList.remove('show'); }, 3000);
}

/* ══════════════════════════════════════════════════════════════
   HELPER
══════════════════════════════════════════════════════════════ */
function esc(s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/* ══════════════════════════════════════════════════════════════
   AUTO FULLSCREEN + BOOT
══════════════════════════════════════════════════════════════ */
var _fsReq = false;
document.body.addEventListener('click', function() {
    if (!_fsReq && !document.fullscreenElement) {
        _fsReq = true;
        document.documentElement.requestFullscreen &&
            document.documentElement.requestFullscreen().catch(function(){});
    }
}, { once: true });

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
</script>
</body>
</html>
<?php /* developed by zhayyn™ */ ?>
