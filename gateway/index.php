<?php
require __DIR__ . '/bootstrap.php';
$cfg = gateway_config();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cfg['app_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f6f8fa; color: #111827; }
        .wrap { max-width: 960px; margin: 28px auto; background: #fff; border-radius: 10px; padding: 22px; box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        h1 { margin: 0 0 12px; font-size: 24px; }
        .muted { color: #6b7280; font-size: 14px; margin-bottom: 16px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        code { background: #111827; color: #f9fafb; padding: 2px 6px; border-radius: 6px; }
        ul { margin: 8px 0 0 18px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1><?php echo htmlspecialchars($cfg['app_name']); ?></h1>
    <div class="muted">Pintu gerbang API untuk status, koneksi, project workspace, dan deploy lokal.</div>

    <div class="card">
        <strong>Endpoint API</strong>
        <ul>
            <li><code>GET /lawangsewu/gateway/api/status.php</code></li>
            <li><code>GET /lawangsewu/gateway/api/connections.php</code></li>
            <li><code>GET /lawangsewu/gateway/api/projects.php</code></li>
            <li><code>POST /lawangsewu/gateway/api/deploy.php</code></li>
        </ul>
    </div>

    <div class="card">
        <strong>Auth</strong>
        <ul>
            <li>Semua endpoint API butuh token: <code>Authorization: Bearer &lt;TOKEN&gt;</code></li>
            <li>Set token di <code>gateway/.env</code> (variabel <code>GATEWAY_API_TOKEN</code>)</li>
        </ul>
    </div>

    <div class="card">
        <strong>Workspace Project</strong>
        <ul>
            <li>Folder coding utama: <code>/var/www/html/lawangsewu/projects</code></li>
            <li>Deploy lokal: copy dari <code>projects/&lt;nama&gt;</code> ke <code>/var/www/html/&lt;nama&gt;</code></li>
        </ul>
    </div>
</div>
</body>
</html>
