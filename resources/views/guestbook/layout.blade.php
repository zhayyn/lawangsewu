<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Buku Tamu Digital')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --pendopo-navy: #12334f;
            --pendopo-gold: #d4a74f;
        }

        body {
            margin: 0;
            min-height: 100dvh;
            overflow-x: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--pendopo-navy);
            background: radial-gradient(circle at top left, rgba(255, 220, 165, 0.28), transparent 28%), linear-gradient(180deg, #edf4f9 0%, #f7fafc 100%);
        }

        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 52px;
            z-index: 10;
            background: linear-gradient(100deg, #0b2137 0%, #143352 45%, #0b2137 100%);
            box-shadow: 0 3px 20px rgba(10, 30, 55, 0.38);
            display: flex;
            align-items: center;
        }

        .site-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent 0%, #d4a74f 20%, #ffe28a 50%, #d4a74f 80%, transparent 100%);
        }

        .site-header-inner {
            display: flex;
            align-items: center;
            width: 100%;
            height: 100%;
            padding: 0 1rem;
            gap: 0.8rem;
        }

        .site-header-badge {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: #d4a74f;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border-right: 1px solid rgba(212, 167, 79, 0.4);
            padding-right: 0.85rem;
            white-space: nowrap;
        }

        .site-header-marquee {
            position: relative;
            flex: 1 1 auto;
            min-width: 180px;
            height: 2rem;
            overflow: hidden;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.09), rgba(255, 255, 255, 0.05));
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .site-header-marquee::before,
        .site-header-marquee::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 42px;
            z-index: 1;
            pointer-events: none;
        }

        .site-header-marquee::before {
            left: 0;
            background: linear-gradient(90deg, rgba(11, 33, 55, 0.92), rgba(11, 33, 55, 0));
        }

        .site-header-marquee::after {
            right: 0;
            background: linear-gradient(270deg, rgba(11, 33, 55, 0.92), rgba(11, 33, 55, 0));
        }

        .site-header-marquee-track {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            gap: 2.5rem;
            min-width: max-content;
            padding-left: 1rem;
            padding-right: 1rem;
            white-space: nowrap;
            color: rgba(244, 249, 255, 0.9);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            animation: pendopo-marquee 28s linear infinite;
            will-change: transform;
        }

        @keyframes pendopo-marquee {
            0% { transform: translate(0, -50%); }
            100% { transform: translate(-50%, -50%); }
        }

        .site-header-badge .badge-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: rgba(212, 167, 79, 0.18);
            border: 1px solid rgba(212, 167, 79, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .site-header-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            flex-shrink: 0;
        }

        .site-pill {
            color: #eef4ff;
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 999px;
            padding: 0.34rem 0.76rem;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            background: rgba(255, 255, 255, 0.08);
        }

        .page-layer {
            padding-top: 52px;
            min-height: 100dvh;
        }

        .batik-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1.6rem;
            padding: clamp(1.25rem, 3vw, 2rem);
            border: 1px solid rgba(255, 255, 255, 0.5);
            background: linear-gradient(135deg, rgba(15, 39, 71, 0.88), rgba(28, 90, 138, 0.82));
            box-shadow: 0 22px 48px rgba(18, 46, 76, 0.2);
            color: #fff;
        }

        .batik-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.78rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255, 247, 230, 0.86);
        }

        .batik-kicker::before {
            content: "";
            width: 36px;
            height: 1px;
            background: rgba(255, 255, 255, 0.55);
        }

        .batik-hero-title {
            margin: 0;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2rem, 3vw, 3.2rem);
            font-weight: 700;
            line-height: 0.95;
            letter-spacing: 0.02em;
        }

        .batik-hero-subtitle {
            max-width: 760px;
            margin: 0.65rem 0 0;
            color: rgba(245, 249, 255, 0.86);
            font-size: clamp(0.95rem, 1.6vw, 1.05rem);
        }

        .batik-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.65rem 1rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.24);
            color: #fff;
            font-size: 0.92rem;
            white-space: nowrap;
        }

        .site-footer-signature {
            text-align: center;
            font-size: 0.82rem;
            color: rgba(18, 51, 79, 0.68);
            padding: 0.9rem 1rem 1.1rem;
            letter-spacing: 0.02em;
        }

        @media (max-width: 767.98px) {
            .site-header {
                height: 44px;
            }

            .page-layer {
                padding-top: 44px;
            }

            .site-header-badge {
                font-size: 0.88rem;
            }

            .site-header-marquee {
                min-width: 120px;
                height: 1.65rem;
            }

            .site-header-marquee::before,
            .site-header-marquee::after {
                width: 22px;
            }

            .site-header-marquee-track {
                font-size: 0.63rem;
                gap: 1.1rem;
                letter-spacing: 0.05em;
                animation-duration: 22s;
            }

            .site-pill {
                font-size: 0.7rem;
                padding: 0.28rem 0.6rem;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
@php
    $isEmbedded = request()->boolean('embedded');
@endphp
@if(!$isEmbedded)
<header class="site-header" role="banner">
    <div class="site-header-inner">
        <div class="site-header-badge">
            <span class="badge-icon"><i class="bi bi-building-fill"></i></span>
            PENDOPO
        </div>

        <div class="site-header-marquee" aria-label="Keterangan operasional Pendopo">
            <div class="site-header-marquee-track">
                <span>Pusat Entri Data &amp; Operasional Pengunjung.</span>
                <span>Pusat Entri Data &amp; Operasional Pengunjung.</span>
            </div>
        </div>

        <div class="site-header-right">
            <a class="site-pill" href="{{ route('lawangsewu.guestbook.form') }}" target="_top"><i class="bi bi-book-half"></i> Form Tamu</a>
            <a class="site-pill" href="{{ route('lawangsewu.guestbook.list', ['period' => 'all']) }}" target="_top"><i class="bi bi-clock-history"></i> Riwayat</a>
        </div>
    </div>
</header>
@endif

<div class="page-layer" @if($isEmbedded) style="padding-top:0" @endif>
    @yield('content')

    @if(!$isEmbedded)
    <footer class="site-footer-signature">
        Developed with <i class="bi bi-heart-fill" style="color:#d1495b"></i> by Dubes Prakom
    </footer>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
@stack('scripts')
</body>
</html>
