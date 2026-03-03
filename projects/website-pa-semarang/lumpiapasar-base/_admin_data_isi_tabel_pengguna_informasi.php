<?php
include('_sys_koneksi.php');
foreach($_POST as $key=>$value) {$$key=$value;}
if(strlen($bulan)==1)
{
  $bulan="0".$bulan;
}

if($bulan=="all")
{
  $filter="  WHERE year(diinput_tanggal)=$tahun ";
}else
{
  $filter=" WHERE month(diinput_tanggal)='$bulan' AND year(diinput_tanggal)=$tahun ";

}
  //rekap
  $sql="SELECT  jenis_info_id, jenis_info, count(id) as jumlah FROM panjar_info_penguna $filter  GROUP BY jenis_info_id "; 
  $db = new Tampil_sekunder(); 
  $arrayData = $db->tampil_data_sekunder($sql);  
  if (count($arrayData)) 
  {
    echo "<div class='row'>";
    foreach ($arrayData as $data) 
    {  
      foreach($data as $key=>$value) {$$key=$value;}
      echo '<div class="w3-cell w3-third w3-center">'.$jenis_info.' : '.$jumlah.'</div>';
    }
    echo "</div>";
  }else
  {
    echo "<center><h5 class='w3-text-red'>Tidak ada Data</h5></center>";
  }
//rekap  
  echo "<div class='w3-row'><hr></div>";

//detail 

//detail


?> 