<?php
// Simple generator: fetch SIPP slide and write slide_sidang.html with full blocks
$outFile = __DIR__ . '/slide_sidang.html';
$url = 'https://sipp.pa-semarang.go.id/slide_sidang';

function fetch_html($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false || !empty($err)) {
        fwrite(STDERR, "cURL error: $err\n");
        return '';
    }
    return $res;
}

$html_sipp = fetch_html($url);
if (empty($html_sipp)) {
    echo "Failed to fetch SIPP page. Aborting.\n";
    exit(1);
}

$dom = new DOMDocument();
@$dom->loadHTML($html_sipp);
$xpath = new DOMXPath($dom);
$rows = $xpath->query('//table//tr');

$no = 1;
$currentBlock = [];
$allRows = [];
if ($rows->length > 0) {
    foreach ($rows as $row) {
        $cells = $row->getElementsByTagName('td');
        if ($cells->length == 0) continue;
        $rowspan = $cells->item(0)->getAttribute('rowspan');
        if (!empty($rowspan)) {
            if (!empty($currentBlock)) {
                $np = $currentBlock['noPerkara'] ?? '';
                if (!empty($np)) {
                    $allRows[] = array(
                        'no' => $no,
                        'noPerkara' => $currentBlock['noPerkara'] ?? '',
                        'agenda' => $currentBlock['agenda'] ?? '',
                        'ruangSidang' => $currentBlock['ruangSidang'] ?? '',
                        'keterangan' => $currentBlock['keterangan'] ?? ''
                    );
                    $no++;
                }
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
    // final block
    if (!empty($currentBlock) && !empty(($currentBlock['noPerkara'] ?? ''))) {
        $allRows[] = array(
            'no' => $no,
            'noPerkara' => $currentBlock['noPerkara'] ?? '',
            'agenda' => $currentBlock['agenda'] ?? '',
            'ruangSidang' => $currentBlock['ruangSidang'] ?? '',
            'keterangan' => $currentBlock['keterangan'] ?? ''
        );
    }
}

// build HTML
$out = "<!doctype html>\n<html><head><meta charset=\"utf-8\"><title>Slide Sidang (generated)</title>\n";
$out .= "<style>body{font-family:Arial,Helvetica,sans-serif}table{width:100%;border-collapse:collapse;}td,th{padding:6px;border:1px solid #ddd}</style>\n";
$out .= "</head><body>\n<table>\n";
foreach ($allRows as $r) {
    $no = htmlspecialchars($r['no']);
    $np = htmlspecialchars($r['noPerkara']);
    $agenda = htmlspecialchars($r['agenda']);
    $ruang = htmlspecialchars($r['ruangSidang']);
    $ket = htmlspecialchars($r['keterangan']);

    $out .= "<tr>\n<td rowspan=5>$no</td>\n<td>Nomor Perkara</td>\n<td>$np</td>\n</tr>\n";
    $out .= "<tr><td>Agenda Sidang</td><td>$agenda</td></tr>\n";
    $out .= "<tr><td>Ruang Sidang</td><td>$ruang</td></tr>\n";
    $out .= "<tr><td>Keterangan</td><td>$ket</td></tr>\n";
    $out .= "<tr><td>Hakim</td><td>-</td></tr>\n";
    $out .= "\n";
}
$out .= "</table>\n</body></html>\n";

file_put_contents($outFile, $out);
echo "Wrote " . $outFile . " (" . count($allRows) . " cases)\n";
?>
