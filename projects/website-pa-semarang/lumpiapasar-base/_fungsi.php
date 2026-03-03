<?php

if ( ! function_exists('format_hari_tanggal'))
{ 
    function format_hari_tanggal($waktu)
    {
        $hari_array = array(
            'Minggu',
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu'
        );
        $hr = date('w', strtotime($waktu));
        $hari = $hari_array[$hr];
        $tanggal = date('j', strtotime($waktu));
        $bulan_array = array(
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        );
        $bl = date('n', strtotime($waktu));
        $bulan = $bulan_array[$bl];
        $tahun = date('Y', strtotime($waktu));
        $jam = date( 'H:i:s', strtotime($waktu));
        
    //untuk menampilkan hari, tanggal bulan tahun jam
    //return "$hari, $tanggal $bulan $tahun $jam";

    //untuk menampilkan hari, tanggal bulan tahun
        return "$hari, $tanggal $bulan $tahun";
    } 

}
if ( ! function_exists('antiInjections'))
{ 
    function antiInjections($string) 
    {
        $filter = stripslashes(strip_tags(htmlspecialchars($string,ENT_QUOTES)));
        $sql = array();$sql[0] = '/from/';$sql[1] = '/select/';$sql[2] = '/union/';$sql[3] = '/order/';$sql[4] = '/insert/';$sql[5] = '/delete/';$sql[6] = '/drop/';$sql[7] = '/tables/';$sql[8] = '/show/';$sql[9] = '/table/';$sql[9] = '/where/';
        $filter= preg_replace($sql, '', $filter);
        $filter = str_replace("table","",$filter);
        return $filter; 
    }
}

if ( ! function_exists('proses_teks'))
{ 
    function proses_teks($data){
        $data           =str_replace ("  ", " ", $data);
        $data           =str_replace ("'", " ", $data);
        $data           =str_replace ('"', " ", $data);
        $data           =str_replace (';', " ", $data);
        $data           =preg_replace('/\s+/', ' ',$data);
        
        return $data;
    }
}

if ( ! function_exists('lempar'))
{     
  function lempar($url) {
    echo '<script language = "javascript">';
    echo 'window.location.href = "'.$url.'"';
    echo '</script>';
}
}	

if ( ! function_exists('terbilang'))
{    
    function terbilang($bilangan)
    {
        $angka = array('0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0',
            '0', '0', '0');
        $kata = array('', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh',
            'delapan', 'sembilan');
        $tingkat = array('', 'ribu', 'juta', 'milyar', 'triliun');

        $panjang_bilangan = strlen($bilangan);

        /* pengujian panjang bilangan */
        if ($panjang_bilangan > 15)
        {
            $kalimat = "Diluar Batas";
            return $kalimat;
        }

    /* mengambil angka-angka yang ada dalam bilangan,
    dimasukkan ke dalam array */
    for ($i = 1; $i <= $panjang_bilangan; $i++)
    {
        $angka[$i] = substr($bilangan, -($i), 1);
    }

    $i = 1;
    $j = 0;
    $kalimat = "";


    /* mulai proses iterasi terhadap array angka */
    while ($i <= $panjang_bilangan)
    {
        $subkalimat = "";
        $kata1 = "";
        $kata2 = "";
        $kata3 = "";

        /* untuk ratusan */
        if ($angka[$i + 2] != "0")
        {
            if ($angka[$i + 2] == "1")
            {
                $kata1 = "seratus";
            }
            else
            {
                $kata1 = $kata[$angka[$i + 2]] . " ratus";
            }
        }

        /* untuk puluhan atau belasan */
        if ($angka[$i + 1] != "0")
        {
            if ($angka[$i + 1] == "1")
            {
                if ($angka[$i] == "0")
                {
                    $kata2 = "sepuluh";
                }
                elseif ($angka[$i] == "1")
                {
                    $kata2 = "sebelas";
                }
                else
                {
                    $kata2 = $kata[$angka[$i]] . " belas";
                }
            }
            else
            {
                $kata2 = $kata[$angka[$i + 1]] . " puluh";
            }
        }

        /* untuk satuan */
        if ($angka[$i] != "0")
        {
            if ($angka[$i + 1] != "1")
            {
                $kata3 = $kata[$angka[$i]];
            }
        }

        /* pengujian angka apakah tidak nol semua,
        lalu ditambahkan tingkat */
        if (($angka[$i] != "0") or ($angka[$i + 1] != "0") or ($angka[$i + 2] != "0"))
        {
            $subkalimat = "$kata1 $kata2 $kata3 " . $tingkat[$j] . " ";
        }

        /* gabungkan variabe sub kalimat (untuk satu blok 3 angka)
        ke variabel kalimat */
        $kalimat = $subkalimat . $kalimat;
        $i = $i + 3;
        $j = $j + 1;

    }

    /* mengganti satu ribu jadi seribu jika diperlukan */
    if (($angka[5] == "0") and ($angka[6] == "0"))
    {
        $kalimat = str_replace("satu ribu", "seribu", $kalimat);
    }
    return trim($kalimat . "");
}

}


if ( ! function_exists('pilihbulan'))
{      
    function pilihbulan($bln){
      switch ($bln){
        case "01":
        return "Januari";
        break;
        case "02":
        return "Pebruari";
        break;
        case "03":
        return "Maret";
        break;
        case "04":
        return "April";
        break;
        case "05":
        return "Mei";
        break;
        case "06":
        return "Juni";
        break;
        case "07":
        return "Juli";
        break;
        case "08":
        return "Agustus";
        break;
        case "09":
        return "September";
        break;
        case "10":
        return "Oktober";
        break;
        case "11":
        return "Nopember";
        break;
        case "12":
        return "Desember";
        break;
    }
}
}



?>