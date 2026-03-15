<?php
declare(strict_types=1);

$pageTitle = 'Public Site Staging';
$pageLead = 'Landing shell baru untuk Lawangsewu dengan boundary auth yang sudah dipisah dari view legacy.';
$metaLine = 'Status login: ' . (!empty($isLoggedIn) ? 'aktif' : 'belum login');
$navLinks = [
    ['href' => (string) ($portalUrl ?? '/portal'), 'label' => 'Portal'],
    ['href' => '/walkthrough', 'label' => 'Walkthrough'],
    ['href' => '/daftar-widget', 'label' => 'Daftar Widget'],
];

ob_start();
?>
<section class="panel">
    <h2>Gateway Landing</h2>
    <p>Portal: <a class="path" href="<?php echo htmlspecialchars((string) ($portalUrl ?? '/portal'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($portalUrl ?? '/portal'), ENT_QUOTES, 'UTF-8'); ?></a></p>
    <?php if (!empty($loggedInName)): ?>
        <p>Nama aktif: <strong><?php echo htmlspecialchars((string) $loggedInName, ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <?php endif; ?>
    <?php if (!empty($loginError)): ?>
        <p>Pesan login: <?php echo htmlspecialchars((string) $loginError, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
require dirname(__DIR__) . '/layouts/base.php';