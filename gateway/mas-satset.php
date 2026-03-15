<?php
require __DIR__ . '/bootstrap.php';
gateway_require_login();

$cfg = gateway_config();
$basePath = $cfg['base_path'];
$gatewayUser = gateway_auth_user();
$runtimeEnv = gateway_load_env(dirname(__DIR__) . '/wa-caraka/.env');
$wordpressBaseUrl = 'http://192.168.88.9/pasemarang';
$websiteChatUrl = $wordpressBaseUrl . '/wp-json/pa-chat/v1/ask';
$websiteChatPageUrl = $wordpressBaseUrl . '/layanan-bantuan-mas-satset/';
$runtimeHealthUrl = 'http://127.0.0.1:8793/health';
$runtimeLlmHealthUrl = 'http://127.0.0.1:8793/llm/health';
$syncLogPath = dirname(__DIR__) . '/logs/wa-caraka-wordpress-sync.log';

function mas_satset_cmd(string $command): string
{
    $output = shell_exec($command . ' 2>/dev/null');
    return trim((string) $output);
}

function mas_satset_http_json(string $url, ?array $payload = null, int $timeout = 10): array
{
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => $headers,
    ];

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $options[CURLOPT_HTTPHEADER] = $headers;
    }

    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $decoded = is_string($raw) ? json_decode($raw, true) : null;

    return [
        'ok' => $error === '' && $status >= 200 && $status < 300,
        'status' => $status,
        'error' => $error,
        'body' => is_array($decoded) ? $decoded : null,
        'raw' => is_string($raw) ? $raw : '',
    ];
}

function mas_satset_badge(bool $ok, string $okText = 'Sehat', string $badText = 'Perlu cek'): array
{
    return [
        'text' => $ok ? $okText : $badText,
        'class' => $ok ? 'is-ok' : 'is-bad',
    ];
}

function mas_satset_capacity_advice(int $cores, float $memAvailGiB): array
{
    if ($cores >= 8 && $memAvailGiB >= 28) {
        return [
            'primary' => 'Server cukup longgar untuk mencoba model 14B, tetapi tetap lebih aman menjadikan 7B sebagai default dan 14B untuk kebutuhan tertentu.',
            'secondary' => 'Pada mesin ini 14B hanya masuk akal jika latensi masih diterima dan layanan lain tidak sedang padat.',
        ];
    }

    if ($cores >= 4 && $memAvailGiB >= 14) {
        return [
            'primary' => 'Rekomendasi paling seimbang adalah naik ke model 7B. Ini realistis untuk kualitas jawaban yang lebih baik tanpa membebani server terlalu jauh.',
            'secondary' => 'Model 14B tidak saya sarankan sebagai default di server ini karena CPU hanya 4 core dan inferensi akan terasa berat.',
        ];
    }

    return [
        'primary' => 'Tetap gunakan model ringan 3B atau 4B agar respons stabil.',
        'secondary' => 'Prioritaskan knowledge website dan prompt sebelum menaikkan ukuran model.',
    ];
}

$cpuModel = mas_satset_cmd("lscpu | awk -F: '/Model name/{gsub(/^ +/, \"\", \$2); print \$2; exit}'");
$cpuCores = (int) mas_satset_cmd('nproc');
$memTotal = mas_satset_cmd("free -h | awk '/^Mem:/{print \$2}'");
$memAvail = mas_satset_cmd("free -h | awk '/^Mem:/{print \$7}'");
$diskRoot = mas_satset_cmd("df -h / | awk 'NR==2{print \$2 \" total | \" \$4 \" free | use \" \$5}'");
$loadAvg = mas_satset_cmd("cat /proc/loadavg | awk '{print \$1 \" / \" \$2 \" / \" \$3}'");
$ollamaModels = preg_split('/\r\n|\r|\n/', mas_satset_cmd('ollama list | sed -n "2,8p"')) ?: [];
$runtimeHealth = mas_satset_http_json($runtimeHealthUrl, null, 6);
$runtimeLlmHealth = mas_satset_http_json($runtimeLlmHealthUrl, null, 6);
$websiteHealth = mas_satset_http_json($websiteChatUrl, ['message' => 'uji'], 12);
$syncCron = mas_satset_cmd("crontab -l | grep 'wa-caraka-wordpress-sync' || true");
$syncLogMtime = is_file($syncLogPath) ? date('Y-m-d H:i:s', (int) filemtime($syncLogPath)) : '-';
$syncLogSize = is_file($syncLogPath) ? number_format((int) filesize($syncLogPath) / 1024, 1) . ' KB' : '-';
$syncLogTail = is_file($syncLogPath) ? mas_satset_cmd('tail -n 18 ' . escapeshellarg($syncLogPath)) : 'Log sync belum tersedia.';
$memAvailGiB = (float) str_replace(['Gi', 'G', ','], ['', '', '.'], $memAvail);
$capacityAdvice = mas_satset_capacity_advice($cpuCores, $memAvailGiB);
$configuredModel = (string) ($runtimeEnv['LLM_MODEL'] ?? '-');
$llmBaseUrl = (string) ($runtimeEnv['LLM_BASE_URL'] ?? '-');
$runtimeAiMode = (string) (($runtimeLlmHealth['body']['aiDefaultMode'] ?? $runtimeEnv['WA_CARAKA_AI_DEFAULT_MODE'] ?? 'prompt-only'));
$runtimeKnowledgeReady = !empty($runtimeLlmHealth['body']['knowledgeEnabled']);
$runtimeLiveDataReady = !empty($runtimeLlmHealth['body']['dbApiEnabled']) && !empty($runtimeLlmHealth['body']['dbApiConfigured']);
$runtimeSupportedModes = is_array($runtimeLlmHealth['body']['supportedAiModes'] ?? null) ? $runtimeLlmHealth['body']['supportedAiModes'] : [];
$presetPrompts = [
    'Apa syarat cerai gugat?',
    'Bagaimana alur pendaftaran perkara?',
    'Jam layanan PA Semarang bagaimana?',
    'Dokumen apa yang perlu dibawa saat sidang?',
];
$requestedPreset = trim((string) ($_GET['preset'] ?? ''));
$defaultPrompt = in_array($requestedPreset, $presetPrompts, true) ? $requestedPreset : 'Apa syarat cerai gugat?';
$testPrompt = trim((string) ($_POST['test_prompt'] ?? $defaultPrompt));
$testResponse = null;
$opsResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? 'test_prompt'));
    if ($action === 'run_sync_now') {
        $syncCommand = 'php ' . escapeshellarg(dirname(__DIR__) . '/wa-caraka/scripts/sync-wordpress-knowledge.php')
            . ' --base-url=' . escapeshellarg($wordpressBaseUrl)
            . ' --label=wordpress-pa-semarang --priority=140';
        $output = mas_satset_cmd($syncCommand);
        clearstatcache(true, $syncLogPath);
        $syncLogMtime = is_file($syncLogPath) ? date('Y-m-d H:i:s', (int) filemtime($syncLogPath)) : '-';
        $syncLogSize = is_file($syncLogPath) ? number_format((int) filesize($syncLogPath) / 1024, 1) . ' KB' : '-';
        $syncLogTail = is_file($syncLogPath) ? mas_satset_cmd('tail -n 18 ' . escapeshellarg($syncLogPath)) : 'Log sync belum tersedia.';
        $opsResult = [
            'label' => 'Run Sync Now',
            'output' => $output !== '' ? $output : 'Sinkronisasi dijalankan. Cek log untuk detail terbaru.',
        ];
    } elseif ($action === 'check_full_health') {
        $opsResult = [
            'label' => 'Cek Health Lengkap',
            'output' => json_encode([
                'runtime_health' => mas_satset_http_json('http://127.0.0.1:8793/health', null, 6),
                'runtime_ready' => mas_satset_http_json('http://127.0.0.1:8793/ready', null, 6),
                'runtime_db' => mas_satset_http_json('http://127.0.0.1:8793/db/health', null, 6),
                'runtime_llm' => mas_satset_http_json('http://127.0.0.1:8793/llm/health', null, 6),
                'website_chat' => mas_satset_http_json($websiteChatUrl, ['message' => 'uji'], 12),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    } else {
        $testResponse = mas_satset_http_json($websiteChatUrl, ['message' => $testPrompt], 20);
    }
}

$runtimeOk = !empty($runtimeHealth['body']['success']);
$websiteOk = !empty($websiteHealth['body']['success']);
$runtimeBadge = mas_satset_badge($runtimeOk, 'Runtime aktif', 'Runtime bermasalah');
$websiteBadge = mas_satset_badge($websiteOk, 'Chat website aktif', 'Chat website bermasalah');
$syncBadge = mas_satset_badge($syncCron !== '', 'Cron sync aktif', 'Cron sync belum aktif');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dubes Prakom Dashboard</title>
    <style>
        :root {
            --bg: #eef4ef;
            --panel: rgba(255, 255, 255, 0.84);
            --line: rgba(25, 104, 63, 0.14);
            --text: #173726;
            --muted: rgba(23, 55, 38, 0.72);
            --green: #1e8b4f;
            --green-dark: #16693c;
            --red: #b42318;
            --shadow: 0 22px 50px rgba(17, 59, 36, 0.12);
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
        .shell { max-width: 1180px; margin: 0 auto; padding: 28px 18px 44px; }
        .hero { display: grid; grid-template-columns: 1.5fr .9fr; gap: 18px; margin-bottom: 18px; }
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 24px; box-shadow: var(--shadow); backdrop-filter: blur(12px); }
        .hero-main { padding: 24px; }
        .eyebrow { display: inline-flex; padding: 8px 12px; border-radius: 999px; background: rgba(30, 139, 79, 0.10); color: var(--green-dark); font-size: 13px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
        h1 { margin: 14px 0 10px; font-size: 34px; line-height: 1.05; }
        .lead { margin: 0; color: var(--muted); line-height: 1.65; font-size: 15px; }
        .hero-side { padding: 22px; display: grid; gap: 12px; align-content: start; }
        .hero-meta { color: var(--muted); font-size: 13px; line-height: 1.6; }
        .quick-links { display: grid; gap: 10px; }
        .quick-links a { text-decoration: none; color: var(--text); background: rgba(255,255,255,0.70); border: 1px solid var(--line); border-radius: 16px; padding: 12px 14px; transition: transform .2s ease, border-color .2s ease, background .2s ease; }
        .quick-links a:hover { transform: translateY(-1px); border-color: rgba(25, 104, 63, 0.22); background: rgba(255,255,255,0.9); }
        .status-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin-bottom: 18px; }
        .card { padding: 20px; }
        .card h2 { margin: 0 0 14px; font-size: 18px; }
        .metric-list, .detail-list { display: grid; gap: 10px; }
        .metric, .detail { display: flex; justify-content: space-between; gap: 12px; padding: 12px 14px; border-radius: 16px; background: rgba(255,255,255,0.72); border: 1px solid rgba(25, 104, 63, 0.10); }
        .label { color: var(--muted); }
        .value { font-weight: 700; text-align: right; }
        .badge { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; padding: 7px 12px; font-size: 12px; font-weight: 700; }
        .badge.is-ok { background: rgba(30,139,79,0.12); color: var(--green-dark); }
        .badge.is-bad { background: rgba(180,35,24,0.10); color: var(--red); }
        .badge.is-soft { background: rgba(26, 115, 232, 0.10); color: #1557b0; }
        .hint { margin-top: 14px; padding: 14px 16px; border-radius: 18px; background: linear-gradient(180deg, rgba(225,242,232,0.95), rgba(239,248,242,0.92)); border: 1px solid rgba(30,139,79,0.12); line-height: 1.65; }
        .wide { margin-bottom: 18px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
        form { display: grid; gap: 12px; }
        textarea { width: 100%; min-height: 104px; border-radius: 18px; border: 1px solid rgba(25,104,63,0.16); padding: 14px 16px; resize: vertical; font: inherit; background: rgba(255,255,255,0.82); color: var(--text); }
        textarea:focus { outline: none; border-color: rgba(30,139,79,0.34); box-shadow: 0 0 0 4px rgba(30,139,79,0.08); }
        button { width: fit-content; border: 0; border-radius: 14px; padding: 12px 18px; background: linear-gradient(180deg, var(--green), var(--green-dark)); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        pre { margin: 0; white-space: pre-wrap; word-break: break-word; padding: 14px 16px; border-radius: 16px; background: #112219; color: #eefcf2; overflow: auto; }
        .footnote { color: var(--muted); font-size: 13px; line-height: 1.6; }
        .chip-row { display:flex; flex-wrap:wrap; gap:10px; margin: 0 0 14px; }
        .chip {
            display:inline-flex;
            align-items:center;
            min-height:38px;
            padding: 0 14px;
            border-radius:999px;
            text-decoration:none;
            color: var(--green-dark);
            background: rgba(30,139,79,0.08);
            border:1px solid rgba(30,139,79,0.14);
            font-size: 13px;
            font-weight:700;
        }
        .chip:hover { background: rgba(30,139,79,0.12); }
        .ops-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .ops-form { display:block; }
        .ops-button { width:100%; justify-content:center; min-height: 52px; }
        @media (max-width: 900px) {
            .hero, .status-grid, .ops-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="shell">
    <section class="hero">
        <div class="panel hero-main">
            <span class="eyebrow">Lawangsewu Channel</span>
            <h1>Dubes Prakom Dashboard</h1>
            <p class="lead">Pusat pantau internal untuk layanan chat website Mas Satset, runtime WA Caraka, knowledge sync WordPress, dan kelayakan model Ollama di server 9.</p>
            <div class="hero-meta">Login sebagai <strong><?php echo htmlspecialchars((string) ($gatewayUser['full_name'] ?: $gatewayUser['username'] ?? '-')); ?></strong> (<?php echo htmlspecialchars((string) ($gatewayUser['role'] ?? '-')); ?>)</div>
        </div>
        <aside class="panel hero-side">
            <div class="quick-links">
                <a href="<?php echo htmlspecialchars($websiteChatPageUrl); ?>" target="_blank" rel="noopener noreferrer">Buka halaman publik Mas Satset</a>
                <a href="<?php echo htmlspecialchars($wordpressBaseUrl); ?>/wp-json/pa-chat/v1/ask" target="_blank" rel="noopener noreferrer">Buka endpoint website chat</a>
                <a href="<?php echo htmlspecialchars(gateway_wa_admin_sso_url('dashboard')); ?>" target="_blank" rel="noopener noreferrer">Buka WA Caraka Admin</a>
                <a href="<?php echo htmlspecialchars($basePath); ?>/index.php">Kembali ke Gateway</a>
                <a href="<?php echo htmlspecialchars(gateway_logout_url()); ?>">Logout dari Lawangsewu</a>
            </div>
        </aside>
    </section>

    <section class="status-grid">
        <article class="panel card">
            <div class="toolbar">
                <h2>Server 9 Capacity</h2>
                <span class="badge <?php echo htmlspecialchars($cpuCores >= 4 && $memAvailGiB >= 14 ? 'is-ok' : 'is-bad'); ?>"><?php echo htmlspecialchars($cpuCores >= 4 && $memAvailGiB >= 14 ? 'Layak untuk 7B' : 'Tetap model ringan'); ?></span>
            </div>
            <div class="metric-list">
                <div class="metric"><span class="label">CPU</span><span class="value"><?php echo htmlspecialchars($cpuModel !== '' ? $cpuModel : '-'); ?></span></div>
                <div class="metric"><span class="label">Core aktif</span><span class="value"><?php echo htmlspecialchars((string) $cpuCores); ?></span></div>
                <div class="metric"><span class="label">RAM</span><span class="value"><?php echo htmlspecialchars($memTotal . ' total | ' . $memAvail . ' tersedia'); ?></span></div>
                <div class="metric"><span class="label">Disk root</span><span class="value"><?php echo htmlspecialchars($diskRoot !== '' ? $diskRoot : '-'); ?></span></div>
                <div class="metric"><span class="label">Load avg</span><span class="value"><?php echo htmlspecialchars($loadAvg !== '' ? $loadAvg : '-'); ?></span></div>
            </div>
            <div class="hint">
                <strong><?php echo htmlspecialchars($capacityAdvice['primary']); ?></strong><br>
                <span class="footnote"><?php echo htmlspecialchars($capacityAdvice['secondary']); ?></span>
            </div>
        </article>

        <article class="panel card">
            <div class="toolbar">
                <h2>Runtime dan Model</h2>
                <span class="badge <?php echo htmlspecialchars($runtimeBadge['class']); ?>"><?php echo htmlspecialchars($runtimeBadge['text']); ?></span>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin:0 0 14px;">
                <span class="badge is-soft"><?php echo htmlspecialchars('Mode AI: ' . $runtimeAiMode); ?></span>
                <span class="badge <?php echo htmlspecialchars($runtimeKnowledgeReady ? 'is-ok' : 'is-bad'); ?>"><?php echo htmlspecialchars($runtimeKnowledgeReady ? 'Knowledge Ready' : 'Knowledge Off'); ?></span>
                <span class="badge <?php echo htmlspecialchars($runtimeLiveDataReady ? 'is-ok' : 'is-bad'); ?>"><?php echo htmlspecialchars($runtimeLiveDataReady ? 'DB/API Ready' : 'DB/API Not Ready'); ?></span>
            </div>
            <div class="detail-list">
                <div class="detail"><span class="label">Model aktif</span><span class="value"><?php echo htmlspecialchars($configuredModel); ?></span></div>
                <div class="detail"><span class="label">LLM base URL</span><span class="value"><?php echo htmlspecialchars($llmBaseUrl); ?></span></div>
                <div class="detail"><span class="label">Default AI mode</span><span class="value"><?php echo htmlspecialchars($runtimeAiMode); ?></span></div>
                <div class="detail"><span class="label">WhatsApp connected</span><span class="value"><?php echo !empty($runtimeHealth['body']['connected']) ? 'Ya' : 'Tidak'; ?></span></div>
                <div class="detail"><span class="label">Auto reply</span><span class="value"><?php echo !empty($runtimeHealth['body']['autoReplyEnabled']) ? 'Aktif' : 'Nonaktif'; ?></span></div>
                <div class="detail"><span class="label">Nomor runtime</span><span class="value"><?php echo htmlspecialchars((string) ($runtimeHealth['body']['activeRuntimePhoneNumber'] ?? '-')); ?></span></div>
            </div>
            <div class="hint">
                <strong>Model terpasang di Ollama</strong><br>
                <span class="footnote"><?php echo htmlspecialchars($ollamaModels !== [] ? implode(' | ', array_filter(array_map('trim', $ollamaModels))) : 'Belum ada model lain terpasang'); ?></span>
                <?php if ($runtimeSupportedModes !== []) : ?>
                    <br><span class="footnote"><?php echo htmlspecialchars('Supported modes: ' . implode(' | ', array_map('strval', $runtimeSupportedModes))); ?></span>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel card">
            <div class="toolbar">
                <h2>Website Chat Health</h2>
                <span class="badge <?php echo htmlspecialchars($websiteBadge['class']); ?>"><?php echo htmlspecialchars($websiteBadge['text']); ?></span>
            </div>
            <div class="detail-list">
                <div class="detail"><span class="label">Endpoint</span><span class="value"><?php echo htmlspecialchars('HTTP ' . (string) $websiteHealth['status']); ?></span></div>
                <div class="detail"><span class="label">Mode terakhir</span><span class="value"><?php echo htmlspecialchars((string) ($websiteHealth['body']['model'] ?? '-')); ?></span></div>
                <div class="detail"><span class="label">Halaman publik</span><span class="value"><a href="<?php echo htmlspecialchars($websiteChatPageUrl); ?>" target="_blank" rel="noopener noreferrer">Buka</a></span></div>
                <div class="detail"><span class="label">Endpoint REST</span><span class="value"><a href="<?php echo htmlspecialchars($websiteChatUrl); ?>" target="_blank" rel="noopener noreferrer">Buka</a></span></div>
            </div>
            <div class="hint">
                <strong>Tujuan operasional</strong><br>
                <span class="footnote">Website chat diprioritaskan memakai knowledge resmi dulu, baru fallback ke Ollama saat konteks website belum cukup.</span>
            </div>
        </article>

        <article class="panel card">
            <div class="toolbar">
                <h2>Knowledge Sync WordPress</h2>
                <span class="badge <?php echo htmlspecialchars($syncBadge['class']); ?>"><?php echo htmlspecialchars($syncBadge['text']); ?></span>
            </div>
            <div class="detail-list">
                <div class="detail"><span class="label">Sumber website</span><span class="value"><?php echo htmlspecialchars($wordpressBaseUrl); ?></span></div>
                <div class="detail"><span class="label">Cron</span><span class="value"><?php echo htmlspecialchars($syncCron !== '' ? 'Setiap jam menit 15' : '-'); ?></span></div>
                <div class="detail"><span class="label">Log terakhir</span><span class="value"><?php echo htmlspecialchars($syncLogMtime); ?></span></div>
                <div class="detail"><span class="label">Ukuran log</span><span class="value"><?php echo htmlspecialchars($syncLogSize); ?></span></div>
            </div>
            <div class="hint">
                <strong>Perintah sync manual</strong><br>
                <span class="footnote">php /var/www/html/lawangsewu/wa-caraka/scripts/sync-wordpress-knowledge.php --base-url=<?php echo htmlspecialchars($wordpressBaseUrl); ?> --label=wordpress-pa-semarang --priority=140</span>
            </div>
        </article>
    </section>

    <section class="panel card wide">
        <div class="toolbar">
            <h2>Ops Actions</h2>
            <span class="footnote">Aksi operasional cepat untuk tim internal Lawangsewu.</span>
        </div>
        <div class="ops-grid">
            <form method="post" class="ops-form">
                <input type="hidden" name="action" value="run_sync_now">
                <button type="submit" class="ops-button">Run Sync Now</button>
            </form>
            <form method="post" class="ops-form">
                <input type="hidden" name="action" value="check_full_health">
                <button type="submit" class="ops-button">Cek Health Lengkap</button>
            </form>
        </div>
        <?php if ($opsResult !== null) : ?>
            <div style="height:14px"></div>
            <div class="toolbar">
                <h2 style="font-size:15px; margin:0"><?php echo htmlspecialchars((string) ($opsResult['label'] ?? 'Ops Result')); ?></h2>
            </div>
            <pre><?php echo htmlspecialchars((string) ($opsResult['output'] ?? '')); ?></pre>
        <?php endif; ?>
    </section>

    <section class="panel card wide">
        <div class="toolbar">
            <h2>Uji Tanya Cepat Website Mas Satset</h2>
            <span class="footnote">Form ini menguji jalur website chat yang sama seperti pengunjung website.</span>
        </div>
        <div class="chip-row">
            <?php foreach ($presetPrompts as $preset) : ?>
                <a class="chip" href="<?php echo htmlspecialchars(gateway_dubes_prakom_url() . '?preset=' . rawurlencode($preset)); ?>"><?php echo htmlspecialchars($preset); ?></a>
            <?php endforeach; ?>
        </div>
        <form method="post">
            <textarea name="test_prompt" placeholder="Tulis pertanyaan untuk diuji..."><?php echo htmlspecialchars($testPrompt); ?></textarea>
            <button type="submit">Kirim Uji Pertanyaan</button>
        </form>
        <?php if ($testResponse !== null) : ?>
            <div style="height: 14px"></div>
            <div class="toolbar">
                <span class="badge <?php echo htmlspecialchars(!empty($testResponse['body']['success']) ? 'is-ok' : 'is-bad'); ?>"><?php echo htmlspecialchars('HTTP ' . (string) $testResponse['status']); ?></span>
                <span class="footnote">Model: <?php echo htmlspecialchars((string) ($testResponse['body']['model'] ?? '-')); ?></span>
            </div>
            <pre><?php echo htmlspecialchars((string) ($testResponse['body']['text'] ?? $testResponse['raw'] ?? 'Tidak ada respons')); ?></pre>
        <?php endif; ?>
    </section>

    <section class="panel card wide">
        <div class="toolbar">
            <h2>Viewer Log Sinkronisasi</h2>
            <span class="footnote">Cuplikan 18 baris terakhir dari log sync WordPress ke knowledge WA Caraka.</span>
        </div>
        <pre><?php echo htmlspecialchars($syncLogTail !== '' ? $syncLogTail : 'Log sync belum tersedia.'); ?></pre>
    </section>
</div>
</body>
</html>