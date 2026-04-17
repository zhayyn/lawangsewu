<?php
require __DIR__ . '/bootstrap.php';
gateway_require_roles(['superadmin', 'admin']);

$cfg = gateway_config();
$user = gateway_auth_user();
$rows = gateway_sso_service_map();

function sso_mapping_absolute_url(string $url): string
{
    if ($url === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $url) === 1) {
        return $url;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1');
    return $scheme . '://' . $host . '/' . ltrim($url, '/');
}

function sso_mapping_probe(string $url): array
{
    if ($url === '') {
        return ['ok' => false, 'status' => 0, 'location' => '', 'error' => 'URL kosong'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT => 'Lawangsewu-SSO-Mapping-Checker/1.0',
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $location = '';
    if (is_string($raw) && preg_match('/^Location:\s*(.+)$/mi', $raw, $m) === 1) {
        $location = trim($m[1]);
    }

    $ok = $error === '' && $status >= 200 && $status < 400;
    return [
        'ok' => $ok,
        'status' => $status,
        'location' => $location,
        'error' => $error,
    ];
}

$runChecks = isset($_GET['check']) && $_GET['check'] === '1';
$checks = [];
if ($runChecks) {
    foreach ($rows as $idx => $row) {
        $loginUrl = sso_mapping_absolute_url((string) ($row['login_url'] ?? ''));
        $accessUrl = sso_mapping_absolute_url((string) ($row['access_url'] ?? ''));
        $checks[$idx] = [
            'login' => sso_mapping_probe($loginUrl),
            'access' => sso_mapping_probe($accessUrl),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapping SSO Lawangsewu</title>
    <style>
        :root {
            --bg: #eef4ef;
            --panel: rgba(255,255,255,0.88);
            --line: rgba(25,104,63,0.14);
            --text: #173726;
            --muted: rgba(23,55,38,0.72);
            --green: #1e8b4f;
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
        .wrap { max-width: 1120px; margin: 0 auto; padding: 26px 18px 40px; }
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 22px; padding: 20px; }
        .top { display: flex; justify-content: space-between; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 12px; }
        .top a { text-decoration: none; color: #17643b; font-weight: 700; }
        h1 { margin: 6px 0 10px; }
        .muted { color: var(--muted); line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #e4ece6; padding: 10px 8px; text-align: left; vertical-align: top; }
        th { font-size: 12px; text-transform: uppercase; color: #37624b; }
        code { background: #102217; color: #edfff3; padding: 2px 6px; border-radius: 6px; }
        .btn { display: inline-block; text-decoration: none; border-radius: 10px; padding: 8px 10px; background: var(--green); color: #fff; font-weight: 700; font-size: 12px; }
        .btn.secondary { background: #2e5f44; }
        .status.ok { color: #11643a; font-weight: 700; }
        .status.bad { color: #9b1d1d; font-weight: 700; }
        .small { font-size: 12px; color: var(--muted); }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div>Login sebagai <strong><?php echo htmlspecialchars((string) ($user['full_name'] ?: $user['username'] ?? '-')); ?></strong></div>
        <div>
            <a href="<?php echo htmlspecialchars(gateway_ui_url('index')); ?>">Portal Utama</a>
            &nbsp;|&nbsp;
            <a href="<?php echo htmlspecialchars(gateway_logout_url()); ?>">Logout</a>
        </div>
    </div>

    <div class="panel">
        <h1>Mapping SSO Lawangsewu</h1>
        <p class="muted">Semua layanan menggunakan satu pintu autentikasi di <code><?php echo htmlspecialchars(gateway_login_url()); ?></code>. Halaman utama setelah login tetap di portal Lawangsewu.</p>
        <p>
            <a class="btn secondary" href="?check=1">Test Semua Endpoint Mapping</a>
            <?php if ($runChecks): ?>
                <span class="small">Hasil pengecekan ditampilkan di kolom Status Login/Akses.</span>
            <?php endif; ?>
        </p>

        <table>
            <thead>
            <tr>
                <th>Layanan</th>
                <th>Login</th>
                <th>Akses</th>
                <th>Keterangan</th>
                <th>Status Login</th>
                <th>Status Akses</th>
                <th>Uji</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $idx => $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string) ($row['service'] ?? '-')); ?></td>
                    <td><code><?php echo htmlspecialchars((string) ($row['login_url'] ?? '-')); ?></code></td>
                    <td><code><?php echo htmlspecialchars((string) ($row['access_url'] ?? '-')); ?></code></td>
                    <td><?php echo htmlspecialchars((string) ($row['notes'] ?? '-')); ?></td>
                    <td>
                        <?php if (!$runChecks): ?>
                            <span class="small">Belum diuji</span>
                        <?php else: $loginCheck = $checks[$idx]['login'] ?? ['ok' => false, 'status' => 0, 'location' => '', 'error' => '']; ?>
                            <div class="status <?php echo !empty($loginCheck['ok']) ? 'ok' : 'bad'; ?>">HTTP <?php echo htmlspecialchars((string) ($loginCheck['status'] ?? 0)); ?></div>
                            <?php if (!empty($loginCheck['location'])): ?><div class="small">Location: <?php echo htmlspecialchars((string) $loginCheck['location']); ?></div><?php endif; ?>
                            <?php if (!empty($loginCheck['error'])): ?><div class="small">Error: <?php echo htmlspecialchars((string) $loginCheck['error']); ?></div><?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$runChecks): ?>
                            <span class="small">Belum diuji</span>
                        <?php else: $accessCheck = $checks[$idx]['access'] ?? ['ok' => false, 'status' => 0, 'location' => '', 'error' => '']; ?>
                            <div class="status <?php echo !empty($accessCheck['ok']) ? 'ok' : 'bad'; ?>">HTTP <?php echo htmlspecialchars((string) ($accessCheck['status'] ?? 0)); ?></div>
                            <?php if (!empty($accessCheck['location'])): ?><div class="small">Location: <?php echo htmlspecialchars((string) $accessCheck['location']); ?></div><?php endif; ?>
                            <?php if (!empty($accessCheck['error'])): ?><div class="small">Error: <?php echo htmlspecialchars((string) $accessCheck['error']); ?></div><?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><a class="btn" href="<?php echo htmlspecialchars((string) ($row['access_url'] ?? '#')); ?>" target="_blank" rel="noopener noreferrer">Buka</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
