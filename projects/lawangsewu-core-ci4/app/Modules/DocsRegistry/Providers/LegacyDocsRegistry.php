<?php

declare(strict_types=1);

namespace App\Modules\DocsRegistry\Providers;

use App\Modules\DocsRegistry\Contracts\DocsRegistryInterface;

final class LegacyDocsRegistry implements DocsRegistryInterface
{
    public function sections(): array
    {
        return [
            [
                'title' => 'Panduan Operasional Utama',
                'caption' => 'Dokumen inti yang paling sering dipakai untuk briefing, operasional, dan pengendalian perubahan.',
                'items' => [
                    $this->document('README-Walkthrough', 'Ringkasan pintu masuk dokumentasi Lawangsewu.', 'overview', '/walkthrough/md/README-Walkthrough.md', '/walkthrough/pdf/README-Walkthrough.pdf'),
                    $this->document('README', 'Ringkasan umum struktur dan akses cepat.', 'overview', '/walkthrough/md/README.md', '/walkthrough/pdf/README.pdf'),
                    $this->document('BACKUP-RECOVERY', 'Panduan backup lokal, offsite, dan restore drill.', 'operations', '/walkthrough/md/BACKUP-RECOVERY.md', '/walkthrough/pdf/BACKUP-RECOVERY.pdf'),
                    $this->document('SECURITY-HARDENING', 'Ringkasan hardening server dan aplikasi Lawangsewu.', 'security', '/walkthrough/md/SECURITY-HARDENING.md', '/walkthrough/pdf/SECURITY-HARDENING.pdf'),
                    $this->document('SECRET-ROTATION', 'Panduan rotasi credential dan rahasia operasional.', 'security', '/walkthrough/md/SECRET-ROTATION.md', '/walkthrough/pdf/SECRET-ROTATION.pdf'),
                ],
            ],
            [
                'title' => 'Keamanan & Akses',
                'caption' => 'Dokumen yang mengatur akses administratif, remote access, dan briefing keamanan.',
                'items' => [
                    $this->document('CLOUDFLARE-CHECKLIST', 'Checklist operasional Cloudflare untuk domain dan akses.', 'security', '/walkthrough/md/CLOUDFLARE-CHECKLIST.md', '/walkthrough/pdf/CLOUDFLARE-CHECKLIST.pdf'),
                    $this->document('CLOUDFLARE-ACCESS-ADMIN-PLAYBOOK', 'Playbook akses admin melalui Cloudflare.', 'security', '/walkthrough/md/CLOUDFLARE-ACCESS-ADMIN-PLAYBOOK.md', '/walkthrough/pdf/CLOUDFLARE-ACCESS-ADMIN-PLAYBOOK.pdf'),
                    $this->document('REMOTE-ACCESS-SECURITY-SERVER9', 'Dokumen keamanan remote access versi lengkap.', 'security', '/walkthrough/md/REMOTE-ACCESS-SECURITY-SERVER9.md', '/walkthrough/pdf/REMOTE-ACCESS-SECURITY-SERVER9.pdf'),
                    $this->document('REMOTE-ACCESS-SECURITY-SERVER9-SIMPLE', 'Versi ringkas briefing remote access.', 'security', '/walkthrough/md/REMOTE-ACCESS-SECURITY-SERVER9-SIMPLE.md', '/walkthrough/pdf/REMOTE-ACCESS-SECURITY-SERVER9-SIMPLE.pdf'),
                    $this->document('REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE', 'Versi satu halaman untuk briefing cepat.', 'security', '/walkthrough/md/REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE.md', '/walkthrough/pdf/REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE.pdf'),
                ],
            ],
            [
                'title' => 'Migrasi & Implementasi',
                'caption' => 'Dokumen terkait perpindahan sistem, laptop, GitHub, dan publikasi portal.',
                'items' => [
                    $this->document('MIGRASI-GITHUB-SERVER', 'Panduan migrasi repository dan deployment server.', 'migration', '/walkthrough/md/MIGRASI-GITHUB-SERVER.md', '/walkthrough/pdf/MIGRASI-GITHUB-SERVER.pdf'),
                    $this->document('README-MIGRASI-LAPTOP', 'Catatan migrasi laptop dan workflow kerja.', 'migration', '/walkthrough/md/README-MIGRASI-LAPTOP.md', '/walkthrough/pdf/README-MIGRASI-LAPTOP.pdf'),
                    $this->document('README-PA-SEMARANG-PENGUMUMAN', 'Panduan berita pengadilan, embed, dan pengumuman publik.', 'publication', '/walkthrough/md/README-PA-SEMARANG-PENGUMUMAN.md', '/walkthrough/pdf/README-PA-SEMARANG-PENGUMUMAN.pdf'),
                    $this->document('AUDIT-WA-CARAKA-20260307', 'Audit WA Caraka dan kondisi implementasi saat itu.', 'audit', '/walkthrough/md/AUDIT-WA-CARAKA-20260307.md', '/walkthrough/pdf/AUDIT-WA-CARAKA-20260307.pdf'),
                ],
            ],
            [
                'title' => 'Referensi Produk & Master Bundle',
                'caption' => 'Dokumen referensi produk, e-book, dan bundle PDF utama untuk distribusi cepat.',
                'items' => [
                    $this->document('E-BOOK WA-CARAKA', 'Dokumentasi produk dan pengantar WA Caraka.', 'product', '/walkthrough/md/E-BOOK WA-CARAKA.md', '/walkthrough/pdf/E-BOOK WA-CARAKA.pdf'),
                    $this->document('Walkthrough-DBPrakom-Master', 'PDF master walkthrough yang menggabungkan dokumen penting.', 'bundle', '/walkthrough/md/README-Walkthrough.md', '/walkthrough/master-pdf'),
                ],
            ],
        ];
    }

    public function allDocuments(): array
    {
        $documents = [];
        foreach ($this->sections() as $section) {
            foreach ($section['items'] as $item) {
                $documents[] = $item + ['section' => $section['title']];
            }
        }

        return $documents;
    }

    public function summary(): array
    {
        $documents = $this->allDocuments();
        $securityCount = count(array_filter($documents, static fn ($item) => (string) ($item['kind'] ?? '') === 'security'));
        $migrationCount = count(array_filter($documents, static fn ($item) => in_array((string) ($item['kind'] ?? ''), ['migration', 'audit'], true)));

        return [
            'sectionCount' => count($this->sections()),
            'total' => count($documents),
            'securityCount' => $securityCount,
            'migrationCount' => $migrationCount,
        ];
    }

    private function document(string $title, string $summary, string $kind, string $mdPath, string $pdfPath): array
    {
        return [
            'title' => $title,
            'summary' => $summary,
            'kind' => $kind,
            'mdPath' => $mdPath,
            'pdfPath' => $pdfPath,
        ];
    }
}