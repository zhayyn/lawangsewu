<?php
declare(strict_types=1);

$pageTitle = (string) (($item['name'] ?? 'Widget') . ' | Staging');
$pageLead = (string) ($item['desc'] ?? 'Representasi route kanonik widget di shell staging CI4.');
$metaLine = 'Path: ' . (string) ($item['path'] ?? '') . ' · Kategori: ' . (string) ($item['category'] ?? '') . ' · Jenis: ' . (string) ($item['kind'] ?? '');
$navLinks = [
    ['href' => '/daftar-widget', 'label' => 'Kembali ke Daftar Widget'],
    ['href' => '/walkthrough', 'label' => 'Walkthrough'],
];

ob_start();
?>
<section class="panel">
    <h2>Metadata</h2>
    <p>Tag utama: <span class="tag"><?php echo htmlspecialchars((string) ($item['tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></p>
    <p>Total entri widget di registry: <?php echo htmlspecialchars((string) (($summary['total'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></p>
</section>

<?php if (!empty($item['snippet'])): ?>
    <section class="panel">
        <h2>Snippet</h2>
        <pre><?php echo htmlspecialchars((string) $item['snippet'], ENT_QUOTES, 'UTF-8'); ?></pre>
    </section>
<?php endif; ?>

<?php if (!empty($relatedItems)): ?>
    <section class="panel">
        <h2>Rute Terkait</h2>
        <ul class="list">
            <?php foreach ($relatedItems as $related): ?>
                <li>
                    <strong><?php echo htmlspecialchars((string) ($related['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <a class="path" href="<?php echo htmlspecialchars((string) ($related['path'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($related['path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
require dirname(__DIR__) . '/layouts/base.php';