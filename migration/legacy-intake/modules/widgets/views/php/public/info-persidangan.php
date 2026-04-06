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

    $html_sipp = '';
    $http_code = 0;
    $error_log = array();
    $allRows = array();

    $buildRows = function (array $rawRows): array {
        $rows = array();
        $no = 1;

        foreach ($rawRows as $rawRow) {
            $noPerkara = trim((string) ($rawRow['noPerkara'] ?? $rawRow['nomor_perkara'] ?? ''));
            if ($noPerkara === '') {
                continue;
            }

            $agenda = trim((string) ($rawRow['agenda'] ?? ''));
            $ruangSidang = trim((string) ($rawRow['ruangSidang'] ?? $rawRow['ruang_sidang'] ?? ''));
            // Check both 'status' and 'keterangan' fields, prioritize 'status' if present
            $keterangan = trim((string) ($rawRow['status'] ?? $rawRow['keterangan'] ?? ''));

            $rows[] = array(
                'no' => $no++,
                'noPerkara' => $noPerkara,
                'agenda' => $agenda !== '' ? $agenda : 'Sidang',
                'ruangSidang' => $ruangSidang !== '' ? $ruangSidang : 'Ruang Sidang',
                'keterangan' => $keterangan !== '' ? $keterangan : 'Terjadwal',
            );
        }

        return $rows;
    };

    $parseSlideRows = function (string $html): array {
        if (trim($html) === '') {
            return array();
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//table//tr');
        if (!$rows || $rows->length === 0) {
            return array();
        }

        $parsed = array();
        $currentBlock = array();

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length == 0) {
                continue;
            }

            $rowspan = $cells->item(0)->getAttribute('rowspan');
            if (!empty($rowspan)) {
                if (!empty($currentBlock['noPerkara'])) {
                    $parsed[] = $currentBlock;
                }
                $currentBlock = array();
            }

            if ($cells->length >= 3) {
                $label = strtoupper(trim($cells->item(1)->textContent));
                $value = trim($cells->item(2)->textContent);
            } elseif ($cells->length == 2) {
                $label = strtoupper(trim($cells->item(0)->textContent));
                $value = trim($cells->item(1)->textContent);
            } else {
                continue;
            }

            if (strpos($label, 'NO PERKARA') !== false || strpos($label, 'NOMOR PERKARA') !== false) {
                $currentBlock['noPerkara'] = $value;
            } elseif (strpos($label, 'AGENDA') !== false) {
                $currentBlock['agenda'] = $value;
            } elseif (strpos($label, 'RUANG') !== false || strpos($label, 'PENGADILAN') !== false) {
                $currentBlock['ruangSidang'] = $value;
            } elseif (strpos($label, 'KETERANGAN') !== false || strpos($label, 'HAKIM') !== false || strpos($label, 'KETUA') !== false) {
                if (empty($currentBlock['keterangan'])) {
                    $currentBlock['keterangan'] = $value;
                }
            }
        }

        if (!empty($currentBlock['noPerkara'])) {
            $parsed[] = $currentBlock;
        }

        return $parsed;
    };

    $loadEnv = function (string $path): void {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), "\"'");
            if ($key !== '') {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    };

    $env = function (string $key, string $default = ''): string {
        $value = getenv($key);
        if ($value === false || trim((string) $value) === '') {
            return $default;
        }
        return trim((string) $value);
    };

    // Prioritas utama: ambil jadwal langsung dari database SIPP (lebih stabil dari scraping).
    try {
        $workspaceRoot = dirname(__DIR__, 4);
        $loadEnv($workspaceRoot . '/.env');
        $loadEnv(dirname(__DIR__, 2) . '/config/.env');

        $dbHost = $env('LW_JADWAL_DB_HOST', $env('LW_STAT_DB_HOST', '192.168.88.10'));
        $dbUser = $env('LW_JADWAL_DB_USER', $env('LW_STAT_DB_USER', 'admin'));
        $dbPass = $env('LW_JADWAL_DB_PASS', $env('LW_STAT_DB_PASS', 'R4h4514@'));
        $dbName = $env('LW_JADWAL_DB_NAME', $env('LW_STAT_DB_NAME', 'sipp'));

        $pdo = new PDO(
            'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
            $dbUser,
            $dbPass,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            )
        );

        try {
            $stmtSipp = $pdo->prepare(
                'SELECT p.nomor_perkara, pjs.agenda, pjs.ruangan AS ruang_sidang,
                        pjs.keterangan AS keterangan_manual, pjs.dihadiri_oleh
                 FROM perkara_jadwal_sidang pjs
                 LEFT JOIN perkara p ON p.perkara_id = pjs.perkara_id
                 WHERE pjs.tanggal_sidang = CURDATE()
                 ORDER BY COALESCE(pjs.jam_sidang, "00:00:00") ASC, pjs.urutan ASC, pjs.id ASC'
            );
            $stmtSipp->execute();
            $sippRows = $stmtSipp->fetchAll();
            if (is_array($sippRows) && count($sippRows) > 0) {
                // Status awal berdasarkan dihadiri_oleh:
                // - Keterangan manual jika ada di kolom keterangan SIPP
                // - Selesai Sidang jika dihadiri_oleh sudah diisi (kehadiran sudah dicatat = sidang sudah berlangsung)
                // - Menunggu Sidang jika belum ada data kehadiran
                foreach ($sippRows as &$row) {
                    $ket = trim((string)($row['keterangan_manual'] ?? ''));
                    if ($ket !== '') {
                        $row['status'] = $ket;
                    } elseif ($row['dihadiri_oleh'] !== null) {
                        $row['status'] = 'Selesai Sidang';
                    } else {
                        $row['status'] = 'Menunggu Sidang';
                    }
                }
                unset($row);

                $allRows = $buildRows($sippRows);
                $error_log[] = 'Source: perkara_jadwal_sidang (live DB)';
            }
        } catch (Throwable $e) {
            $error_log[] = 'SIPP schedule query error: ' . $e->getMessage();
        }

        if (count($allRows) === 0) {
            try {
                $stmtLocal = $pdo->prepare('SELECT nomor_perkara, agenda, ruang_sidang, keterangan FROM jadwal_persidangan_local WHERE tanggal_sidang = CURDATE() ORDER BY urutan ASC, id ASC');
                $stmtLocal->execute();
                $localRows = $stmtLocal->fetchAll();
                if (is_array($localRows) && count($localRows) > 0) {
                    $allRows = $buildRows($localRows);
                    $error_log[] = 'Source: jadwal_persidangan_local (manual DB)';
                }
            } catch (Throwable $e) {
                $error_log[] = 'Local table query skipped: ' . $e->getMessage();
            }
        }
    } catch (Throwable $e) {
        $error_log[] = 'DB fallback error: ' . $e->getMessage();
    }

    // Fallback jika DB kosong/tidak bisa diakses: coba scrape slide SIPP.
    if (count($allRows) === 0) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://sipp.pa-semarang.go.id/slide_sidang');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $html_sipp = (string) curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curl_error !== '') {
            $error_log[] = 'CURL Error: ' . $curl_error;
        }
        if ($http_code >= 400) {
            $error_log[] = 'HTTP Error: ' . $http_code;
        }
        if (strpos($html_sipp, 'cf_chl') !== false || strpos($html_sipp, 'Just a moment') !== false) {
            $error_log[] = 'Cloudflare challenge detected';
        }

        $slideRows = $parseSlideRows($html_sipp);
        if (count($slideRows) > 0) {
            $allRows = $buildRows($slideRows);
            $error_log[] = 'Source: slide_sidang_remote';
        }
    }

    // Fallback lanjutan: gunakan cache slide lokal.
    if (count($allRows) === 0) {
        $cacheFile = dirname(__DIR__, 2) . '/html/public/slide_sidang.html';
        if (is_readable($cacheFile)) {
            $cacheHtml = (string) file_get_contents($cacheFile);
            $cacheRows = $parseSlideRows($cacheHtml);
            if (count($cacheRows) > 0) {
                $allRows = $buildRows($cacheRows);
                $error_log[] = 'Source: slide_sidang_cache';
            }
        }
    }

    // Fallback terakhir: tampilkan perkara yang sedang dipanggil dari antrian.
    if (count($allRows) === 0) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://antrian.pa-semarang.go.id/tv_media/display_bawah');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $antrianRaw = (string) curl_exec($ch);
        curl_close($ch);

        $antrianRows = json_decode($antrianRaw, true);
        if (is_array($antrianRows) && count($antrianRows) > 0) {
            $mappedRows = array();
            foreach ($antrianRows as $antrianRow) {
                $mappedRows[] = array(
                    'nomor_perkara' => (string) ($antrianRow['no_perk'] ?? ''),
                    'agenda' => 'Sedang berlangsung',
                    'ruang_sidang' => (string) ($antrianRow['nama_ruang'] ?? ''),
                    'keterangan' => 'Antrian ' . (string) ($antrianRow['no_antrian'] ?? '-'),
                );
            }
            $allRows = $buildRows($mappedRows);
            $error_log[] = 'Source: antrian_display_bawah';
        }
    }

    // Override keterangan dengan status live dari display antrian (Sedang Sidang)
    // Fetch satu kali dari antrian API – cepat karena tidak akan di-cache
    $sedangSidangSet = array();
    $chLive = curl_init();
    curl_setopt($chLive, CURLOPT_URL, 'https://antrian.pa-semarang.go.id/tv_media/display_bawah');
    curl_setopt($chLive, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($chLive, CURLOPT_TIMEOUT, 5);
    curl_setopt($chLive, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($chLive, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chLive, CURLOPT_SSL_VERIFYHOST, false);
    $liveBawah = (string) curl_exec($chLive);
    curl_close($chLive);
    if ($liveBawah !== '') {
        $liveBawahRows = json_decode($liveBawah, true);
        if (is_array($liveBawahRows)) {
            foreach ($liveBawahRows as $lb) {
                $noPerk = trim((string)($lb['no_perk'] ?? ''));
                if ($noPerk !== '') {
                    $sedangSidangSet[$noPerk] = true;
                }
            }
        }
    }
    // Terapkan override "Sedang Sidang" pada baris yang cocok
    foreach ($allRows as &$row) {
        if (isset($sedangSidangSet[$row['noPerkara']])) {
            $row['keterangan'] = 'Sedang Sidang';
        }
    }
    unset($row);

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

            /* Status keterangan sidang */
            .status-sedang   { color: #c0392b; font-weight: 700; }
            .status-selesai  { color: #27ae60; font-weight: 600; }
            .status-menunggu { color: #7f8c8d; }
            .status-manual   { color: #2980b9; }

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

                $formatKeterangan = function (string $ket): string {
                    if ($ket === 'Sedang Sidang')  return '<span class="status-sedang">&#9654; Sedang Sidang</span>';
                    if ($ket === 'Selesai Sidang') return '<span class="status-selesai">&#10003; Selesai Sidang</span>';
                    if ($ket === 'Menunggu Sidang' || $ket === 'Terjadwal') return '<span class="status-menunggu">Menunggu Sidang</span>';
                    return '<span class="status-manual">' . htmlspecialchars($ket) . '</span>';
                };

                if ($totalRows > 0) {
                    foreach ($allRows as $rowData) {
                        $ruangSidangDisplay = $formatRuangSidang((string)$rowData['ruangSidang']);
                        $ket = (string)$rowData['keterangan'];
                        ?>
                        <tr data-noperkara="<?php echo htmlspecialchars($rowData['noPerkara']); ?>">
                            <td><?php echo $rowData['no']; ?></td>
                            <td><?php echo htmlspecialchars($rowData['noPerkara']); ?></td>
                            <td><?php echo htmlspecialchars($rowData['agenda']); ?></td>
                            <td><span class="ruang-sidang-text"><?php echo $ruangSidangDisplay; ?></span></td>
                            <td class="td-keterangan" data-base="<?php echo htmlspecialchars($ket); ?>"><?php echo $formatKeterangan($ket); ?></td>
                        </tr>
                        <?php
                    }

                    $loopCount = min(10, $totalRows);
                    for ($i = 0; $i < $loopCount; $i++) {
                        $rowData = $allRows[$i];
                        $ruangSidangDisplay = $formatRuangSidang((string)$rowData['ruangSidang']);
                        $ket = (string)$rowData['keterangan'];
                        ?>
                        <tr data-noperkara="<?php echo htmlspecialchars($rowData['noPerkara']); ?>">
                            <td><?php echo $rowData['no']; ?></td>
                            <td><?php echo htmlspecialchars($rowData['noPerkara']); ?></td>
                            <td><?php echo htmlspecialchars($rowData['agenda']); ?></td>
                            <td><span class="ruang-sidang-text"><?php echo $ruangSidangDisplay; ?></span></td>
                            <td class="td-keterangan" data-base="<?php echo htmlspecialchars($ket); ?>"><?php echo $formatKeterangan($ket); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    // Show debug info jika developer mode aktif
                    $show_debug = isset($_GET['debug']) && $_GET['debug'] === '1';
                    ?>
                    <tr>
                        <td colspan="5" class="no-data">
                            Tidak ada sidang hari ini
                            <?php if ($show_debug && !empty($error_log)): ?>
                            <br><small style="color:#f00;font-size:10px;margin-top:10px;display:block;">
                                Debug: <?php echo implode(" | ", $error_log); ?>
                                <br>HTML Length: <?php echo strlen($html_sipp); ?> bytes
                                <br>HTTP Code: <?php echo $http_code; ?>
                            </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        </div>
    </body>

    <script>
        // ================================================================
        // LIVE STATUS UPDATE: Poll antrian setiap 30 detik
        // Sedang Sidang  = saat ini tampil di display antrian
        // Selesai Sidang = pernah "Sedang" tapi sekarang sudah tidak ada
        // Menunggu Sidang = belum dipanggil
        // ================================================================
        (function() {
            var previousSedang = {}; // { no_perk: true } dari polling sebelumnya

            function fmtKet(status) {
                if (status === 'Sedang Sidang')  return '<span class="status-sedang">&#9654; Sedang Sidang</span>';
                if (status === 'Selesai Sidang') return '<span class="status-selesai">&#10003; Selesai Sidang</span>';
                if (status === 'Menunggu Sidang') return '<span class="status-menunggu">Menunggu Sidang</span>';
                return '<span class="status-manual">' + status.replace(/[<>&"]/g, function(c) {
                    return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[c];
                }) + '</span>';
            }

            function pollAntrian() {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '?proxy=bawah&t=' + Date.now(), true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState !== 4 || xhr.status !== 200) return;
                    try {
                        var list = JSON.parse(xhr.responseText);
                        if (!Array.isArray(list)) return;

                        var currentSedang = {};
                        list.forEach(function(item) {
                            var np = (item.no_perk || '').trim();
                            if (np) currentSedang[np] = true;
                        });

                        var rows = document.querySelectorAll('tr[data-noperkara]');
                        rows.forEach(function(tr) {
                            var np = tr.getAttribute('data-noperkara');
                            var td = tr.querySelector('.td-keterangan');
                            if (!td) return;
                            var base = td.getAttribute('data-base') || '';

                            if (currentSedang[np]) {
                                td.innerHTML = fmtKet('Sedang Sidang');
                            } else if (previousSedang[np]) {
                                // Barusan selesai dipanggil
                                td.innerHTML = fmtKet('Selesai Sidang');
                                td.setAttribute('data-base', 'Selesai Sidang');
                            } else if (base === 'Sedang Sidang') {
                                // Server render was Sedang, now gone
                                td.innerHTML = fmtKet('Selesai Sidang');
                                td.setAttribute('data-base', 'Selesai Sidang');
                            }
                            // Jika base adalah Selesai Sidang dari server (dihadiri_oleh) -> biarkan
                            // Jika Menunggu Sidang dan tidak di antrian -> biarkan
                        });

                        previousSedang = currentSedang;
                    } catch(e) {}
                };
                xhr.send();
            }

            // Poll segera dan setiap 30 detik
            pollAntrian();
            setInterval(pollAntrian, 30000);
        })();

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
