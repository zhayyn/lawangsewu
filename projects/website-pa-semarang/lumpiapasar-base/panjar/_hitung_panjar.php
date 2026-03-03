<div class="w3-card-4">
    <div class="w3-container w3-teal">
      <h5>HASIL</h5>
    </div>
    <div class="w3-container">
<?php
include('../_sys_koneksi.php');

  foreach($_POST as $key=>$value) {$$key=$value;}
  $nama_pihak=trim($nama_pihak);
  $nama_pihak=str_replace("'", " ",$nama_pihak);
  $nama_pihak=str_replace('"', " ",$nama_pihak);
  $nama_pihak=str_replace('?', " ",$nama_pihak);
  $nama_pihak=str_replace('<', " ",$nama_pihak);
  $nama_pihak=str_replace('>', " ",$nama_pihak);
  $nama_pihak=str_replace('-', " ",$nama_pihak);
  $nama_pihak=str_replace('+', " ",$nama_pihak);
  $nama_pihak=str_replace(';', " ",$nama_pihak);
  $nama_pihak=str_replace('(', " ",$nama_pihak);
  $nama_pihak=str_replace(')', " ",$nama_pihak);
  $nama_pihak=str_replace('   ', " ",$nama_pihak);
  $nama_pihak=str_replace('  ', " ",$nama_pihak);
 

$namapa='';
$sql="SELECT id,value FROM sys_config WHERE id=62 OR id=61 OR id=63 OR id=82 ";
       //echo $sql;
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
    }else
    if($id==82)
    {
      $kode_satker  =$value;
    } 
  }
}
//cek ongkir
$ongkir=50000;
$sql="SELECT biaya FROM panjar_ongkos_kirim LIMIT 1";
$db = new Tampil_sekunder(); 
$arrayData = $db->tampil_data_sekunder($sql);
if (count($arrayData)) 
{ 
  foreach ($arrayData as $data) 
  {
    foreach($data as $key=>$value) {$$key=$value;}
    $ongkir=$biaya;
  }
}
//cek ongkir

//inputkan panjar_jenis_biaya yang pasti
  //sebelum di inputkan maka di cek dulu kl sudah ada maka tidak perlu
    
  $sql="SELECT id FROM panjar_data WHERE id_panjar=$id_panjar";
  //echo $sql;  
  $db = new Tampil_sekunder(); 
  $jumlah_pasti= $db->jumlah_data_sekunder($sql);
  if($jumlah_pasti == 0 )
  {
      $sql="SELECT urutan,nama_biaya, biaya, jumlah_dikalikan FROM panjar_jenis_biaya WHERE pihak=0 AND jenis_pendaftaran_id=$jenis_pendaftaran_id";
      $db = new Tampil_sekunder(); 
      $arrayData = $db->tampil_data_sekunder($sql);
      if (count($arrayData)) 
      { 
        foreach ($arrayData as $data) 
        {         
           $a=array(                   
                      'id_panjar'=>$id_panjar 
                      ,'jenis_pendaftaran_id'=>$jenis_pendaftaran_id 
                      ,'jenis_pendaftaran'=>$jenis_pendaftaran 
                      ,'urutan'=>$data["urutan"] 
                      ,'jumlah'=>$data["jumlah_dikalikan"] 
                      ,'uraian'=>$data["nama_biaya"]
                      ,'biaya'=>$data["biaya"]
                      ,'jumlah_x_biaya'=>$data["biaya"]*$data["jumlah_dikalikan"]
                      ,'diinput_tanggal'=>date("Y-m-d H:i:s")
                    );
                   // echo json_encode($a);
            $InertKan = new Tambah_sekunder();   
            $simpan=$InertKan->tambah_data_sekunder('panjar_data', $a) ;
        }
      }
  }
  //exit;
  //sebelum di inputkan maka di cek dulu kl sudah ada maka tidak perlu
//inputkan panjar_jenis_biaya yang pasti 

//inputkan untuk sebutan_id 1
if($sebutan_id==1)
{
      $sql="SELECT urutan,nama_biaya, biaya, jumlah_dikalikan FROM panjar_jenis_biaya WHERE pihak=1 AND jenis_pendaftaran_id=$jenis_pendaftaran_id";
      $db = new Tampil_sekunder(); 
      $arrayData = $db->tampil_data_sekunder($sql);
      if (count($arrayData)) 
      { 
        foreach ($arrayData as $data) 
        {
            $biaya=$data["biaya"];
            if($biaya==0)
            {
              $biaya=$nilai;
              if($kode_satker<>$satker_code)
              {
                $biaya=$biaya+$ongkir;
                $uraian=$data["nama_biaya"]." (".$nama_pihak.")<br>".$data["jumlah_dikalikan"]." x @Rp".number_format($biaya,0,',','.')."<br>termasuk ongkos kirim";
              }else
              {
                $uraian=$data["nama_biaya"]." (".$nama_pihak.")<br>".$data["jumlah_dikalikan"]." x @Rp".number_format($biaya,0,',','.');
              }
            }else
            {
              $biaya=$data["biaya"];
              $uraian=$data["nama_biaya"]." (".$nama_pihak.")";
            }
           $a=array(                   
                      'id_panjar'=>$id_panjar 
                      ,'jenis_pendaftaran_id'=>$jenis_pendaftaran_id 
                      ,'jenis_pendaftaran'=>$jenis_pendaftaran 
                      ,'urutan'=>$data["urutan"]
                      ,'nama_pihak'=>$nama_pihak
                      ,'sebutan_id'=>$sebutan_id
                      ,'sebutan'=>$sebutan
                      ,'alamat'=>$alamat
                      ,'satker'=>$satker_code
                      ,'jumlah'=>$data["jumlah_dikalikan"]
                      ,'uraian'=>$uraian
                      ,'biaya'=>$biaya
                      ,'jumlah_x_biaya'=>$biaya*$data["jumlah_dikalikan"]
                      ,'diinput_tanggal'=>date("Y-m-d H:i:s")
                    );
                   // echo json_encode($a);
            $InertKan = new Tambah_sekunder();   
            $simpan=$InertKan->tambah_data_sekunder('panjar_data', $a) ;
        }
      }
}
//inputkan untuk sebutan_id 1


//inputkan untuk sebutan_id 2
if($sebutan_id==2)
{
      if($ghoib==0)
      {
        $tambahan=" AND ghoib=1 ";
        $alamat="tidak diketahui tempat tinggalnya";
      }else
      {
        $tambahan=" AND ghoib=0 ";
      }
      $sql="SELECT urutan,nama_biaya, biaya, jumlah_dikalikan FROM panjar_jenis_biaya WHERE pihak=2 $tambahan AND jenis_pendaftaran_id=$jenis_pendaftaran_id";
      //echo $sql;
      $db = new Tampil_sekunder(); 
      $arrayData = $db->tampil_data_sekunder($sql);
      if (count($arrayData)) 
      { 
        foreach ($arrayData as $data) 
        {
            $biaya=$data["biaya"];
            if($biaya==0)
            {
              $biaya=$nilai;
              if($kode_satker<>$satker_code)
              {
                $biaya=$biaya+$ongkir;
                $uraian=$data["nama_biaya"]." (".$nama_pihak.")<br>".$data["jumlah_dikalikan"]." x @Rp".number_format($biaya,0,',','.')."<br>termasuk ongkos kirim";
              }else
              {
                $uraian=$data["nama_biaya"]." (".$nama_pihak.")<br>".$data["jumlah_dikalikan"]." x @Rp".number_format($biaya,0,',','.');
              }
            }else
            {
              $biaya=$data["biaya"];
              $uraian=$data["nama_biaya"]." (".$nama_pihak.")";
            }
           $a=array(                   
                      'id_panjar'=>$id_panjar 
                      ,'jenis_pendaftaran_id'=>$jenis_pendaftaran_id 
                      ,'jenis_pendaftaran'=>$jenis_pendaftaran 
                      ,'urutan'=>$data["urutan"]
                      ,'nama_pihak'=>$nama_pihak
                      ,'sebutan_id'=>$sebutan_id
                      ,'sebutan'=>$sebutan
                      ,'alamat'=>$alamat
                      ,'satker'=>$satker_code
                      ,'jumlah'=>$data["jumlah_dikalikan"]
                      ,'uraian'=>$uraian
                      ,'biaya'=>$biaya
                      ,'jumlah_x_biaya'=>$biaya*$data["jumlah_dikalikan"]
                      ,'diinput_tanggal'=>date("Y-m-d H:i:s")
                    );
                   // echo json_encode($a);
            $InertKan = new Tambah_sekunder();   
            $simpan=$InertKan->tambah_data_sekunder('panjar_data', $a) ;
        }
      }
}
//inputkan untuk sebutan_id 2
include('_tampilkan_hasil.php');
?>
  </div>
</div>