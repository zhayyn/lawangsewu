<?php
if(!isset($_SESSION)){session_start();}
include('_sys_config.php');
date_default_timezone_set('Asia/Jakarta');
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
function tanggal_indonesia($tanggal){
  $bulan = array (1 =>   'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'); $pecahkan = explode('-', $tanggal);
  return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
} 


foreach($_POST as $key=>$value) {$$key=$value;}

if(base64_decode($aksi)=='antriansidangcek'){
  $data["nomor_perkara"]=$_POST["nomor_perkara"];
  echo curl("http://202.145.13.125/ptsp/api_lumpia/_antriansidangcek",$data);
  exit;
}else
if(base64_decode($aksi)=='proses_antrian_sidang'){ 
  $data["perkara_id"]=$_POST["idunik"];
  echo curl("http://202.145.13.125/ptsp/api_lumpia/_antriansidangproses",$data);
  exit;
}
else{
  echo "Access Forbidden";
}
?>