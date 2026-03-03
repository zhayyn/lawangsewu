<?php
include("_sys_koneksi.php");


function arr2md5($arrinput){
   $hasil='';
    foreach($arrinput as $val){
        if($hasil==''){
            $hasil=md5($val);
        }
        else {
            $code=md5($val);
            for($hit=0;$hit<min(array(strlen($code),strlen($hasil)));$hit++){
                $hasil[$hit]=chr(ord($hasil[$hit]) ^ ord($code[$hit]));
            }
        }
    }
    return(md5($hasil));
}
function getPassword($pase){ 
	$pass = arr2md5($pase);
	return $pass;
			 
}

foreach($_POST as $key=>$value) {$$key=$value;}
if($tabel=="panjar_jenis_pendaftaran"){
	$isi=array(
		'jenis_pendaftaran'	=>$jenis_pendaftaran
		,'keterangan'			=>$keterangan
		,'sebutan_p'			=>$sebutan_p
		,'sebutan_t'			=>$sebutan_t
		,'ikon'					=>$ikon
		,'aktif'				=>$aktif
	);
}else
if($tabel=="panjar_jenis_biaya"){
	$isi=array(
		'urutan' =>$urutan
		,'nama_biaya' =>$nama_biaya
		,'biaya' =>$biaya
		,'jumlah_dikalikan' =>$jumlah_dikalikan
		,'ghoib' =>$ghoib
		,'pihak' =>$pihak
		,'aktif' =>$aktif

	);
}else
if($tabel=="panjar_kelurahan_komdanas"){
	$isi=array(
		'nilai' =>$nilai
	);
}else
if($tabel=="panjar_ongkos_kirim"){
	$isi=array(
		'biaya' =>$biaya
	);
}else
if($tabel=="antrian_sidang_config"){
	$isi=array(
		'waktu_mulai_antri' =>$waktu_mulai_antri
	);
}else
if($tabel=="panjar_users"){
	$isi=array(
		$field =>$isi
	);
	$kolom=$kunci;
	$kunci=$isi_kunci;
}else
if($tabel=="password"){
	$test=arr2md5(array($user_activation_key,$kata_sandi));
	$isi=array(
		'user_pass' =>$test
	);
	$tabel='panjar_users';
}
$db = new Edit_sekunder(); 
$proses=$db->edit_data_sekunder($tabel,$kolom,$kunci,$isi);