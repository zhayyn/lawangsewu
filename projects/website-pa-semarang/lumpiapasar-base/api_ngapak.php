<?php
    // include('_sys_config.php');
    date_default_timezone_set('Asia/Jakarta');
    $url_api="https://aps.pa-semarang.go.id/api_monitoring/get_data_api";
    // $url_api="https://apskasir.pa-semarang.go.id/api_monitoring/get_data_api";
    //$url_api="http://124.158.186.170/aps_badilag/api_monitoring/get_data_api";
    
	require_once("_encess.php");
	$kunci   = "aSS2844";     
    
    function curl($url, $data){
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch); 
        curl_close($ch);      
        return $output;
    }
    
	function tgl_indo($tanggal){
		$bulan = array (
			1 =>   'Januari',
			'Februari',
			'Maret',
			'April',
			'Mei',
			'Juni',
			'Juli',
			'Agustus',
			'September',
			'Oktober',
			'November',
			'Desember'
		);
		$pecahkan = explode('-', $tanggal);
		return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
	}    
    
    foreach($_POST as $key=>$value) {$$key=$value;}
    
    if($aksi==base64_encode('cari_data_verif')){
        $acide = base64_decode($acid);
        $sql = "SELECT * FROM ngapak.tbl_ambil_ac WHERE id=$acide"; 
        $data["req"]=base64_encode($sql);
        echo curl($url_api, $data);
        
    } else if($aksi=='simpan_antrian_ac'){
	   // $encrit = new CI_Encrypt();
	    $enperkid = 0; //$encrit->encode($perkara_id, $kunci);	
	    $enpihakno = 0; //$encrit->encode($pihak_no, $kunci);	
	    
        $sqls = "
            INSERT INTO `ngapak`.`tbl_ambil_ac` (
            `perkara_id`,`nomor_perkara`,`nomor_ac`,`nama_pihak`,`pihak_no`,`tgl_ambil`, `jam_ambil`,`no_hp`,`stat`,
            `ket`,`tgl_input`, `jenis_pembayaran`, `kunci1`,`kunci2`
            )
            VALUES(
            $perkara_id, '".$nomor_perkara."','".$nomor_akta_cerai."','".$nama."', $pihak_no, DATE('".$tgl_ambil."'), TIME('".$ijam."'), '".$nomor_hp."', 0,
            'Request dari LumpiaPasar', NOW(), $jenis_bayar, '".$enperkid."','".$enpihakno."'
            );
        ";
        $data["req"]=base64_encode($sqls);
        $hasil = curl($url_api, $data); 
        
        //KIRIM NOTIF KE PIHAK
        $mess = "
            *WA Notifikasi PA Semarang* 
            _Permohonan Pengambilan Produk_
            Pastikan Anda menerima pesan WA ini
            Petugas kami akan melakukan *verfifikasi* terhadap permohonan Anda:
                Nomor Perkara *".$nomor_perkara."* 
                Nomor Akta Cerai *".$nomor_akta_cerai."* 
            Kami akan update proses validasi permohonan Anda dengan mengirimkan pesan ke nomor Whatsapp *".$nomor_hp."* ini.
            Terimakasih
            ";
        $url2 = 'https://sinofita.pa-semarang.go.id/wame_c/send_pesan';
        $kirim2 = array(
            'tujuan'  => trim($nomor_hp),
            'pesan'=> trim($mess)
        );
        $options2 = array(
          'http' => array(
            'method'  => 'POST',
            'content' => json_encode( $kirim2),
            'header'=>  "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n"
            )
        );
        $context2  = stream_context_create( $options2 );
        $result2 = file_get_contents( $url2, false, $context2 );
        $response = json_decode( $result2 ); 

        //KIRIM NOTIF ADMIN via TABEL WAME SINOFITA
        $notif_admin = "*WA-NOTIFIASI PA Semarang* Telah datang permohonan pengambilan AC *LUMPIAPASAR* atas nama ".$nama." *".$nomor_perkara."* nomor AC: *".$nomor_akta_cerai."*. Mohon untuk segera ditindaklanjuti";
        $sqls = "
            INSERT INTO `wame`.`outbox` (
                `tipe`,`penerima`,`pesan`,`stat`,`tipe_pesan`
            )
            VALUES
                ( 7,'085155140533','".$notif_admin."', 0,1);  

        ";
        $data["req"]=base64_encode($sqls);
        $hasil = curl($url_api, $data);


        $arr = array(
            'stat'=>'1',
            'pesan'=>'Berhasil disimpan'
        );
        
        echo    "<script>
                    if(!alert('Pengadilan Agama Semarang akan melakukan verifikasi terhadap permohonan Anda. Mohon ditunggu Whatsapp Notifikasi dari Pengadilan Agama Semarang untuk langkah selanjutnya')) document.location = 'akta_cerai.php';
                </script>";   
                
                
    } else if($aksi==base64_encode('cari_nomor_perkara')){
        $nomor_perkara = base64_decode(trim($_POST["noperk"]));
        $nomor_perkara = preg_replace("/[^A-Za-z0-9 \/.]/", "", $nomor_perkara);
        $nomor_perkara = str_replace(" ", "", $nomor_perkara);
        if($_POST["jenis"]==base64_encode("akta")){
            $sql = "SELECT
            perkara.nomor_perkara as value
            ,perkara_pihak1.nama as pihak1_text
            ,perkara_pihak2.nama as pihak2_text
            ,replace(perkara_pihak1.alamat,\"\\n\",\" \") AS pihak1_alamat
            ,replace(perkara_pihak2.alamat,\"\\n\",\" \") AS pihak2_alamat
            ,perkara.para_pihak as para_pihak
            ,perkara_akta_cerai.nomor_akta_cerai as nomor_akta_cerai
            ,perkara_akta_cerai.tgl_penyerahan_akta_cerai AS pac 
            ,perkara_akta_cerai.tgl_penyerahan_akta_cerai_pihak2 AS pac2
            ,perkara.perkara_id
            FROM sipp.perkara_akta_cerai
            LEFT JOIN  sipp.perkara  ON perkara.perkara_id=perkara_akta_cerai.perkara_id
            LEFT JOIN  sipp.perkara_pihak1  ON perkara_pihak1.perkara_id=perkara_akta_cerai.perkara_id 
            LEFT JOIN  sipp.perkara_pihak2  ON perkara_pihak2.perkara_id=perkara_akta_cerai.perkara_id
            WHERE sipp.perkara_akta_cerai.nomor_akta_cerai IS NOT NULL and SUBSTRING_INDEX(nomor_perkara,'/',1) = '$nomor_perkara'
            ORDER BY SPLIT_STRING(perkara.nomor_perkara,'/',3) DESC
            , SPLIT_STRING(perkara.nomor_perkara,'/',2) DESC
            , SPLIT_STRING(perkara.nomor_perkara,'/',1) DESC"; 
        }
        $data["req"]=base64_encode($sql);
        echo curl($url_api, $data); 
        
    } else {
        echo 'Access Forbiden';
    }

?>