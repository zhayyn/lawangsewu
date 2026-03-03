<?php
include('_sys_koneksi.php');
foreach($_POST as $key=>$value) {$$key=$value;}
if(isset($_POST["pagination"]))
{
	$sql="SELECT id_panjar	FROM panjar_data GROUP BY id_panjar";
	$db = new Tampil_sekunder(); 
	$jumlah_data = $db->jumlah_data_sekunder($sql);
	echo '<div class="w3-container w3-cell">Jumlah : '.$jumlah_data.' data</div>';
	$pages = ceil($jumlah_data/$limit);
	
	if($pages>=2)
	{
		$i=0;
		echo '<div class="w3-container w3-cell">';
		echo '<div class="w3-bar w3-right" id="pagination">';
		while ($i < $pages) {
			$limitnya=$i*$limit;
			$i++;
		  	echo '<a href="#" onclick="tampilkan_data_panjar('.$limitnya.','.$limit.')" class="w3-button">'.$i.'</a>';
		} 
		echo '</div>'; 
		echo '</div>'; 
	}
	exit;
}
if(isset($_POST["detail"]))
{
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
              </tr>";
     
      }

      echo "</tbody></table><hr>";
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
                <td>Nama Pihak<br>Tempat Tinggal</td>
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
	exit;
}

$return_arr=array();
$row_array=array();
$row_hasl=array(); 
	  $sql="SELECT 
				id_panjar
				,jenis_pendaftaran_id
				,jenis_pendaftaran
				,(SELECT GROUP_CONCAT(DISTINCT CONCAT(a.nama_pihak,', tempat tinggal ',a.alamat,', sebagai ',a.sebutan) ORDER BY a.id ASC
				        SEPARATOR '<br>') FROM panjar_data AS a WHERE a.id_panjar=panjar_data.id_panjar AND nama_pihak IS NOT NUll  ) AS pihak 
				,sum(jumlah_x_biaya) AS total_panjar
				,diinput_tanggal
				FROM
				panjar_data GROUP BY id_panjar
				ORDER BY id_panjar DESC
				LIMIT $limit OFFSET $mulai";
      $db = new Tampil_sekunder(); 
      $arrayData = $db->tampil_data_sekunder($sql);
      if (count($arrayData)) 
      {
      	$nomor=$mulai;
        foreach ($arrayData as $data){
        	$nomor++;
        	$row_array['nomor'] = $nomor;
        	$row_array['id_panjar'] = $data['id_panjar'];
			$row_array['jenis_pendaftaran_id'] = $data['jenis_pendaftaran_id'];
			$row_array['jenis_pendaftaran'] = $data['jenis_pendaftaran'];
			$row_array['pihak'] = $data['pihak'];
			$row_array['total_panjar'] = $data['total_panjar'];
			$row_array['diinput_tanggal'] = $data['diinput_tanggal'];
            array_push($return_arr,$row_array);
        }
        $row_hasl=array('data'=>$return_arr);
        echo json_encode($row_hasl); 
      } 

?> 