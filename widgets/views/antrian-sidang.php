<?php
/* developed by zhayyn™ */

// 1. MENGIZINKAN AKSES DARI WEBSITE UTAMA
header("Access-Control-Allow-Origin: *");
header("X-Frame-Options: ALLOW-FROM https://pa-semarang.go.id/");

// 2. TRIK MAGIC: Mengambil data SIPP dan mewarnainya otomatis dalam 1 file!
if (isset($_GET['ambil_sipp'])) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://sipp.pa-semarang.go.id/slide_sidang");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Aman dari error SSL SIPP
    $html_sipp = curl_exec($ch);
    curl_close($ch);

    // Menyuntikkan CSS Hijau kita ke dalam HTML SIPP yang ditarik
    /* developed by zhayyn™ */

    // Menyuntikkan CSS Hijau kita ke dalam HTML SIPP yang ditarik
    $css_hijau = "
    <style>
        /* developed by zhayyn™ */
        table thead th, table th { 
            background-color: #084228 !important; 
            color: #ffffff !important; 
            border: none !important; 
            padding: 12px !important;
        }
        table tbody tr:nth-child(even) td { background-color: #f4f8f6 !important; }
        table tbody tr:nth-child(odd) td { background-color: #ffffff !important; }
        table td { 
            border-bottom: 1px solid #eaeaea !important; 
            padding: 10px !important;
            color: #333 !important;
        }
        body { background-color: transparent !important; overflow: hidden !important; }

        /* --- LOGIKA MAGIC UNTUK MEMBALIK ARAH SLIDE --- */
        .marquee, marquee, [class*='marquee'] {
            display: flex !important;
            flex-direction: column !important;
            animation: scrollUp 20s linear infinite !important; /* Kecepatan bisa diatur di sini */
            position: relative !important;
        }

        @keyframes scrollUp {
            0% { transform: translateY(100%); }
            100% { transform: translateY(-100%); }
        }
        /* dikembangkan oleh zhayyn™ */
    </style>";
    
    // Menampilkan hasil SIPP yang sudah di-custom warnanya
    echo str_replace('</head>', $css_hijau . '</head>', $html_sipp);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Widget Antrian PA Semarang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <style type="text/css">
        /* --- TEMA ELEGAN CUSTOM PEI --- */
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        .bg-hijau-elegan { background: linear-gradient(135deg, #084228, #0d6b41) !important; color: white !important; }
        .teks-hijau { color: #084228 !important; }
        .bg-oranye { background-color: #ff6600 !important; color: white !important; }
        
        .kartu-utama { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; margin: 15px; }
        .header-kartu { padding: 15px; font-weight: 600; text-align: center; border-bottom: 3px solid #ff6600; }
        .badge-elegan { background-color: #084228; color: white; border-radius: 8px; padding: 5px 15px; font-weight: bold; }
        .tag-antrian-besar { border-radius: 15px; border: 3px solid white; padding: 10px 20px; display: inline-block; font-size: 24px; font-weight: bold;}
        
        .flex-container { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid #eaeaea; }
        .grid-ruang { display: grid; grid-template-columns: 1fr 1fr 1fr; text-align: center; padding: 15px; gap: 10px; }
        .garis-batas { border-right: 2px dashed #ccc; }
        
        .blink { animation: blink-animation 1s steps(5, start) infinite; }
        @keyframes blink-animation { to { color: #ff6600; } }
        
        .botbar { background-color: #084228; color: white; text-align: center; padding: 10px; font-size: 14px; position: fixed; bottom: 0; width: 100%;}
        .botbar a { color: #ff6600; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div style="padding-bottom: 60px;">
    <div class="bg-hijau-elegan" style="padding: 15px; text-align: center;">
        <span style="font-size: 18px;"><i class="fas fa-gavel" style="color:#ff6600;"></i> <strong>LIVE MONITOR ANTRIAN SIDANG</strong> | <span id="tgl_indo"></span></span> 
        <span class="bg-oranye" id="jam" style="padding: 5px 15px; border-radius: 8px; margin-left: 10px; font-weight:bold;"></span>
    </div> 

    <div class="kartu-utama">
        <div class="header-kartu bg-hijau-elegan">PANGGILAN SIDANG SAAT INI</div>           
        
        <div class="flex-container" style="background-color: #fafafa;">
            <div>
                <div class="teks-hijau" style="font-size: 24px;"><b><span id="rsidang_atas">Menunggu...</span></b></div>
                <div style="color: #555; font-size: 18px; margin-top: 5px;"><b><span id="no_perkara_atas">---</span></b></div>
            </div>
            <div class="bg-oranye tag-antrian-besar" style="transform:rotate(-5deg)">
                <span id="no_antrian_atas"> --- </span>
            </div>                           
        </div>
        
        <div style="text-align: center; padding-top: 15px; font-weight: bold; color: #777; font-size: 14px;">INFORMASI ANTRIAN RUANG SIDANG</div>
        <div class="grid-ruang">
            <div class="garis-batas">
                <div class="teks-hijau" style="font-size: 14px;"><b>RUANG SIDANG UTAMA</b></div>
                <div style="color:#555; font-size: 12px; margin: 8px 0;"><span id="noperk1">---</span></div>
                <div class="badge-elegan"><span id="no1">---</span></div>
            </div>
            <div class="garis-batas">
                <div class="teks-hijau" style="font-size: 14px;"><b>RUANG SIDANG 2</b></div>
                <div style="color:#555; font-size: 12px; margin: 8px 0;"><span id="noperk2">---</span></div>
                <div class="badge-elegan"><span id="no2">---</span></div>
            </div>
            <div>
                <div class="teks-hijau" style="font-size: 14px;"><b>RUANG SIDANG 3</b></div>
                <div style="color:#555; font-size: 12px; margin: 8px 0;"><span id="noperk3">---</span></div>
                <div class="badge-elegan"><span id="no3">---</span></div>
            </div>
        </div>
    </div>

    <div style="margin: 15px; border-radius: 15px; overflow: hidden; border: 1px solid #eaeaea; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
        <iframe src="?ambil_sipp=1" width="100%" height="350px" frameborder="0" scrolling="no" style="display:block;"></iframe>
    </div>
</div>

<div class="botbar">
    Detail jadwal sidang dapat dilihat pada <a href="https://sipp.pa-semarang.go.id/list_jadwal_sidang" target="_blank">SIPP</a> atau Aplikasi <strong><a href="https://lumpiapasar.pa-semarang.go.id" target="_blank">LUMPIAPASAR</strong>
</div>

<script>        
    // Format Tanggal
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('tgl_indo').innerText = new Date().toLocaleDateString('id-ID', options);

    // Engine AJAX Penarik Data (Sama dengan aslinya)
    setInterval(my_ajax, 5000);

    function my_ajax(){
        var zz = new Date();
        document.getElementById("jam").innerHTML = zz.toLocaleTimeString([], {hour12: false});

        var oReqjs = new XMLHttpRequest(); 
        oReqjs.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var obj = JSON.parse(this.responseText);
                if(parseInt(obj.jml_sidang) > 0){
                    
                    // Tarik Data Bawah (Ruang 1-3)
                    $.get("https://antrian.pa-semarang.go.id/tv_media/display_bawah", function(data){
                        var objData = JSON.parse(data);
                        if(objData !== null){
                            for (i = 0; i < objData.length; i++) {
                                var rsidang = parseInt(objData[i].r_sidang);
                                if(rsidang < 4) {
                                    $("#no"+rsidang).html(objData[i].no_antrian);
                                    $("#noperk"+rsidang).html(objData[i].no_perk);
                                }
                            }
                        }
                    });

                    // Tarik Data Atas (Panggilan Utama)
                    $.get("https://antrian.pa-semarang.go.id/tv_media/display_atas", function(data){
                        var obj2 = JSON.parse(data);
                        if(obj2 !== null){
                            if($("#rsidang_atas").html() !== obj2.nama_ruang || $("#no_antrian_atas").html() !== obj2.no_antrian){
                                $("#rsidang_atas").html(obj2.nama_ruang);
                                $("#no_antrian_atas").html(obj2.no_antrian);
                                $("#no_perkara_atas").html(obj2.no_perk);
                                $("#no_antrian_atas, #no_perkara_atas").addClass("blink");
                                setTimeout(function(){ $("#no_antrian_atas, #no_perkara_atas").removeClass("blink"); }, 5000);
                            }
                        }
                    });
                }
            }
        };
        oReqjs.open("post", "https://antrian.pa-semarang.go.id/tv_media/ada_sidang", true);
        oReqjs.send();
    }
</script>
</body>
</html>
