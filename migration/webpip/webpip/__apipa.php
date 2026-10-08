<?php
include('_sys_config.php');

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

foreach($_POST as $key=>$value) {$$key=$value;}
if(isset($_POST['aksi'])){

  if($aksi==base64_encode('logout')){
    session_start();
    session_unset();
    session_destroy();
    header("Location: index.php");    
  }

  if($aksi==base64_encode('cari_nomor_perkara'))
  {
    $nomor_perkara = base64_decode(trim($_POST["noperk"]));
    $nomor_perkara = preg_replace("/[^A-Za-z0-9 \/.]/", "", $nomor_perkara);
    $nomor_perkara = str_replace(" ", "", $nomor_perkara);
    if($_POST["jenis"]==base64_encode("akta")){
        $sql = "
            select a.nomor_perkara AS value, a.perkara_id, b.nama AS pihak2_text, x_strip_html_tag(b.alamat)  AS pihak2_alamat, e.nama_gelar as nm_jurusita, 
            		d.pekerjaan, IFNULL(d.agama_nama,'ISLAM') agama, 
            		case 
            			when d.tanggal_lahir is not null then DATE_FORMAT(FROM_DAYS(DATEDIFF(now(),d.tanggal_lahir)), '%Y')+0
            			else 0
            		end as umur, a.jenis_perkara_text AS jenis_perkara, f.tanggal_putusan as tgl_putusan		
            FROM perkara a
            	left outer join perkara_pihak2 b on b.perkara_id = a.perkara_id 
            	left outer join pihak d on d.id = b.pihak_id 
            	left outer join perkara_penetapan c on c.perkara_id = a.perkara_id 
            	left outer join jurusita e on e.id = c.jurusita_id 
            	left outer join perkara_putusan f on f.perkara_id = a.perkara_id
            WHERE SUBSTRING_INDEX(a.nomor_perkara,'/',1) = '$nomor_perkara'
                          ORDER BY SPLIT_STRING(a.nomor_perkara,'/',3) DESC
                          , SPLIT_STRING(a.nomor_perkara,'/',2) DESC
                          , SPLIT_STRING(a.nomor_perkara,'/',1) DESC
        ";
    }
    //echo $sql;
    //exit;
    $data["req"]=base64_encode($sql);
    echo curl($url_pa,$data);
    exit;
  }
}

?>