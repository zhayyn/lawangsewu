<?php
$baseUrl = 'http://192.168.88.10/lumpiapasar/panjar/_panjar_data_wilayah.php';

function fetchOptions($postData) {
    global $baseUrl;
    $ch = curl_init($baseUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $html = curl_exec($ch);
    curl_close($ch);
    
    $options = [];
    if (preg_match_all('/<option\s+value="([^"]+)"[^>]*>(.*?)<\/option>/is', $html, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
            $val = trim($matches[1][$i]);
            $label = trim(strip_tags($matches[2][$i]));
            if ($val !== '') {
                $options[] = ['value' => $val, 'label' => $label];
            }
        }
    }
    return $options;
}

echo "Fetching kecamatan...\n";
$kecamatanList = fetchOptions(['jenis' => 'kecamatan', 'id_regencies' => 'KOTA SEMARANG']);
$data = [];

echo "Found " . count($kecamatanList) . " kecamatan.\n";

foreach ($kecamatanList as $kec) {
    echo "Fetching kelurahan for " . $kec['label'] . "...\n";
    $kelList = fetchOptions(['jenis' => 'kelurahan', 'id_district' => $kec['value']]);
    
    $kelurahans = [];
    foreach ($kelList as $kel) {
        $parts = explode('^', $kel['value']);
        $kelurahans[] = [
            'nama' => $kel['label'],
            'biaya_raw' => isset($parts[3]) ? (int)$parts[3] : 0,
            'satker_code' => $parts[1] ?? '3322',
            'alamat' => $parts[4] ?? 'SEMARANG'
        ];
    }
    
    $data[] = [
        'kecamatan' => $kec['label'],
        'id_kecamatan' => $kec['value'],
        'kelurahan' => $kelurahans
    ];
}

$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents('/var/www/lawangsewu/public/data-radius-semarang.json', $json);
echo "Data exported to /var/www/lawangsewu/public/data-radius-semarang.json\n";
