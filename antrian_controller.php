<?php
/* Antrian Controller - refactor dari antria.php */

// ANTI CACHE - PENTING UNTUK REAL-TIME MONITORING
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Simple logging (appends to logs/antrian_controller.log)
$logfile = __DIR__ . '/logs/antrian_controller.log';
function ac_log($msg) {
    global $logfile;
    $ts = date('Y-m-d H:i:s');
    $entry = "[$ts] " . $msg . PHP_EOL;
    @file_put_contents($logfile, $entry, FILE_APPEND | LOCK_EX);
}
ac_log("Request: " . ($_SERVER['REQUEST_METHOD'] ?? 'CLI') . ' ' . ($_SERVER['REQUEST_URI'] ?? '')); 

// HEADER ANTI-CACHE & KEAMANAN
header("Access-Control-Allow-Origin: *");
header("X-Frame-Options: ALLOWALL");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, proxy-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("ETag: " . md5(microtime()));

// Endpoint format_jadwal (render jadwal bersih untuk iframe)
if (isset($_GET['format_jadwal'])) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, proxy-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    ac_log('format_jadwal requested');

    // Prefer local static file when present (avoid remote cURL)
    $localFile = __DIR__ . '/slide_sidang.html';
    if (is_readable($localFile)) {
        $html_sipp = @file_get_contents($localFile);
        if ($html_sipp === false) {
            ac_log('Failed to read local slide_sidang.html');
            $html_sipp = '';
        } else {
            ac_log('Loaded local slide_sidang.html, length=' . strlen($html_sipp));
        }
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://sipp.pa-semarang.go.id/slide_sidang");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $html_sipp = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if (!empty($curlErr)) {
            ac_log('cURL error fetching SIPP: ' . $curlErr);
            $html_sipp = '';
        } else {
            ac_log('Fetched SIPP length: ' . strlen((string)$html_sipp));
        }
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html_sipp);
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query('//table//tr');

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            * { margin: 0; padding: 0; }
            body { font-family: 'Segoe UI', sans-serif; padding: 10px; }

            .jadwal-table { width: 100%; border-collapse: collapse; background: white; }
            .jadwal-table-wrapper { overflow: hidden; height: 440px; position: relative; }
            @media (max-width: 768px) { .jadwal-table-wrapper { height: 380px; } }
            @media (max-width: 480px) { .jadwal-table-wrapper { height: 350px; } }

            .jadwal-table thead { background: linear-gradient(135deg, #084228, #0d6b41); }
            .jadwal-table-head { border-collapse: collapse; table-layout: fixed; width: 100%; position: relative; z-index: 20; }
            .jadwal-table-head th { color: white; padding: 14px 10px; font-size: 13px; font-weight: 600; letter-spacing: 0.5px; border-bottom: 3px solid #ff6600; text-align: left; white-space: normal; }
            .jadwal-scroll-area { overflow: hidden; position: relative; z-index: 1; }
            .jadwal-table-body { border-collapse: collapse; width: 100%; table-layout: fixed; }
            .jadwal-table-body tbody { display: table-row-group; vertical-align: inherit; border-color: inherit; }
            .jadwal-table-body td { box-sizing: border-box; }
            @media (max-width: 768px) { .jadwal-table th { padding: 10px 8px; font-size: 11px; } }
            @media (max-width: 480px) { .jadwal-table th { padding: 8px 6px; font-size: 10px; } }

            .jadwal-table td { padding: 12px 10px; font-size: 12px; color: #333; border-bottom: 1px solid #ddd; vertical-align: middle; box-sizing: border-box; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
            /* allow wrapping for ruangan (col 2) and keterangan (col 4) so long texts wrap */
            .jadwal-table-body td:nth-child(2), .jadwal-table-body td:nth-child(4) { white-space: normal; word-wrap: break-word; }
            .jadwal-table-head th:nth-child(2), .jadwal-table-head th:nth-child(4) { white-space: normal; }
            .case-link { display: block; font-size: 12px; color: #0066cc; opacity: 1; margin-top: 4px; text-decoration: underline; font-weight: 600; transition: color 0.2s; }
            .case-link:hover { color: #003d99; text-decoration: none; }
            .highlight { background: linear-gradient(90deg, rgba(255,102,0,0.06), transparent) !important; font-weight: 600; }

            @media (max-width: 768px) { .jadwal-table td { padding: 9px 8px; font-size: 11px; } }
            @media (max-width: 480px) { .jadwal-table td { padding: 7px 6px; font-size: 10px; } }

            .jadwal-table tbody tr:nth-child(odd) { background-color: #f9fafb; }
            .jadwal-table tbody tr:nth-child(even) { background-color: #f4f8f6; }
            .jadwal-table tbody tr:hover { background-color: #e8f5e9; box-shadow: inset 0 0 5px rgba(8, 66, 40, 0.1); }

            .no-data { text-align: center; padding: 40px; color: #999; font-style: italic; }
            @keyframes scrollUpComplete { 0% { transform: translateY(0); } 99% { transform: translateY(-100%); } 100% { transform: translateY(0); } }
        </style>
    </head>
    <body>
        <div class="jadwal-table-wrapper">
            <table class="jadwal-table jadwal-table-head">
            <colgroup>
                <col style="width:140px">
                <col style="width:160px">
                <col>
                <col style="width:160px">
            </colgroup>
            <thead>
                <tr>
                    <th style="width:140px;">No<br><span style="font-weight:600;font-size:11px;">Nomor Perkara</span></th>
                    <th style="width:160px;">Ruang Sidang</th>
                    <th>Agenda Sidang</th>
                    <th style="width:160px;">Keterangan</th>
                </tr>
            </thead>
            </table>

            <div class="jadwal-scroll-area">
                <table class="jadwal-table jadwal-table-body" data-total-rows="0">
                <colgroup>
                    <col style="width:140px">
                    <col style="width:160px">
                    <col>
                    <col style="width:160px">
                </colgroup>
                <tbody>
                <?php
                $no = 1;
                $found = false;
                $currentBlock = array();
                $allRows = array();

                if ($rows->length > 0) {
                    foreach ($rows as $row) {
                        $cells = $row->getElementsByTagName('td');
                        if ($cells->length == 0) continue;
                        $rowspan = $cells->item(0)->getAttribute('rowspan');
                        if (!empty($rowspan)) {
                            if (!empty($currentBlock)) {
                                $noPerkara = $currentBlock['noPerkara'] ?? '';
                                $agenda = $currentBlock['agenda'] ?? '';
                                $ruangSidang = $currentBlock['ruangSidang'] ?? '';
                                $keterangan = $currentBlock['keterangan'] ?? 'Terjadwal';
                                if (!empty($noPerkara)) {
                                    $found = true;
                                    $rowData = array('no' => $no, 'noPerkara' => $noPerkara, 'agenda' => $agenda, 'ruangSidang' => $ruangSidang, 'keterangan' => $keterangan);
                                    $allRows[] = $rowData;
                                    $no++;
                                }
                            }
                            $currentBlock = array();
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
                            } elseif (strpos($label, 'HAKIM') !== false || strpos($label, 'KETUA') !== false) {
                                $currentBlock['keterangan'] = !empty($currentBlock['keterangan']) ? $currentBlock['keterangan'] : $value;
                            }
                        } elseif ($cells->length == 2) {
                            $label = strtoupper(trim($cells->item(0)->textContent));
                            $value = trim($cells->item(1)->textContent);
                            if (strpos($label, 'NO PERKARA') !== false || strpos($label, 'NOMOR PERKARA') !== false) {
                                $currentBlock['noPerkara'] = $value;
                            } elseif (strpos($label, 'AGENDA') !== false) {
                                $currentBlock['agenda'] = $value;
                            } elseif (strpos($label, 'RUANG') !== false) {
                                $currentBlock['ruangSidang'] = $value;
                            }
                        }
                    }

                    if (!empty($currentBlock)) {
                        $noPerkara = $currentBlock['noPerkara'] ?? '';
                        $agenda = $currentBlock['agenda'] ?? '';
                        $ruangSidang = $currentBlock['ruangSidang'] ?? '';
                        $keterangan = $currentBlock['keterangan'] ?? 'Terjadwal';
                        if (!empty($noPerkara)) {
                            $found = true;
                            $rowData = array('no' => $no, 'noPerkara' => $noPerkara, 'agenda' => $agenda, 'ruangSidang' => $ruangSidang, 'keterangan' => $keterangan);
                            $allRows[] = $rowData;
                        }
                    }
                }

                if (!empty($allRows)) {
                    $totalRows = count($allRows);
                    ac_log('Parsed rows: ' . $totalRows);
                    foreach ($allRows as $rowData) {
                        $no = $rowData['no'];
                        $noPerkara = htmlspecialchars($rowData['noPerkara']);
                        $agenda = htmlspecialchars($rowData['agenda']);
                        $ruang = htmlspecialchars($rowData['ruangSidang']);
                        $ket = htmlspecialchars($rowData['keterangan']);

                        $sippSearch = 'https://sipp.pa-semarang.go.id/?s=' . rawurlencode($rowData['noPerkara']);
                        $compactNo = "<div style='font-weight:700;font-size:14px;margin-bottom:4px'>{$no}</div>";
                        $compactNo .= "<a class='case-link' href='" . htmlspecialchars($sippSearch) . "' target='_blank' rel='noopener noreferrer' title='Klik untuk lihat detail di SIPP'><strong>" . $noPerkara . "</strong></a>";

                        $isHighlight = 0;
                        $lower = strtolower($rowData['agenda'] . ' ' . $rowData['keterangan']);
                        if (strpos($lower, 'cerai') !== false || strpos($lower, 'eksekusi') !== false || strpos($lower, 'peringatan') !== false) {
                            $isHighlight = 1;
                        }

                        echo "<tr" . ($isHighlight ? " class='highlight'" : "") . ">\n";
                        echo "<td style=\"width:70px;text-align:center;vertical-align:middle;cursor:pointer;\">{$compactNo}</td>\n";
                        echo "<td style=\"width:160px;\">{$ruang}</td>\n";
                        echo "<td>{$agenda}</td>\n";
                        echo "<td style=\"width:160px;\">{$ket}</td>\n";
                        echo "</tr>\n";
                    }

                    $loopCount = min(10, count($allRows));
                    for ($i = 0; $i < $loopCount; $i++) {
                        $rowData = $allRows[$i];
                        $no = $rowData['no'];
                        $noPerkara = htmlspecialchars($rowData['noPerkara']);
                        $agenda = htmlspecialchars($rowData['agenda']);
                        $ruang = htmlspecialchars($rowData['ruangSidang']);
                        $ket = htmlspecialchars($rowData['keterangan']);

                        $sippSearch = 'https://sipp.pa-semarang.go.id/?s=' . rawurlencode($rowData['noPerkara']);
                        $compactNo = "<div style='font-weight:700;font-size:14px;margin-bottom:4px'>{$no}</div>";
                        $compactNo .= "<a class='case-link' href='" . htmlspecialchars($sippSearch) . "' target='_blank' rel='noopener noreferrer' title='Klik untuk lihat detail di SIPP'><strong>" . $noPerkara . "</strong></a>";

                        $isHighlight = 0;
                        $lower = strtolower($rowData['agenda'] . ' ' . $rowData['keterangan']);
                        if (strpos($lower, 'cerai') !== false || strpos($lower, 'eksekusi') !== false || strpos($lower, 'peringatan') !== false) {
                            $isHighlight = 1;
                        }

                        echo "<tr" . ($isHighlight ? " class='highlight'" : "") . ">\n";
                        echo "<td style=\"width:70px;text-align:center;vertical-align:middle;cursor:pointer;\">{$compactNo}</td>\n";
                        echo "<td style=\"width:160px;\">{$ruang}</td>\n";
                        echo "<td>{$agenda}</td>\n";
                        echo "<td style=\"width:160px;\">{$ket}</td>\n";
                        echo "</tr>\n";
                    }

                    echo "<!-- TOTAL_ROWS:" . $totalRows . " -->";
                } else {
                    echo "<!-- TOTAL_ROWS:0 -->";
                    ?>
                        <tr>
                            <td colspan="4" class="no-data">Tidak ada sidang hari ini</td>
                        </tr>
                        <?php
                }
                ?>
                </tbody>
                </table>
                </div>
            </div>
    </body>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scrollArea = document.querySelector('.jadwal-scroll-area');
            const bodyTable = document.querySelector('.jadwal-table-body');
            
            if (!scrollArea || !bodyTable) {
                console.error('[AUTO-SCROLL] Missing scrollArea or bodyTable');
                return;
            }
            
            const tbody = bodyTable.querySelector('tbody');
            if (!tbody) {
                console.error('[AUTO-SCROLL] Missing tbody');
                return;
            }
            
            const rows = Array.from(tbody.querySelectorAll('tr'));
            if (rows.length === 0) {
                console.error('[AUTO-SCROLL] No rows found');
                return;
            }

            console.log('[AUTO-SCROLL] Found', rows.length, 'rows');
            
            // Wait for rendering to complete
            setTimeout(function() {
                try {
                    const firstRow = tbody.querySelector('tr');
                    const rowHeight = firstRow.getBoundingClientRect().height || 50;
                    const visibleCount = 10;
                    const originalRowCount = rows.length;
                    
                    console.log('[AUTO-SCROLL] Row height:', rowHeight, 'px, Visible rows:', visibleCount, 'Total rows:', originalRowCount);
                    
                    // Set scroll area height
                    scrollArea.style.height = (visibleCount * rowHeight) + 'px';
                    scrollArea.style.overflow = 'hidden';
                    
                    // Clone first 10 rows for seamless loop
                    const rowsToClone = Math.min(visibleCount, originalRowCount);
                    for (let i = 0; i < rowsToClone; i++) {
                        const cloned = rows[i].cloneNode(true);
                        tbody.appendChild(cloned);
                    }
                    
                    const allRows = tbody.querySelectorAll('tr');
                    console.log('[AUTO-SCROLL] After clone, total rows:', allRows.length);
                    
                    // Calculate animation parameters
                    let durationPerRow = originalRowCount < 20 ? 12 : 8;
                    const totalDuration = originalRowCount * durationPerRow;
                    const scrollDistance = originalRowCount * rowHeight;
                    
                    console.log('[AUTO-SCROLL] Duration per row:', durationPerRow, 's, Total:', totalDuration, 's, Scroll distance:', scrollDistance, 'px');
                    
                    // Use setInterval for smooth scrolling
                    let currentScroll = 0;
                    const scrollStep = scrollDistance / (totalDuration * 60); // 60fps
                    
                    setInterval(function() {
                        currentScroll += scrollStep;
                        
                        // Reset when reached end
                        if (currentScroll >= scrollDistance) {
                            currentScroll = 0;
                        }
                        
                        tbody.style.transform = 'translateY(-' + currentScroll + 'px)';
                    }, 1000 / 60); // 60fps
                    
                    console.log('[AUTO-SCROLL] Scroll animation started');
                    
                } catch(err) {
                    console.error('[AUTO-SCROLL] Error during setup:', err.message);
                }
            }, 500);
        });
    </script>
    </html>
    <?php
    exit;
}
?>
            const scrollDistance = originalRowCount * rowHeight;
