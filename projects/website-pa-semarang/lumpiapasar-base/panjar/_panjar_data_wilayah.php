<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }    
    
	include("../_sys_koneksi.php");     
	switch ($_POST['jenis']) {
		//ambil data kota / kabupaten
		case 'kota':
		$id_provinces = $_POST['id_provinces'];
		if($id_provinces == ''){
		     exit;
		}else{
            $_SESSION["lkab"] = $_POST['id_provinces'];
            echo '<option></option>';
			$sql_propinsi="SELECT kabkota FROM komdanas_area WHERE prop='".$id_provinces."'  GROUP BY kabkota ORDER BY kabkota ASC";
            $db = new Tampil_sekunder(); 
            $arrayData = $db->tampil_data_sekunder($sql_propinsi);  
            if (count($arrayData)) 
            { 
              foreach ($arrayData as $data) 
              {  
                foreach($data as $key=>$value) {$$key=$value;} 
                   echo '<option value="'.$kabkota.'">'.$kabkota.'</option>';
              }
            }   
	     	exit;    
		}
		break;

		//ambil data kecamatan
		case 'kecamatan':
		$id_regencies = $_POST['id_regencies'];
		if($id_regencies == ''){
		     exit;
		}else{
		  //  $_SESSION["lkec"] = $_POST['id_regencies'];
            echo '<option></option>';

			$sql_propinsi="SELECT kec FROM komdanas_area WHERE kabkota='".$id_regencies."'  GROUP BY kec ORDER BY kec ASC";
            $db = new Tampil_sekunder(); 
            $arrayData = $db->tampil_data_sekunder($sql_propinsi);  
            if (count($arrayData)) 
            { 
              foreach ($arrayData as $data) 
              {  
                foreach($data as $key=>$value) {$$key=$value;} 
                   echo '<option value="'.$kec.'">'.$kec.'</option>';
              }
            }   
		     exit;    
		}
		break;
		

		//ambil data kelurahan
		case 'kelurahan':
		$id_district = $_POST['id_district'];
		if($id_district == ''){
		     exit;
		}else{

            echo '<option></option>';
            // if (isset($_SESSION["lkec"])){
		    
			 //   $sql_propinsi="SELECT * FROM panjar_kelurahan_komdanas WHERE kec='".$id_district."' AND kabkota = '".$_SESSION["lkec"]."' GROUP BY kel ORDER BY kel ASC";
            // }else{
                $sql_propinsi="SELECT * FROM komdanas_area WHERE kec='".$id_district."'  GROUP BY kel ORDER BY kel ASC";
            // }
            $db = new Tampil_sekunder(); 
            $arrayData = $db->tampil_data_sekunder($sql_propinsi);  
            if (count($arrayData)) 
            { 
              foreach ($arrayData as $data) 
              {  
                foreach($data as $key=>$value) {$$key=$value;} 
                   echo '<option value="'.$kel.'^'.$satker_code.'^'.$satker_name.'^'.$nilai.'^'.$kel.', Kecamatan '.$kec.', '.$kabkota.' '.$prop_name.'">'.$kel.'</option>';
              }
            } 
		     exit;    
		}
		break;
		
	}
?>