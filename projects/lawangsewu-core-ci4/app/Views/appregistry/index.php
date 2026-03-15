<?php
declare(strict_types=1);

$pageTitle = 'App Registry Staging';
$pageLead = 'Daftar launcher internal dan sibling app yang sudah dipisah dari metadata portal.';
$metaLine = 'Total: ' . (string) (($summary['total'] ?? 0)) . ' · Visible: ' . (string) (($summary['visible'] ?? 0)) . ' · Status: ' . (string) (($summary['statusLabel'] ?? ''));
$navLinks = [
    ['href' => '/portal', 'label' => 'Portal'],
    ['href' => '/walkthrough', 'label' => 'Walkthrough'],
    ['href' => '/daftar-widget', 'label' => 'Daftar Widget'],
];

ob_start();
?>
<section class="panel">
    <h2>Aplikasi</h2>
    <ul class="list">
        <?php foreach (($applications ?? []) as $app): ?>
            <li>
                <strong><?php echo htmlspecialchars((string) ($app['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                <span class="tag"><?php echo htmlspecialchars((string) ($app['kind'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                <p><?php echo htmlspecialchars((string) ($app['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <a class="path" href="/app-registry/launch?app=<?php echo rawurlencode((string) ($app['key'] ?? '')); ?>">Buka launcher</a>
                <p>Target: <?php echo htmlspecialchars((string) ($app['path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php
$content = (string) ob_get_clean();
require dirname(__DIR__) . '/layouts/base.php';