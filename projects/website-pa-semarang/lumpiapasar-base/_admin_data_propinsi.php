<?php
include("_sys_koneksi.php");
$sql="SELECT prop, prop_name FROM panjar_kelurahan_komdanas GROUP BY prop ORDER BY prop_name ASC";
$db = new Tampil_sekunder(); 
$arrayData = $db->tampil_data_sekunder($sql);
$isi='';
if (count($arrayData)) 
{
	foreach ($arrayData as $data){
			$isi.="<option value=".$data["prop"].">".$data["prop_name"]."</option>";
	}
	//$isi=array_push($isi, 'respon');
	//$isi=array('status'=>'ok','respon'=>$isi);
	echo $isi;	
}
