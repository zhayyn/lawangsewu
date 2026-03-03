<?php
include("_sys_koneksi.php");
if(isset($_POST["aksi"])){
	foreach($_POST as $key=>$value) {$$key=$value;}
	if($aksi=="upload"){
		$ds          = DIRECTORY_SEPARATOR;  //1
		$storeFolder = 'assets/banner/';   //2
		if (!empty($_FILES)) 
		{
			
			$number=time().rand(100, 999)."_";
			$media_nama= $number."_".$_FILES["file"]["name"];
		    $tempFile =$_FILES['file']['tmp_name'];   	 
			$targetPath = dirname( __FILE__ ) . $ds. $storeFolder;  
			$nama_file= strtolower($media_nama);

			$nama_file=	str_replace("  ", " ", $nama_file);
			$nama_file=	str_replace(" ", "_", $nama_file);
			//$nama_file=ereg_replace("[^A-Za-z0-9._]", "", $nama_file); 

			//echo $media_file;
		    $targetFile =  $targetPath.  $nama_file;  //5
			$proses= move_uploaded_file($tempFile,$targetFile); //6
			$a=array(
			'image'=>$nama_file
          );
           $InertKan = new Tambah_sekunder();   
           $simpan=$InertKan->tambah_data_sekunder('dt_slide', $a) ;
			 
		}
		//upload

	}else
	if($aksi=="hapus"){
		unlink($file);
		$db = new Hapus_sekunder(); 
		$proses=$db->hapus_data_sekunder($id,'id', 'dt_slide');

	}else{
		$sql="SELECT * FROM dt_slide ORDER BY urutan ASC ";
	    $db = new Tampil_sekunder(); 
	    $arrayData = $db->tampil_data_sekunder($sql); 
	    $no=0;
	    echo "<table class='w3-table-all'><tr><td>No</td><td>Gambar</td><td>Urutan</td><td>#</td></tr>";
	    if (count($arrayData)){ 
			foreach ($arrayData as $data){ 
				foreach($data as $key=>$value) {$$key=$value;}
				$no++;
		 		$dir = 'assets/banner/';
	          	echo '<tr><td>'.$no.'</td><td> <img src="'.$dir.$image.'" style="width: 250px"></td><td><input type="number" value="'.$urutan.'" onchange="edit_field('."'".'dt_slide'."'".','."'".'urutan'."'".','."'".'id'."'".', '.$id.', this.value)"></td><td><button class="w3-btn w3-red" onclick=hapus("'.$dir.$image.'",'.$id.')>Hapus</button></td> </tr>'; 
          	}
      	}
      	echo "</table>";
	}
}
?>