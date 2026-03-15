<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widget Tidak Ditemukan</title>
</head>
<body>
    <main>
        <h1>Widget Tidak Ditemukan</h1>
        <p>Path <strong><?php echo htmlspecialchars((string) ($path ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong> belum ada di registry staging.</p>
    </main>
</body>
</html>