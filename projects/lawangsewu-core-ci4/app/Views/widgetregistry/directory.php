<?php
declare(strict_types=1);

$pageTitle = 'Daftar Widget Lawangsewu';

$publicBaseUrl = 'https://lawangsewu.pa-semarang.go.id';
$absoluteUrl = static fn (string $path): string => rtrim($publicBaseUrl, '/') . $path;
$iframeHeight = static function (array $item): string {
    $path = (string) ($item['path'] ?? '');
    return match ($path) {
        '/berita-pengadilan' => '900',
        '/pengumuman-peradilan', '/pengumuman-peradilan-embed' => '860',
        '/widget-pengumuman' => '720',
        '/monitor-persidangan', '/antrian-persidangan' => '840',
        '/dashboard-perkara', '/dashboard-ecourt', '/dashboard-hakim' => '820',
        '/bridge-server10', '/monitor-wa' => '860',
        default => '820',
    };
};
$iframeSnippet = static function (array $item) use ($absoluteUrl, $iframeHeight): string {
    $url = $absoluteUrl((string) ($item['path'] ?? ''));
    $title = (string) ($item['name'] ?? 'Widget Lawangsewu');
    $snippet = trim((string) ($item['snippet'] ?? ''));
    if ($snippet !== '') {
        $snippet = str_replace('src="/', 'src="' . rtrim($absoluteUrl('/'), '/') . '/', $snippet);
        return $snippet;
    }

    return '<iframe src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" width="100%" height="' . $iframeHeight($item) . '" style="border:0;" loading="lazy" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"></iframe>';
};
$cardBadgeClass = static fn (array $item): string => in_array((string) ($item['tag'] ?? ''), ['Embed', 'Panduan'], true) ? 'badge warn' : 'badge';
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
<style>
    :root {
        --bg: linear-gradient(180deg, #f3fbf7 0%, #ecf2fb 52%, #f7f3eb 100%);
        --ink: #15253a;
        --paper: rgba(255, 255, 255, 0.94);
        --line: rgba(21, 37, 58, 0.12);
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        background: var(--bg);
        color: var(--ink);
        font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
    }
    .page {
        width: min(1180px, calc(100% - 32px));
        margin: 28px auto 40px;
    }
    .widget-directory {
        display: grid;
        gap: 18px;
    }
    .widget-hero,
    .widget-section,
    .widget-foot {
        background: var(--paper);
        border: 1px solid var(--line);
        border-radius: 28px;
        box-shadow: 0 18px 44px rgba(21, 37, 58, 0.09);
    }
    .widget-hero,
    .widget-section {
        padding: 24px;
    }
    .widget-label,
    .widget-credit,
    .badge,
    .badge.warn {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .widget-label,
    .widget-credit,
    .badge {
        background: #e3f4ed;
        color: #0a7558;
    }
    .badge.warn {
        background: #fdf4e4;
        color: #b97816;
    }
    .widget-hero h2,
    .widget-section h2,
    .widget-card h3 {
        margin: 0;
    }
    .widget-hero h2 {
        margin-top: 14px;
        font-size: clamp(32px, 4vw, 48px);
        line-height: 1.05;
    }
    .widget-actions,
    .widget-card-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .widget-actions {
        margin-top: 20px;
    }
    .widget-actions a,
    .widget-card-actions a,
    .widget-card-actions button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 10px 14px;
        border-radius: 14px;
        border: 1px solid rgba(21, 37, 58, 0.12);
        text-decoration: none;
        background: #fff;
        color: #15253a;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }
    .widget-actions .primary,
    .widget-card-actions .primary {
        background: linear-gradient(135deg, #0a7558, #138967);
        border-color: transparent;
        color: #fff;
    }
    .widget-search {
        margin-top: 18px;
    }
    .widget-search input {
        width: 100%;
        border: 1px solid rgba(21, 37, 58, 0.12);
        border-radius: 16px;
        padding: 14px 16px;
        font: inherit;
        color: #15253a;
        background: #fff;
    }
    .widget-meta {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }
    .widget-pill {
        padding: 12px 14px;
        border-radius: 18px;
        background: #fff;
        border: 1px solid rgba(21, 37, 58, 0.12);
        color: #5f728b;
        font-size: 13px;
        line-height: 1.6;
    }
    .widget-pill strong {
        display: block;
        color: #15253a;
        margin-bottom: 4px;
    }
    .widget-section-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: end;
        margin-bottom: 14px;
    }
    .widget-section-head span {
        color: #5f728b;
        font-size: 14px;
        font-weight: 600;
    }
    .widget-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
    .widget-card {
        padding: 18px;
        border-radius: 22px;
        border: 1px solid rgba(21, 37, 58, 0.12);
        background: #fff;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .widget-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }
    .widget-path {
        display: block;
        border-radius: 14px;
        background: #f8fbff;
        border: 1px solid rgba(21, 37, 58, 0.12);
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 700;
        color: #31506e;
        word-break: break-all;
        text-decoration: none;
    }
    .widget-snippet {
        padding: 12px 14px;
        border-radius: 16px;
        background: #132238;
        color: #eef4fb;
        font-size: 12px;
        line-height: 1.6;
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .widget-foot {
        padding: 18px 20px;
        color: #5f728b;
        font-size: 14px;
        line-height: 1.7;
    }
    .widget-copy-status {
        min-height: 20px;
        color: #0a7558;
        font-size: 13px;
        font-weight: 700;
    }
    @media (max-width: 900px) {
        .widget-grid,
        .widget-meta {
            grid-template-columns: 1fr;
        }
        .widget-section-head {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    @media (max-width: 760px) {
        .widget-actions a,
        .widget-card-actions a,
        .widget-card-actions button {
            width: 100%;
        }
        .widget-card-top {
            flex-direction: column;
        }
    }
</style>
</head>
<body>
<main class="page">
<div class="widget-directory">
    <section class="widget-hero">
        <span class="widget-label">Daftar Widget Lawangsewu</span>
        <h2>Satu halaman untuk semua link widget final dan snippet iframe.</h2>
        <p>Gunakan halaman ini sebagai direktori resmi widget publik Lawangsewu. Setiap kartu menampilkan URL final root domain dan snippet iframe yang bisa langsung dipakai untuk kebutuhan embed.</p>
        <div class="widget-actions">
            <a class="primary" href="<?php echo htmlspecialchars($absoluteUrl('/berita-pengadilan'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Buka contoh widget</a>
            <a href="/">Katalog utama</a>
            <a href="/walkthrough">Walkthrough</a>
        </div>
        <div class="widget-search">
            <input id="widgetSearch" type="search" placeholder="Cari widget, misalnya: pengumuman, iframe, persidangan, radius, whatsapp">
        </div>
        <div class="widget-meta">
            <div class="widget-pill"><strong>URL final</strong>Semua tautan memakai root domain tanpa prefix ganda /lawangsewu.</div>
            <div class="widget-pill"><strong>Siap embed</strong>Gunakan tombol salin iframe untuk menempelkan widget ke portal lain.</div>
            <div class="widget-pill"><strong>Publik rapi</strong>Link klik langsung membuka widget final yang sama seperti dipakai user publik.</div>
        </div>
        <div class="widget-credit">developed by dbprakom</div>
    </section>

    <?php foreach (($categories ?? []) as $category): ?>
        <section class="widget-section">
            <div class="widget-section-head">
                <div>
                    <h2><?php echo htmlspecialchars((string) ($category['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <span><?php echo htmlspecialchars((string) ($category['caption'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="widget-grid">
                <?php foreach (($category['items'] ?? []) as $item): ?>
                    <?php
                    $path = (string) ($item['path'] ?? '');
                    $fullUrl = $absoluteUrl($path);
                    $snippet = $iframeSnippet($item);
                    $searchText = strtolower(implode(' ', array_filter([
                        (string) ($item['name'] ?? ''),
                        (string) ($item['desc'] ?? ''),
                        (string) ($item['tag'] ?? ''),
                        (string) ($item['kind'] ?? ''),
                        (string) ($category['title'] ?? ''),
                    ])));
                    ?>
                    <article class="widget-card" data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="widget-card-top">
                            <div>
                                <h3><?php echo htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars((string) ($item['desc'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <span class="<?php echo htmlspecialchars($cardBadgeClass($item), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($item['tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <a class="widget-path" href="<?php echo htmlspecialchars($fullUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($fullUrl, ENT_QUOTES, 'UTF-8'); ?></a>
                        <div class="widget-snippet"><?php echo htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="widget-card-actions">
                            <a class="primary" href="<?php echo htmlspecialchars($fullUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Buka widget</a>
                            <button type="button" data-copy-text="<?php echo htmlspecialchars($fullUrl, ENT_QUOTES, 'UTF-8'); ?>">Salin URL</button>
                            <button type="button" data-copy-text="<?php echo htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'); ?>">Salin iframe</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="widget-foot">
        Halaman ini adalah adapter staging yang mengikuti kontrak URL final Lawangsewu. Alias lama tetap harus diarahkan ke target kanonik, dan route yang belum lolos verdict `safe` tidak boleh di-shadow tanpa review visual atau fungsi.
        <div class="widget-copy-status" id="widgetCopyStatus"></div>
    </section>
</div>
<script>
    (function () {
        var search = document.getElementById('widgetSearch');
        var status = document.getElementById('widgetCopyStatus');
        var cards = Array.prototype.slice.call(document.querySelectorAll('[data-search]'));

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-copy-text]');
            if (!button) {
                return;
            }

            var text = button.getAttribute('data-copy-text') || '';
            if (!navigator.clipboard || !text) {
                if (status) {
                    status.textContent = 'Clipboard tidak tersedia di browser ini.';
                }
                return;
            }

            navigator.clipboard.writeText(text).then(function () {
                if (status) {
                    status.textContent = 'Konten berhasil disalin.';
                }
            }).catch(function () {
                if (status) {
                    status.textContent = 'Gagal menyalin konten.';
                }
            });
        });

        if (search) {
            search.addEventListener('input', function () {
                var keyword = (search.value || '').toLowerCase().trim();
                cards.forEach(function (card) {
                    var haystack = (card.getAttribute('data-search') || '').toLowerCase();
                    card.style.display = keyword === '' || haystack.indexOf(keyword) !== -1 ? '' : 'none';
                });
            });
        }
    }());
</script>
</main>
</body>
</html>