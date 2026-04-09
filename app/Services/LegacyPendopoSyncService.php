<?php

namespace App\Services;

use App\Models\GuestbookEntry;
use App\Models\GuestbookSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class LegacyPendopoSyncService
{
    private const LEGACY_PATHS = [
        '/var/www/pendopo',
        '/var/www/lawangsewu_legacy/pendopo',
    ];

    private const CONNECTION = 'legacy_pendopo';

    public function legacyRoot(): ?string
    {
        foreach (self::LEGACY_PATHS as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    public function legacyStatus(): array
    {
        $root = $this->legacyRoot();

        return [
            'root' => $root,
            'is_archived' => $root === '/var/www/lawangsewu_legacy/pendopo',
            'exists' => $root !== null,
        ];
    }

    public function syncAll(): array
    {
        $connection = $this->legacyConnection();

        $settings = $connection->table('settings')->first();
        $rows = $connection->table('datatamu')->orderBy('checkin')->get();

        $this->syncSettings($settings);
        $imported = $this->syncEntries($rows);
        $photos = $this->syncPhotos();

        return [
            'entries' => $imported,
            'photos' => $photos,
            'legacy_total' => $rows->count(),
        ];
    }

    public function dashboardSummary(): array
    {
        $now = now('Asia/Jakarta');

        $monthlySummary = array_fill(0, 12, 0);
        $monthlyRows = GuestbookEntry::query()
            ->selectRaw('MONTH(checkin) as month_number, COUNT(*) as total')
            ->whereBetween('checkin', [$now->copy()->startOfYear(), $now->copy()->endOfYear()])
            ->groupByRaw('MONTH(checkin)')
            ->orderByRaw('MONTH(checkin)')
            ->get();

        foreach ($monthlyRows as $row) {
            $monthNumber = (int) ($row->month_number ?? 0);
            if ($monthNumber >= 1 && $monthNumber <= 12) {
                $monthlySummary[$monthNumber - 1] = (int) $row->total;
            }
        }

        return [
            'stats' => [
                'day' => GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])->count(),
                'week' => GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY), $now->copy()->endOfWeek(\Carbon\CarbonInterface::SUNDAY)])->count(),
                'month' => GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->count(),
                'year' => GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfYear(), $now->copy()->endOfYear()])->count(),
                'all' => GuestbookEntry::query()->count(),
            ],
            'monthly_summary' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                'data' => $monthlySummary,
            ],
            'recent_entries' => GuestbookEntry::query()
                ->orderByDesc('checkin')
                ->limit(10)
                ->get()
                ->map(fn (GuestbookEntry $entry) => [
                    'id' => $entry->id,
                    'name' => $entry->name,
                    'position' => $entry->position,
                    'institution' => $entry->institution,
                    'purpose' => $entry->purpose,
                    'checkin' => $entry->checkin?->timezone('Asia/Jakarta')->format('d M Y H:i') !== null
                        ? $entry->checkin->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
                        : null,
                ])
                ->values(),
            'photo_count' => $this->countPhotos(public_path('guestbook/photos')),
            'legacy' => $this->legacyDatasetSummary(),
        ];
    }

    public function archiveLegacy(): ?string
    {
        $root = $this->legacyRoot();

        if ($root === null || $root === '/var/www/lawangsewu_legacy/pendopo') {
            return $root;
        }

        $targetRoot = '/var/www/lawangsewu_legacy';
        $target = $targetRoot . '/pendopo';

        if (! is_dir($targetRoot)) {
            File::makeDirectory($targetRoot, 0755, true);
        }

        if (is_dir($target)) {
            File::deleteDirectory($target);
        }

        if (! @rename($root, $target)) {
            throw new RuntimeException('Gagal mengarsipkan folder legacy Pendopo.');
        }

        return $target;
    }

    private function syncSettings(object|null $settings): void
    {
        GuestbookSetting::query()->updateOrCreate(
            ['id' => '1'],
            [
                'per_page' => max(5, min(100, (int) ($settings->jum_data_hal ?? 10))),
                'require_identity_fields' => ((string) ($settings->isi_data ?? '1')) === '1',
                'event_name' => trim(strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], ' ', (string) ($settings->nama_acara ?? 'Pendopo Pengadilan Agama Semarang')))),
            ]
        );
    }

    private function syncEntries(Collection $rows): int
    {
        $payload = $rows->map(function ($row) {
            $institution = $this->normalizeDisplayCase((string) ($row->instansi ?? ''));

            return [
                'id' => (string) $row->id,
                'name' => $this->normalizeDisplayCase((string) ($row->nama ?? '')),
                'position' => $this->normalizeDisplayCase((string) ($row->jabatan ?? '')),
                'institution_category' => $this->categorizeInstansi($institution),
                'institution' => $institution,
                'purpose' => $this->normalizeDisplayCase((string) ($row->keperluan ?? '')),
                'checkin' => $row->checkin,
            ];
        })->filter(fn (array $row) => $row['id'] !== '');

        foreach ($payload->chunk(200) as $chunk) {
            GuestbookEntry::query()->upsert(
                $chunk->values()->all(),
                ['id'],
                ['name', 'position', 'institution_category', 'institution', 'purpose', 'checkin']
            );
        }

        return $payload->count();
    }

    private function syncPhotos(): int
    {
        $root = $this->legacyRoot();
        if ($root === null) {
            return 0;
        }

        $source = $root . '/public/foto';
        $target = public_path('guestbook/photos');

        if (! is_dir($source)) {
            return 0;
        }

        if (! is_dir($target)) {
            File::makeDirectory($target, 0775, true);
        }

        $copied = 0;

        foreach (File::files($source) as $file) {
            $destination = $target . DIRECTORY_SEPARATOR . $file->getFilename();

            if (is_file($destination)) {
                continue;
            }

            File::copy($file->getPathname(), $destination);
            $copied++;
        }

        return $copied;
    }

    private function legacyConnection()
    {
        $root = $this->legacyRoot();
        if ($root === null) {
            throw new RuntimeException('Folder legacy Pendopo tidak ditemukan.');
        }

        $env = $this->parseLegacyEnv($root . '/.env');
        $database = trim((string) ($env['database.default.database'] ?? ''));
        $username = trim((string) ($env['database.default.username'] ?? ''));
        $password = (string) ($env['database.default.password'] ?? '');
        $host = trim((string) ($env['database.default.hostname'] ?? 'localhost'));
        $port = (int) ($env['database.default.port'] ?? 3306);

        if ($database === '' || $username === '') {
            throw new RuntimeException('Konfigurasi database legacy Pendopo tidak lengkap.');
        }

        config()->set('database.connections.' . self::CONNECTION, [
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ]);

        DB::purge(self::CONNECTION);

        return DB::connection(self::CONNECTION);
    }

    private function parseLegacyEnv(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $values[$key] = trim($value, "\"'");
        }

        return $values;
    }

    private function legacyDatasetSummary(): array
    {
        try {
            $connection = $this->legacyConnection();

            return [
                'entries' => (int) $connection->table('datatamu')->count(),
                'photos' => $this->countPhotos(($this->legacyRoot() ?? '') . '/public/foto'),
            ];
        } catch (\Throwable) {
            return [
                'entries' => 0,
                'photos' => 0,
            ];
        }
    }

    private function countPhotos(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        return count(File::files($directory));
    }

    private function normalizeDisplayCase(string $value): string
    {
        $clean = trim((string) preg_replace('/\s+/', ' ', $value));
        if ($clean === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            $clean = mb_convert_case(mb_strtolower($clean, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        } else {
            $clean = ucwords(strtolower($clean));
        }

        $clean = preg_replace('/\bPa\b/u', 'PA', $clean) ?? $clean;
        $clean = preg_replace('/\bPn\b/u', 'PN', $clean) ?? $clean;
        $clean = preg_replace('/\bPta\b/u', 'PTA', $clean) ?? $clean;

        return $clean;
    }

    private function categorizeInstansi(string $instansi): ?string
    {
        $upper = strtoupper($instansi);

        if ($upper === '') {
            return null;
        }

        if (preg_match('/\b(PENGADILAN|PA|PTA|PN|MAHKAMAH)\b/u', $upper)) {
            return 'MAHKAMAH_AGUNG';
        }

        if (preg_match('/\b(MAHASISWA|MHS|UNIVERSITAS|UNIV|SEKOLAH|SMA|SMK|SMP|SD|MADRASAH|POLITEKNIK|POLTEK|AKADEMI|KAMPUS)\b/u', $upper)) {
            return 'UNIVERSITAS_SEKOLAH';
        }

        if (preg_match('/\b(PT\.?|CV\.?|UD\.?|TBK|PERSERO|PERUSAHAAN|INSTANSI|DINAS|KEMENTERIAN|PEMERINTAH|PEMDA|KANTOR|BANK|YAYASAN|RUMAH\s+SAKIT|RS\.?|BUMN|BUMD|SWASTA)\b/u', $upper)) {
            return 'INSTANSI_PERUSAHAAN';
        }

        return 'PERSEORANGAN';
    }
}
