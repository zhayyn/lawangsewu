<?php
/* developed by zhayyn™ */

if (function_exists('opcache_reset')) {
    opcache_reset();
}

header("Access-Control-Allow-Origin: *");
header("X-Frame-Options: ALLOWALL");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, proxy-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("ETag: " . md5(microtime()));

function info_persidangan_load_env(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim(trim($value), "\"'");
        if ($key !== '') {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function info_persidangan_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || trim($value) === '') {
        return $default;
    }
    return trim($value);
}

function info_persidangan_db(): ?PDO
{
    static $pdo = null;
    static $attempted = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if ($attempted) {
        return null;
    }
    $attempted = true;

    $root = dirname(__DIR__, 4);
    info_persidangan_load_env($root . '/.env');
    info_persidangan_load_env(dirname(__DIR__, 2) . '/config/.env');

    $host = info_persidangan_env('LW_JADWAL_DB_HOST', info_persidangan_env('LW_STAT_DB_HOST', 'localhost'));
    $user = info_persidangan_env('LW_JADWAL_DB_USER', info_persidangan_env('LW_STAT_DB_USER', 'admin'));
    $pass = info_persidangan_env('LW_JADWAL_DB_PASS', info_persidangan_env('LW_STAT_DB_PASS', ''));
    $name = info_persidangan_env('LW_JADWAL_DB_NAME', info_persidangan_env('LW_STAT_DB_NAME', 'sipp'));

    try {
        $pdo = new PDO(
            'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS jadwal_persidangan_local (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tanggal_sidang DATE NOT NULL,
                nomor_perkara VARCHAR(100) NOT NULL,
                agenda VARCHAR(255) NOT NULL,
                ruang_sidang VARCHAR(255) NOT NULL,
                keterangan VARCHAR(255) NOT NULL,
                urutan INT NOT NULL DEFAULT 0,
                sumber VARCHAR(30) NOT NULL DEFAULT "manual",
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_tanggal_sidang (tanggal_sidang),
                KEY idx_tanggal_urutan (tanggal_sidang, urutan)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS jadwal_persidangan_runtime_status (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tanggal_sidang DATE NOT NULL,
                nomor_perkara VARCHAR(100) NOT NULL,
                ruang_key VARCHAR(120) NOT NULL,
                ruang_sidang VARCHAR(255) NOT NULL DEFAULT "",
                agenda VARCHAR(255) NOT NULL DEFAULT "",
                status VARCHAR(30) NOT NULL DEFAULT "menunggu_sidang",
                ever_active TINYINT(1) NOT NULL DEFAULT 0,
                last_seen_active_at DATETIME NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uniq_runtime (tanggal_sidang, nomor_perkara, ruang_key),
                KEY idx_runtime_tanggal (tanggal_sidang),
                KEY idx_runtime_perkara (tanggal_sidang, nomor_perkara)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        // Backward-compatible for existing table.
        try {
            $pdo->exec('ALTER TABLE jadwal_persidangan_runtime_status ADD COLUMN ruang_sidang VARCHAR(255) NOT NULL DEFAULT "" AFTER ruang_key');
        } catch (Throwable $e) {
            // Column likely already exists.
        }
        try {
            $pdo->exec('ALTER TABLE jadwal_persidangan_runtime_status ADD COLUMN agenda VARCHAR(255) NOT NULL DEFAULT "" AFTER ruang_sidang');
        } catch (Throwable $e) {
            // Column likely already exists.
        }
    } catch (Throwable $e) {
        return null;
    }

    return $pdo;
}

function info_persidangan_fetch_local_rows(string $tanggal): array
{
    $db = info_persidangan_db();
    if (!$db instanceof PDO) {
        return [];
    }

    try {
        $stmt = $db->prepare(
            'SELECT nomor_perkara, agenda, ruang_sidang, keterangan
             FROM jadwal_persidangan_local
             WHERE tanggal_sidang = :tanggal
             ORDER BY urutan ASC, id ASC'
        );
        $stmt->execute([':tanggal' => $tanggal]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    if (!is_array($rows)) {
        return [];
    }

    $result = [];
    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            continue;
        }
        $result[] = [
            'no' => $index + 1,
            'noPerkara' => trim((string) ($row['nomor_perkara'] ?? '')),
            'agenda' => trim((string) ($row['agenda'] ?? '')),
            'ruangSidang' => trim((string) ($row['ruang_sidang'] ?? '')),
            'keterangan' => trim((string) ($row['keterangan'] ?? '')),
        ];
    }

    return $result;
}

function info_persidangan_sync_local_rows(string $tanggal, array $rows, string $source = 'auto_sync'): void
{
    $db = info_persidangan_db();
    if (!$db instanceof PDO || $rows === []) {
        return;
    }

    try {
        $db->beginTransaction();

        $deleteStmt = $db->prepare('DELETE FROM jadwal_persidangan_local WHERE tanggal_sidang = :tanggal');
        $deleteStmt->execute([':tanggal' => $tanggal]);

        $insertStmt = $db->prepare(
            'INSERT INTO jadwal_persidangan_local
                (tanggal_sidang, nomor_perkara, agenda, ruang_sidang, keterangan, urutan, sumber, created_at, updated_at)
             VALUES
                (:tanggal, :nomor_perkara, :agenda, :ruang_sidang, :keterangan, :urutan, :sumber, NOW(), NOW())'
        );

        $urutan = 10;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $nomorPerkara = trim((string) ($row['noPerkara'] ?? ''));
            if ($nomorPerkara === '') {
                continue;
            }

            $insertStmt->execute([
                ':tanggal' => $tanggal,
                ':nomor_perkara' => $nomorPerkara,
                ':agenda' => trim((string) ($row['agenda'] ?? 'Sidang')),
                ':ruang_sidang' => trim((string) ($row['ruangSidang'] ?? 'Ruang Sidang')),
                ':keterangan' => trim((string) ($row['keterangan'] ?? '-')),
                ':urutan' => $urutan,
                ':sumber' => $source,
            ]);

            $urutan += 10;
        }

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
    }
}

function info_persidangan_normalize_room(string $value): string
{
    $value = strtolower(trim($value));
    if ($value === '') {
        return '';
    }

    $value = str_replace(['sidang dalam gedung', 'ruang sidang'], '', $value);
    $value = preg_replace('/[^a-z0-9]+/', '', $value);

    return is_string($value) ? $value : '';
}

function info_persidangan_runtime_key(string $nomorPerkara, string $ruangSidang): string
{
    return strtolower(trim($nomorPerkara)) . '|' . info_persidangan_normalize_room($ruangSidang);
}

function info_persidangan_runtime_load(string $tanggal): array
{
    $db = info_persidangan_db();
    if (!$db instanceof PDO) {
        return ['byKey' => [], 'everByPerkara' => []];
    }

    try {
        $stmt = $db->prepare(
            'SELECT nomor_perkara, ruang_key, status, ever_active
             FROM jadwal_persidangan_runtime_status
             WHERE tanggal_sidang = :tanggal'
        );
        $stmt->execute([':tanggal' => $tanggal]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return ['byKey' => [], 'everByPerkara' => []];
    }

    $byKey = [];
    $everByPerkara = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $nomorPerkara = strtolower(trim((string) ($row['nomor_perkara'] ?? '')));
        $ruangKey = (string) ($row['ruang_key'] ?? '');
        $key = $nomorPerkara . '|' . $ruangKey;
        $everActive = (int) ($row['ever_active'] ?? 0) === 1;

        $byKey[$key] = [
            'ever_active' => $everActive,
            'status' => trim((string) ($row['status'] ?? '')),
            'nomor_perkara' => trim((string) ($row['nomor_perkara'] ?? '')),
        ];

        if ($nomorPerkara !== '' && $everActive) {
            $everByPerkara[$nomorPerkara] = true;
        }
    }

    return ['byKey' => $byKey, 'everByPerkara' => $everByPerkara];
}

function info_persidangan_runtime_upsert_active(string $tanggal, array $activeRows): void
{
    if ($activeRows === []) {
        return;
    }

    $db = info_persidangan_db();
    if (!$db instanceof PDO) {
        return;
    }

    try {
        $stmt = $db->prepare(
            'INSERT INTO jadwal_persidangan_runtime_status
                (tanggal_sidang, nomor_perkara, ruang_key, status, ever_active, last_seen_active_at, updated_at)
             VALUES
                (:tanggal, :nomor_perkara, :ruang_key, "dipanggil_sidang", 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                status = "dipanggil_sidang",
                ever_active = 1,
                last_seen_active_at = NOW(),
                updated_at = NOW()'
        );

        foreach ($activeRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $nomorPerkara = trim((string) ($row['noPerkara'] ?? ''));
            if ($nomorPerkara === '') {
                continue;
            }

            $ruangKey = info_persidangan_normalize_room((string) ($row['ruangSidang'] ?? ''));
            $stmt->execute([
                ':tanggal' => $tanggal,
                ':nomor_perkara' => $nomorPerkara,
                ':ruang_key' => $ruangKey,
            ]);
        }
    } catch (Throwable $e) {
        return;
    }
}

function info_persidangan_append_runtime_rows(array $rows, string $tanggal, array $activeRows = []): array
{
    if ($tanggal !== date('Y-m-d')) {
        return $rows;
    }

    $runtime = info_persidangan_runtime_load($tanggal);
    $runtimeByKey = is_array($runtime['byKey'] ?? null) ? $runtime['byKey'] : [];
    if ($runtimeByKey === []) {
        return $rows;
    }

    $existingPerkara = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $existingPerkara[strtolower(trim((string) ($row['noPerkara'] ?? '')))] = true;
    }

    // Always surface active-room cases even when source jadwal list does not contain them.
    foreach ($activeRows as $active) {
        if (!is_array($active)) {
            continue;
        }

        $nomorPerkara = trim((string) ($active['noPerkara'] ?? ''));
        if ($nomorPerkara === '') {
            continue;
        }

        $npLower = strtolower($nomorPerkara);
        if (isset($existingPerkara[$npLower])) {
            continue;
        }

        $rows[] = [
            'no' => count($rows) + 1,
            'noPerkara' => $nomorPerkara,
            'agenda' => trim((string) ($active['agenda'] ?? '')) !== '' ? (string) $active['agenda'] : 'Sidang',
            'ruangSidang' => trim((string) ($active['ruangSidang'] ?? '')) !== '' ? (string) $active['ruangSidang'] : 'Ruang Sidang',
            'keterangan' => '',
        ];
        $existingPerkara[$npLower] = true;
    }

    foreach ($runtimeByKey as $item) {
        if (!is_array($item) || empty($item['ever_active'])) {
            continue;
        }

        $nomorPerkara = trim((string) ($item['nomor_perkara'] ?? ''));
        if ($nomorPerkara === '') {
            continue;
        }

        $npLower = strtolower($nomorPerkara);
        if (isset($existingPerkara[$npLower])) {
            continue;
        }

        $rows[] = [
            'no' => count($rows) + 1,
            'noPerkara' => $nomorPerkara,
            'agenda' => 'Sidang',
            'ruangSidang' => 'Ruang Sidang',
            'keterangan' => '',
        ];
        $existingPerkara[$npLower] = true;
    }

    foreach ($rows as $idx => $row) {
        if (!is_array($row)) {
            continue;
        }
        $rows[$idx]['no'] = $idx + 1;
    }

    return $rows;
}

function info_persidangan_runtime_mark_finished(string $tanggal, array $rowsToFinish): void
{
    if ($rowsToFinish === []) {
        return;
    }

    $db = info_persidangan_db();
    if (!$db instanceof PDO) {
        return;
    }

    try {
        $stmt = $db->prepare(
            'UPDATE jadwal_persidangan_runtime_status
             SET status = "selesai_sidang", updated_at = NOW()
             WHERE tanggal_sidang = :tanggal AND nomor_perkara = :nomor_perkara AND ruang_key = :ruang_key'
        );

        foreach ($rowsToFinish as $item) {
            if (!is_array($item)) {
                continue;
            }
            $stmt->execute([
                ':tanggal' => $tanggal,
                ':nomor_perkara' => (string) ($item['nomor_perkara'] ?? ''),
                ':ruang_key' => (string) ($item['ruang_key'] ?? ''),
            ]);
        }
    } catch (Throwable $e) {
        return;
    }
}

function info_persidangan_apply_status(array $rows, string $tanggal): array
{
    if ($rows === []) {
        return [];
    }

    $today = date('Y-m-d');
    $activeRows = $tanggal === $today ? info_persidangan_fetch_active_rows() : [];
    if ($tanggal === $today) {
        info_persidangan_runtime_upsert_active($tanggal, $activeRows);
    }

    $runtime = info_persidangan_runtime_load($tanggal);
    $runtimeByKey = is_array($runtime['byKey'] ?? null) ? $runtime['byKey'] : [];
    $runtimeEverByPerkara = is_array($runtime['everByPerkara'] ?? null) ? $runtime['everByPerkara'] : [];

    $activeKeys = [];
    $activePerkara = [];

    foreach ($activeRows as $active) {
        if (!is_array($active)) {
            continue;
        }
        $nomorPerkaraActive = strtolower(trim((string) ($active['noPerkara'] ?? '')));
        if ($nomorPerkaraActive !== '') {
            $activePerkara[$nomorPerkaraActive] = true;
        }

        $key = $nomorPerkaraActive . '|' . info_persidangan_normalize_room((string) ($active['ruangSidang'] ?? ''));
        if ($key !== '|') {
            $activeKeys[$key] = true;
        }
    }

    // Build active index per room so rows before active case in same room are marked completed.
    $activeIndexByRoom = [];
    if ($tanggal === $today) {
        $normalizedRows = [];
        foreach ($rows as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalizedRows[$idx] = [
                'nomor' => strtolower(trim((string) ($row['noPerkara'] ?? ''))),
                'room' => info_persidangan_normalize_room((string) ($row['ruangSidang'] ?? '')),
            ];
        }

        foreach ($activeRows as $active) {
            if (!is_array($active)) {
                continue;
            }
            $activeNomor = strtolower(trim((string) ($active['noPerkara'] ?? '')));
            $activeRoom = info_persidangan_normalize_room((string) ($active['ruangSidang'] ?? ''));
            if ($activeNomor === '' || $activeRoom === '') {
                continue;
            }

            foreach ($normalizedRows as $rowIdx => $nr) {
                if (($nr['nomor'] ?? '') === $activeNomor && ($nr['room'] ?? '') === $activeRoom) {
                    if (!isset($activeIndexByRoom[$activeRoom]) || $rowIdx < $activeIndexByRoom[$activeRoom]) {
                        $activeIndexByRoom[$activeRoom] = $rowIdx;
                    }
                    break;
                }
            }
        }
    }

    $result = [];
    $rowsToFinish = [];
    foreach ($rows as $rowIdx => $row) {
        if (!is_array($row)) {
            continue;
        }

        $nomorPerkara = trim((string) ($row['noPerkara'] ?? ''));
        $ruang = trim((string) ($row['ruangSidang'] ?? ''));
        $existingKet = trim((string) ($row['keterangan'] ?? ''));

        $existingLower = strtolower($existingKet);
        $hasMeaningfulKet = $existingKet !== '' && $existingKet !== '-' && $existingKet !== '---';

        $runtimeKey = info_persidangan_runtime_key($nomorPerkara, $ruang);
        $key = strtolower($nomorPerkara) . '|' . info_persidangan_normalize_room($ruang);
        $isActive = isset($activeKeys[$key]) || isset($activePerkara[strtolower($nomorPerkara)]);
        $everActive = !empty($runtimeByKey[$runtimeKey]['ever_active']) || isset($runtimeEverByPerkara[strtolower($nomorPerkara)]);

        if ($isActive) {
            $keterangan = 'Dipanggil sidang';
        } elseif ($tanggal === $today && ($activeIndexByRoom[info_persidangan_normalize_room($ruang)] ?? null) !== null) {
            $roomKey = info_persidangan_normalize_room($ruang);
            $activeIdx = (int) ($activeIndexByRoom[$roomKey] ?? -1);
            if ($activeIdx >= 0 && $rowIdx < $activeIdx) {
                $keterangan = 'Sudah selesai sidang';
            } else {
                $keterangan = 'Menunggu sidang';
            }
        } elseif ($tanggal < $today) {
            $keterangan = 'Sudah selesai sidang';
        } elseif ($tanggal > $today) {
            $keterangan = 'Menunggu sidang';
        } elseif ($everActive) {
            $keterangan = 'Sudah selesai sidang';
            $rowsToFinish[] = [
                'nomor_perkara' => $nomorPerkara,
                'ruang_key' => info_persidangan_normalize_room($ruang),
            ];
        } elseif (strpos($existingLower, 'selesai') !== false) {
            $keterangan = 'Sudah selesai sidang';
        } elseif (strpos($existingLower, 'sedang') !== false) {
            $keterangan = 'Sedang sidang';
        } elseif (strpos($existingLower, 'tunggu') !== false || strpos($existingLower, 'menunggu') !== false) {
            $keterangan = 'Menunggu sidang';
        } elseif ($hasMeaningfulKet) {
            $keterangan = $existingKet;
        } else {
            $keterangan = 'Menunggu sidang';
        }

        $result[] = [
            'no' => (int) ($row['no'] ?? (count($result) + 1)),
            'noPerkara' => $nomorPerkara,
            'agenda' => trim((string) ($row['agenda'] ?? '')),
            'ruangSidang' => $ruang,
            'keterangan' => $keterangan,
        ];
    }

    if ($tanggal === $today && $rowsToFinish !== []) {
        info_persidangan_runtime_mark_finished($tanggal, $rowsToFinish);
    }

    return $result;
}

function info_persidangan_compact_keterangan(string $value): string
{
    $clean = trim(preg_replace('/\s+/', ' ', $value));
    if ($clean === '') {
        return '-';
    }

    if (stripos($clean, 'Nomor antrian aktif:') === 0) {
        $antrian = trim(substr($clean, strlen('Nomor antrian aktif:')));
        return $antrian !== '' ? ('Antrian ' . $antrian) : 'Antrian aktif';
    }

    if (stripos($clean, 'Proses persidangan sedang berlangsung') === 0) {
        return 'Sedang berlangsung';
    }

    return $clean;
}

function info_persidangan_http_fetch(string $url, bool $isPost = false): array
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Lawangsewu Info Persidangan)');
    if ($isPost) {
        curl_setopt($ch, CURLOPT_POST, 1);
    }

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'body' => is_string($body) ? $body : '',
        'status' => $status,
        'error' => $error,
    ];
}

function info_persidangan_parse_slide_rows(string $html): array
{
    if (trim($html) === '') {
        return [];
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query('//table//tr');

    $result = [];
    $no = 1;
    $currentBlock = [];

    if ($rows->length <= 0) {
        return [];
    }

    foreach ($rows as $row) {
        $cells = $row->getElementsByTagName('td');
        if ($cells->length === 0) {
            continue;
        }

        $rowspan = $cells->item(0)->getAttribute('rowspan');
        if ($rowspan !== '') {
            if (!empty($currentBlock) && !empty($currentBlock['noPerkara'])) {
                $result[] = [
                    'no' => $no++,
                    'noPerkara' => $currentBlock['noPerkara'],
                    'agenda' => $currentBlock['agenda'] ?? '',
                    'ruangSidang' => $currentBlock['ruangSidang'] ?? '',
                    'keterangan' => $currentBlock['keterangan'] ?? 'Terjadwal',
                ];
            }
            $currentBlock = [];
        }

        if ($cells->length >= 3) {
            $label = strtoupper(trim($cells->item(1)->textContent));
            $value = trim($cells->item(2)->textContent);
            if (strpos($label, 'NO PERKARA') !== false || strpos($label, 'NOMOR PERKARA') !== false) {
                $currentBlock['noPerkara'] = $value;
            } elseif (strpos($label, 'AGENDA') !== false) {
                $currentBlock['agenda'] = $value;
            } elseif (strpos($label, 'RUANG') !== false || strpos($label, 'PENGADILAN') !== false) {
                $currentBlock['ruangSidang'] = $value;
            } elseif (strpos($label, 'HAKIM') !== false || strpos($label, 'KETUA') !== false || strpos($label, 'KETERANGAN') !== false) {
                $currentBlock['keterangan'] = !empty($currentBlock['keterangan']) ? $currentBlock['keterangan'] : $value;
            }
        } elseif ($cells->length === 2) {
            $label = strtoupper(trim($cells->item(0)->textContent));
            $value = trim($cells->item(1)->textContent);
            if (strpos($label, 'NO PERKARA') !== false || strpos($label, 'NOMOR PERKARA') !== false) {
                $currentBlock['noPerkara'] = $value;
            } elseif (strpos($label, 'AGENDA') !== false) {
                $currentBlock['agenda'] = $value;
            } elseif (strpos($label, 'RUANG') !== false) {
                $currentBlock['ruangSidang'] = $value;
            } elseif (strpos($label, 'KETERANGAN') !== false) {
                $currentBlock['keterangan'] = $value;
            }
        }
    }

    if (!empty($currentBlock) && !empty($currentBlock['noPerkara'])) {
        $result[] = [
            'no' => $no++,
            'noPerkara' => $currentBlock['noPerkara'],
            'agenda' => $currentBlock['agenda'] ?? '',
            'ruangSidang' => $currentBlock['ruangSidang'] ?? '',
            'keterangan' => $currentBlock['keterangan'] ?? 'Terjadwal',
        ];
    }

    return $result;
}

function info_persidangan_fetch_active_rows(): array
{
    $sources = [
        info_persidangan_http_fetch('https://antrian.pa-semarang.go.id/tv_media/display_atas'),
        info_persidangan_http_fetch('https://antrian.pa-semarang.go.id/tv_media/display_bawah'),
    ];

    $rows = [];
    $seen = [];

    foreach ($sources as $source) {
        if (($source['status'] ?? 0) < 200 || ($source['status'] ?? 0) >= 300 || ($source['body'] ?? '') === '') {
            continue;
        }

        $decoded = json_decode((string) $source['body'], true);
        if (is_array($decoded) && array_is_list($decoded)) {
            $items = $decoded;
        } elseif (is_array($decoded)) {
            $items = [$decoded];
        } else {
            $items = [];
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $noPerkara = trim((string) ($item['no_perk'] ?? ''));
            $ruangSidang = trim((string) ($item['nama_ruang'] ?? ''));
            $nomorAntrian = trim((string) ($item['no_antrian'] ?? ''));
            if ($noPerkara === '' || $ruangSidang === '') {
                continue;
            }

            $key = $noPerkara . '|' . $ruangSidang;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
                'no' => count($rows) + 1,
                'noPerkara' => $noPerkara,
                'agenda' => 'Proses persidangan sedang berlangsung',
                'ruangSidang' => $ruangSidang,
                'keterangan' => $nomorAntrian !== '' ? ('Nomor antrian aktif: ' . $nomorAntrian) : 'Sedang berlangsung',
            ];
        }
    }

    return $rows;
}

// =========================================================================
// FITUR PROXY BYPASS CORS: Mengambil data JSON tanpa diblokir oleh Browser!
// =========================================================================
if (isset($_GET['proxy'])) {
    header('Content-Type: application/json');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, proxy-revalidate");
    
    $target_url = "";
    $is_post = false;
    
    if ($_GET['proxy'] == 'ada_sidang') {
        $target_url = "https://antrian.pa-semarang.go.id/tv_media/ada_sidang";
        $is_post = true; 
    } elseif ($_GET['proxy'] == 'atas') {
        $target_url = "https://antrian.pa-semarang.go.id/tv_media/display_atas";
    } elseif ($_GET['proxy'] == 'bawah') {
        $target_url = "https://antrian.pa-semarang.go.id/tv_media/display_bawah";
    }
    
    if ($target_url !== "") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if ($is_post) { curl_setopt($ch, CURLOPT_POST, 1); }
        $result = curl_exec($ch);
        curl_close($ch);
        
        echo $result ? $result : '{}';
    }
    exit;
}
// =========================================================================

if (isset($_GET['format_jadwal'])) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, proxy-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    $tanggal = trim((string) ($_GET['tanggal'] ?? date('Y-m-d')));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $tanggal = date('Y-m-d');
    }

    $allRows = [];
    $isSyncedFromSource = false;

    // Fallback ke sumber lama jika jadwal lokal belum diisi.
    $slideResponse = info_persidangan_http_fetch('https://sipp.pa-semarang.go.id/slide_sidang');
    if (($slideResponse['status'] ?? 0) >= 200 && ($slideResponse['status'] ?? 0) < 300) {
        $allRows = info_persidangan_parse_slide_rows((string) ($slideResponse['body'] ?? ''));
        if ($allRows !== []) {
            info_persidangan_sync_local_rows($tanggal, $allRows, 'sipp_slide');
            $isSyncedFromSource = true;
        }
    }

    if ($allRows === []) {
        $localSlideFile = dirname(__DIR__, 2) . '/html/public/slide_sidang.html';
        if (is_readable($localSlideFile)) {
            $localHtml = file_get_contents($localSlideFile);
            if (is_string($localHtml) && trim($localHtml) !== '') {
                $allRows = info_persidangan_parse_slide_rows($localHtml);
                if ($allRows !== []) {
                    info_persidangan_sync_local_rows($tanggal, $allRows, 'slide_cache');
                    $isSyncedFromSource = true;
                }
            }
        }
    }

    if (!$isSyncedFromSource) {
        $allRows = info_persidangan_fetch_local_rows($tanggal);
    }

    $activeRowsForToday = $tanggal === date('Y-m-d') ? info_persidangan_fetch_active_rows() : [];
    if ($tanggal === date('Y-m-d')) {
        info_persidangan_runtime_upsert_active($tanggal, $activeRowsForToday);
    }

    if ($allRows === []) {
        $allRows = $activeRowsForToday;
    }

    $allRows = info_persidangan_append_runtime_rows($allRows, $tanggal, $activeRowsForToday);

    $allRows = info_persidangan_apply_status($allRows, $tanggal);
    
    $totalRows = count($allRows);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            * { margin: 0; padding: 0; }
            body { background: transparent; font-family: 'Segoe UI', sans-serif; padding: 10px; }
            
            .jadwal-table { width: 100%; border-collapse: collapse; background: white; table-layout: fixed; }
            
            .jadwal-table-wrapper { 
                overflow: hidden; 
                height: 450px; 
                position: relative; 
                background: white; 
                border-bottom: 2px solid #084228;
            }
            
            .jadwal-table thead { 
                display: table; 
                width: 100%; 
                table-layout: fixed; 
                background: linear-gradient(135deg, #084228, #0d6b41); 
                position: relative; 
                z-index: 10; 
            }
            
            .jadwal-table tbody { display: block; width: 100%; }
            .jadwal-table tbody tr { display: table; width: 100%; table-layout: fixed; }
            
            .jadwal-table th:nth-child(1), .jadwal-table td:nth-child(1) { width: 5%; text-align: center; }
            .jadwal-table th:nth-child(2), .jadwal-table td:nth-child(2) { width: 22%; }
            .jadwal-table th:nth-child(3), .jadwal-table td:nth-child(3) { width: 29%; }
            .jadwal-table th:nth-child(4), .jadwal-table td:nth-child(4) { width: 24%; }
            .jadwal-table th:nth-child(5), .jadwal-table td:nth-child(5) { width: 20%; }

            .jadwal-table th { color: white; padding: 14px 10px; font-size: 13px; font-weight: 600; text-transform: uppercase; border-bottom: 3px solid #ff6600; text-align: left; }
            .jadwal-table td { padding: 12px 10px; font-size: 12px; color: #333; border-bottom: 1px solid #ddd; vertical-align: middle; box-sizing: border-box; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
            
            .jadwal-table tbody tr:nth-child(odd) { background-color: #f9fafb; }
            .jadwal-table tbody tr:nth-child(even) { background-color: #f4f8f6; }
            
            .jadwal-table tbody:hover { animation-play-state: paused !important; cursor: pointer; }
            .jadwal-table tbody tr:hover { background-color: #e8f5e9; }
            
            .jadwal-table td:nth-child(1) { font-weight: 600; color: #084228; }
            .jadwal-table td:nth-child(4) { font-weight: 600; color: #ff6600; }
            .jadwal-table td:nth-child(4) {
                text-align: left;
                line-height: 1.25;
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
            }
            .jadwal-table td:nth-child(2),
            .jadwal-table td:nth-child(5) {
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
                overflow-wrap: anywhere;
                word-break: break-word;
                line-height: 1.25;
            }
            .ruang-sidang-text {
                display: inline-block;
                max-width: 100%;
                white-space: normal;
                overflow-wrap: anywhere;
                word-break: normal;
                text-align: left;
                line-height: 1.25;
            }
            .no-data { text-align: center; padding: 40px; color: #999; font-style: italic; }

            @media (max-width: 768px) {
                .jadwal-table th { padding: 10px 8px; font-size: 11px; }
                .jadwal-table td { padding: 9px 8px; font-size: 11px; }
                .jadwal-table-wrapper { height: 380px; }
            }
        </style>
    </head>
    <body>
        <div class="jadwal-table-wrapper">
            <table class="jadwal-table" data-total-rows="<?php echo $totalRows; ?>">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nomor Perkara</th>
                    <th>Agenda Sidang</th>
                    <th>Ruang Sidang</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $formatRuangSidang = function (string $value): string {
                    $clean = trim(preg_replace('/\s+/', ' ', $value));
                    if ($clean === '') {
                        return '---';
                    }

                    if (strlen($clean) <= 30) {
                        return htmlspecialchars($clean);
                    }

                    $prefix = 'Sidang Dalam Gedung ';
                    if (stripos($clean, $prefix) === 0) {
                        $suffix = trim(substr($clean, strlen($prefix)));
                        return htmlspecialchars(trim($prefix)) . '<br>' . htmlspecialchars($suffix);
                    }

                    return htmlspecialchars($clean);
                };

                if ($totalRows > 0) {
                    foreach ($allRows as $rowData) {
                        $ruangSidangDisplay = $formatRuangSidang((string)$rowData['ruangSidang']);
                        ?>
                        <tr>
                            <td><?php echo $rowData['no']; ?></td>
                            <td><?php echo htmlspecialchars($rowData['noPerkara']); ?></td>
                            <td><?php echo htmlspecialchars($rowData['agenda']); ?></td>
                            <td><span class="ruang-sidang-text"><?php echo $ruangSidangDisplay; ?></span></td>
                            <td><?php echo htmlspecialchars((string) $rowData['keterangan']); ?></td>
                        </tr>
                        <?php
                    }
                    
                    $loopCount = min(10, $totalRows);
                    for ($i = 0; $i < $loopCount; $i++) {
                        $rowData = $allRows[$i];
                        $ruangSidangDisplay = $formatRuangSidang((string)$rowData['ruangSidang']);
                        ?>
                        <tr>
                            <td><?php echo $rowData['no']; ?></td>
                            <td><?php echo htmlspecialchars($rowData['noPerkara']); ?></td>
                            <td><?php echo htmlspecialchars($rowData['agenda']); ?></td>
                            <td><span class="ruang-sidang-text"><?php echo $ruangSidangDisplay; ?></span></td>
                            <td><?php echo htmlspecialchars((string) $rowData['keterangan']); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr><td colspan="5" class="no-data">Tidak ada sidang hari ini</td></tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        </div>
    </body>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.querySelector('.jadwal-table');
            if (!table) return;

            const originalRowCount = parseInt(table.getAttribute('data-total-rows')) || 0;
            if (originalRowCount === 0) return;

            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const rows = tbody.querySelectorAll('tr');
            
            let scrollDistance = 0;
            if (rows.length > originalRowCount) {
                scrollDistance = rows[originalRowCount].getBoundingClientRect().top - rows[0].getBoundingClientRect().top;
            }

            if (scrollDistance <= 0) return;

            const visibleCount = 8; 
            const thead = table.querySelector('thead');
            const headHeight = thead ? Math.ceil(thead.getBoundingClientRect().height) : 56;
            
            const rowHeight = Math.ceil(rows[0].getBoundingClientRect().height) || 40;
            const wrapper = document.querySelector('.jadwal-table-wrapper');
            if (wrapper) wrapper.style.height = (headHeight + visibleCount * rowHeight) + 'px';

            let durationPerRow;
            if (originalRowCount <= 5) {
                durationPerRow = 10; 
            } else if (originalRowCount <= 15) {
                durationPerRow = 7;  
            } else {
                durationPerRow = 5;  
            }

            const totalDuration = originalRowCount * durationPerRow;

            const name = 'scrollRows_' + Date.now();
            const styleEl = document.createElement('style');
            styleEl.innerHTML = `@keyframes ${name} { 0% { transform: translateY(0); } 100% { transform: translateY(-${scrollDistance}px); } }`;
            document.head.appendChild(styleEl);

            tbody.style.willChange = 'transform';
            tbody.style.animation = `${name} ${totalDuration}s linear infinite 1s`;
        });
    </script>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Widget Antrian & Jadwal Sidang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root { --hijau-tua: #084228; --aksen-oranye: #ff6600; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; overflow-x: hidden; }
        .bg-hijau-elegan { background: linear-gradient(135deg, var(--hijau-tua), #0d6b41) !important; color: white !important; }
        .teks-hijau { color: var(--hijau-tua) !important; }
        .bg-oranye { background-color: var(--aksen-oranye) !important; color: white !important; }
        .kartu-utama { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; margin: 15px; }
        .header-kartu { padding: 15px; font-weight: 600; text-align: center; border-bottom: 3px solid var(--aksen-oranye); }
        .badge-elegan { background-color: var(--hijau-tua); color: white; border-radius: 8px; padding: 5px 15px; font-weight: bold; }
        .tag-antrian-besar { border-radius: 15px; border: 3px solid white; padding: 10px 20px; font-size: 28px; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .flex-container { display: flex; justify-content: space-between; align-items: center; padding: 25px; background: #fafafa; }
        .grid-ruang { display: grid; grid-template-columns: 1fr 1fr 1fr; text-align: center; padding: 15px; gap: 10px; }
        .garis-batas { border-right: 2px dashed #ccc; }
        .blink { animation: blink-animation 1s steps(5, start) infinite; }
        @keyframes blink-animation { to { color: var(--aksen-oranye); } }
        .botbar { background-color: var(--hijau-tua); color: white; text-align: center; padding: 12px; font-size: 14px; position: fixed; bottom: 0; width: 100%; z-index: 1000; }
        .botbar a { color: var(--aksen-oranye); text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div style="padding-bottom: 60px;">
    <div class="bg-hijau-elegan" style="padding: 15px; text-align: center;">
        <span style="font-size: 18px;"><i class="fas fa-gavel" style="color:var(--aksen-oranye);"></i> <strong>LIVE MONITOR ANTRIAN SIDANG</strong> | <span id="tgl_indo"></span></span> 
        <span class="bg-oranye" id="jam" style="padding: 5px 15px; border-radius: 8px; margin-left: 10px; font-weight:bold;"></span>
    </div> 

    <div class="kartu-utama">
        <div style="text-align: center; padding-top: 15px; font-weight: bold; color: #777; font-size: 14px; text-transform:uppercase; letter-spacing:1px;">Informasi Sidang Dalam Gedung</div>
        <div class="grid-ruang">
            <div class="garis-batas">
                <div class="teks-hijau" style="font-size: 13px;"><b>RUANG SIDANG UTAMA</b></div>
                <div style="color:#555; font-size: 11px; margin: 8px 0;"><span id="noperk1">---</span></div>
                <div class="badge-elegan"><span id="no1">---</span></div>
            </div>
            <div class="garis-batas">
                <div class="teks-hijau" style="font-size: 13px;"><b>RUANG SIDANG 2</b></div>
                <div style="color:#555; font-size: 11px; margin: 8px 0;"><span id="noperk2">---</span></div>
                <div class="badge-elegan"><span id="no2">---</span></div>
            </div>
            <div>
                <div class="teks-hijau" style="font-size: 13px;"><b>RUANG SIDANG 3</b></div>
                <div style="color:#555; font-size: 11px; margin: 8px 0;"><span id="noperk3">---</span></div>
                <div class="badge-elegan"><span id="no3">---</span></div>
            </div>
        </div>
    </div>

    <div style="margin: 15px; border-radius: 15px; overflow: hidden; border: 1px solid #eaeaea; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
        <div class="bg-hijau-elegan" style="padding: 10px; text-align: center; font-size: 14px; font-weight: bold;">JADWAL PERSIDANGAN HARI INI</div>
        <iframe id="sippFrame" src="?format_jadwal=1&t=0" width="100%" height="480px" frameborder="0" scrolling="no" style="display:block; border: none;"></iframe>
    </div>
</div>

<div class="botbar">
    <i class="fas fa-thumbtack" style="color: var(--aksen-oranye); margin-right: 5px;"></i> Detail jadwal pada <a href="https://sipp.pa-semarang.go.id" target="_blank">SIPP</a> atau <a href="https://lumpiapasar.pa-semarang.go.id" target="_blank">LUMPIAPASAR</a>
</div>

<script>        
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('tgl_indo').innerText = new Date().toLocaleDateString('id-ID', options);

    setInterval(updateData, 5000);
    setInterval(refreshSIPP, 900000); 
    
    updateData();

    function refreshSIPP() {
        var sippFrame = document.getElementById('sippFrame');
        sippFrame.src = '?format_jadwal=1&t=' + new Date().getTime();
    }

    // PENYEMPURNAAN PROXY BROWSER MENGGUNAKAN NATIVE JS
    function updateData() {
        var d = new Date();
        document.getElementById("jam").innerHTML = d.toLocaleTimeString([], {hour12: false});
        
        // Kita gunakan PHP Proxy kita sendiri agar kebal CORS!
        var cacheKiller = '&t=' + new Date().getTime();

        // 1. Cek apakah ada sidang
        var reqAda = new XMLHttpRequest();
        reqAda.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                try {
                    var obj = JSON.parse(this.responseText);
                    console.log("Status Sidang:", obj); // Untuk pantauan di F12
                    
                    if (obj && parseInt(obj.jml_sidang) > 0) {
                        
                        // 2. Ambil data ruangan (Bawah)
                        var reqBawah = new XMLHttpRequest();
                        reqBawah.onreadystatechange = function() {
                            if (this.readyState == 4 && this.status == 200) {
                                try {
                                    var objBawah = JSON.parse(this.responseText);
                                    if (objBawah !== null) {
                                        for (var i = 0; i < objBawah.length; i++) {
                                            var rs = parseInt(objBawah[i].r_sidang);
                                            // Hanya proses ruang 1, 2, 3
                                            if (rs >= 1 && rs <= 3) {
                                                var noEl = document.getElementById("no" + rs);
                                                var perkEl = document.getElementById("noperk" + rs);
                                                if(noEl) noEl.innerHTML = objBawah[i].no_antrian;
                                                if(perkEl) perkEl.innerHTML = objBawah[i].no_perk;
                                            }
                                        }
                                    }
                                } catch(e) {}
                            }
                        };
                        reqBawah.open("GET", "?proxy=bawah" + cacheKiller, true);
                        reqBawah.send();

                    }
                } catch(e) { 
                    // Jika memang JSON kosong karena di server asli belum ada yg dipanggil
                }
            }
        };
        // Tembak ke file kita sendiri, bukan ke antrian.pa-semarang (Bypass CORS)
        reqAda.open("GET", "?proxy=ada_sidang" + cacheKiller, true);
        reqAda.send();
    }
</script>
</body>
</html>
<?php
/* developed by zhayyn™ */
?>