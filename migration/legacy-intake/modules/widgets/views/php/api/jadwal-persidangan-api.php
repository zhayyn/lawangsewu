<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function jp_load_env(string $path): void
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

function jp_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || trim($value) === '') {
        return $default;
    }
    return trim($value);
}

function jp_json(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function jp_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $root = dirname(__DIR__, 4);
    jp_load_env($root . '/.env');
    jp_load_env(dirname(__DIR__, 2) . '/config/.env');

    $host = jp_env('LW_JADWAL_DB_HOST', jp_env('LW_STAT_DB_HOST', 'localhost'));
    $user = jp_env('LW_JADWAL_DB_USER', jp_env('LW_STAT_DB_USER', 'admin'));
    $pass = jp_env('LW_JADWAL_DB_PASS', jp_env('LW_STAT_DB_PASS', ''));
    $name = jp_env('LW_JADWAL_DB_NAME', jp_env('LW_STAT_DB_NAME', 'sipp'));

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

    return $pdo;
}

function jp_date(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return date('Y-m-d');
    }
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if (!$dt) {
        return date('Y-m-d');
    }
    return $dt->format('Y-m-d');
}

function jp_parse_slide_rows(string $html): array
{
    if (trim($html) === '') {
        return [];
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query('//table//tr');
    if ($rows->length <= 0) {
        return [];
    }

    $result = [];
    $currentBlock = [];

    foreach ($rows as $row) {
        $cells = $row->getElementsByTagName('td');
        if ($cells->length === 0) {
            continue;
        }

        $rowspan = $cells->item(0)->getAttribute('rowspan');
        if ($rowspan !== '') {
            if (!empty($currentBlock['nomor_perkara'])) {
                $result[] = [
                    'nomor_perkara' => $currentBlock['nomor_perkara'],
                    'agenda' => $currentBlock['agenda'] ?? 'Sidang',
                    'ruang_sidang' => $currentBlock['ruang_sidang'] ?? 'Ruang Sidang',
                    'keterangan' => $currentBlock['keterangan'] ?? '-',
                ];
            }
            $currentBlock = [];
        }

        if ($cells->length >= 3) {
            $label = strtoupper(trim($cells->item(1)->textContent));
            $value = trim($cells->item(2)->textContent);
        } elseif ($cells->length === 2) {
            $label = strtoupper(trim($cells->item(0)->textContent));
            $value = trim($cells->item(1)->textContent);
        } else {
            continue;
        }

        if (strpos($label, 'NO PERKARA') !== false || strpos($label, 'NOMOR PERKARA') !== false) {
            $currentBlock['nomor_perkara'] = $value;
        } elseif (strpos($label, 'AGENDA') !== false) {
            $currentBlock['agenda'] = $value;
        } elseif (strpos($label, 'RUANG') !== false || strpos($label, 'PENGADILAN') !== false) {
            $currentBlock['ruang_sidang'] = $value;
        } elseif (strpos($label, 'KETERANGAN') !== false || strpos($label, 'HAKIM') !== false || strpos($label, 'KETUA') !== false) {
            if (empty($currentBlock['keterangan'])) {
                $currentBlock['keterangan'] = $value;
            }
        }
    }

    if (!empty($currentBlock['nomor_perkara'])) {
        $result[] = [
            'nomor_perkara' => $currentBlock['nomor_perkara'],
            'agenda' => $currentBlock['agenda'] ?? 'Sidang',
            'ruang_sidang' => $currentBlock['ruang_sidang'] ?? 'Ruang Sidang',
            'keterangan' => $currentBlock['keterangan'] ?? '-',
        ];
    }

    return $result;
}

try {
    $db = jp_db();
    $action = trim((string) ($_GET['action'] ?? 'list'));

    if ($action === 'list') {
        $tanggal = jp_date($_GET['tanggal'] ?? date('Y-m-d'));
        $stmt = $db->prepare('SELECT id, tanggal_sidang, nomor_perkara, agenda, ruang_sidang, keterangan, urutan, sumber FROM jadwal_persidangan_local WHERE tanggal_sidang = :tanggal ORDER BY urutan ASC, id ASC');
        $stmt->execute([':tanggal' => $tanggal]);
        $rows = $stmt->fetchAll();
        jp_json(200, ['ok' => true, 'tanggal' => $tanggal, 'rows' => $rows]);
    }

    if ($action === 'save') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jp_json(405, ['ok' => false, 'message' => 'Method not allowed']);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $tanggal = jp_date($_POST['tanggal_sidang'] ?? date('Y-m-d'));
        $nomorPerkara = trim((string) ($_POST['nomor_perkara'] ?? ''));
        $agenda = trim((string) ($_POST['agenda'] ?? ''));
        $ruangSidang = trim((string) ($_POST['ruang_sidang'] ?? ''));
        $keterangan = trim((string) ($_POST['keterangan'] ?? ''));
        $urutan = (int) ($_POST['urutan'] ?? 0);

        if ($nomorPerkara === '' || $agenda === '' || $ruangSidang === '') {
            jp_json(422, ['ok' => false, 'message' => 'Nomor perkara, agenda, dan ruang sidang wajib diisi']);
        }

        if ($id > 0) {
            $stmt = $db->prepare('UPDATE jadwal_persidangan_local SET tanggal_sidang = :tanggal, nomor_perkara = :nomor_perkara, agenda = :agenda, ruang_sidang = :ruang_sidang, keterangan = :keterangan, urutan = :urutan, sumber = :sumber, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':tanggal' => $tanggal,
                ':nomor_perkara' => $nomorPerkara,
                ':agenda' => $agenda,
                ':ruang_sidang' => $ruangSidang,
                ':keterangan' => $keterangan,
                ':urutan' => $urutan,
                ':sumber' => 'manual',
                ':id' => $id,
            ]);
        } else {
            if ($urutan <= 0) {
                $maxStmt = $db->prepare('SELECT COALESCE(MAX(urutan), 0) AS max_urutan FROM jadwal_persidangan_local WHERE tanggal_sidang = :tanggal');
                $maxStmt->execute([':tanggal' => $tanggal]);
                $maxRow = $maxStmt->fetch();
                $urutan = (int) ($maxRow['max_urutan'] ?? 0) + 10;
            }

            $stmt = $db->prepare('INSERT INTO jadwal_persidangan_local (tanggal_sidang, nomor_perkara, agenda, ruang_sidang, keterangan, urutan, sumber, created_at, updated_at) VALUES (:tanggal, :nomor_perkara, :agenda, :ruang_sidang, :keterangan, :urutan, :sumber, NOW(), NOW())');
            $stmt->execute([
                ':tanggal' => $tanggal,
                ':nomor_perkara' => $nomorPerkara,
                ':agenda' => $agenda,
                ':ruang_sidang' => $ruangSidang,
                ':keterangan' => $keterangan,
                ':urutan' => $urutan,
                ':sumber' => 'manual',
            ]);
            $id = (int) $db->lastInsertId();
        }

        jp_json(200, ['ok' => true, 'id' => $id, 'message' => 'Data jadwal tersimpan']);
    }

    if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jp_json(405, ['ok' => false, 'message' => 'Method not allowed']);
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            jp_json(422, ['ok' => false, 'message' => 'ID tidak valid']);
        }

        $stmt = $db->prepare('DELETE FROM jadwal_persidangan_local WHERE id = :id');
        $stmt->execute([':id' => $id]);
        jp_json(200, ['ok' => true, 'message' => 'Data jadwal dihapus']);
    }

    if ($action === 'import_cache') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jp_json(405, ['ok' => false, 'message' => 'Method not allowed']);
        }

        $tanggal = jp_date($_POST['tanggal_sidang'] ?? date('Y-m-d'));
        $cacheFile = dirname(__DIR__, 2) . '/html/public/slide_sidang.html';
        if (!is_readable($cacheFile)) {
            jp_json(404, ['ok' => false, 'message' => 'File cache slide_sidang.html tidak ditemukan']);
        }

        $html = (string) file_get_contents($cacheFile);
        $parsedRows = jp_parse_slide_rows($html);
        if ($parsedRows === []) {
            jp_json(422, ['ok' => false, 'message' => 'Cache slide tidak berisi jadwal yang valid']);
        }

        $db->beginTransaction();
        $deleteStmt = $db->prepare('DELETE FROM jadwal_persidangan_local WHERE tanggal_sidang = :tanggal');
        $deleteStmt->execute([':tanggal' => $tanggal]);

        $insertStmt = $db->prepare('INSERT INTO jadwal_persidangan_local (tanggal_sidang, nomor_perkara, agenda, ruang_sidang, keterangan, urutan, sumber, created_at, updated_at) VALUES (:tanggal, :nomor_perkara, :agenda, :ruang_sidang, :keterangan, :urutan, :sumber, NOW(), NOW())');

        $urutan = 10;
        foreach ($parsedRows as $row) {
            $insertStmt->execute([
                ':tanggal' => $tanggal,
                ':nomor_perkara' => trim((string) ($row['nomor_perkara'] ?? '')),
                ':agenda' => trim((string) ($row['agenda'] ?? 'Sidang')),
                ':ruang_sidang' => trim((string) ($row['ruang_sidang'] ?? 'Ruang Sidang')),
                ':keterangan' => trim((string) ($row['keterangan'] ?? '-')),
                ':urutan' => $urutan,
                ':sumber' => 'cache_slide',
            ]);
            $urutan += 10;
        }

        $db->commit();
        jp_json(200, ['ok' => true, 'message' => 'Impor cache berhasil', 'total' => count($parsedRows)]);
    }

    jp_json(404, ['ok' => false, 'message' => 'Action tidak dikenal']);
} catch (Throwable $e) {
    jp_json(500, ['ok' => false, 'message' => 'Terjadi kesalahan server', 'error' => $e->getMessage()]);
}
