<?php
require __DIR__ . '/bootstrap.php';

$requestedReturn = gateway_normalize_return_path((string) ($_REQUEST['return'] ?? ''), gateway_ui_url('index'));

if (gateway_is_logged_in()) {
    header('Location: ' . $requestedReturn);
    exit;
}

$error = gateway_flash_get('auth_error');
$submittedUsername = '';
$legacyLoginNotice = isset($_GET['legacy_wa_login']) && $_GET['legacy_wa_login'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedUsername = trim((string) ($_POST['username'] ?? ''));
    $result = gateway_attempt_login($submittedUsername, (string) ($_POST['password'] ?? ''));
    if (!empty($result['ok'])) {
        header('Location: ' . $requestedReturn);
        exit;
    }

    $error = (string) ($result['message'] ?? 'Login gagal.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login</title>
    <style>
        :root {
            --bg: #040c1d;
            --panel: rgba(5,15,33,0.94);
            --line: rgba(126,252,255,0.14);
            --text: #dff6ff;
            --muted: rgba(143, 186, 215, 0.82);
            --accent: #11dcff;
            --accent-2: #7df9ff;
            --shadow: 0 22px 50px rgba(0,0,0,0.28);
            --danger-bg: rgba(255,94,103,0.08);
            --danger-line: rgba(255,94,103,0.18);
            --danger-text: #ffc4ca;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", Verdana, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(61, 215, 255, 0.14), transparent 28%),
                radial-gradient(circle at right, rgba(61, 215, 255, 0.08), transparent 24%),
                linear-gradient(180deg, #01040d 0%, var(--bg) 100%);
            display: grid;
            place-items: center;
            padding: 20px;
        }
        .shell {
            width: min(380px, 100%);
            border-radius: 28px;
            background: var(--panel);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
            padding: 24px;
        }
        .mark {
            margin: 0 0 20px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            text-align: center;
            color: var(--accent-2);
        }
        .error {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 16px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
            color: var(--danger-text);
        }
        .warn {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(255, 122, 0, 0.09);
            border: 1px solid rgba(255, 122, 0, 0.22);
            color: #ffc98a;
        }
        form { display: grid; gap: 14px; }
        label { display: none; }
        input {
            width: 100%;
            min-height: 54px;
            border-radius: 18px;
            border: 1px solid rgba(126,252,255,0.14);
            background: rgba(9,22,45,0.88);
            padding: 0 14px;
            font: inherit;
            color: var(--text);
        }
        input::placeholder { color: var(--muted); }
        input:focus { outline: none; border-color: rgba(126,252,255,0.34); box-shadow: 0 0 0 4px rgba(17,220,255,0.08); }
        button {
            min-height: 54px;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            color: #03101c;
            font: inherit;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            cursor: pointer;
        }
        .footnote { margin-top: 14px; color: var(--muted); font-size: 12px; line-height: 1.6; text-align: center; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="mark">Portal Login</div>
            <?php if ($legacyLoginNotice) : ?>
                <div class="warn">URL login lama WA Caraka sudah dinonaktifkan. Gunakan portal Lawangsewu ini sebagai satu-satunya pintu login (SSO).</div>
            <?php endif; ?>
            <?php if ($error !== null && $error !== '') : ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="<?php echo htmlspecialchars(gateway_ui_url('login')); ?>">
                <input type="hidden" name="return" value="<?php echo htmlspecialchars($requestedReturn); ?>">
                <input type="text" name="username" value="<?php echo htmlspecialchars($submittedUsername); ?>" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Masuk</button>
            </form>
            <div class="footnote"><?php echo htmlspecialchars(gateway_ui_url('login')); ?></div>
    </div>
</body>
</html>