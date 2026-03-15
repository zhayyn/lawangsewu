<?php
declare(strict_types=1);

$pageTitle = 'Docs Registry Staging';
$pageLead = 'Indeks dokumentasi Lawangsewu dibangun dari registry, bukan lagi bergantung penuh pada halaman HTML statis.';
$metaLine = 'Total dokumen: ' . (string) (($summary['total'] ?? 0));
$navLinks = [
    ['href' => (string) ($masterPdfPath ?? '/walkthrough/master-pdf'), 'label' => 'PDF Master'],
    ['href' => (string) ($widgetDirectoryPath ?? '/daftar-widget'), 'label' => 'Daftar Widget'],
    ['href' => '/', 'label' => 'Landing'],
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
                    <strong><?php echo htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="tag"><?php echo htmlspecialchars((string) ($item['kind'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <p><?php echo htmlspecialchars((string) ($item['summary'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <a class="path" href="<?php echo htmlspecialchars((string) ($item['mdPath'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>">MD</a>
                    <a class="path" href="<?php echo htmlspecialchars((string) ($item['pdfPath'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>">PDF</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php
endforeach;
$content = (string) ob_get_clean();
require dirname(__DIR__) . '/layouts/base.php';