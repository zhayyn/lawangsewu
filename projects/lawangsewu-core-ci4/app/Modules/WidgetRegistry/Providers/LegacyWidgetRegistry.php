<?php

declare(strict_types=1);

namespace App\Modules\WidgetRegistry\Providers;

use App\Modules\WidgetRegistry\Contracts\WidgetRegistryInterface;

final class LegacyWidgetRegistry implements WidgetRegistryInterface
{
    public function categories(): array
    {
        return [
            [
                'title' => 'Pengumuman & Publikasi',
                'caption' => 'Kumpulan halaman publikasi resmi, feed pengumuman, dan halaman embed yang paling sering dipakai.',
                'items' => [
                    $this->item('Berita Pengadilan', '/berita-pengadilan', 'Halaman agregasi berita, artikel, dan RSS peradilan.', 'Halaman', 'reference', '', 'widgets/views/html/public/berita-pengadilan.html'),
                    $this->item('Pengumuman Peradilan', '/pengumuman-peradilan', 'Halaman penuh pengumuman resmi untuk publik.', 'Halaman', 'reference', '', 'widgets/views/php/public/pa-semarang-pengumuman.php'),
                    $this->item('Pengumuman Peradilan Embed', '/pengumuman-peradilan-embed', 'Versi ringan untuk iframe atau penempatan sempit.', 'Embed', 'embed', '<iframe src="/pengumuman-peradilan-embed?source=all&amp;limit=5" loading="lazy"></iframe>', 'widgets/views/php/public/pa-semarang-pengumuman-embed.php'),
                    $this->item('Widget Pengumuman', '/widget-pengumuman', 'Widget singkat untuk menampilkan daftar pengumuman pendek.', 'Widget', 'embed', '<iframe src="/widget-pengumuman?source=all&amp;limit=5" loading="lazy"></iframe>', 'widgets/views/php/public/widget-pengumuman-rss.php'),
                    $this->item('Panduan Embed Pengumuman', '/panduan-embed-pengumuman', 'Panduan publik untuk integrasi iframe pengumuman.', 'Panduan', 'reference', '', 'widgets/views/html/public/pa-semarang-embed-snippet.html'),
                ],
            ],
            [
                'title' => 'Persidangan & Layanan Publik',
                'caption' => 'Halaman publik untuk informasi sidang, antrian, dan tampilan layar layanan.',
                'items' => [
                    $this->item('Info Persidangan', '/info-persidangan', 'Halaman informasi sidang publik standar.', 'Sidang', 'display', '', 'widgets/views/php/public/info-persidangan.php'),
                    $this->item('Info Persidangan Hijau Tua', '/info-persidangan-hijautua', 'Variasi tampilan informasi sidang tema hijau tua.', 'Sidang', 'display', '', 'widgets/views/php/public/info-persidangan-hijautua.php'),
                    $this->item('Info Persidangan Stabilo', '/info-persidangan-stabilo', 'Variasi tampilan informasi sidang dengan kontras tinggi.', 'Sidang', 'display', '', 'widgets/views/php/public/info-persidangan-stabilo.php'),
                    $this->item('Monitor Persidangan', '/monitor-persidangan', 'Monitor jadwal dan panggilan sidang untuk display publik.', 'Monitor', 'display', '', 'widgets/views/php/public/monitor-antrian-sidang.php'),
                    $this->item('Antrian Persidangan', '/antrian-persidangan', 'Tampilan antrian sidang dan urutan pemanggilan.', 'Antrian', 'display', '', 'widgets/views/php/public/antrian-sidang.php'),
                ],
            ],
            [
                'title' => 'Dashboard & Monitoring',
                'caption' => 'Dashboard ringan dan bridge monitoring yang cocok tetap berada di CI4 Core.',
                'items' => [
                    $this->item('Dashboard Perkara', '/dashboard-perkara', 'Ringkasan tren dan komposisi perkara.', 'Dashboard', 'analytics', '', 'widgets/views/php/public/statistik-perkara.php'),
                    $this->item('Dashboard eCourt', '/dashboard-ecourt', 'Ringkasan data penerimaan eCourt.', 'Dashboard', 'analytics', '', 'widgets/views/php/public/statistik-ecourt.php'),
                    $this->item('Dashboard Hakim', '/dashboard-hakim', 'Ringkasan performa hakim dan penyelesaian perkara.', 'Dashboard', 'analytics', '', 'widgets/views/php/public/statistik-hakim.php'),
                    $this->item('Bridge Server 10', '/bridge-server10', 'Halaman bridge debug dan pembacaan data Server 10.', 'Bridge', 'monitoring', '', 'widgets/views/html/public/server10-data-app.html'),
                    $this->item('Monitor WA', '/monitor-wa', 'Status runtime dan viewer QR WA v2.', 'WA', 'monitoring', '', 'widgets/views/html/public/wa-v2-qr-viewer.html'),
                ],
            ],
            [
                'title' => 'Biaya & Radius',
                'caption' => 'Halaman informasi biaya dan radius layanan yang sering dipakai publik.',
                'items' => [
                    $this->item('Biaya Perkara', '/biaya-perkara', 'Informasi biaya proses berperkara.', 'Biaya', 'reference', '', 'widgets/views/html/public/biaya-proses-berperkara.html'),
                    $this->item('Radius Ghaib', '/radius-ghaib', 'Informasi biaya radius ghaib.', 'Biaya', 'reference', '', 'widgets/views/php/public/biaya-radius-ghaib.php'),
                    $this->item('Radius Kecamatan', '/radius-kecamatan', 'Tabel radius kecamatan dan biaya terkait.', 'Biaya', 'reference', '', 'widgets/views/php/public/tabel-radius-kecamatan.php'),
                ],
            ],
        ];
    }

    public function allItems(): array
    {
        $items = [];
        foreach ($this->categories() as $category) {
            foreach ($category['items'] as $item) {
                $items[] = $item + ['category' => $category['title']];
            }
        }

        return $items;
    }

    public function findByPath(string $path): ?array
    {
        foreach ($this->allItems() as $item) {
            if ((string) ($item['path'] ?? '') === $path) {
                return $item;
            }
        }

        return null;
    }

    public function summary(): array
    {
        $allItems = $this->allItems();
        $embedCount = count(array_filter($allItems, static fn ($item) => (string) ($item['kind'] ?? '') === 'embed'));
        $monitoringCount = count(array_filter($allItems, static fn ($item) => in_array((string) ($item['kind'] ?? ''), ['monitoring', 'analytics'], true)));

        return [
            'categoryCount' => count($this->categories()),
            'total' => count($allItems),
            'embedCount' => $embedCount,
            'monitoringCount' => $monitoringCount,
        ];
    }

    private function item(string $name, string $path, string $desc, string $tag, string $kind, string $snippet = '', string $sourcePath = ''): array
    {
        return [
            'name' => $name,
            'path' => $path,
            'desc' => $desc,
            'tag' => $tag,
            'kind' => $kind,
            'snippet' => $snippet,
            'sourcePath' => $sourcePath,
        ];
    }
}