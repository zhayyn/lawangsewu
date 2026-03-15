<?php
/* developed by dubes favour-it */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function sd_load_env(string $path): void
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

function sd_env(string $key, string $default = ''): string
{
    $v = getenv($key);
    if ($v === false || trim($v) === '') {
        return $default;
    }
    return trim($v);
}

function sd_log(string $msg): void
{
    $root = dirname(__DIR__, 4);
    $logPath = $root . '/logs/statistik-data.log';
    @file_put_contents($logPath, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function sd_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $root = dirname(__DIR__, 4);
    sd_load_env($root . '/.env');
    sd_load_env(dirname(__DIR__, 2) . '/config/.env');

    $host = sd_env('LW_STAT_DB_HOST', 'localhost');
    $user = sd_env('LW_STAT_DB_USER', 'admin');
    $pass = sd_env('LW_STAT_DB_PASS', '');
    $name = sd_env('LW_STAT_DB_NAME', 'sipp');

    $pdo = new PDO(
        'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    return $pdo;
}

function sd_fetch_one(PDO $db, string $sql, array $params = []): array
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return is_array($row) ? $row : [];
}

function sd_fetch_all(PDO $db, string $sql, array $params = []): array
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function sd_int($value): int
{
    return (int)($value ?? 0);
}

function sd_summary_perkara(PDO $db, int $year): array
{
    $sisaLalu = sd_fetch_one($db, "
        SELECT COUNT(*) jml
        FROM perkara a
        LEFT JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
        WHERE (YEAR(c.tanggal_minutasi) > :prev OR c.tanggal_minutasi IS NULL)
          AND YEAR(a.tanggal_pendaftaran) < :year
    ", [':prev' => $year - 1, ':year' => $year]);

    $masuk = sd_fetch_one($db, "
        SELECT COUNT(*) jml
        FROM perkara a
        WHERE YEAR(a.tanggal_pendaftaran) = :year
    ", [':year' => $year]);

    $putus = sd_fetch_one($db, "
        SELECT COUNT(*) jml
        FROM perkara a
        LEFT JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
        WHERE YEAR(c.tanggal_putusan) = :year
    ", [':year' => $year]);

    $belumPutus = sd_fetch_one($db, "
        SELECT COUNT(*) jml
        FROM perkara a
        LEFT JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
        WHERE c.tanggal_putusan IS NULL OR LENGTH(TRIM(c.tanggal_putusan)) = 0
    ");

    return [
        'sisa_lalu' => sd_int($sisaLalu['jml'] ?? 0),
        'masuk' => sd_int($masuk['jml'] ?? 0),
        'putus' => sd_int($putus['jml'] ?? 0),
        'belum_putus' => sd_int($belumPutus['jml'] ?? 0),
    ];
}

function sd_perbandingan_tahun(PDO $db, int $year): array
{
    $rows = sd_fetch_all($db, "
        SELECT b.nama kategori, a.jenis_perkara_text jenis,
               SUM(IF(YEAR(a.tanggal_pendaftaran)=:y4,1,0)) th4,
               SUM(IF(YEAR(a.tanggal_pendaftaran)=:y3,1,0)) th3,
               SUM(IF(YEAR(a.tanggal_pendaftaran)=:y2,1,0)) th2,
               SUM(IF(YEAR(a.tanggal_pendaftaran)=:y1,1,0)) th1,
               SUM(IF(YEAR(a.tanggal_pendaftaran)=:y0,1,0)) th0
        FROM perkara a
        LEFT JOIN alur_perkara b ON b.id = a.alur_perkara_id
        WHERE YEAR(a.tanggal_pendaftaran) BETWEEN :yMin AND :yMax
        GROUP BY a.jenis_perkara_id
        ORDER BY a.alur_perkara_id ASC, a.jenis_perkara_text ASC
    ", [
        ':y4' => $year - 4,
        ':y3' => $year - 3,
        ':y2' => $year - 2,
        ':y1' => $year - 1,
        ':y0' => $year,
        ':yMin' => $year - 5,
        ':yMax' => $year,
    ]);

    $totals = ['th4' => 0, 'th3' => 0, 'th2' => 0, 'th1' => 0, 'th0' => 0];
    foreach ($rows as &$row) {
        $row['th4'] = sd_int($row['th4'] ?? 0);
        $row['th3'] = sd_int($row['th3'] ?? 0);
        $row['th2'] = sd_int($row['th2'] ?? 0);
        $row['th1'] = sd_int($row['th1'] ?? 0);
        $row['th0'] = sd_int($row['th0'] ?? 0);
        $totals['th4'] += $row['th4'];
        $totals['th3'] += $row['th3'];
        $totals['th2'] += $row['th2'];
        $totals['th1'] += $row['th1'];
        $totals['th0'] += $row['th0'];
    }
    unset($row);

    return ['rows' => $rows, 'totals' => $totals];
}

function sd_bulanan_perkara(PDO $db, int $year): array
{
    return sd_fetch_all($db, "
        SELECT MONTH(a.tanggal_pendaftaran) bln, COUNT(*) jml
        FROM perkara a
        WHERE YEAR(a.tanggal_pendaftaran)=:year
        GROUP BY MONTH(a.tanggal_pendaftaran)
        ORDER BY bln
    ", [':year' => $year]);
}

function sd_pie_alur_perkara(PDO $db, int $year): array
{
    return sd_fetch_all($db, "
        SELECT c.nama label, COUNT(*) jml
        FROM perkara a
        LEFT JOIN alur_perkara c ON c.id = a.alur_perkara_id
        WHERE YEAR(a.tanggal_pendaftaran)=:year
        GROUP BY a.alur_perkara_id
    ", [':year' => $year]);
}

function sd_pie_jenis_perkara(PDO $db, int $year): array
{
    return sd_fetch_all($db, "
        SELECT a.jenis_perkara_text label, COUNT(*) jml
        FROM perkara a
        WHERE YEAR(a.tanggal_pendaftaran)=:year
        GROUP BY a.jenis_perkara_id
    ", [':year' => $year]);
}

function sd_ecourt(PDO $db, int $year): array
{
    $summary = [];

    $summary['sisa_lalu'] = sd_int(sd_fetch_one($db, "
        SELECT COUNT(*) jml
        FROM perkara a
        LEFT JOIN perkara_efiling_id b ON b.perkara_id = a.perkara_id
        LEFT JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
        WHERE (YEAR(c.tanggal_minutasi) > :prev OR c.tanggal_minutasi IS NULL)
          AND YEAR(a.tanggal_pendaftaran) < :year
          AND b.efiling_id IS NOT NULL
    ", [':prev' => $year - 1, ':year' => $year])['jml'] ?? 0);

    $summary['masuk'] = sd_int(sd_fetch_one($db, "
        SELECT COUNT(*) jml
        FROM perkara a
        LEFT JOIN perkara_efiling_id b ON b.perkara_id = a.perkara_id
        WHERE YEAR(a.tanggal_pendaftaran)=:year
          AND b.efiling_id IS NOT NULL
    ", [':year' => $year])['jml'] ?? 0);

    $summary['putus'] = sd_int(sd_fetch_one($db, "
        SELECT COUNT(*) jml
        FROM perkara a
        LEFT JOIN perkara_efiling_id b ON b.perkara_id = a.perkara_id
        LEFT JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
        WHERE b.efiling_id IS NOT NULL
          AND YEAR(c.tanggal_putusan)=:year
    ", [':year' => $year])['jml'] ?? 0);

    $summary['belum_putus'] = sd_int(sd_fetch_one($db, "
        SELECT COUNT(*) jml
        FROM perkara a
        LEFT JOIN perkara_efiling_id b ON b.perkara_id = a.perkara_id
        LEFT JOIN perkara_putusan c ON c.perkara_id = a.perkara_id
        WHERE b.efiling_id IS NOT NULL
          AND (c.tanggal_putusan IS NULL OR LENGTH(TRIM(c.tanggal_putusan)) = 0)
    ")['jml'] ?? 0);

    $bulanan = sd_fetch_all($db, "
        SELECT MONTH(a.tanggal_pendaftaran) bln, COUNT(*) jml
        FROM perkara a
        LEFT JOIN perkara_efiling_id b ON b.perkara_id = a.perkara_id
        WHERE YEAR(a.tanggal_pendaftaran)=:year
          AND b.efiling_id IS NOT NULL
        GROUP BY MONTH(a.tanggal_pendaftaran)
        ORDER BY bln
    ", [':year' => $year]);

    $alur = sd_fetch_all($db, "
        SELECT c.nama label, COUNT(*) jml
        FROM perkara a
        LEFT JOIN perkara_efiling_id b ON b.perkara_id = a.perkara_id
        LEFT JOIN alur_perkara c ON c.id = a.alur_perkara_id
        WHERE YEAR(a.tanggal_pendaftaran)=:year
          AND b.efiling_id IS NOT NULL
        GROUP BY a.alur_perkara_id
    ", [':year' => $year]);

    $jenis = sd_fetch_all($db, "
        SELECT a.jenis_perkara_text label, COUNT(*) jml
        FROM perkara a
        LEFT JOIN perkara_efiling_id b ON b.perkara_id = a.perkara_id
        WHERE YEAR(a.tanggal_pendaftaran)=:year
          AND b.efiling_id IS NOT NULL
        GROUP BY a.jenis_perkara_id
    ", [':year' => $year]);

    return [
        'summary' => $summary,
        'bulanan' => $bulanan,
        'alur' => $alur,
        'jenis' => $jenis,
    ];
}

function sd_hakim(PDO $db, int $year): array
{
    $summaryHakim = sd_fetch_all($db, "
        SELECT h.hakim_nama,
               SUM(IF(YEAR(p.tanggal_pendaftaran)=:prev AND (YEAR(pp.tanggal_putusan)>=:year OR pp.tanggal_putusan IS NULL),1,0)) AS sisa,
               SUM(IF(YEAR(p.tanggal_pendaftaran)=:year,1,0)) AS diterima,
               SUM(IF(TIMESTAMPDIFF(MONTH, p.tanggal_pendaftaran, pp.tanggal_putusan)=0,1,0)) AS a0,
               SUM(IF(TIMESTAMPDIFF(MONTH, p.tanggal_pendaftaran, pp.tanggal_putusan)=1,1,0)) AS a1,
               SUM(IF(TIMESTAMPDIFF(MONTH, p.tanggal_pendaftaran, pp.tanggal_putusan)=2,1,0)) AS a2,
               SUM(IF(TIMESTAMPDIFF(MONTH, p.tanggal_pendaftaran, pp.tanggal_putusan)=3,1,0)) AS a3,
               SUM(IF(TIMESTAMPDIFF(MONTH, p.tanggal_pendaftaran, pp.tanggal_putusan)=4,1,0)) AS a4,
               SUM(IF(TIMESTAMPDIFF(MONTH, p.tanggal_pendaftaran, pp.tanggal_putusan)>=5,1,0)) AS a5,
               SUM(IF(pp.tanggal_putusan IS NULL,1,0)) AS sisa_sekarang
        FROM perkara_hakim_pn h
        LEFT JOIN perkara p ON p.perkara_id = h.perkara_id
        LEFT JOIN perkara_putusan pp ON pp.perkara_id = p.perkara_id
        WHERE p.perkara_id IS NOT NULL
          AND YEAR(p.tanggal_pendaftaran) BETWEEN :prev AND :year
        GROUP BY h.hakim_nama
        HAVING sisa > 0 OR diterima > 0
        ORDER BY h.hakim_nama ASC
    ", [':prev' => $year - 1, ':year' => $year]);

    $alurPenyelesaian = sd_fetch_all($db, "
        SELECT ap.nama AS alur,
               SUM(IF(TIMESTAMPDIFF(DAY, p.tanggal_pendaftaran, pp.tanggal_putusan) <= 120,1,0)) AS j_ok,
               SUM(IF(TIMESTAMPDIFF(DAY, p.tanggal_pendaftaran, pp.tanggal_putusan) > 120,1,0)) AS j5,
               COUNT(pp.perkara_id) AS jml_putusan,
               ROUND(AVG(TIMESTAMPDIFF(DAY, p.tanggal_pendaftaran, pp.tanggal_putusan)),0) AS avg_putus_hari,
               MIN(TIMESTAMPDIFF(DAY, p.tanggal_pendaftaran, pp.tanggal_putusan)) AS putus_tercepat,
               MAX(TIMESTAMPDIFF(DAY, p.tanggal_pendaftaran, pp.tanggal_putusan)) AS putus_terlama
        FROM perkara_putusan pp
        LEFT JOIN perkara p ON p.perkara_id = pp.perkara_id
        LEFT JOIN alur_perkara ap ON ap.id = p.alur_perkara_id
        WHERE YEAR(pp.tanggal_putusan) = :year
        GROUP BY p.alur_perkara_id
        ORDER BY ap.nama ASC
    ", [':year' => $year]);

    return [
        'summary_hakim' => $summaryHakim,
        'penyelesaian_by_alur' => $alurPenyelesaian,
    ];
}

$hal = strtolower(trim((string)($_GET['hal'] ?? 'perkara')));
$allowed = ['perkara', 'ecourt', 'hakim'];
if (!in_array($hal, $allowed, true)) {
    $hal = 'perkara';
}

$tahun = (int)($_GET['tahun'] ?? date('Y'));
$nowYear = (int)date('Y');
if ($tahun < 2015 || $tahun > ($nowYear + 1)) {
    $tahun = $nowYear;
}

try {
    $db = sd_db();
    $response = [
        'ok' => true,
        'hal' => $hal,
        'tahun' => $tahun,
        'ringkasan' => sd_summary_perkara($db, $tahun),
    ];

    if ($hal === 'perkara') {
        $banding = sd_perbandingan_tahun($db, $tahun);
        $response['rows'] = $banding['rows'];
        $response['totals'] = $banding['totals'];
        $response['perkara_bulanan'] = sd_bulanan_perkara($db, $tahun);
        $response['perkara_alur'] = sd_pie_alur_perkara($db, $tahun);
        $response['perkara_jenis'] = sd_pie_jenis_perkara($db, $tahun);
    } elseif ($hal === 'ecourt') {
        $ec = sd_ecourt($db, $tahun);
        $response['summary'] = $ec['summary'];
        $response['bulanan'] = $ec['bulanan'];
        $response['alur'] = $ec['alur'];
        $response['jenis'] = $ec['jenis'];
    } else {
        $hk = sd_hakim($db, $tahun);
        $response['summary_hakim'] = $hk['summary_hakim'];
        $response['penyelesaian_by_alur'] = $hk['penyelesaian_by_alur'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    sd_log('API error hal=' . $hal . ': ' . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'message' => 'Gagal memuat data statistik. Cek konfigurasi database/statistik.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/* developed by dubes favour-it */
