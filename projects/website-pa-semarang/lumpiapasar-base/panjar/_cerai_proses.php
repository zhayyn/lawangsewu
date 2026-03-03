<?php

include('../_sys_koneksi.php');
if(isset($_POST["nama_p"]) AND isset($_POST["nama_t"]) AND isset($_POST["satker_code"])AND isset($_POST["nilai"]) AND isset($_POST["alamat"]) AND isset($_POST["ghoib"]))
{
  foreach($_POST as $key=>$value) {$$key=$value;}
}else
{
  exit;
}

$namapa='';
$sql="SELECT id,
value 
FROM sys_config WHERE id=62 OR id=61 OR id=63 OR id=82 ";
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
?>   

      <?php
//cek ongkir
      $ongkir=0;
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
            $uraian=$data["nama_biaya"]." (".$nama_p.")<br>".$data["jumlah_dikalikan"]." x @Rp".number_format($biaya,0,',','.')."<br>termasuk ongkos kirim";
          }else
          {
            $uraian=$data["nama_biaya"]." (".$nama_p.")<br>".$data["jumlah_dikalikan"]." x @Rp".number_format($biaya,0,',','.');
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
          ,'nama_pihak'=>$nama_p
          ,'sebutan_id'=>1
          ,'sebutan'=>$sebutan_p
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

//inputkan untuk sebutan_id 1

//inputkan untuk sebutan_id 2 
    if($ghoib==0)
    {
      $tambahan=" AND ghoib=1 ";
      $alamat1="tidak diketahui tempat tinggalnya";
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
          $biaya=$nilai1;
          if($kode_satker<>$satker_code1)
          {
            $biaya=$biaya+$ongkir;
            $uraian=$data["nama_biaya"]." (".$nama_t.")<br>".$data["jumlah_dikalikan"]." x @Rp".number_format($biaya,0,',','.')."<br>termasuk ongkos kirim";
          }else
          {
            $uraian=$data["nama_biaya"]." (".$nama_t.")<br>".$data["jumlah_dikalikan"]." x @Rp".number_format($biaya,0,',','.');
          }
        }else
        {
          $biaya=$data["biaya"];
          $uraian=$data["nama_biaya"]." (".$nama_t.")";
        }
        $a=array(                   
          'id_panjar'=>$id_panjar 
          ,'jenis_pendaftaran_id'=>$jenis_pendaftaran_id 
          ,'jenis_pendaftaran'=>$jenis_pendaftaran 
          ,'urutan'=>$data["urutan"]
          ,'nama_pihak'=>$nama_t
          ,'sebutan_id'=>2
          ,'sebutan'=>$sebutan_t
          ,'alamat'=>$alamat1
          ,'satker'=>$satker_code1
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

//inputkan untuk sebutan_id 2

    ?>


<div class="w3-card-4">
    <div class="w3-container w3-teal">
        <h5>IDENTITAS  :</h5>
    </div>
    <div class="w3-container">
      
                <strong>ANDA : </strong><br>
                <strong><?php echo $nama_p?></strong><br><?php echo $alamat?>
                <br>
                <br>
                <strong>PASANGAN ANDA :</strong><br>
                <strong><?php echo $nama_t?></strong><br><?php echo $alamat1?>
    </div>
</div>
<div class="w3-card-4">
    <div class="w3-container w3-teal">
        <h5>PERHITUNGAN PANJAR BIAYA PERKARA:</h5>
    </div>
    <div class="w3-container">
       <?php 
              $sql="SELECT * FROM panjar_data WHERE id_panjar='$id_panjar'  ORDER BY urutan ASC";
    //echo $sql;
              $db = new Tampil_sekunder(); 
              $arrayData = $db->tampil_data_sekunder($sql); 
              $no=0;
              $jumlah_total=0;
              if (count($arrayData)) 
              { 
                echo "<table class='w3-table-all'>
                <tr>
                <td>No</td>
                <td>Uraian</td>
                <td style='text-align:right'>Jumlah<br>Rp</td>
                </tr>
                <tbody>";
                foreach ($arrayData as $data) 
                {  
                  $no++;
                  foreach($data as $key=>$value) {$$key=$value;}

                  echo "
                  <tr>
                  <td>".$no."</td>
                  <td>".$uraian."</td>
                  <td style='text-align:right'>".number_format($jumlah_x_biaya,0,',','.')."</td>
                  </tr>";
                  $jumlah_total=$jumlah_total+$jumlah_x_biaya;
                }
                echo "
                <tr>
                <td colspan='2' style='text-align:right'><strong>TOTAL</strong></td>
                <td style='text-align:right'><strong>".number_format($jumlah_total,0,',','.')."</strong></td>
                </tr>";

                echo "</tbody></table>";
              }?>
    </div>
</div>