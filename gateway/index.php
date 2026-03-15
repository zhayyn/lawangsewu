<?php
require __DIR__ . '/bootstrap.php';
gateway_require_login();
$cfg = gateway_config();
$basePath = $cfg['base_path'];
$gatewayUser = gateway_auth_user();
$masSatsetUrl = gateway_dubes_prakom_url();
$masSatsetLandingUrl = gateway_mas_satset_url();
$ssoMappingUrl = gateway_sso_mapping_url();
$gatewayUserRole = strtolower(trim((string) ($gatewayUser['role'] ?? '')));
$waCarakaEmbedUrl = gateway_wa_admin_sso_url('dashboard?embed=1');
$waCarakaAdminUrl = gateway_wa_admin_sso_url('dashboard');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cfg['app_name']); ?></title>
    <style>
        :root {
            --bg: #eef4ef;
            --panel: rgba(255,255,255,0.84);
            --line: rgba(25,104,63,0.14);
            --text: #173726;
            --muted: rgba(23,55,38,0.72);
            --green: #1e8b4f;
            --green-dark: #16693c;
            --shadow: 0 24px 60px rgba(17,59,36,0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Trebuchet MS", Verdana, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(113, 204, 146, 0.18), transparent 28%),
                radial-gradient(circle at right, rgba(83, 179, 122, 0.12), transparent 24%),
                linear-gradient(180deg, #f7fbf7 0%, var(--bg) 100%);
        }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 28px 18px 44px; }
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 24px; box-shadow: var(--shadow); backdrop-filter: blur(12px); }
        .topbar { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:16px; flex-wrap:wrap; }
        .topbar a { color: var(--green-dark); text-decoration:none; font-weight:700; }
        .hero { display:grid; grid-template-columns: 1.5fr .9fr; gap: 18px; margin-bottom: 18px; }
        .hero-main, .hero-side { padding: 24px; }
        .eyebrow { display:inline-flex; padding:8px 12px; border-radius:999px; background:rgba(30,139,79,0.10); color: var(--green-dark); font-size:12px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
        h1 { margin: 16px 0 10px; font-size: 34px; line-height: 1.05; }
        .lead { margin: 0; color: var(--muted); line-height: 1.7; font-size: 15px; }
        .hero-meta { margin-top: 16px; color: var(--muted); font-size: 13px; }
        .quick-links, .cards, .meta-grid { display:grid; gap: 14px; }
        .quick-link, .card { display:block; text-decoration:none; color:inherit; border-radius:20px; border:1px solid var(--line); background: rgba(255,255,255,0.74); padding: 18px; transition: transform .22s ease, border-color .22s ease, box-shadow .22s ease, background .22s ease; }
        .quick-link:hover, .card:hover { transform: translateY(-2px); border-color: rgba(25,104,63,0.22); box-shadow: 0 14px 28px rgba(17,59,36,0.10); background: rgba(255,255,255,0.9); }
        .cards { grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 18px; }
        .link-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
        .muted { color: var(--muted); font-size: 14px; line-height: 1.65; }
        .meta-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .meta-box { border-radius: 18px; background: rgba(255,255,255,0.72); border: 1px solid rgba(25,104,63,0.10); padding: 14px 16px; }
        .meta-label { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        .meta-value { margin-top: 6px; font-size: 18px; font-weight: 700; }
        code { background: #112219; color: #f1fff5; padding: 2px 6px; border-radius: 6px; }
        ul { margin: 8px 0 0 18px; }
        @media (max-width: 900px) {
            .hero, .cards, .meta-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <div>Login sebagai <strong><?php echo htmlspecialchars((string) ($gatewayUser['full_name'] ?: $gatewayUser['username'] ?? '-')); ?></strong></div>
        <div><a href="<?php echo htmlspecialchars(gateway_logout_url()); ?>">Logout Portal</a></div>
    </div>
    <section class="hero">
        <div class="panel hero-main">
            <span class="eyebrow">Lawangsewu Portal</span>
            <h1><?php echo htmlspecialchars($cfg['app_name']); ?></h1>
            <p class="lead">Control center internal yang modern, bersih, dan elegan untuk mengelola WA Caraka, observabilitas website chat, knowledge sync WordPress, dan utilitas gateway Lawangsewu.</p>
            <div class="hero-meta">Kredensial portal ini mengikuti akun WA Caraka, dan akses ke dashboard WA Caraka sekarang masuk melalui signed SSO dari portal Lawangsewu.</div>
        </div>
        <aside class="panel hero-side">
            <div class="quick-links">
                <a class="quick-link" href="<?php echo htmlspecialchars($masSatsetUrl); ?>">
                    <div class="link-title">Dubes Prakom Ops</div>
                    <div class="muted">Pantau website chat Mas Satset, runtime, model, sync WordPress, dan jalankan uji cepat dari satu halaman.</div>
                </a>
                <a class="quick-link" href="<?php echo htmlspecialchars($masSatsetLandingUrl); ?>">
                    <div class="link-title">Mas Satset</div>
                    <div class="muted">Landing page khusus untuk uji tanya jawab cepat website dan menambah knowledge baru sebagai basis jawaban AI.</div>
                </a>
                <a class="quick-link" href="<?php echo htmlspecialchars($waCarakaAdminUrl); ?>" target="_blank" rel="noopener noreferrer">
                    <div class="link-title">WA Caraka Admin via SSO</div>
                    <div class="muted">Buka kontrol utama runtime WhatsApp, inbox operator, messages, devices, dan pengaturan LLM tanpa login ulang.</div>
                </a>
                <?php if (in_array($gatewayUserRole, ['superadmin', 'admin'], true)) : ?>
                    <a class="quick-link" href="<?php echo htmlspecialchars($ssoMappingUrl); ?>">
                        <div class="link-title">Mapping SSO</div>
                        <div class="muted">Daftar mapping login terpadu untuk semua layanan portal Lawangsewu.</div>
                    </a>
                <?php endif; ?>
            </div>
        </aside>
    </section>

    <section class="meta-grid">
        <div class="meta-box">
            <div class="meta-label">Status SSO</div>
            <div class="meta-value"><?php echo htmlspecialchars(gateway_sso_status_label()); ?></div>
        </div>
        <div class="meta-box">
            <div class="meta-label">UI Login</div>
            <div class="meta-value">Dubes Prakom</div>
        </div>
        <div class="meta-box">
            <div class="meta-label">API Base</div>
            <div class="meta-value"><?php echo htmlspecialchars($basePath); ?></div>
        </div>
        <div class="meta-box">
            <div class="meta-label">Workspace Root</div>
            <div class="meta-value">/var/www/html/lawangsewu</div>
        </div>
    </section>

    <div style="height:18px"></div>

    <section class="cards">
        <div class="card">
            <div class="link-title">Endpoint API</div>
            <div class="muted">Endpoint internal untuk status, koneksi, daftar project, dan deploy lokal.</div>
            <ul>
                <li><code>GET <?php echo htmlspecialchars($basePath); ?>/api/status.php</code></li>
                <li><code>GET <?php echo htmlspecialchars($basePath); ?>/api/connections.php</code></li>
                <li><code>GET <?php echo htmlspecialchars($basePath); ?>/api/projects.php</code></li>
                <li><code>POST <?php echo htmlspecialchars($basePath); ?>/api/deploy.php</code></li>
            </ul>
        </div>

        <div class="card">
            <div class="link-title">Auth dan Workspace</div>
            <div class="muted">Autentikasi API tetap berbasis token, sedangkan UI gateway memakai kredensial user WA Caraka yang sama.</div>
            <ul>
                <li>API token: <code>Authorization: Bearer &lt;TOKEN&gt;</code></li>
                <li>Token ada di <code>gateway/.env</code> pada <code>GATEWAY_API_TOKEN</code></li>
                <li>Source project: <code>/var/www/html/lawangsewu/projects</code></li>
                <li>Deploy lokal: <code>projects/&lt;nama&gt;</code> ke <code>/var/www/html/&lt;nama&gt;</code></li>
            </ul>
        </div>
    </section>

    <div class="panel" style="padding:20px; margin-top:18px;">
        <div class="link-title">Embed WA Caraka di Lawangsewu</div>
        <div class="muted" style="margin-bottom:14px;">WA Caraka ditampilkan langsung di portal Lawangsewu melalui signed SSO. Selama sesi portal aktif, iframe ini akan membuka dashboard WA Caraka tanpa login kedua.</div>
        <iframe src="<?php echo htmlspecialchars($waCarakaEmbedUrl); ?>" title="Embed WA Caraka" style="width:100%; min-height:860px; border:1px solid rgba(25,104,63,0.14); border-radius:18px; background:#fff;"></iframe>
    </div>
</div>
</body>
</html>
