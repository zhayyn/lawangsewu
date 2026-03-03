<?php
include("_sys_koneksi.php");
foreach($_POST as $key=>$value) {$$key=$value;}
$sql="SELECT * FROM $tabel WHERE $kunci=$isi";
$db = new Tampil_sekunder(); 
$arrayData = $db->tampil_data_sekunder($sql); 
if (count($arrayData)) 
{
	foreach ($arrayData as $data){
		if($tabel=="panjar_jenis_pendaftaran"){
			$isi=array(
				'id'	=>$data["id"]
				,'jenis_pendaftaran'	=>$data["jenis_pendaftaran"]
				,'keterangan'	=>$data["keterangan"]
				,'sebutan_p'	=>$data["sebutan_p"]
				,'sebutan_t'	=>$data["sebutan_t"]
				,'ikon'	=>$data["ikon"]
				,'aktif'	=>$data["aktif"]
			);
		}else
		if($tabel=="panjar_jenis_biaya"){
			$isi=array(
				'id' =>$data['id']
				,'urutan' =>$data['urutan']
				,'jenis_pendaftaran_id' =>$data['jenis_pendaftaran_id']
				,'nama_biaya' =>$data['nama_biaya']
				,'biaya' =>$data['biaya']
				,'jumlah_dikalikan' =>$data['jumlah_dikalikan']
				,'ghoib' =>$data['ghoib']
				,'pihak' =>$data['pihak']
				,'aktif' =>$data['aktif']
			);
		}else
		if($tabel=="panjar_data"){
			$isi=array(
				'id_panjar' =>$data['id_panjar']
				,'jenis_pendaftaran_id' =>$data['jenis_pendaftaran_id']
				,'jenis_pendaftaran' =>$data['jenis_pendaftaran']
				,'pihak' =>$data['pihak']
				,'total_panjar' =>$data['total_panjar']
				,'diinput_tanggal' =>$data['diinput_tanggal']
			);
		}
	}
	echo json_encode($isi);
	
}
