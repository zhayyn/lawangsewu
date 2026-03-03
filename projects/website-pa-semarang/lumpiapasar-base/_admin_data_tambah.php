<?php
include("_sys_koneksi.php");
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
		,'jenis_pendaftaran_id' =>$jenis_pendaftaran_id
		,'nama_biaya' =>$nama_biaya
		,'biaya' =>$biaya
		,'jumlah_dikalikan' =>$jumlah_dikalikan
		,'ghoib' =>$ghoib
		,'pihak' =>$pihak
		,'aktif' =>$aktif

	);
}
$db = new Tambah_sekunder(); 
$proses=$db->tambah_data_sekunder($tabel,$isi);