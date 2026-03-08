<?php
require __DIR__ . '/bootstrap.php';

if (gateway_is_logged_in()) {
    header('Location: ' . gateway_dubes_prakom_url());
    exit;
}

$error = gateway_flash_get('auth_error');
$submittedUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedUsername = trim((string) ($_POST['username'] ?? ''));
    $result = gateway_attempt_login($submittedUsername, (string) ($_POST['password'] ?? ''));
    if (!empty($result['ok'])) {
        header('Location: ' . gateway_dubes_prakom_url());
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
    <title>Login Dubes Prakom</title>
    <style>
        :root {
            --bg: #eef4ef;
            --panel: rgba(255,255,255,0.84);
            --line: rgba(25,104,63,0.14);
            --text: #173726;
            --muted: rgba(23,55,38,0.72);
            --green: #1e8b4f;
            --green-dark: #16693c;
            --shadow: 0 22px 50px rgba(17,59,36,0.12);
            --danger-bg: rgba(180,35,24,0.08);
            --danger-line: rgba(180,35,24,0.18);
            --danger-text: #992018;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", Verdana, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(113, 204, 146, 0.18), transparent 28%),
                radial-gradient(circle at right, rgba(83, 179, 122, 0.12), transparent 24%),
                linear-gradient(180deg, #f7fbf7 0%, var(--bg) 100%);
            display: grid;
            place-items: center;
            padding: 20px;
        }
        .shell {
            width: min(980px, 100%);
            display: grid;
            grid-template-columns: 1.15fr 0.95fr;
            border-radius: 28px;
            overflow: hidden;
            background: var(--panel);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
            backdrop-filter: blur(12px);
        }
        .brand {
            padding: 34px;
            background: linear-gradient(180deg, rgba(30,139,79,0.96), rgba(17,92,51,0.96));
            color: #fff;
        }
        .brand .eyebrow {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.14);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .brand h1 { margin: 18px 0 10px; font-size: 34px; line-height: 1.05; }
        .brand p { margin: 0; line-height: 1.7; color: rgba(255,255,255,0.88); }
        .info {
            margin-top: 24px;
            padding: 18px;
            border-radius: 20px;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.14);
        }
        .info strong { display: block; margin-bottom: 8px; }
        .form-wrap { padding: 34px; }
        .form-wrap h2 { margin: 0 0 8px; font-size: 28px; }
        .lead { margin: 0 0 24px; color: var(--muted); line-height: 1.65; }
        .error {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 16px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-line);
            color: var(--danger-text);
        }
        form { display: grid; gap: 14px; }
        label { display: grid; gap: 8px; font-size: 14px; font-weight: 700; }
        input {
            width: 100%;
            min-height: 50px;
            border-radius: 16px;
            border: 1px solid rgba(25,104,63,0.16);
            background: rgba(255,255,255,0.82);
            padding: 0 14px;
            font: inherit;
            color: var(--text);
        }
        input:focus { outline: none; border-color: rgba(30,139,79,0.34); box-shadow: 0 0 0 4px rgba(30,139,79,0.08); }
        button {
            min-height: 50px;
            border: 0;
            border-radius: 16px;
            background: linear-gradient(180deg, var(--green), var(--green-dark));
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        .footnote { margin-top: 16px; color: var(--muted); font-size: 13px; line-height: 1.6; }
        @media (max-width: 860px) {
            .shell { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="brand">
            <span class="eyebrow">Lawangsewu Channel</span>
            <h1>Dubes Prakom</h1>
            <p>Masuk ke kanal Lawangsewu dengan akun admin yang sama seperti WA Caraka. Background dan nuansa visual dashboard tetap dipertahankan agar konsisten, modern, dan bersih.</p>
            <div class="info">
                <strong>Kredensial</strong>
                <div>Username dan password mengikuti tabel user admin WA Caraka.</div>
                <div>Setelah login, Anda akan diarahkan ke dashboard Lawangsewu.</div>
            </div>
        </section>
        <section class="form-wrap">
            <h2>Masuk</h2>
            <p class="lead">Gunakan akun WA Caraka yang sudah aktif. Tidak perlu akun terpisah untuk Lawangsewu.</p>
            <?php if ($error !== null && $error !== '') : ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="<?php echo htmlspecialchars(gateway_ui_url('login')); ?>">
                <label>
                    Username
                    <input type="text" name="username" value="<?php echo htmlspecialchars($submittedUsername); ?>" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required>
                </label>
                <button type="submit">Login ke Dubes Prakom</button>
            </form>
            <div class="footnote">URL login: <?php echo htmlspecialchars(gateway_ui_url('login')); ?></div>
        </section>
    </div>
</body>
</html>