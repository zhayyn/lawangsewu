<?php
$rootPath = dirname(__DIR__, 4);
require_once $rootPath . '/gateway/bootstrap.php';

function lw_portal_prefix(): string
{
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/portal'), PHP_URL_PATH);
    if ($path === '/lawangsewu' || str_starts_with($path, '/lawangsewu/')) {
        return '/lawangsewu';
    }

    return '';
}

function lw_portal_public_url(string $path): string
{
    return lw_portal_prefix() . $path;
}

function lw_portal_card_symbol(array $item): array
{
    $name = strtolower((string) ($item['name'] ?? ''));
    $tag = strtolower((string) ($item['tag'] ?? ''));

    return match (true) {
        str_contains($name, 'mapping') || str_contains($name, 'gateway') => ['symbol' => '🔒', 'class' => 'symbol-lock'],
        str_contains($name, 'walkthrough') || str_contains($tag, 'dokumen') => ['symbol' => '✦', 'class' => 'symbol-star'],
        str_contains($name, 'swagger') || str_contains($tag, 'api') => ['symbol' => '⬢', 'class' => 'symbol-cube'],
        str_contains($name, 'wa caraka') || str_contains($tag, 'aplikasi') => ['symbol' => '◉', 'class' => 'symbol-orb'],
        str_contains($name, 'mas satset') || str_contains($tag, 'ai') => ['symbol' => '✺', 'class' => 'symbol-flare'],
        str_contains($tag, 'security') => ['symbol' => '🛡', 'class' => 'symbol-shield'],
        str_contains($tag, 'data') || str_contains($tag, 'bridge') => ['symbol' => '✦', 'class' => 'symbol-star'],
        str_contains($tag, 'publik') || str_contains($tag, 'embed') || str_contains($tag, 'sidang') => ['symbol' => '✶', 'class' => 'symbol-sun'],
        default => ['symbol' => '✦', 'class' => 'symbol-star'],
    };
}

function lw_portal_filter_group(string $sectionTitle, array $item): string
{
    $section = strtolower($sectionTitle);
    $tag = strtolower((string) ($item['tag'] ?? ''));
    $kind = strtolower((string) ($item['kind'] ?? 'public'));

    return match (true) {
        $kind === 'internal' => 'internal',
        str_contains($section, 'reference') || in_array($tag, ['dokumen', 'security'], true) => 'reference',
        str_contains($section, 'public') => 'public',
        str_contains($section, 'data') || in_array($tag, ['data', 'bridge'], true) => 'data',
        default => 'all',
    };
}

function lw_portal_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $initials .= strtoupper(substr($part, 0, 1));
        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'PU';
}

$requestedPath = gateway_normalize_return_path((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/portal'), PHP_URL_PATH), '/portal');
if (!gateway_is_logged_in()) {
    header('Location: ' . gateway_login_url_with_return($requestedPath));
    exit;
}

$gatewayUser = gateway_auth_user() ?? [];
$gatewayUserName = (string) (($gatewayUser['full_name'] ?? '') !== '' ? $gatewayUser['full_name'] : ($gatewayUser['username'] ?? 'Pengguna'));
$gatewayUserRole = strtoupper((string) ($gatewayUser['role'] ?? 'user'));
$gatewayUserInitials = lw_portal_initials($gatewayUserName);
$isAdminRole = in_array(strtolower((string) ($gatewayUser['role'] ?? '')), ['superadmin', 'admin'], true);

$sections = [
    [
        'title' => 'Internal Tools',
        'caption' => 'Akses inti untuk operasional harian dan pengelolaan sistem yang membutuhkan sesi login aktif.',
        'items' => [
            [
                'name' => 'WA Caraka Admin via SSO',
                'path' => gateway_wa_admin_sso_url('dashboard'),
                'desc' => 'Masuk ke operator dashboard, messages, devices, LLM, dan kontrol admin tanpa login ulang.',
                'tag' => 'Aplikasi',
                'kind' => 'internal',
            ],
            [
                'name' => 'Portal Gateway',
                'path' => gateway_ui_url('index'),
                'desc' => 'Pusat SSO, integrasi layanan, dan utilitas gateway Lawangsewu.',
                'tag' => 'SSO',
                'kind' => 'internal',
            ],
            [
                'name' => 'Dubes Prakom Ops',
                'path' => gateway_dubes_prakom_url(),
                'desc' => 'Pantauan runtime, website chat, sinkronisasi knowledge, dan operasional Dubes Prakom.',
                'tag' => 'Operasional',
                'kind' => 'internal',
            ],
            [
                'name' => 'Mas Satset AI',
                'path' => gateway_mas_satset_url(),
                'desc' => 'Laboratorium input knowledge dan uji cepat jawaban AI WA Caraka.',
                'tag' => 'AI',
                'kind' => 'internal',
            ],
            [
                'name' => 'Swagger UI Internal',
                'path' => lw_portal_public_url('/wa-caraka-admin/wa/docs/swagger'),
                'desc' => 'Dokumentasi dan pengujian cepat endpoint internal admin WA Caraka.',
                'tag' => 'API',
                'kind' => 'internal',
            ],
        ],
    ],
    [
        'title' => 'Reference',
        'caption' => 'Dokumentasi, widget directory, dan referensi operasional yang tetap sering dibuka dari satu dashboard.',
        'items' => array_values(array_filter([
            [
                'name' => 'Walkthrough Lawangsewu',
                'path' => lw_portal_public_url('/walkthrough'),
                'desc' => 'Bundel dokumentasi implementasi, migrasi, keamanan, dan operasional.',
                'tag' => 'Dokumen',
                'kind' => 'public',
            ],
            [
                'name' => 'Daftar Link Widget',
                'path' => lw_portal_public_url('/daftar-widget'),
                'desc' => 'Direktori URL final widget publik beserta jalur embed cepat.',
                'tag' => 'Publik',
                'kind' => 'public',
            ],
            $isAdminRole ? [
                'name' => 'Mapping SSO Layanan',
                'path' => gateway_sso_mapping_url(),
                'desc' => 'Peta login terpadu untuk layanan yang terhubung dengan gateway.',
                'tag' => 'Admin',
                'kind' => 'internal',
            ] : null,
            [
                'name' => 'Remote Access Security',
                'path' => lw_portal_public_url('/REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE-OFFICIAL.html'),
                'desc' => 'Dokumen resmi satu halaman untuk briefing keamanan dan tata kelola akses.',
                'tag' => 'Security',
                'kind' => 'public',
            ],
        ])),
    ],
    [
        'title' => 'Public Views',
        'caption' => 'Halaman publik yang bisa dibuka cepat tanpa harus berpindah ke menu lain.',
        'items' => [
            [
                'name' => 'Berita Pengadilan',
                'path' => lw_portal_public_url('/berita-pengadilan'),
                'desc' => 'Artikel, berita, dan RSS pengadilan dalam satu halaman publik.',
                'tag' => 'Publik',
                'kind' => 'public',
            ],
            [
                'name' => 'Pengumuman Peradilan',
                'path' => lw_portal_public_url('/pengumuman-peradilan'),
                'desc' => 'Halaman penuh pengumuman resmi MA dan Badilag.',
                'tag' => 'Publik',
                'kind' => 'public',
            ],
            [
                'name' => 'Widget Pengumuman',
                'path' => lw_portal_public_url('/widget-pengumuman'),
                'desc' => 'Versi ringkas feed pengumuman untuk kebutuhan embed.',
                'tag' => 'Embed',
                'kind' => 'public',
            ],
            [
                'name' => 'Monitor Persidangan',
                'path' => lw_portal_public_url('/monitor-persidangan'),
                'desc' => 'Monitor ruang sidang, panggilan, dan jadwal untuk layar publik.',
                'tag' => 'Sidang',
                'kind' => 'public',
            ],
        ],
    ],
    [
        'title' => 'Data & Monitoring',
        'caption' => 'Kumpulan dashboard dan bridge data yang membantu pembacaan cepat kondisi sistem.',
        'items' => [
            [
                'name' => 'Dashboard Perkara',
                'path' => lw_portal_public_url('/dashboard-perkara'),
                'desc' => 'Tren perkara, komposisi jenis perkara, dan ringkasan perbandingan.',
                'tag' => 'Data',
                'kind' => 'public',
            ],
            [
                'name' => 'Dashboard eCourt',
                'path' => lw_portal_public_url('/dashboard-ecourt'),
                'desc' => 'Ringkasan penerimaan eCourt dan distribusi bulanan.',
                'tag' => 'Data',
                'kind' => 'public',
            ],
            [
                'name' => 'Dashboard Hakim',
                'path' => lw_portal_public_url('/dashboard-hakim'),
                'desc' => 'Performansi hakim dan penyelesaian perkara per alur.',
                'tag' => 'Data',
                'kind' => 'public',
            ],
            [
                'name' => 'Bridge Server 10',
                'path' => lw_portal_public_url('/bridge-server10'),
                'desc' => 'Bridge debug endpoint panjar, wilayah, dan jalur data Server 10.',
                'tag' => 'Bridge',
                'kind' => 'public',
            ],
            [
                'name' => 'Monitor WA',
                'path' => lw_portal_public_url('/monitor-wa'),
                'desc' => 'Viewer QR dan status runtime WA v2 untuk pemantauan cepat.',
                'tag' => 'WA',
                'kind' => 'public',
            ],
        ],
    ],
];

$allItems = [];
foreach ($sections as $section) {
    foreach ($section['items'] as $item) {
        $allItems[] = $item;
    }
}

$internalCount = count(array_filter($allItems, static fn ($item) => (string) ($item['kind'] ?? '') === 'internal'));
$publicCount = count($allItems) - $internalCount;
$docCount = count(array_filter($allItems, static fn ($item) => in_array((string) ($item['tag'] ?? ''), ['Dokumen', 'Security'], true)));
$favoriteLeadItems = array_slice($allItems, 0, 4);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAWANGSEWU | Portal Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@400;600;700&family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f6f6f1;
            --paper: #fffdf8;
            --panel: #ffffff;
            --ink: #1d1d1b;
            --muted: #6d6d66;
            --line: #e9e5db;
            --green: #1a8917;
            --green-dark: #0f6d13;
            --green-soft: #edf7ec;
            --gold: #f2c94c;
            --blue: #4f8df7;
            --shadow: 0 18px 40px rgba(23, 22, 18, 0.06);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: linear-gradient(180deg, #fbfaf5 0%, var(--bg) 100%);
            color: var(--ink);
            font-family: 'Manrope', sans-serif;
        }
        a { color: inherit; }
        .shell {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding: 28px 0 56px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            position: sticky;
            top: 10px;
            z-index: 10;
            padding: 14px 0 18px;
            border-bottom: 1px solid var(--line);
            background: rgba(251, 250, 245, 0.92);
            backdrop-filter: blur(12px);
        }
        .brand {
            display: grid;
            gap: 4px;
        }
        .brand-mark {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .brand-title {
            font-family: 'Source Serif 4', serif;
            font-size: clamp(28px, 4vw, 42px);
            line-height: 1;
        }
        .brand-copy {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
            max-width: 720px;
        }
        .top-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px 10px 10px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
            min-height: 48px;
        }
        .user-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(180deg, #dff7db, #7fd487);
            color: var(--green-dark);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.08em;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8), 0 8px 18px rgba(26, 137, 23, 0.12);
        }
        .user-meta {
            display: grid;
            gap: 2px;
        }
        .user-name {
            font-size: 13px;
            font-weight: 800;
        }
        .user-role {
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .top-links a,
        .cta,
        .card-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 16px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.02em;
            border: 1px solid transparent;
            transition: transform 180ms ease, box-shadow 180ms ease, background 180ms ease;
        }
        .cta,
        .card-actions a.primary {
            background: linear-gradient(180deg, #25a020, var(--green));
            color: #fff;
            box-shadow: 0 10px 20px rgba(26, 137, 23, 0.16);
        }
        .top-links a,
        .card-actions a.secondary {
            background: #fff;
            color: var(--green-dark);
            border-color: #d8ead5;
        }
        .top-links a:hover,
        .cta:hover,
        .card-actions a:hover {
            transform: translateY(-1px);
        }
        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(300px, 0.65fr);
            gap: 28px;
            padding: 34px 0 22px;
            border-bottom: 1px solid var(--line);
        }
        .hero-copy {
            display: grid;
            gap: 18px;
        }
        .eyebrow {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--green-soft);
            color: var(--green-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .hero h1 {
            margin: 0;
            font-family: 'Source Serif 4', serif;
            font-size: clamp(38px, 6.4vw, 62px);
            line-height: 0.98;
            max-width: 800px;
        }
        .hero p {
            margin: 0;
            max-width: 760px;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.8;
        }
        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .hero-meta {
            display: grid;
            gap: 14px;
            align-content: start;
        }
        .meta-card {
            padding: 18px 20px;
            border-radius: 24px;
            background: var(--panel);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
        }
        .meta-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .meta-value {
            font-family: 'Source Serif 4', serif;
            font-size: 30px;
            line-height: 1;
            margin-bottom: 8px;
        }
        .meta-copy {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
        }
        .search-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 22px 0 10px;
        }
        .filter-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            padding: 6px 0 4px;
        }
        .filter-chip {
            appearance: none;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff;
            color: var(--muted);
            min-height: 38px;
            padding: 0 14px;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background 180ms ease, color 180ms ease, border-color 180ms ease, transform 180ms ease;
        }
        .filter-chip:hover {
            transform: translateY(-1px);
            border-color: #cfe4cc;
            color: var(--green-dark);
        }
        .filter-chip.is-active {
            background: linear-gradient(180deg, #25a020, var(--green));
            border-color: transparent;
            color: #fff;
            box-shadow: 0 10px 20px rgba(26, 137, 23, 0.14);
        }
        .search-row input {
            width: 100%;
            min-height: 54px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #fff;
            padding: 0 18px;
            font: inherit;
            color: var(--ink);
        }
        .search-row input:focus {
            outline: none;
            border-color: #b9dbb6;
            box-shadow: 0 0 0 4px rgba(26, 137, 23, 0.06);
        }
        .search-meta {
            color: var(--muted);
            font-size: 14px;
        }
        .search-meta strong {
            color: var(--green-dark);
        }
        .favorites-panel {
            display: grid;
            gap: 14px;
            padding: 18px 20px 8px;
        }
        .favorites-box {
            display: grid;
            gap: 14px;
            padding: 20px;
            border-radius: 24px;
            background: linear-gradient(180deg, #fffef9, #f7f5ee);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
        }
        .favorites-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .favorites-head h2 {
            margin: 0 0 4px;
            font-family: 'Source Serif 4', serif;
            font-size: 28px;
            font-weight: 600;
        }
        .favorites-head p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
            max-width: 760px;
        }
        .favorites-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .favorite-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            padding: 0 14px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #e7e0c7;
            color: var(--ink);
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 10px 18px rgba(23, 22, 18, 0.04);
        }
        .favorite-pill:hover {
            transform: translateY(-1px);
        }
        .favorite-pill-symbol {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--green-soft);
            color: var(--green-dark);
            font-size: 14px;
        }
        .favorites-empty {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
        }
        .favorites-empty[hidden] {
            display: none;
        }
        .section {
            padding-top: 28px;
        }
        .section[hidden] {
            display: none;
        }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 16px;
            margin-bottom: 18px;
        }
        .section-head h2 {
            margin: 0 0 6px;
            font-family: 'Source Serif 4', serif;
            font-size: 34px;
            font-weight: 600;
        }
        .section-head p {
            margin: 0;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
            max-width: 760px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .card {
            display: grid;
            gap: 16px;
            padding: 22px;
            border-radius: 26px;
            background: var(--panel);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
        }
        .card.is-favorite {
            border-color: #dfe6c4;
            box-shadow: 0 18px 36px rgba(23, 22, 18, 0.08);
        }
        .card-top {
            display: grid;
            gap: 16px;
        }
        .card-head {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 16px;
            align-items: start;
        }
        .symbol {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            border-radius: 18px;
            font-size: 23px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8), 0 8px 18px rgba(23, 22, 18, 0.08);
            transform: perspective(120px) rotateX(12deg);
            overflow: hidden;
        }
        .symbol::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.45), rgba(255,255,255,0));
            opacity: 0.72;
        }
        .symbol-star {
            background: linear-gradient(180deg, #fff3b7, var(--gold));
            color: #8f6800;
        }
        .symbol-lock {
            background: linear-gradient(180deg, #cfe1ff, var(--blue));
            color: #143f8f;
        }
        .symbol-cube {
            background: linear-gradient(180deg, #d8d9ff, #8a8cff);
            color: #3135a6;
        }
        .symbol-orb {
            background: linear-gradient(180deg, #d8ffd9, #48c665);
            color: #0c6627;
        }
        .symbol-flare {
            background: linear-gradient(180deg, #ffe0d2, #ff9a72);
            color: #9c3f12;
        }
        .symbol-shield {
            background: linear-gradient(180deg, #d7ecff, #79b7ff);
            color: #134b93;
        }
        .symbol-sun {
            background: linear-gradient(180deg, #fff1c7, #ffcf64);
            color: #8a6100;
        }
        .favorite-toggle {
            appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff;
            color: #b8b2a4;
            font: inherit;
            font-size: 18px;
            cursor: pointer;
            transition: transform 180ms ease, border-color 180ms ease, color 180ms ease, background 180ms ease;
        }
        .favorite-toggle:hover {
            transform: translateY(-1px);
            border-color: #e4dbad;
            color: #a27b00;
        }
        .favorite-toggle.is-active {
            color: #8f6800;
            background: linear-gradient(180deg, #fff7d2, #f2c94c);
            border-color: #f0dd8f;
        }
        .card-tag {
            display: inline-flex;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            background: var(--green-soft);
            color: var(--green-dark);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .card h3 {
            margin: 0 0 8px;
            font-family: 'Source Serif 4', serif;
            font-size: 25px;
            font-weight: 600;
            line-height: 1.12;
        }
        .card p {
            margin: 0;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.8;
        }
        .path {
            display: inline-flex;
            width: fit-content;
            padding: 8px 12px;
            border-radius: 999px;
            background: #f9f7f1;
            border: 1px solid var(--line);
            color: var(--muted);
            font-size: 12px;
            word-break: break-word;
        }
        .card-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .empty-state {
            display: none;
            margin-top: 24px;
            padding: 18px 20px;
            border-radius: 20px;
            background: #fff;
            border: 1px dashed #d9d5c8;
            color: var(--muted);
            text-align: center;
        }
        .empty-state.visible {
            display: block;
        }
        .foot {
            margin-top: 34px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 14px;
            line-height: 1.8;
        }
        .foot strong {
            color: var(--ink);
        }
        .credit {
            display: inline-flex;
            margin-top: 10px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--green-soft);
            color: var(--green-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        @media (max-width: 920px) {
            .hero,
            .grid,
            .search-row {
                grid-template-columns: 1fr;
            }
            .section-head {
                align-items: start;
                flex-direction: column;
            }
            .top-links {
                justify-content: flex-start;
            }
        }
        @media (max-width: 640px) {
            .shell {
                width: min(100% - 18px, 1120px);
                padding-top: 18px;
            }
            .hero h1 {
                font-size: 42px;
            }
            .section-head h2 {
                font-size: 30px;
            }
            .card-head {
                grid-template-columns: 1fr;
            }
            .card-actions a,
            .cta,
            .top-links a {
                width: 100%;
            }
            .user-chip {
                width: 100%;
            }
            .filter-chip {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <div class="topbar">
            <div class="brand">
                <div class="brand-mark">Portal Editorial</div>
                <div class="brand-title">Lawangsewu Portal</div>
                <div class="brand-copy">Ruang kerja ringkas untuk tools inti, referensi, widget, dan monitor penting.</div>
            </div>
            <div class="top-links">
                <div class="user-chip">
                    <span class="user-avatar"><?php echo htmlspecialchars($gatewayUserInitials, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="user-meta">
                        <span class="user-name"><?php echo htmlspecialchars($gatewayUserName, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($gatewayUserRole, ENT_QUOTES, 'UTF-8'); ?> session active</span>
                    </span>
                </div>
                <a href="<?php echo htmlspecialchars(gateway_ui_url('index'), ENT_QUOTES, 'UTF-8'); ?>">Gateway</a>
                <a href="<?php echo htmlspecialchars(gateway_logout_url(), ENT_QUOTES, 'UTF-8'); ?>">Logout</a>
            </div>
        </div>

        <section class="hero">
            <div class="hero-copy">
                <span class="eyebrow">Editorial Workspace</span>
                <h1>Satu meja kerja bersih untuk akses inti Lawangsewu.</h1>
                <p>Semua akses utama diringkas agar cepat dipindai, mudah dibuka, dan tidak terasa ramai.</p>
                <div class="hero-actions">
                    <a class="cta" href="<?php echo htmlspecialchars(gateway_wa_admin_sso_url('dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Buka WA Caraka</a>
                    <a class="cta" href="<?php echo htmlspecialchars(lw_portal_public_url('/daftar-widget'), ENT_QUOTES, 'UTF-8'); ?>">Daftar Widget</a>
                </div>
            </div>
            <aside class="hero-meta">
                <article class="meta-card">
                    <div class="meta-label">Sesi Aktif</div>
                    <div class="meta-value"><?php echo htmlspecialchars($gatewayUserName, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="meta-copy">Role: <?php echo htmlspecialchars($gatewayUserRole, ENT_QUOTES, 'UTF-8'); ?></div>
                </article>
                <article class="meta-card">
                    <div class="meta-label">Ringkasan</div>
                    <div class="meta-value"><?php echo $internalCount + $publicCount; ?></div>
                    <div class="meta-copy"><?php echo $internalCount; ?> internal, <?php echo $publicCount; ?> publik, <?php echo $docCount; ?> dokumen penting.</div>
                </article>
            </aside>
        </section>

        <div class="search-row">
            <input id="portalSearch" type="search" placeholder="Cari tools, widget, dashboard, dokumentasi, atau jalur URL..." autocomplete="off">
            <div class="search-meta"><strong id="searchCount"><?php echo $internalCount + $publicCount; ?></strong> items tersedia</div>
        </div>
        <div class="filter-row">
            <button class="filter-chip is-active" type="button" data-filter="all">Semua</button>
            <button class="filter-chip" type="button" data-filter="internal">Internal</button>
            <button class="filter-chip" type="button" data-filter="favorites">Favorit</button>
            <button class="filter-chip" type="button" data-filter="reference">Dokumen</button>
            <button class="filter-chip" type="button" data-filter="public">Publik</button>
            <button class="filter-chip" type="button" data-filter="data">Data</button>
        </div>

        <section class="favorites-panel" aria-labelledby="portal-favorites-title">
            <div class="favorites-box">
                <div class="favorites-head">
                    <div>
                        <h2 id="portal-favorites-title">Favorit Anda</h2>
                        <p>Tautan yang paling sering dipakai akan muncul di sini untuk akses satu klik.</p>
                    </div>
                </div>
                <div class="favorites-list" id="portalFavoritesList">
                    <?php foreach ($favoriteLeadItems as $item): ?>
                    <?php $favoriteSymbol = lw_portal_card_symbol($item); ?>
                    <a class="favorite-pill" href="<?php echo htmlspecialchars((string) $item['path'], ENT_QUOTES, 'UTF-8'); ?>" data-favorite-placeholder>
                        <span class="favorite-pill-symbol"><?php echo htmlspecialchars((string) $favoriteSymbol['symbol'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <div class="favorites-empty" id="portalFavoritesEmpty" hidden>Tandai kartu dengan ikon bintang untuk membangun daftar favorit pribadi.</div>
            </div>
        </section>

        <?php foreach ($sections as $section): ?>
        <section class="section" data-section>
            <div class="section-head">
                <div>
                    <h2><?php echo htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($section['caption'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
            <div class="grid" data-grid>
                <?php foreach ($section['items'] as $item): ?>
                <?php $symbol = lw_portal_card_symbol($item); ?>
                <article class="card" data-card data-card-id="<?php echo htmlspecialchars(md5((string) $item['name'] . '|' . (string) $item['path']), ENT_QUOTES, 'UTF-8'); ?>" data-filter-group="<?php echo htmlspecialchars(lw_portal_filter_group((string) $section['title'], $item), ENT_QUOTES, 'UTF-8'); ?>" data-search="<?php echo htmlspecialchars(strtolower(implode(' ', [(string) $section['title'], (string) $item['name'], (string) $item['tag'], (string) $item['kind'], (string) $item['desc'], (string) $item['path']])), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="card-top">
                        <div class="card-head">
                            <span class="symbol <?php echo htmlspecialchars((string) $symbol['class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $symbol['symbol'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <div>
                                <span class="card-tag"><?php echo htmlspecialchars((string) $item['tag'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <h3><?php echo htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars((string) $item['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <button class="favorite-toggle" type="button" data-favorite-toggle aria-label="Tandai favorit">★</button>
                        </div>
                        <span class="path"><?php echo htmlspecialchars((string) $item['path'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="card-actions">
                        <a class="primary" href="<?php echo htmlspecialchars((string) $item['path'], ENT_QUOTES, 'UTF-8'); ?>">Buka</a>
                        <a class="secondary" href="<?php echo htmlspecialchars((string) $item['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Tab Baru</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>

        <section class="empty-state" id="portalEmptyState">Tidak ada hasil yang cocok. Coba kata kunci lain seperti WA, gateway, widget, security, atau dashboard.</section>

        <section class="foot">
            <strong>Catatan:</strong> landing tetap menjadi gerbang brand, sementara portal ini difokuskan untuk ritme kerja yang lebih tenang dan cepat dibaca.
            <div class="credit">developed by dbprakom</div>
        </section>
    </main>

    <script>
        (function () {
            const searchInput = document.getElementById('portalSearch');
            const cards = Array.from(document.querySelectorAll('[data-card]'));
            const sections = Array.from(document.querySelectorAll('[data-section]'));
            const countNode = document.getElementById('searchCount');
            const emptyState = document.getElementById('portalEmptyState');
            const favoritesList = document.getElementById('portalFavoritesList');
            const favoritesEmpty = document.getElementById('portalFavoritesEmpty');
            const filterButtons = Array.from(document.querySelectorAll('[data-filter]'));
            const favoriteButtons = Array.from(document.querySelectorAll('[data-favorite-toggle]'));
            let activeFilter = 'all';
            const storageKey = 'lawangsewu-portal-favorites';
            let favoriteIds = [];

            if (!searchInput || cards.length === 0 || !countNode || !emptyState || !favoritesList || !favoritesEmpty) {
                return;
            }

            try {
                favoriteIds = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
                if (!Array.isArray(favoriteIds)) {
                    favoriteIds = [];
                }
            } catch (error) {
                favoriteIds = [];
            }

            function favoriteSet() {
                return new Set(favoriteIds);
            }

            function renderFavoritesRail() {
                const favorites = favoriteSet();
                const favoriteCards = cards.filter((card) => favorites.has(card.getAttribute('data-card-id') || ''));

                favoritesList.innerHTML = '';

                if (favoriteCards.length === 0) {
                    favoritesEmpty.hidden = false;
                    return;
                }

                favoritesEmpty.hidden = true;
                favoriteCards.slice(0, 8).forEach((card) => {
                    const titleNode = card.querySelector('h3');
                    const linkNode = card.querySelector('.card-actions a.primary');
                    const symbolNode = card.querySelector('.symbol');
                    if (!titleNode || !linkNode || !symbolNode) {
                        return;
                    }

                    const pill = document.createElement('a');
                    pill.className = 'favorite-pill';
                    pill.href = linkNode.getAttribute('href') || '#';

                    const symbol = document.createElement('span');
                    symbol.className = 'favorite-pill-symbol';
                    symbol.textContent = symbolNode.textContent || '✦';

                    const label = document.createElement('span');
                    label.textContent = titleNode.textContent || 'Favorit';

                    pill.appendChild(symbol);
                    pill.appendChild(label);
                    favoritesList.appendChild(pill);
                });
            }

            function syncFavoriteUI() {
                const favorites = favoriteSet();
                cards.forEach((card) => {
                    const cardId = card.getAttribute('data-card-id') || '';
                    const isFavorite = favorites.has(cardId);
                    card.classList.toggle('is-favorite', isFavorite);
                    const button = card.querySelector('[data-favorite-toggle]');
                    if (button) {
                        button.classList.toggle('is-active', isFavorite);
                        button.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
                    }
                });

                sections.forEach((section) => {
                    const grid = section.querySelector('[data-grid]');
                    if (!grid) {
                        return;
                    }
                    const sectionCards = Array.from(grid.querySelectorAll('[data-card]'));
                    sectionCards.sort((left, right) => {
                        const leftFav = favorites.has(left.getAttribute('data-card-id') || '') ? 1 : 0;
                        const rightFav = favorites.has(right.getAttribute('data-card-id') || '') ? 1 : 0;
                        return rightFav - leftFav;
                    }).forEach((card) => grid.appendChild(card));
                });

                renderFavoritesRail();
            }

            function updateFilter() {
                const query = searchInput.value.trim().toLowerCase();
                const favorites = favoriteSet();
                let visibleCards = 0;

                cards.forEach((card) => {
                    const haystack = card.getAttribute('data-search') || '';
                    const group = card.getAttribute('data-filter-group') || 'all';
                    const cardId = card.getAttribute('data-card-id') || '';
                    const matchedQuery = query === '' || haystack.includes(query);
                    const matchedFilter = activeFilter === 'all'
                        || group === activeFilter
                        || (activeFilter === 'favorites' && favorites.has(cardId));
                    const matched = matchedQuery && matchedFilter;
                    card.hidden = !matched;
                    if (matched) {
                        visibleCards += 1;
                    }
                });

                sections.forEach((section) => {
                    const visibleInSection = section.querySelectorAll('[data-card]:not([hidden])').length;
                    section.hidden = visibleInSection === 0;
                });

                countNode.textContent = String(visibleCards);
                emptyState.classList.toggle('visible', visibleCards === 0);
            }

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    activeFilter = button.getAttribute('data-filter') || 'all';
                    filterButtons.forEach((chip) => {
                        chip.classList.toggle('is-active', chip === button);
                    });
                    updateFilter();
                });
            });

            favoriteButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const card = button.closest('[data-card]');
                    if (!card) {
                        return;
                    }
                    const cardId = card.getAttribute('data-card-id') || '';
                    if (cardId === '') {
                        return;
                    }
                    const next = new Set(favoriteIds);
                    if (next.has(cardId)) {
                        next.delete(cardId);
                    } else {
                        next.add(cardId);
                    }
                    favoriteIds = Array.from(next);
                    window.localStorage.setItem(storageKey, JSON.stringify(favoriteIds));
                    syncFavoriteUI();
                    updateFilter();
                });
            });

            searchInput.addEventListener('input', updateFilter);
            syncFavoriteUI();
            updateFilter();
        })();
    </script>
</body>
</html>
