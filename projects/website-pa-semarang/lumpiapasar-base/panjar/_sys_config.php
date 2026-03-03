<?php
include('../_sys_koneksi.php');
$namapa='';
$sql="SELECT id, value FROM sys_config WHERE id=62 OR id=61 OR id=63 ";
$db = new Tampil(); 
$arrayData = $db->tampil_data($sql);  
if (count($arrayData)) 
{ 
    foreach ($arrayData as $data) 
    {  
        foreach($data as $key=>$value) {$$key=$value;}
        if($id==61)
        {
            $KodePN=$value;
        }else
        if($id==62)
        {
            $namapa =$value;
        }else
        if($id==63)
        {
            $AlamatPN  =$value;
        } else
        if($id==82)
        {
            $kode_satker  =$value;
        } 
    }
}
$default_propinsi="Jawa Tengah";
$default_propinsi_id='0300';
$default_kota='Kota Semarang';
?>