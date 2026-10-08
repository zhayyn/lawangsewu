<?php
	ini_set('upload_max_filesize', '20M');
	ini_set('post_max_size', '20M');
	// include('_sys_koneksi.php');
    include('_sys_header.php');
    include('_fungsi.php');
    ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
?> 

<body oncontextmenu="return false;">
<div class="w3-container">
<?php
	$stat = "";
	foreach($_POST as $key => $value) {echo " '$key' : '$value' <br>";}
	foreach($_POST as $key=>$value) {
		$$key=trim($value);
	}
	if(
		isset($_POST["inama_saksi"])
		) 
		// AND isset($_POST["nomor_perkara"]) AND isset($_POST["nomor_akta_cerai"]) AND isset($_POST["produk"]) AND 
		// isset($_POST["para_pihak"]) AND  isset($_POST["kirim"]) AND 
		// isset($_POST["nama_pengambil"]) AND isset($_POST["jadwal"]) AND
		// isset($_POST["perkara_id"]) AND isset($_POST["pihak_no"]) AND //isset($_POST["email"]) AND 
		// isset($_POST["nomor_hp"]) )
	{



		// $permohonan_id="".$nomor_hp."-".rand(100, 999);
		// $password_packing=base64_encode(rand(1000, 9999));

		// $password_pelayanan=base64_encode(rand(1000, 9990));

		if($tipe_input == '0'){
			// JIKA INPUTAN DATA PIHAK BARU
			$arr=array(
				'jenis_pihak_id'=>(int)$jenis_pihak,
				'jenis_indentitas'=>(int)$jenis_idc, 
				'nomor_indentitas'=>trim($no_idc),
				'nama'=>trim($nama), 	
				'tempat_lahir'=>trim($tempat_lahir), 
				'tanggal_lahir'=>$tgl_lahir,
				'jenis_kelamin'=>trim($jkel), 			
				'golongan_darah'=>trim($goldar),			
				'alamat'=>trim($alamat),		
				'rtrw'=>NULL,
				'kelurahan'=>NULL,
				'kecamatan'=>NULL,
				'kabupaten_id'=>NULL,
				'kabupaten'	=>NULL,
				'propinsi_id'=>NULL,
				'propinsi'=>NULL,	
				'telepon'=>trim($nowa),
				'email'=>trim($email), 	
				'agama_id'=>(int)$agama_id,	
				'agama_nama'=>trim($agama), 
				'status_kawin'=>(int)$marstat,
				'pekerjaan'=>'Serabut kelapa', 
				'pendidikan_id'=>(int)$pend_id,	
				'pendidikan'=>trim($pend), 
				'warga_negara_id'=>(int)$wn_id, 
				'warga_negara'=>trim($wn), 
				'keterangan'=>trim($ket),  
				'difabel'=>trim($difabel),
				'diinput_oleh'=>'loket1'
			);
			print_r($arr);
			$InertKan = new Tambah();   
			$simpan=$InertKan->tambah_data('pihak', $arr);	

			$stat = "manual SUKSES";		

		} else {
			//JIKA INPUTAN DATA PIHAK LAMA
			// $InertKan = new Tambah_sekunder();   
			// $simpan=$InertKan->tambah_data_sekunder('perkara_pihak5', $a) ;	
			$stat = "auto SUKSES";
		}
          

           

	} else {
		$stat = "GAGAL";

	}


?>
<div class="w3-container" id="permohonan">
    <center>
        <div class="w3-panel w3-pale-blue w3-border"> 
            <p></p><h4><b>DATA SAKSI</b></h4></p>
            Nomor Perkara:
            <h2><b><?=$nomor_perkara ?></b></h2>
    	    <h5><b><?=$stat ?></b></h5>
    	    <p></p>
    	    <br>
        </div>

        <div class="w3-panel w3-pale-red w3-border"> 
            <p></p><h4><b>Terimakasih</b></h4></p>
        </div>		
    </center>
</div>

<center><a href="permohonan.php" style="text-decoration: none" class="w3-btn w3-red">Kembali ke Halaman Utama</a>

</div>
</body>
</html>