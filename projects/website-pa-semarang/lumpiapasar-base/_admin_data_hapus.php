<?php
include("_sys_koneksi.php");
foreach($_POST as $key=>$value) {$$key=$value;}
$db = new Hapus_sekunder(); 
$proses=$db->hapus_data_sekunder($isi, $kunci, $tabel);