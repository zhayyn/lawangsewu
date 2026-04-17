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
$businessLaravelUrl = gateway_business_laravel_sso_url('dashboard');
$jatidiriUrl = gateway_jatidiri_sso_url('dashboard');
$userInitial = strtoupper(substr((string) ($gatewayUser['full_name'] ?? $gatewayUser['username'] ?? 'U'), 0, 1));
$userName = htmlspecialchars((string) ($gatewayUser['full_name'] ?: $gatewayUser['username'] ?? '-'));
$authSuccessMessage = gateway_flash_get('auth_success');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cfg['app_name']); ?> · Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        details summary { list-style: none; }
        details summary::-webkit-details-marker { display: none; }
        details[open] .chevron { transform: rotate(180deg); }
        .chevron { transition: transform 0.2s ease; }
        a, button { transition: all 0.15s ease; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 antialiased">

<!--
  ╔══════════════════════════════════════════════════════╗
  ║  OLD HTML — preserved for reference, do not delete  ║
  ╚══════════════════════════════════════════════════════╝

  <div class="wrap">
    <div class="topbar">
      <div>Login sebagai <strong>...</strong></div>
      <div><a href="...">Logout Portal</a></div>
    </div>
    <section class="hero">
      <div class="panel hero-main">
        <span class="eyebrow">Lawangsewu Portal</span>
        <h1>...</h1>
        <p class="lead">Control center internal...</p>
        <div class="hero-meta">Kredensial portal ini...</div>
      </div>
      <aside class="panel hero-side">
        <div class="quick-links">
          <a class="quick-link" href="...">Dubes Prakom Ops</a>
          <a class="quick-link" href="...">Mas Satset</a>
          <a class="quick-link" href="...">WA Caraka Admin via SSO</a>
          <a class="quick-link" href="...">Business Laravel Helpdesk</a>
          <a class="quick-link" href="...">Jatidiri Laravel 12</a>
          [conditional] <a class="quick-link" href="...">Mapping SSO</a>
        </div>
      </aside>
    </section>
    <section class="meta-grid">
      <div class="meta-box">Status SSO / UI Login / API Base / Workspace Root</div>
    </section>
    <section class="cards">
      <div class="card">Endpoint API ...</div>
      <div class="card">Auth dan Workspace ...</div>
    </section>
    <div class="panel">
      <iframe src="..." ...>Embed WA Caraka</iframe>
    </div>
  </div>
-->

<!-- ═══════════════════════════ TOP NAV ═══════════════════════════ -->
<header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
    <div class="max-w-6xl mx-auto px-5 h-14 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center text-white font-bold text-sm select-none">L</div>
            <span class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($cfg['app_name']); ?></span>
            <span class="hidden sm:block text-slate-200">|</span>
            <span class="hidden sm:block text-slate-400 text-xs">Dashboard Admin</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-emerald-100 rounded-full flex items-center justify-center text-xs font-bold text-emerald-700 select-none">
                    <?php echo $userInitial; ?>
                </div>
                <span class="text-sm text-slate-700 font-medium hidden sm:block"><?php echo $userName; ?></span>
                <span class="text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full font-medium capitalize"><?php echo htmlspecialchars($gatewayUserRole); ?></span>
            </div>
            <a href="<?php echo htmlspecialchars(gateway_logout_url()); ?>"
               class="text-xs text-slate-400 hover:text-red-500 border border-slate-200 hover:border-red-200 rounded-lg px-3 py-1.5">
                Logout
            </a>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto px-5 py-8">

    <?php if ($authSuccessMessage !== null && $authSuccessMessage !== '') : ?>
    <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <div>
                <div class="font-semibold">Login berhasil</div>
                <div class="text-emerald-700"><?php echo htmlspecialchars($authSuccessMessage); ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════ PAGE TITLE ═══════════════════════════ -->
    <div class="mb-7">
        <h1 class="text-xl font-bold text-slate-800">Selamat datang, <?php echo $userName; ?></h1>
        <p class="text-slate-400 mt-1 text-sm">Kelola semua layanan Lawangsewu dari satu portal terpadu.</p>
    </div>

    <!-- ═══════════════════════════ STATUS STRIP ═══════════════════════════ -->
    <div class="flex flex-wrap gap-2 mb-8">
        <div class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-4 py-2 shadow-sm">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-xs text-slate-500 font-medium">SSO: <?php echo htmlspecialchars(gateway_sso_status_label()); ?></span>
        </div>
        <div class="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-4 py-2 shadow-sm">
            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
            <span class="text-xs text-slate-500 font-mono"><?php echo htmlspecialchars($basePath); ?></span>
        </div>
    </div>

    <!-- ═══════════════════════════ 4 MAIN CARDS ═══════════════════════════ -->
    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-4">Layanan Utama</p>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">

        <!-- Card 1: Portal WA-Caraka -->
        <a href="<?php echo htmlspecialchars($waCarakaAdminUrl); ?>" target="_blank" rel="noopener noreferrer"
           class="group bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md hover:border-emerald-200 hover:-translate-y-0.5">
            <div class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center mb-4 group-hover:bg-emerald-100">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-800 text-sm mb-1">Portal WA-Caraka</p>
            <p class="text-xs text-slate-400 leading-relaxed">Runtime WhatsApp, inbox operator, devices, dan LLM.</p>
        </a>

        <!-- Card 2: Mas-Satset AI -->
        <a href="<?php echo htmlspecialchars($masSatsetLandingUrl); ?>"
           class="group bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md hover:border-violet-200 hover:-translate-y-0.5">
            <div class="w-11 h-11 bg-violet-50 rounded-xl flex items-center justify-center mb-4 group-hover:bg-violet-100">
                <svg class="w-6 h-6 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-800 text-sm mb-1">Mas-Satset AI</p>
            <p class="text-xs text-slate-400 leading-relaxed">Tanya jawab cepat dan tambah knowledge basis AI.</p>
        </a>

        <!-- Card 3: Daftar Widget -->
        <a href="/lawangsewu/widget-directory-shadow-proxy.php"
           class="group bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md hover:border-sky-200 hover:-translate-y-0.5">
            <div class="w-11 h-11 bg-sky-50 rounded-xl flex items-center justify-center mb-4 group-hover:bg-sky-100">
                <svg class="w-6 h-6 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-800 text-sm mb-1">Daftar Widget</p>
            <p class="text-xs text-slate-400 leading-relaxed">Direktori semua widget publik portal Lawangsewu.</p>
        </a>

        <!-- Card 4: SSO Login (admin/superadmin only) -->
        <?php if (in_array($gatewayUserRole, ['superadmin', 'admin'], true)) : ?>
        <a href="<?php echo htmlspecialchars($ssoMappingUrl); ?>"
           class="group bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md hover:border-amber-200 hover:-translate-y-0.5">
            <div class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center mb-4 group-hover:bg-amber-100">
                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-800 text-sm mb-1">SSO Login</p>
            <p class="text-xs text-slate-400 leading-relaxed">Mapping login terpadu semua layanan portal.</p>
        </a>
        <?php else : ?>
        <div class="bg-slate-50 rounded-2xl border border-dashed border-slate-200 p-5 opacity-60">
            <div class="w-11 h-11 bg-slate-100 rounded-xl flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-400 text-sm mb-1">SSO Login</p>
            <p class="text-xs text-slate-300 leading-relaxed">Akses terbatas — hanya admin.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════════════ LAYANAN LAINNYA ═══════════════════════════ -->
    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-4">Layanan Lainnya</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-10">

        <a href="<?php echo htmlspecialchars($masSatsetUrl); ?>"
           class="bg-white rounded-xl border border-slate-200 px-4 py-3 flex items-center gap-3 hover:shadow-sm hover:border-teal-200">
            <div class="w-9 h-9 bg-teal-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-sm text-slate-700">Dubes Prakom Ops</p>
                <p class="text-xs text-slate-400">Runtime &amp; observabilitas website chat</p>
            </div>
        </a>

        <a href="<?php echo htmlspecialchars($businessLaravelUrl); ?>" target="_blank" rel="noopener noreferrer"
           class="bg-white rounded-xl border border-slate-200 px-4 py-3 flex items-center gap-3 hover:shadow-sm hover:border-orange-200">
            <div class="w-9 h-9 bg-orange-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-sm text-slate-700">Business Helpdesk</p>
                <p class="text-xs text-slate-400">Ticketing internal Lawangsewu via SSO</p>
            </div>
        </a>

        <a href="<?php echo htmlspecialchars($jatidiriUrl); ?>" target="_blank" rel="noopener noreferrer"
           class="bg-white rounded-xl border border-slate-200 px-4 py-3 flex items-center gap-3 hover:shadow-sm hover:border-indigo-200">
            <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-sm text-slate-700">Jatidiri Laravel 12</p>
                <p class="text-xs text-slate-400">Direktori pegawai terpadu via SSO</p>
            </div>
        </a>

    </div>

    <!-- ═══════════════════════════ EMBED WA CARAKA ═══════════════════════════ -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm mb-8">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
            <h2 class="font-semibold text-slate-700 text-sm">Embed WA Caraka</h2>
        </div>
        <p class="text-xs text-slate-400 mb-4">Dashboard WA Caraka langsung di portal ini via signed SSO — tanpa login kedua selama sesi aktif.</p>
        <iframe src="<?php echo htmlspecialchars($waCarakaEmbedUrl); ?>" title="Embed WA Caraka"
                class="w-full rounded-xl border border-slate-200"
                style="min-height:860px;"></iframe>
    </div>

    <!-- ═══════════════════════════ REFERENSI TEKNIS (ACCORDION) ═══════════════════════════ -->
    <!--
      [OLD CARDS SECTION]
      <section class="cards">
        <div class="card">Endpoint API ...</div>
        <div class="card">Auth dan Workspace ...</div>
      </section>
      <section class="meta-grid">
        <div class="meta-box">Status SSO / UI Login / API Base / Workspace Root</div>
      </section>
    -->

    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-4">Referensi Teknis</p>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">

        <!-- Accordion: Endpoint API -->
        <details>
            <summary class="flex items-center justify-between px-5 py-4 cursor-pointer hover:bg-slate-50 select-none">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="font-medium text-slate-700 text-sm">Endpoint API</span>
                </div>
                <svg class="chevron w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="px-5 py-4 bg-slate-50 border-t border-slate-100">
                <p class="text-xs text-slate-500 mb-3">Endpoint internal untuk status, koneksi, project, dan deploy lokal:</p>
                <ul class="space-y-2">
                    <li><code class="font-mono text-xs bg-slate-800 text-emerald-300 px-2 py-1 rounded">GET <?php echo htmlspecialchars($basePath); ?>/api/status.php</code></li>
                    <li><code class="font-mono text-xs bg-slate-800 text-emerald-300 px-2 py-1 rounded">GET <?php echo htmlspecialchars($basePath); ?>/api/connections.php</code></li>
                    <li><code class="font-mono text-xs bg-slate-800 text-emerald-300 px-2 py-1 rounded">GET <?php echo htmlspecialchars($basePath); ?>/api/projects.php</code></li>
                    <li><code class="font-mono text-xs bg-slate-800 text-emerald-300 px-2 py-1 rounded">POST <?php echo htmlspecialchars($basePath); ?>/api/deploy.php</code></li>
                </ul>
            </div>
        </details>

        <!-- Accordion: Auth & Workspace -->
        <details>
            <summary class="flex items-center justify-between px-5 py-4 cursor-pointer hover:bg-slate-50 select-none">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="font-medium text-slate-700 text-sm">Auth &amp; Workspace</span>
                </div>
                <svg class="chevron w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="px-5 py-4 bg-slate-50 border-t border-slate-100">
                <ul class="space-y-2 text-xs text-slate-500">
                    <li>API token: <code class="font-mono bg-slate-800 text-emerald-300 px-2 py-0.5 rounded">Authorization: Bearer &lt;TOKEN&gt;</code></li>
                    <li>Token di: <code class="font-mono bg-slate-800 text-emerald-300 px-2 py-0.5 rounded">gateway/.env → GATEWAY_API_TOKEN</code></li>
                    <li>Source: <code class="font-mono bg-slate-800 text-emerald-300 px-2 py-0.5 rounded">/var/www/html/lawangsewu/projects</code></li>
                    <li>Deploy: <code class="font-mono bg-slate-800 text-emerald-300 px-2 py-0.5 rounded">projects/&lt;nama&gt; → /var/www/html/&lt;nama&gt;</code></li>
                </ul>
            </div>
        </details>

        <!-- Accordion: Info Sistem -->
        <details>
            <summary class="flex items-center justify-between px-5 py-4 cursor-pointer hover:bg-slate-50 select-none">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium text-slate-700 text-sm">Info Sistem</span>
                </div>
                <svg class="chevron w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="px-5 py-4 bg-slate-50 border-t border-slate-100">
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <p class="text-slate-400 uppercase tracking-wide font-semibold mb-1">Status SSO</p>
                        <p class="font-medium text-slate-700"><?php echo htmlspecialchars(gateway_sso_status_label()); ?></p>
                    </div>
                    <div>
                        <p class="text-slate-400 uppercase tracking-wide font-semibold mb-1">UI Login</p>
                        <p class="font-medium text-slate-700">Dubes Prakom</p>
                    </div>
                    <div>
                        <p class="text-slate-400 uppercase tracking-wide font-semibold mb-1">API Base</p>
                        <p class="font-mono text-slate-700"><?php echo htmlspecialchars($basePath); ?></p>
                    </div>
                    <div>
                        <p class="text-slate-400 uppercase tracking-wide font-semibold mb-1">Workspace Root</p>
                        <p class="font-mono text-slate-700">/var/www/html/lawangsewu</p>
                    </div>
                </div>
            </div>
        </details>

    </div>

</main>

<footer class="border-t border-slate-200 bg-white mt-8">
    <div class="max-w-6xl mx-auto px-5 py-4 text-center text-xs text-slate-300">
        <?php echo htmlspecialchars($cfg['app_name']); ?> &middot; Portal Admin Internal &middot; <?php echo date('Y'); ?>
    </div>
</footer>

</body>
</html>
