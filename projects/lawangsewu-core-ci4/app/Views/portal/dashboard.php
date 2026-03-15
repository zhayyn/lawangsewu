<?php
declare(strict_types=1);

$pageTitle = 'Portal Staging';
$pageLead = 'Dashboard shell baru yang sekarang sudah mengomposisikan app, docs, dan widget dari registry terpisah.';
$metaLine = (string) ($gatewayUserName ?? 'Pengguna') . ' · ' . (string) ($gatewayUserRole ?? 'USER');
$navLinks = [
    ['href' => '/app-registry', 'label' => 'App Registry'],
    ['href' => '/walkthrough', 'label' => 'Walkthrough'],
    ['href' => '/daftar-widget', 'label' => 'Daftar Widget'],
];

ob_start();
foreach (($sections ?? []) as $section):
?>
    <section class="panel">
        <h2><?php echo htmlspecialchars((string) ($section['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars((string) ($section['caption'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        <ul class="list">
            <?php foreach (($section['items'] ?? []) as $item): ?>
                <li>
                    <strong><?php echo htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="tag"><?php echo htmlspecialchars((string) ($item['tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <p><?php echo htmlspecialchars((string) ($item['desc'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <a class="path" href="/portal/launch?path=<?php echo rawurlencode((string) ($item['path'] ?? '')); ?>">Buka dari portal</a>
                    <p>Target: <?php echo htmlspecialchars((string) ($item['path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php
endforeach;
$content = (string) ob_get_clean();
require dirname(__DIR__) . '/layouts/base.php';