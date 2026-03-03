<?php
include('_sys_koneksi.php');
foreach($_POST as $key=>$value) {$$key=$value;}

$return_arr=array();
$row_array=array();
$row_hasl=array();
if($jenis=="propinsi"){
	  $sql="SELECT prop, prop_name FROM panjar_kelurahan_komdanas GROUP BY prop ORDER BY prop_name ASC";
      $db = new Tampil_sekunder(); 
      $arrayData = $db->tampil_data_sekunder($sql);
      if (count($arrayData)) 
      {
        foreach ($arrayData as $data){
        	$row_array['prop'] = $data['prop'];
        	$row_array['prop_name'] = $data['prop_name'];
            array_push($return_arr,$row_array);
        }
        $row_hasl=array('data'=>$return_arr);
        echo json_encode($row_hasl); 
      }
}else
if($jenis=="satker"){
	  if(isset($_POST["prop"]))
	  {
	  	if($prop<>"all")
	  	{
	  		$filter=" WHERE prop='$prop' ";
	  	}else
	  	{
	  		$filter="";
	  	}	
	  }else
	  {
	  	$filter="";
	  }
	  $sql="select satker_code, satker_name from panjar_kelurahan_komdanas $filter group by satker_code order by satker_name ASC";
      $db = new Tampil_sekunder(); 
      $arrayData = $db->tampil_data_sekunder($sql);
      if (count($arrayData)) 
      {
        foreach ($arrayData as $data){
        	$row_array['satker_code'] = $data['satker_code'];
        	$row_array['satker_name'] = $data['satker_name'];
            array_push($return_arr,$row_array);
        }
        $row_hasl=array('data'=>$return_arr);
        echo json_encode($row_hasl); 
      }
}else
if($jenis=="radius"){
	if($satker_code<>"all")
	{
		$filter=" WHERE satker_code='$satker_code' ";
	}else
	{
				
		if($prop<>"all")
		{
			$filter=" WHERE prop='$prop' " ;
		}else
		{
			$filter=" "; 
		}
	}
	//echo $filter;exit;
	  $sql="select * from panjar_kelurahan_komdanas $filter ORDER by satker_code ASC, kabkota ASC, kec ASC, kel ASC";
	  //echo $sql;
      $db = new Tampil_sekunder(); 
      $arrayData = $db->tampil_data_sekunder($sql);
      if (count($arrayData)) 
      {
        foreach ($arrayData as $data){
        	$row_array['id'] = $data['id'];
			$row_array['satker_name'] = $data['satker_name'];
			$row_array['satker_code'] = $data['satker_code'];
			$row_array['prop'] = $data['prop'];
			$row_array['prop_name'] = $data['prop_name'];
			$row_array['kabkota'] = $data['kabkota'];
			$row_array['kec'] = $data['kec'];
			$row_array['kel'] = $data['kel'];
			$row_array['nomor_radius'] = $data['nomor_radius'];
			$row_array['nilai'] = $data['nilai'];

            array_push($return_arr,$row_array);
        }
        $row_hasl=array('data'=>$return_arr);
        echo json_encode($row_hasl); 
      }
}
?> 