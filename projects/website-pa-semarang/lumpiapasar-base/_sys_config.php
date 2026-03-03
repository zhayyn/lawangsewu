<?php
include('_sys_koneksi.php');
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
        } 
    }
}
// $url_api=base64_decode("aHR0cDovLzIwMi4xNDUuMTMuMTI1Ly9hcHNfYmFkaWxhZy9hcGlfbW9uaXRvcmluZy9nZXRfZGF0YV9hcGk=");
//$KodePN="PA.Smg";
$url_api = "http://192.168.88.10/aps_badilag/api_monitoring/get_data_api";
// $url_api="https://aps.pa-semarang.go.id/api_monitoring/get_data_api";
//$url_api="http://124.158.186.170/aps_badilag/api_monitoring/get_data_api";
?>