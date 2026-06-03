<?php

namespace App\Services;

use App\Models\Employee;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Throwable;
use ZipArchive;

class SikepSyncService
{
    private const SOURCE_PRIORITIES = [
        'sikep-api' => 300,
        'sikep-portal-bezetting-docx' => 250,
        'sikep-portal-login' => 240,
        'bootstrap-import' => 100,
    ];

    public function sync(string $source, string $username = '', string $password = ''): array
    {
        return match ($source) {
            'sikep' => $this->syncFromSikep(),
            'sikep-portal' => $this->syncFromSikepPortal($username, $password),
            default => [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'Sumber tidak dikenali. Gunakan sikep atau sikep-portal.',
            ],
        };
    }

    private function syncFromSikepPortal(string $username, string $password): array
    {
        $loginUrl = trim((string) config('sikep.portal_login_url', ''));
        $employeeUrl = trim((string) config('sikep.portal_employee_export_url', ''));

        if ($loginUrl === '' || $employeeUrl === '' || $username === '' || $password === '') {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'Konfigurasi login SIKEP portal belum lengkap (login URL, export URL, username, password).',
            ];
        }

        $verifyTls = (bool) config('sikep.verify_tls', true);
        $timeout = (int) config('sikep.timeout_seconds', 15);
        $cookieJar = new CookieJar();

        try {
            $preflight = Http::timeout($timeout)
            ->withOptions(['verify' => $verifyTls, 'cookies' => $cookieJar])
                ->get($loginUrl);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'Gagal mengakses halaman login SIKEP: ' . $e->getMessage(),
            ];
        }

        $html = (string) $preflight->body();
        $csrfField = $this->extractCsrfFieldName($html)
            ?? (string) config('sikep.portal_csrf_field', '_csrf');
        $csrfValue = $this->extractCsrfValue($html, $csrfField);

        $usernameField = (string) config('sikep.portal_username_field', 'username');
        $passwordField = (string) config('sikep.portal_password_field', 'password');

        $payload = [
            $usernameField => $username,
            $passwordField => $password,
        ];

        if ($csrfValue !== null && $csrfField !== '') {
            $payload[$csrfField] = $csrfValue;
        }

        try {
            $login = Http::timeout($timeout)
                ->withOptions(['verify' => $verifyTls, 'cookies' => $cookieJar])
                ->asForm()
                ->post($loginUrl, $payload);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'Gagal submit login SIKEP: ' . $e->getMessage(),
            ];
        }

        try {
            $export = Http::timeout($timeout)
                ->withOptions(['verify' => $verifyTls, 'cookies' => $cookieJar])
                ->get($employeeUrl);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'Gagal mengambil data export pegawai SIKEP: ' . $e->getMessage(),
            ];
        }

        if (!$export->successful()) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'Export SIKEP merespons status HTTP ' . $export->status(),
            ];
        }

        $contentType = strtolower((string) $export->header('Content-Type', ''));
        $disposition = strtolower((string) $export->header('Content-Disposition', ''));
        $body = (string) $export->body();

        if (str_contains($contentType, 'application/octet-stream') || str_contains($disposition, '.docx')) {
            $rows = $this->parseDocxEmployeeRows($body);
            if (count($rows) === 0) {
                return [
                    'ok' => false,
                    'inserted' => 0,
                    'updated' => 0,
                    'message' => 'Login SIKEP berhasil, tetapi file DOCX bezetting tidak dapat diparse menjadi daftar pegawai.',
                ];
            }

            return $this->upsertEmployees($rows, 'sikep-portal-bezetting-docx');
        }

        if (str_contains($contentType, 'application/json')) {
            $json = $export->json();
            $rows = Arr::get($json, 'data', Arr::get($json, 'items', Arr::get($json, 'results', [])));
            if (!is_array($rows) || count($rows) === 0) {
                return [
                    'ok' => false,
                    'inserted' => 0,
                    'updated' => 0,
                    'message' => 'Login SIKEP berhasil, tetapi payload pegawai kosong/tidak dikenali.',
                ];
            }

            return $this->upsertEmployees($rows, 'sikep-portal-login');
        }

        $rows = $this->parseCsvRows($body);
        if (count($rows) === 0) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'Login SIKEP berhasil, tetapi export pegawai tidak dapat diparse. Pastikan URL export mengarah CSV/JSON.',
            ];
        }

        return $this->upsertEmployees($rows, 'sikep-portal-login');
    }

    private function syncFromSikep(): array
    {
        $baseUrl = trim((string) config('sikep.base_url'));
        if ($baseUrl === '') {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'SIKEP base URL belum dikonfigurasi.',
            ];
        }

        $endpoint = trim((string) config('sikep.employee_endpoint', '/pegawai'));
        $token = trim((string) config('sikep.token', ''));
        $headers = [];

        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        try {
            $response = Http::timeout((int) config('sikep.timeout_seconds', 15))
                ->withOptions([
                    'verify' => (bool) config('sikep.verify_tls', true),
                ])
                ->acceptJson()
                ->withHeaders($headers)
                ->get(rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/'));
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'Gagal koneksi ke SIKEP: ' . $e->getMessage(),
            ];
        }

        if (!$response->successful()) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'SIKEP merespons status HTTP ' . $response->status(),
            ];
        }

        $payload = $response->json();
        $rows = Arr::get($payload, 'data', Arr::get($payload, 'items', Arr::get($payload, 'results', [])));

        if ($rows === null) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'SIKEP terhubung tetapi tidak mengembalikan data pegawai pada endpoint ini.',
            ];
        }

        if (!is_array($rows)) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'Format payload SIKEP tidak dikenali.',
            ];
        }

        if (count($rows) === 0) {
            return [
                'ok' => false,
                'inserted' => 0,
                'updated' => 0,
                'message' => 'SIKEP tidak mengembalikan daftar pegawai. Kemungkinan endpoint/token belum sesuai.',
            ];
        }

        return $this->upsertEmployees($rows, 'sikep-api');
    }

    private function upsertEmployees(array $rows, string $source): array
    {
        $inserted = 0;
        $updated = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $nip = trim((string) ($row['nip'] ?? ''));
            $name = trim((string) ($row['name'] ?? $row['nama'] ?? $row['nmpeg'] ?? ''));
            if ($nip === '' || $name === '') {
                continue;
            }

            $match = [
                'nip' => $nip,
            ];

            $data = [
                'external_uid' => trim((string) ($row['external_uid'] ?? $row['idpns'] ?? '')),
                'name' => $name,
                'email' => trim((string) ($row['email'] ?? '')) ?: null,
                'phone' => trim((string) ($row['phone'] ?? $row['notelp'] ?? '')) ?: null,
                'position' => trim((string) ($row['position'] ?? $row['jabatan'] ?? $row['kdjab'] ?? '')) ?: null,
                'rank' => trim((string) ($row['rank'] ?? $row['golongan'] ?? $row['kdgol'] ?? '')) ?: null,
                'satker_code' => trim((string) ($row['satker_code'] ?? $row['kdsatker'] ?? '')) ?: null,
                'satker_name' => trim((string) ($row['satker_name'] ?? '')) ?: null,
                'employment_status' => trim((string) ($row['employment_status'] ?? $row['jpns'] ?? '')) ?: null,
                'source_system' => $source,
                'merged_sources' => [$source],
                'source_payload' => $row,
                'last_synced_at' => now(),
            ];

            $existing = Employee::query()->where($match)->first();
            if ($existing instanceof Employee) {
                $existing->fill($this->mergeEmployeeData($existing->toArray(), $data));
                $existing->save();
                $updated++;
                continue;
            }

            Employee::query()->create(array_merge($match, $data));
            $inserted++;
        }

        return [
            'ok' => true,
            'inserted' => $inserted,
            'updated' => $updated,
            'message' => 'Sinkronisasi selesai untuk sumber ' . $source,
        ];
    }

    private function mergeEmployeeData(array $existing, array $incoming): array
    {
        $existingSource = (string) ($existing['source_system'] ?? '');
        $incomingSource = (string) ($incoming['source_system'] ?? '');

        $existingPriority = $this->sourcePriority($existingSource);
        $incomingPriority = $this->sourcePriority($incomingSource);

        $mergedSources = array_values(array_unique(array_filter(array_merge(
            (array) ($existing['merged_sources'] ?? []),
            (array) ($incoming['merged_sources'] ?? []),
            [$existingSource, $incomingSource]
        ))));

        $fields = [
            'external_uid',
            'name',
            'email',
            'phone',
            'position',
            'rank',
            'satker_code',
            'satker_name',
            'employment_status',
            'source_payload',
            'last_synced_at',
        ];

        $result = $existing;

        foreach ($fields as $field) {
            $incomingValue = $incoming[$field] ?? null;
            $existingValue = $existing[$field] ?? null;

            if ($incomingValue === null || $incomingValue === '') {
                continue;
            }

            if ($existingValue === null || $existingValue === '') {
                $result[$field] = $incomingValue;
                continue;
            }

            if ($incomingPriority >= $existingPriority) {
                $result[$field] = $incomingValue;
            }
        }

        $result['source_system'] = $incomingPriority >= $existingPriority && $incomingSource !== ''
            ? $incomingSource
            : $existingSource;
        $result['merged_sources'] = $mergedSources;

        return $result;
    }

    private function sourcePriority(string $source): int
    {
        return self::SOURCE_PRIORITIES[$source] ?? 0;
    }

    private function parseCsvRows(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw);
        if (!is_array($lines) || count($lines) < 2) {
            return [];
        }

        $delimiter = str_contains($lines[0], ';') ? ';' : ',';
        $header = str_getcsv((string) $lines[0], $delimiter);
        $normalizedHeader = array_map(function ($item): string {
            $key = Str::of((string) $item)->lower()->trim()->replace(' ', '_')->replace('-', '_')->value();
            return $key;
        }, $header);

        $rows = [];
        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line, $delimiter);
            $assoc = [];
            foreach ($normalizedHeader as $idx => $key) {
                $assoc[$key] = $values[$idx] ?? null;
            }

            $rows[] = [
                'nip' => $assoc['nip'] ?? $assoc['username'] ?? null,
                'nmpeg' => $assoc['nama'] ?? $assoc['nama_pegawai'] ?? $assoc['name'] ?? null,
                'email' => $assoc['email'] ?? null,
                'notelp' => $assoc['notelp'] ?? $assoc['phone'] ?? null,
                'kdjab' => $assoc['jabatan'] ?? $assoc['position'] ?? null,
                'kdgol' => $assoc['golongan'] ?? $assoc['rank'] ?? null,
                'kdsatker' => $assoc['satker'] ?? $assoc['satker_code'] ?? null,
                'idpns' => $assoc['idpns'] ?? $assoc['id'] ?? null,
            ];
        }

        return $rows;
    }

    private function extractCsrfFieldName(string $html): ?string
    {
        if (preg_match('/name="([^"]*csrf[^"]*)"\s+value="/i', $html, $matches) === 1) {
            return (string) ($matches[1] ?? null);
        }

        return null;
    }

    private function extractCsrfValue(string $html, string $csrfField): ?string
    {
        if ($csrfField === '') {
            return null;
        }

        $pattern = '/name="' . preg_quote($csrfField, '/') . '"\s+value="([^"]+)"/i';
        if (preg_match($pattern, $html, $matches) === 1) {
            return (string) ($matches[1] ?? null);
        }

        return null;
    }

    private function parseDocxEmployeeRows(string $binary): array
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sikep-docx-');
        if ($tmpFile === false) {
            return [];
        }

        file_put_contents($tmpFile, $binary);

        try {
            $zip = new ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                return [];
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if (!is_string($xml) || $xml === '') {
                return [];
            }

            $dom = new DOMDocument();
            $dom->loadXML($xml);

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            $headingTexts = $xpath->query('//w:p//w:t');
            $satkerName = null;
            foreach ($headingTexts as $textNode) {
                $text = trim((string) $textNode->textContent);
                if (str_starts_with($text, 'di Pengadilan')) {
                    $satkerName = $text;
                    break;
                }
            }

            $tables = $xpath->query('//w:tbl');
            if ($tables->length === 0) {
                return [];
            }

            $rows = $xpath->query('.//w:tr', $tables->item(0));
            $parsed = [];

            foreach ($rows as $row) {
                $cells = $xpath->query('.//w:tc', $row);
                if ($cells->length < 9) {
                    continue;
                }

                $values = [];
                foreach ($cells as $cell) {
                    $texts = $xpath->query('.//w:t', $cell);
                    $buffer = '';
                    foreach ($texts as $text) {
                        $buffer .= (string) $text->textContent;
                    }

                    $values[] = trim((string) preg_replace('/\s+/', ' ', $buffer));
                }

                if (!preg_match('/^\d+$/', (string) ($values[0] ?? ''))) {
                    continue;
                }

                $nameNip = (string) ($values[1] ?? '');
                if (!preg_match('/^(.*?)(\d{18})$/', $nameNip, $matches)) {
                    continue;
                }

                $name = trim((string) ($matches[1] ?? ''));
                $nip = trim((string) ($matches[2] ?? ''));
                if ($name === '' || $nip === '') {
                    continue;
                }

                $rankText = (string) ($values[2] ?? '');
                $rank = $rankText;
                if (preg_match('/^(.*?)([IVX]+\/[a-e])$/i', $rankText, $rankMatches)) {
                    $rank = trim((string) ($rankMatches[2] ?? $rankText));
                }

                $position = trim((string) ($values[5] ?? ''));
                $education = trim((string) ($values[8] ?? ''));

                $parsed[] = [
                    'nip' => $nip,
                    'nmpeg' => $name,
                    'kdgol' => $rank,
                    'kdjab' => $position,
                    'kdsatker' => null,
                    'satker_name' => $satkerName,
                    'employment_status' => $education,
                    'source_payload' => [
                        'row_number' => $values[0] ?? null,
                        'name_nip' => $nameNip,
                        'pangkat_golongan' => $rankText,
                        'tmt_golongan' => $values[3] ?? null,
                        'nomor_sk_golongan' => $values[4] ?? null,
                        'jabatan' => $position,
                        'tmt_jabatan' => $values[6] ?? null,
                        'nomor_sk_jabatan' => $values[7] ?? null,
                        'pendidikan' => $education,
                        'satker_name' => $satkerName,
                    ],
                ];
            }

            return $parsed;
        } finally {
            @unlink($tmpFile);
        }
    }
}
