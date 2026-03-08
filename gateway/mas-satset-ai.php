<?php
require __DIR__ . '/bootstrap.php';
gateway_require_login();

$cfg = gateway_config();
$gatewayUser = gateway_auth_user();
$portalUrl = gateway_ui_url('index');
$dubesPrakomUrl = gateway_dubes_prakom_url();
$logoutUrl = gateway_logout_url();
$wordpressBaseUrl = 'http://192.168.88.9/pasemarang';
$websiteChatUrl = $wordpressBaseUrl . '/wp-json/pa-chat/v1/ask';
$websiteChatPageUrl = $wordpressBaseUrl . '/layanan-bantuan-mas-satset/';

function mas_satset_lab_http_json(string $url, ?array $payload = null, int $timeout = 12): array
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

    $body = is_string($raw) ? json_decode($raw, true) : null;

    return [
        'ok' => $error === '' && $status >= 200 && $status < 300,
        'status' => $status,
        'error' => $error,
        'body' => is_array($body) ? $body : null,
        'raw' => is_string($raw) ? $raw : '',
    ];
}

function mas_satset_lab_fetch_stats(PDO $pdo): array
{
    $stats = [
        'total' => 0,
        'active' => 0,
        'inactive' => 0,
    ];

    $row = $pdo->query('SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active, SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive FROM wacaraka_faq')->fetch();
    if (is_array($row)) {
        $stats['total'] = (int) ($row['total'] ?? 0);
        $stats['active'] = (int) ($row['active'] ?? 0);
        $stats['inactive'] = (int) ($row['inactive'] ?? 0);
    }

    return $stats;
}

$knowledgeForm = [
    'question' => trim((string) ($_POST['question'] ?? '')),
    'answer' => trim((string) ($_POST['answer'] ?? '')),
    'tags' => trim((string) ($_POST['tags'] ?? 'manual,mas-satset-portal')),
    'priority' => trim((string) ($_POST['priority'] ?? '150')),
    'is_active' => isset($_POST['is_active']) ? '1' : '0',
];
$testPrompt = trim((string) ($_POST['test_prompt'] ?? 'Apa saja layanan dan informasi yang bisa saya tanyakan?'));
$testResponse = null;
$formError = null;
$searchQuery = trim((string) ($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? 'test_chat'));

    if ($action === 'save_knowledge') {
        if ($knowledgeForm['question'] === '' || $knowledgeForm['answer'] === '') {
            $formError = 'Judul knowledge dan isi knowledge wajib diisi.';
        } else {
            $priority = is_numeric($knowledgeForm['priority']) ? (int) $knowledgeForm['priority'] : 150;
            $tags = preg_replace('/\s*,\s*/', ',', $knowledgeForm['tags']) ?? $knowledgeForm['tags'];
            try {
                $stmt = gateway_admin_pdo()->prepare('INSERT INTO wacaraka_faq (question, answer, tags, priority, is_active, created_at, updated_at) VALUES (:question, :answer, :tags, :priority, :is_active, NOW(), NOW())');
                $stmt->execute([
                    ':question' => $knowledgeForm['question'],
                    ':answer' => $knowledgeForm['answer'],
                    ':tags' => $tags !== '' ? $tags : null,
                    ':priority' => $priority,
                    ':is_active' => $knowledgeForm['is_active'] === '1' ? 1 : 0,
                ]);

                gateway_flash_set('success', 'Knowledge baru Mas Satset berhasil disimpan ke basis jawaban AI.');
                header('Location: ' . gateway_mas_satset_url());
                exit;
            } catch (Throwable $e) {
                $formError = 'Knowledge belum bisa disimpan ke database. Periksa koneksi atau schema knowledge.';
            }
        }
    } else {
        $testResponse = mas_satset_lab_http_json($websiteChatUrl, ['message' => $testPrompt], 20);
    }
}

$successMessage = gateway_flash_get('success');
$pdo = gateway_admin_pdo();
$stats = mas_satset_lab_fetch_stats($pdo);
$chatHealth = mas_satset_lab_http_json($websiteChatUrl, ['message' => 'uji cepat mas satset'], 12);

$whereSql = '';
$params = [];
if ($searchQuery !== '') {
    $whereSql = 'WHERE question LIKE :search OR answer LIKE :search OR tags LIKE :search';
    $params[':search'] = '%' . $searchQuery . '%';
}

$stmt = $pdo->prepare("SELECT id, question, answer, tags, priority, is_active, updated_at FROM wacaraka_faq {$whereSql} ORDER BY priority DESC, updated_at DESC LIMIT 24");
$stmt->execute($params);
$knowledgeItems = $stmt->fetchAll();
if (!is_array($knowledgeItems)) {
    $knowledgeItems = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mas Satset Lab</title>
    <style>
        :root {
            --bg: #f4f7f1;
            --panel: rgba(255,255,255,0.88);
            --line: rgba(38, 93, 56, 0.12);
            --text: #173726;
            --muted: rgba(23,55,38,0.7);
            --green: #1f8d51;
            --green-deep: #13673a;
            --green-soft: rgba(31, 141, 81, 0.1);
            --amber: #8a5a00;
            --red: #b42318;
            --shadow: 0 24px 60px rgba(15, 57, 33, 0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Trebuchet MS", Verdana, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(113, 204, 146, 0.18), transparent 26%),
                radial-gradient(circle at right, rgba(58, 148, 102, 0.12), transparent 24%),
                linear-gradient(180deg, #fafcf8 0%, var(--bg) 100%);
        }
        a { color: inherit; }
        .shell { max-width: 1220px; margin: 0 auto; padding: 28px 18px 42px; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
        .topbar-nav { display:flex; gap:10px; flex-wrap:wrap; }
        .topbar-nav a { text-decoration:none; font-weight:700; color:var(--green-deep); padding:10px 14px; border-radius:999px; background:rgba(255,255,255,0.78); border:1px solid var(--line); }
        .hero { display:grid; grid-template-columns: 1.35fr .95fr; gap:18px; margin-bottom:18px; }
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 26px; box-shadow: var(--shadow); backdrop-filter: blur(12px); }
        .hero-main, .hero-side, .card { padding: 24px; }
        .eyebrow { display:inline-flex; padding:8px 12px; border-radius:999px; background:var(--green-soft); color:var(--green-deep); font-size:12px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
        h1 { margin:14px 0 10px; font-size:34px; line-height:1.05; }
        h2 { margin:0 0 12px; font-size:20px; }
        .lead { margin:0; line-height:1.7; color:var(--muted); font-size:15px; }
        .hero-side { display:grid; gap:12px; align-content:start; }
        .hero-link { text-decoration:none; display:block; padding:16px 18px; border-radius:18px; background:rgba(255,255,255,0.76); border:1px solid var(--line); transition:transform .22s ease, border-color .22s ease, background .22s ease; }
        .hero-link:hover { transform:translateY(-2px); border-color:rgba(19,103,58,0.24); background:rgba(255,255,255,0.96); }
        .hero-link strong { display:block; margin-bottom:6px; font-size:17px; }
        .hero-link span { color:var(--muted); font-size:13px; line-height:1.6; }
        .meta-grid, .content-grid, .stat-grid { display:grid; gap:18px; }
        .meta-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom:18px; }
        .meta-box { padding:16px 18px; border-radius:18px; background:rgba(255,255,255,0.72); border:1px solid rgba(25,104,63,0.10); }
        .meta-label { font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); }
        .meta-value { margin-top:6px; font-size:18px; font-weight:700; }
        .content-grid { grid-template-columns: 1.08fr .92fr; }
        .stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top:14px; }
        .stat-box { padding:14px 16px; border-radius:18px; background:rgba(255,255,255,0.78); border:1px solid var(--line); }
        .stat-box strong { display:block; font-size:22px; margin-top:4px; }
        .muted { color:var(--muted); line-height:1.65; font-size:14px; }
        .alert { padding:14px 16px; border-radius:16px; margin-top:14px; font-size:14px; line-height:1.6; }
        .alert.success { background:rgba(31,141,81,0.11); color:var(--green-deep); border:1px solid rgba(31,141,81,0.18); }
        .alert.error { background:rgba(180,35,24,0.10); color:var(--red); border:1px solid rgba(180,35,24,0.18); }
        .field-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px; }
        label { display:grid; gap:7px; font-size:13px; font-weight:700; color:var(--green-deep); }
        input[type="text"], input[type="number"], textarea {
            width:100%; border-radius:16px; border:1px solid rgba(25,104,63,0.16); padding:13px 14px; font:inherit; color:var(--text); background:rgba(255,255,255,0.86);
        }
        textarea { min-height:150px; resize:vertical; }
        input:focus, textarea:focus { outline:none; border-color:rgba(31,141,81,0.36); box-shadow:0 0 0 4px rgba(31,141,81,0.08); }
        .actions { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        button { border:0; border-radius:16px; padding:12px 18px; background:linear-gradient(180deg, var(--green), var(--green-deep)); color:#fff; font:inherit; font-weight:700; cursor:pointer; }
        .quick-prompt-row { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
        .chip { display:inline-flex; align-items:center; min-height:38px; padding:0 14px; border-radius:999px; text-decoration:none; color:var(--green-deep); background:var(--green-soft); border:1px solid rgba(31,141,81,0.15); font-size:13px; font-weight:700; }
        .badge { display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; font-size:12px; font-weight:700; }
        .badge.ok { color:var(--green-deep); background:rgba(31,141,81,0.12); }
        .badge.warn { color:var(--amber); background:rgba(201,145,0,0.12); }
        .result-box, .knowledge-list { margin-top:14px; }
        pre { margin:0; padding:16px; border-radius:18px; background:#102218; color:#eefcf2; white-space:pre-wrap; word-break:break-word; overflow:auto; }
        .knowledge-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .knowledge-search { display:flex; gap:10px; flex-wrap:wrap; width:100%; }
        .knowledge-search input { flex:1 1 260px; }
        .knowledge-items { display:grid; gap:12px; }
        .knowledge-item { padding:18px; border-radius:20px; border:1px solid var(--line); background:rgba(255,255,255,0.78); }
        .knowledge-item-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:8px; }
        .knowledge-item h3 { margin:0; font-size:17px; }
        .knowledge-meta { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
        .knowledge-meta span { padding:6px 10px; border-radius:999px; background:rgba(31,141,81,0.10); color:var(--green-deep); font-size:12px; font-weight:700; }
        .footnote { color:var(--muted); font-size:13px; line-height:1.6; }
        @media (max-width: 920px) {
            .hero, .content-grid, .meta-grid, .stat-grid, .field-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="topbar">
        <div>Login sebagai <strong><?php echo htmlspecialchars((string) ($gatewayUser['full_name'] ?: $gatewayUser['username'] ?? '-')); ?></strong></div>
        <div class="topbar-nav">
            <a href="<?php echo htmlspecialchars($portalUrl); ?>">Portal Lawangsewu</a>
            <a href="<?php echo htmlspecialchars($dubesPrakomUrl); ?>">Dubes Prakom Ops</a>
            <a href="<?php echo htmlspecialchars($logoutUrl); ?>">Logout</a>
        </div>
    </div>

    <section class="hero">
        <div class="panel hero-main">
            <span class="eyebrow">Mas Satset Lab</span>
            <h1>Landing Page Uji Cepat dan Knowledge</h1>
            <p class="lead">Halaman ini dipakai tim Lawangsewu untuk menguji tanya jawab website Mas Satset dan menambah knowledge baru yang akan diprioritaskan sebagai dasar jawaban AI sebelum fallback ke Ollama.</p>
            <div class="stat-grid">
                <div class="stat-box"><span class="muted">Total knowledge</span><strong><?php echo htmlspecialchars((string) $stats['total']); ?></strong></div>
                <div class="stat-box"><span class="muted">Knowledge aktif</span><strong><?php echo htmlspecialchars((string) $stats['active']); ?></strong></div>
                <div class="stat-box"><span class="muted">Knowledge nonaktif</span><strong><?php echo htmlspecialchars((string) $stats['inactive']); ?></strong></div>
            </div>
        </div>
        <aside class="panel hero-side">
            <a class="hero-link" href="<?php echo htmlspecialchars($websiteChatPageUrl); ?>" target="_blank" rel="noopener noreferrer">
                <strong>Buka Halaman Publik Mas Satset</strong>
                <span>Untuk melihat pengalaman pengunjung website seperti di produksi.</span>
            </a>
            <a class="hero-link" href="<?php echo htmlspecialchars($websiteChatUrl); ?>" target="_blank" rel="noopener noreferrer">
                <strong>Buka Endpoint Website Chat</strong>
                <span>Endpoint ini dipakai oleh landing page untuk uji pertanyaan cepat.</span>
            </a>
            <div class="hero-link">
                <strong>Status Chat Website</strong>
                <span><span class="badge <?php echo !empty($chatHealth['body']['success']) ? 'ok' : 'warn'; ?>"><?php echo htmlspecialchars('HTTP ' . (string) $chatHealth['status']); ?></span> <?php echo htmlspecialchars((string) ($chatHealth['body']['model'] ?? ($chatHealth['error'] !== '' ? $chatHealth['error'] : 'belum ada model terdeteksi'))); ?></span>
            </div>
        </aside>
    </section>

    <section class="meta-grid">
        <div class="meta-box">
            <div class="meta-label">Prioritas Jawaban</div>
            <div class="meta-value">Knowledge dulu</div>
        </div>
        <div class="meta-box">
            <div class="meta-label">Fallback</div>
            <div class="meta-value">Ollama / LLM</div>
        </div>
        <div class="meta-box">
            <div class="meta-label">Basis Tabel</div>
            <div class="meta-value">wacaraka_faq</div>
        </div>
        <div class="meta-box">
            <div class="meta-label">Kanal</div>
            <div class="meta-value">Mas Satset Website</div>
        </div>
    </section>

    <section class="content-grid">
        <article class="panel card">
            <h2>Uji Tanya Jawab Cepat</h2>
            <p class="muted">Gunakan form ini untuk menguji pertanyaan yang sama dengan yang dipakai pengunjung di website. Hasil dari knowledge basis dan fallback model akan terlihat di sini.</p>
            <div class="quick-prompt-row">
                <?php foreach ([
                    'Apa saja layanan dan informasi yang bisa saya tanyakan?',
                    'Bagaimana syarat cerai gugat?',
                    'Jam layanan PA Semarang bagaimana?',
                    'Bagaimana cara cek status perkara?',
                ] as $promptPreset) : ?>
                    <button type="button" class="chip" onclick="document.getElementById('test_prompt').value = <?php echo htmlspecialchars(json_encode($promptPreset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>;"><?php echo htmlspecialchars($promptPreset); ?></button>
                <?php endforeach; ?>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="test_chat">
                <label>
                    Pertanyaan uji
                    <textarea id="test_prompt" name="test_prompt" placeholder="Tulis pertanyaan untuk Mas Satset..."><?php echo htmlspecialchars($testPrompt); ?></textarea>
                </label>
                <div class="actions">
                    <button type="submit">Kirim Uji Pertanyaan</button>
                </div>
            </form>
            <?php if ($testResponse !== null) : ?>
                <div class="result-box">
                    <div class="actions" style="margin-bottom:10px;">
                        <span class="badge <?php echo !empty($testResponse['body']['success']) ? 'ok' : 'warn'; ?>"><?php echo htmlspecialchars('HTTP ' . (string) $testResponse['status']); ?></span>
                        <span class="footnote">Mode: <?php echo htmlspecialchars((string) ($testResponse['body']['model'] ?? '-')); ?></span>
                    </div>
                    <pre><?php echo htmlspecialchars((string) ($testResponse['body']['text'] ?? $testResponse['raw'] ?? 'Tidak ada respons')); ?></pre>
                </div>
            <?php endif; ?>
        </article>

        <article class="panel card">
            <h2>Tambah Knowledge Baru</h2>
            <p class="muted">Knowledge yang disimpan di sini akan masuk ke basis jawaban Mas Satset. Saat pertanyaan cocok, sistem akan memprioritaskan knowledge ini sebelum meminta jawaban generatif dari model.</p>
            <?php if ($successMessage !== null) : ?>
                <div class="alert success"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            <?php if ($formError !== null) : ?>
                <div class="alert error"><?php echo htmlspecialchars($formError); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="action" value="save_knowledge">
                <label>
                    Judul knowledge / pertanyaan utama
                    <input type="text" name="question" value="<?php echo htmlspecialchars($knowledgeForm['question']); ?>" placeholder="Contoh: Bagaimana syarat cerai gugat?">
                </label>
                <label>
                    Isi knowledge / jawaban dasar
                    <textarea name="answer" placeholder="Tulis jawaban yang akan dijadikan basis tanya jawab Mas Satset."><?php echo htmlspecialchars($knowledgeForm['answer']); ?></textarea>
                </label>
                <div class="field-grid">
                    <label>
                        Tags
                        <input type="text" name="tags" value="<?php echo htmlspecialchars($knowledgeForm['tags']); ?>" placeholder="Contoh: manual,mas-satset-portal,layanan">
                    </label>
                    <label>
                        Priority
                        <input type="number" min="0" max="999" name="priority" value="<?php echo htmlspecialchars($knowledgeForm['priority']); ?>">
                    </label>
                </div>
                <label style="display:flex; align-items:center; gap:10px; font-weight:700;">
                    <input type="checkbox" name="is_active" value="1" <?php echo $knowledgeForm['is_active'] === '1' ? 'checked' : ''; ?>>
                    Aktifkan knowledge ini segera
                </label>
                <div class="actions">
                    <button type="submit">Simpan ke Basis Knowledge</button>
                </div>
                <div class="footnote">Rekomendasi: gunakan bahasa formal, ringkas, dan langsung ke inti. Jika knowledge diambil dari aturan atau halaman resmi, masukkan konteks pentingnya di isi jawaban.</div>
            </form>
        </article>
    </section>

    <section class="panel card" style="margin-top:18px;">
        <div class="knowledge-toolbar">
            <div>
                <h2>Daftar Knowledge Mas Satset</h2>
                <div class="muted">Menampilkan 24 knowledge terbaru berdasarkan prioritas dan waktu pembaruan.</div>
            </div>
            <form method="get" class="knowledge-search">
                <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Cari berdasarkan judul, isi, atau tags...">
                <button type="submit">Cari</button>
                <?php if ($searchQuery !== '') : ?>
                    <a class="chip" href="<?php echo htmlspecialchars(gateway_mas_satset_url()); ?>">Reset</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="knowledge-items">
            <?php if ($knowledgeItems === []) : ?>
                <div class="knowledge-item">
                    <div class="muted">Belum ada knowledge yang cocok dengan pencarian ini.</div>
                </div>
            <?php else : ?>
                <?php foreach ($knowledgeItems as $item) : ?>
                    <article class="knowledge-item">
                        <div class="knowledge-item-head">
                            <div>
                                <h3><?php echo htmlspecialchars((string) ($item['question'] ?? '-')); ?></h3>
                                <div class="footnote">Diperbarui <?php echo htmlspecialchars((string) ($item['updated_at'] ?? '-')); ?></div>
                            </div>
                            <span class="badge <?php echo (int) ($item['is_active'] ?? 0) === 1 ? 'ok' : 'warn'; ?>"><?php echo (int) ($item['is_active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif'; ?></span>
                        </div>
                        <div class="muted"><?php echo nl2br(htmlspecialchars((string) ($item['answer'] ?? ''))); ?></div>
                        <div class="knowledge-meta">
                            <span>Priority <?php echo htmlspecialchars((string) ($item['priority'] ?? '0')); ?></span>
                            <?php if (trim((string) ($item['tags'] ?? '')) !== '') : ?>
                                <span><?php echo htmlspecialchars((string) $item['tags']); ?></span>
                            <?php endif; ?>
                            <span>ID <?php echo htmlspecialchars((string) ($item['id'] ?? '-')); ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
</body>
</html>
