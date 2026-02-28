<?php
/* developed by zhayyn™ - Green Glassmorphism Edition */

if (function_exists('opcache_reset')) {
    opcache_reset();
}

header("Access-Control-Allow-Origin: *");
header("X-Frame-Options: ALLOWALL");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, proxy-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// =========================================================================
// FITUR PROXY BYPASS CORS
// =========================================================================
if (isset($_GET['proxy'])) {
    header('Content-Type: application/json');
    $target_url = "";
    $is_post = false;
    
    if ($_GET['proxy'] == 'ada_sidang') { $target_url = "https://antrian.pa-semarang.go.id/tv_media/ada_sidang"; $is_post = true; } 
    elseif ($_GET['proxy'] == 'bawah') { $target_url = "https://antrian.pa-semarang.go.id/tv_media/display_bawah"; }
    
    if ($target_url !== "") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $target_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($is_post) { curl_setopt($ch, CURLOPT_POST, 1); }
        $result = curl_exec($ch);
        curl_close($ch);
        echo $result ? $result : '{}';
    }
    exit;
}

if (isset($_GET['format_jadwal'])) {
    // KODE PARSING SIPP (Tetap Seperti Semula)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://sipp.pa-semarang.go.id/slide_sidang");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $html_sipp = curl_exec($ch);
    curl_close($ch);
    $dom = new DOMDocument();
    @$dom->loadHTML($html_sipp);
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query('//table//tr');
    $no = 1; $allRows = array(); $currentBlock = array();
    foreach ($rows as $row) {
        $cells = $row->getElementsByTagName('td');
        if ($cells->length == 0) continue;
        $rowspan = $cells->item(0)->getAttribute('rowspan');
        if (!empty($rowspan)) {
            if (!empty($currentBlock['noPerkara'])) { $allRows[] = $currentBlock; }
            $currentBlock = array('no' => $no++);
        }
        if ($cells->length >= 3) {
            $label = strtoupper(trim($cells->item(1)->textContent));
            $value = trim($cells->item(2)->textContent);
            if (strpos($label, 'NO PERKARA') !== false) { $currentBlock['noPerkara'] = $value; }
            elseif (strpos($label, 'AGENDA') !== false) { $currentBlock['agenda'] = $value; }
            elseif (strpos($label, 'RUANG') !== false) { $currentBlock['ruangSidang'] = $value; }
            elseif (strpos($label, 'HAKIM') !== false) { $currentBlock['keterangan'] = $value; }
        }
    }
    if (!empty($currentBlock['noPerkara'])) { $allRows[] = $currentBlock; }
    $totalRows = count($allRows);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            * { margin: 0; padding: 0; }
            body { background: transparent; font-family: 'Segoe UI', sans-serif; color: #fff; padding: 10px; }
            .jadwal-table { width: 100%; border-collapse: collapse; }
            .jadwal-table-wrapper { overflow: hidden; height: 450px; position: relative; }
            .jadwal-table thead { background: rgba(8, 66, 40, 0.9); border-bottom: 3px solid #cddc39; }
            .jadwal-table th { color: #fff; padding: 12px; font-size: 13px; text-transform: uppercase; text-align: left; }
            .jadwal-table td { padding: 12px; font-size: 12px; color: #eee; border-bottom: 1px solid rgba(255,255,255,0.1); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            /* GREEN GLASSMORPHISM PADA BARIS TABEL */
            .jadwal-table tbody tr { background: rgba(23, 74, 56, 0.6); backdrop-filter: blur(5px); }
            .jadwal-table tbody tr:nth-child(even) { background: rgba(23, 74, 56, 0.4); }
            .jadwal-table tbody tr:hover { background: rgba(255, 102, 0, 0.3) !important; }
        </style>
    </head>
    <body>
        <div class="jadwal-table-wrapper">
            <table class="jadwal-table" data-total-rows="<?php echo $totalRows; ?>">
                <thead><tr><th>No</th><th>Nomor Perkara</th><th>Agenda Sidang</th><th>Ruang</th><th>Ket</th></tr></thead>
                <tbody>
                    <?php foreach ($allRows as $r) { ?>
                        <tr><td><?php echo $r['no']; ?></td><td><?php echo $r['noPerkara']; ?></td><td><?php echo $r['agenda']; ?></td><td><?php echo $r['ruangSidang']; ?></td><td><?php echo $r['keterangan']; ?></td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </body>
    </html>
    <?php exit;
} ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --hijau-botol: #084228; --aksen-lemon: #cddc39; --aksen-oranye: #ff6600; }
        body { 
            background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('MASUKKAN_URL_GAMBAR_DI_SINI');
            background-size: cover; background-attachment: fixed;
            font-family: 'Segoe UI', sans-serif; margin: 0; color: #fff;
        }
        /* HEADER 3D GLASS STYLE */
        .header-monitor { 
            background: linear-gradient(180deg, rgba(22, 138, 86, 0.9) 0%, rgba(8, 66, 40, 0.95) 100%);
            padding: 15px; text-align: center; border-bottom: 4px solid var(--aksen-lemon);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        /* GREEN GLASSMORPHISM CARDS */
        .glass-card { 
            background: rgba(23, 74, 56, 0.7); 
            backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px; margin: 15px; overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .inner-card { 
            background: rgba(255,255,255,0.05); border-radius: 10px; padding: 15px;
            border: 1px solid rgba(255,255,255,0.1); margin: 10px;
        }
        .grid-ruang { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; padding: 10px; }
        .ruang-box { text-align: center; border-right: 1px solid rgba(255,255,255,0.1); }
        .ruang-box:last-child { border: none; }
        .antrian-no { 
            font-size: 24px; font-weight: 800; color: var(--aksen-lemon); 
            background: rgba(0,0,0,0.3); border-radius: 8px; padding: 5px; margin-top: 5px;
        }
        .perkara-no { font-size: 11px; color: #ccc; margin: 5px 0; }
        .jam-box { background: var(--aksen-oranye); padding: 5px 15px; border-radius: 6px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header-monitor">
        <span style="font-size: 18px; font-weight: 700;">
            <i class="fas fa-gavel" style="color:var(--aksen-lemon);"></i> LIVE MONITOR ANTRIAN SIDANG
            <span class="jam-box" id="jam" style="margin-left:15px;">00:00:00</span>
        </span>
    </div>

    <div class="glass-card">
        <div style="text-align:center; padding-top:10px; font-size:12px; color:var(--aksen-lemon); font-weight:700;">INFORMASI SIDANG DALAM GEDUNG</div>
        <div class="grid-ruang">
            <div class="ruang-box">
                <div style="font-size:12px; font-weight:700;">RUANG UTAMA</div>
                <div class="perkara-no"><span id="noperk1">---</span></div>
                <div class="antrian-no"><span id="no1">---</span></div>
            </div>
            <div class="ruang-box">
                <div style="font-size:12px; font-weight:700;">RUANG 2</div>
                <div class="perkara-no"><span id="noperk2">---</span></div>
                <div class="antrian-no"><span id="no2">---</span></div>
            </div>
            <div class="ruang-box">
                <div style="font-size:12px; font-weight:700;">RUANG 3</div>
                <div class="perkara-no"><span id="noperk3">---</span></div>
                <div class="antrian-no"><span id="no3">---</span></div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div style="background:rgba(0,0,0,0.2); padding:10px; text-align:center; font-weight:700; font-size:14px; border-bottom:2px solid var(--aksen-lemon);">JADWAL PERSIDANGAN HARI INI</div>
        <iframe id="sippFrame" src="?format_jadwal=1" width="100%" height="450px" frameborder="0" scrolling="no" style="background:transparent;"></iframe>
    </div>

    <script>
        function updateTime() {
            document.getElementById("jam").innerHTML = new Date().toLocaleTimeString([], {hour12: false});
        }
        setInterval(updateTime, 1000);
        updateTime();

        // AJAX UPDATE DATA (Gunakan Proxy Tuan Muda)
        function updateData() {
            var req = new XMLHttpRequest();
            req.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    try {
                        var obj = JSON.parse(this.responseText);
                        if (obj && obj.length > 0) {
                            for (var i = 0; i < obj.length; i++) {
                                var rs = parseInt(obj[i].r_sidang);
                                if (rs >= 1 && rs <= 3) {
                                    document.getElementById("no" + rs).innerHTML = obj[i].no_antrian;
                                    document.getElementById("noperk" + rs).innerHTML = obj[i].no_perk;
                                }
                            }
                        }
                    } catch(e) {}
                }
            };
            req.open("GET", "?proxy=bawah&t=" + new Date().getTime(), true);
            req.send();
        }
        setInterval(updateData, 5000);
        updateData();
    </script>
</body>
</html>