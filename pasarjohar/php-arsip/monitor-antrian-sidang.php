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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://sipp.pa-semarang.go.id/slide_sidang");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $html_sipp = curl_exec($ch);
    curl_close($ch);

    $dom = new DOMDocument();
    @$dom->loadHTML($html_sipp);
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query('//table//tr');
    
    $no = 1;
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
                    if (!empty($noPerkara)) {
                        $allRows[] = array(
                            'no' => $no++, 
                            'noPerkara' => $noPerkara, 
                            'agenda' => $currentBlock['agenda'] ?? '', 
                            'ruangSidang' => $currentBlock['ruangSidang'] ?? '', 
                            'keterangan' => $currentBlock['keterangan'] ?? 'Terjadwal'
                        );
                    }
                }
                $currentBlock = array();
            }
            
            if ($cells->length >= 3) {
                $label = strtoupper(trim($cells->item(1)->textContent));
                $value = trim($cells->item(2)->textContent);
                if (strpos($label, 'NO PERKARA') !== false || strpos($label, 'NOMOR PERKARA') !== false) { $currentBlock['noPerkara'] = $value; }
                elseif (strpos($label, 'AGENDA') !== false) { $currentBlock['agenda'] = $value; }
                elseif (strpos($label, 'RUANG') !== false || strpos($label, 'PENGADILAN') !== false) { $currentBlock['ruangSidang'] = $value; }
                elseif (strpos($label, 'HAKIM') !== false || strpos($label, 'KETUA') !== false) { $currentBlock['keterangan'] = !empty($currentBlock['keterangan']) ? $currentBlock['keterangan'] : $value; }
            } elseif ($cells->length == 2) {
                $label = strtoupper(trim($cells->item(0)->textContent));
                $value = trim($cells->item(1)->textContent);
                if (strpos($label, 'NO PERKARA') !== false || strpos($label, 'NOMOR PERKARA') !== false) { $currentBlock['noPerkara'] = $value; }
                elseif (strpos($label, 'AGENDA') !== false) { $currentBlock['agenda'] = $value; }
                elseif (strpos($label, 'RUANG') !== false) { $currentBlock['ruangSidang'] = $value; }
            }
        }
        
        if (!empty($currentBlock)) {
            $noPerkara = $currentBlock['noPerkara'] ?? '';
            if (!empty($noPerkara)) {
                $allRows[] = array(
                    'no' => $no++, 
                    'noPerkara' => $noPerkara, 
                    'agenda' => $currentBlock['agenda'] ?? '', 
                    'ruangSidang' => $currentBlock['ruangSidang'] ?? '', 
                    'keterangan' => $currentBlock['keterangan'] ?? 'Terjadwal'
                );
            }
        }
    }
    
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
                height: 440px; 
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
            .jadwal-table th:nth-child(3), .jadwal-table td:nth-child(3) { width: 33%; }
            .jadwal-table th:nth-child(4), .jadwal-table td:nth-child(4) { width: 20%; }
            .jadwal-table th:nth-child(5), .jadwal-table td:nth-child(5) { width: 20%; }

            .jadwal-table th { color: white; padding: 14px 10px; font-size: 13px; font-weight: 600; text-transform: uppercase; border-bottom: 3px solid #ff6600; text-align: left; }
            .jadwal-table td { padding: 12px 10px; font-size: 12px; color: #333; border-bottom: 1px solid #ddd; vertical-align: middle; box-sizing: border-box; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
            
            .jadwal-table tbody tr:nth-child(odd) { background-color: #f9fafb; }
            .jadwal-table tbody tr:nth-child(even) { background-color: #f4f8f6; }
            
            .jadwal-table tbody:hover { animation-play-state: paused !important; cursor: pointer; }
            .jadwal-table tbody tr:hover { background-color: #e8f5e9; }
            
            .jadwal-table td:nth-child(1) { font-weight: 600; color: #084228; }
            .jadwal-table td:nth-child(4) { font-weight: 600; color: #ff6600; }
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
                if ($totalRows > 0) {
                    foreach ($allRows as $rowData) {
                        ?>
                        <tr>
                            <td><?php echo $rowData['no']; ?></td>
                            <td><?php echo htmlspecialchars($rowData['noPerkara']); ?></td>
                            <td><?php echo htmlspecialchars($rowData['agenda']); ?></td>
                            <td><?php echo htmlspecialchars($rowData['ruangSidang']); ?></td>
                            <td><?php echo htmlspecialchars($rowData['keterangan']); ?></td>
                        </tr>
                        <?php
                    }
                    
                    $loopCount = min(10, $totalRows);
                    for ($i = 0; $i < $loopCount; $i++) {
                        $rowData = $allRows[$i];
                        ?>
                        <tr>
                            <td><?php echo $rowData['no']; ?></td>
                            <td><?php echo htmlspecialchars($rowData['noPerkara']); ?></td>
                            <td><?php echo htmlspecialchars($rowData['agenda']); ?></td>
                            <td><?php echo htmlspecialchars($rowData['ruangSidang']); ?></td>
                            <td><?php echo htmlspecialchars($rowData['keterangan']); ?></td>
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
        <div class="header-kartu bg-hijau-elegan">PANGGILAN SIDANG SAAT INI</div>          
        <div class="flex-container">
            <div>
                <div class="teks-hijau" style="font-size: 26px;"><b><span id="rsidang_atas">MENUNGGU...</span></b></div>
                <div style="color: #555; font-size: 20px; margin-top: 5px;"><b><span id="no_perkara_atas">---</span></b></div>
            </div>
            <div class="bg-oranye tag-antrian-besar">
                <span id="no_antrian_atas"> --- </span>
            </div>                                         
        </div>
        
        <div style="text-align: center; padding-top: 15px; font-weight: bold; color: #777; font-size: 14px; text-transform:uppercase; letter-spacing:1px;">Informasi Ruang Sidang</div>
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
        <iframe id="sippFrame" src="?format_jadwal=1&t=0" width="100%" height="450px" frameborder="0" scrolling="no" style="display:block; border: none;"></iframe>
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

                        // 3. Ambil data panggilan atas
                        var reqAtas = new XMLHttpRequest();
                        reqAtas.onreadystatechange = function() {
                            if (this.readyState == 4 && this.status == 200) {
                                try {
                                    var objAtas = JSON.parse(this.responseText);
                                    if (objAtas !== null && objAtas.nama_ruang) {
                                        var elRsAtas = document.getElementById('rsidang_atas');
                                        var elNoAtas = document.getElementById('no_antrian_atas');
                                        var elPerkAtas = document.getElementById('no_perkara_atas');
                                        
                                        // Update jika beda (baru dipanggil)
                                        if (elRsAtas.innerHTML !== objAtas.nama_ruang.toUpperCase() || elNoAtas.innerHTML !== objAtas.no_antrian) {
                                            elRsAtas.innerHTML = objAtas.nama_ruang.toUpperCase();
                                            elNoAtas.innerHTML = objAtas.no_antrian;
                                            elPerkAtas.innerHTML = objAtas.no_perk;
                                            
                                            // Efek berkedip saat ada panggilan baru
                                            elNoAtas.classList.add("blink");
                                            elPerkAtas.classList.add("blink");
                                            setTimeout(function() { 
                                                elNoAtas.classList.remove("blink"); 
                                                elPerkAtas.classList.remove("blink"); 
                                            }, 5000);
                                        }
                                    }
                                } catch(e) {}
                            }
                        };
                        reqAtas.open("GET", "?proxy=atas" + cacheKiller, true);
                        reqAtas.send();

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