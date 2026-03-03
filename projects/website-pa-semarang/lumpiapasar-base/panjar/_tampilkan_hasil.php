<?php

  echo "<h5>DATA PIHAK</h5>";
//tampilkan data hasil input  
    $sql="SELECT * FROM panjar_data WHERE id_panjar='$id_panjar' AND  nama_pihak IS NOT NULL GROUP BY nama_pihak ORDER BY id ASC";
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
                <td>Sebutan<br>Nama Pihak<br>Tempat Tinggal</td>
                <td>#</td>
              </tr>
              <tbody>";
      foreach ($arrayData as $data) 
      {  
        $no++;
        foreach($data as $key=>$value) {$$key=$value;}
         
        echo "
              <tr>
                <td>".$no."</td>
                <td>".$sebutan."<br><b>".$nama_pihak."</b><br>".$alamat."</td>
                <td style='text-align:center'><a onclick=".'"'."hapus_pihak("."'".$id_panjar."','".$nama_pihak."'".")".'"'." href='#' style='color:red' title='Hapus ".$nama_pihak."'><i class='material-icons'>delete_forever</i></a></td>
              </tr>";
     
      }

      echo "</tbody></table><hr><ul><li>untuk menghapus data pihak klik ikon <i class='material-icons' style='color:red'>delete_forever</i> pada baris data</li><li>untuk menambah pihak, klik tambah</li></ul>";
    }
//tampilkan data hasil input
    echo "<hr><h4>PERHITUNGAN</h4>";
//tampilkan perhitungan
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
    }

//tampilkan perhitungan
?>