<?php
declare(strict_types=1);

$pageTitle = (string) ($pageTitle ?? 'Lawangsewu Core Staging');
$pageLead = (string) ($pageLead ?? 'Shell staging untuk migrasi CI4 Lawangsewu.');
$metaLine = (string) ($metaLine ?? '');
$content = (string) ($content ?? '');
$navLinks = is_array($navLinks ?? null) ? $navLinks : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        :root {
            --bg: #f4f7fb;
            --paper: #ffffff;
            --line: #d8e0ea;
            --ink: #17324a;
            --muted: #5e7388;
            --accent: #0f6c5b;
            --soft: #eaf4f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: linear-gradient(180deg, #f6faf8 0%, #eef4fb 52%, #f6f7fb 100%);
            color: var(--ink);
            font: 15px/1.6 system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .shell {
            width: min(1120px, calc(100% - 28px));
            margin: 22px auto 44px;
        }
        .hero,
        .panel {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: 0 14px 34px rgba(23, 50, 74, 0.08);
        }
        .hero {
            padding: 22px 24px;
            margin-bottom: 16px;
        }
        .label {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            background: var(--soft);
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        h1, h2, h3 { margin: 0; }
        h1 { margin-top: 12px; font-size: clamp(30px, 4vw, 46px); line-height: 1.05; }
        h2 { font-size: 22px; margin-bottom: 8px; }
        p { margin: 8px 0 0; color: var(--muted); }
        .meta { margin-top: 10px; font-size: 13px; color: var(--muted); }
        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }
        .nav a {
            display: inline-flex;
            padding: 10px 12px;
            border-radius: 12px;
            text-decoration: none;
            background: #fff;
            border: 1px solid var(--line);
            color: var(--ink);
            font-weight: 600;
        }
        .stack {
            display: grid;
            gap: 14px;
        }
        .panel {
            padding: 18px 20px;
        }
        .list {
            list-style: none;
            margin: 14px 0 0;
            padding: 0;
            display: grid;
            gap: 10px;
        }
        .list li {
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px 15px;
            background: #fff;
        }
        .path {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 8px;
            border-radius: 10px;
            background: #f4f8fb;
            border: 1px solid var(--line);
            text-decoration: none;
            color: var(--ink);
            font-weight: 600;
        }
        .tag {
            display: inline-flex;
            margin-left: 8px;
            padding: 4px 8px;
            border-radius: 999px;
            background: var(--soft);
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
        }
        pre {
            overflow: auto;
            padding: 12px;
            border-radius: 14px;
            background: #10273b;
            color: #f4f7fb;
        }
        @media (max-width: 760px) {
            .shell { width: min(100% - 18px, 1120px); margin-top: 14px; }
            .hero, .panel { padding: 16px; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="hero">
            <span class="label">Lawangsewu Core Staging</span>
            <h1><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($pageLead, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if ($metaLine !== ''): ?>
                <div class="meta"><?php echo htmlspecialchars($metaLine, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($navLinks !== []): ?>
                <nav class="nav">
                    <?php foreach ($navLinks as $link): ?>
                        <a href="<?php echo htmlspecialchars((string) ($link['href'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($link['label'] ?? 'Link'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </section>
        <div class="stack"><?php echo $content; ?></div>
    </main>
</body>
</html>