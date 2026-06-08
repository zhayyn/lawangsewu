<?php
  include('_sys_config.php');
  include('_fungsi.php');
  
  $referer = $_SERVER['HTTP_REFERER'];
  
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

    if($aksi=='edit_profile'){
      if (isset($_POST['password'])){
        $isi=array(     
          'email' => trim($email),  
          'password' => md5($password)
        );  
      } else {
        $isi=array(     
          'email' => trim($email)     
        );          
      }  
      $db = new Edit(); 
      $proses=$db->edit_data("sys_users", "userid", $userid, $isi);
      var_dump($isi);      
      exit;
    }

    if($aksi=='simpan_config'){
        $isi=array(     
          'NM_JS' => trim($inmjs),  
          'JAB_JS' => trim($ijabjs),
          'STR_ADDR' => trim($istraddr),
          'WA_KEY' => trim($iwakey)
        );  
 
      $db = new Edit(); 
      $proses=$db->edit_data("tbl_conf", "id_conf", $iidconf, $isi);
      var_dump($isi);
      echo "<script>
                window.location.href = 'https://pa-semarang.go.id/webpip/home_admin.php';
            </script>";
        exit;    
      exit;
    }    
    
    if($aksi=='hapus_data_pip'){
      if (isset($_POST['id_pip'])){
          $db = new Hapus(); 
          $proses=$db->hapus_data($id_pip, "id", "tbl_pip");
        //   var_dump($isi);          
      }
      exit;
    }    
    
    if($aksi=='simpan_pip'){
       $uploadOk = 0;
       $info_upload = "";
       $fileasu = basename($_FILES["pip_file"]["name"]);

       if(strlen($fileasu) > 0){
           $target_dir = "DOC/";
           $nama_dok = 'PIP'.$perkara_id.'_'.str_replace(" ", "",basename($_FILES["pip_file"]["name"]));
          $target_file = $target_dir.$nama_dok;
          $uploadOk = 1;
          $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
          if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "pdf" ) {
              $info_upload ="Foto/Scan relaas hanya mengizinkan jenis gambar";
              $uploadOk = 0;
                // exit;
          }
          if ($uploadOk == 0) {
              $info_upload = "Tidak ada file yang berhasil di-upload";
                // exit;
          }else{
                // $uploadya=move_uploaded_file($_FILES["file_lakon"]["tmp_name"], $target_file);
             $uploadya=move_uploaded_file($_FILES["pip_file"]["tmp_name"], $target_file);
              if ($uploadya) {
                  $info_upload = "Upload File SUKSES";
              }else{
                  $info_upload = "Upload file GAGAL";
                  // var_dump($uploadya);
                    // exit;
              }
          }
           $nama_dok = $nama_dok;
       } else{
        $nama_dok = "no_file";
       }
        $data = [];
        $isi=array(     
          'perkara_id' => trim($perkara_id),
          'nomor_perkara' => trim($nomor_perkara),
          'jurusita_nama' => trim($jurusita),
          'nama_pihak' => trim($nama),
          'umur' => trim($umur),
          'pekerjaan' => trim($pekerjaan),
          'agama' => trim($agama),
          'alamat' => trim($alamat),
          'jenis_perkara' => trim($jenis_perkara),
          'tgl_putusan' => $tgl_putusan,          
          'link_file' => trim($nama_dok) 
        );          

      $db = new Tambah(); 
      $proses=$db->tambah_data("tbl_pip", $isi);
      
      $asu = '
        <p>Pada hari ini Rabu, 20 September 2023 saya, <br /><b>'.$jurusita.'</b> Jurusita Pengganti pada Pengadilan Agama Semarang <br /> atas perintah Ketua Majelis Hakim tersebut;<br />
        <br /> <i><b>TELAH MEMBERITAHUKAN KEPADA:</b></i><br /> <b>'.$nama.'</b><br /> Umur '.$umur.' tahun, agama '.$agama.', pekerjaan '.$pekerjaan.', 
        alamat dahulu '.$alamat.'</p>
        <p><span style="color: #ff0000;"><strong>dan sekarang tidak diketahui alamat dan keberadaannya di seluruh wilayah Republik Indonesia</strong></span><br />
        <span id="page11R_mcid49" class="markedContent"></span><br /> Tentang putusan Pengadilan Agama Semarang, '.$tgl_putusan.' Nomor <b>'.$nomor_perkara.'</b>, dalam perkara '.$jenis_perkara.'</p>
      ';
      $celeng='
        <p><a class="btn btn-danger btn-large maxima-more" href="webpip/DOC/'.$nama_doc.'" target="_blank" rel="noopener noreferrer">🗎 Lihat Dokumen PIP</a></p>      
      ';
      $tgl_iki = date("Y-m-d H:i:s");
      
      $tit = 'Surat Pemberitahuan Isi Putusan '.$nama;
      $altit = strtolower($tit);
      $altit = str_replace(' ', '-', $altit);
      $imgs = '{"image_intro":"","float_intro":"","image_intro_alt":"","image_intro_caption":"","image_fulltext":"","float_fulltext":"","image_fulltext_alt":"","image_fulltext_caption":""}';
      $urlss = '{"urla":false,"urlatext":"","targeta":"","urlb":false,"urlbtext":"","targetb":"","urlc":false,"urlctext":"","targetc":""}';
      $attribs = '{"article_layout":"","show_title":"","link_titles":"","show_tags":"","show_intro":"","info_block_position":"","info_block_show_title":"","show_category":"","link_category":"","show_parent_category":"","link_parent_category":"","show_associations":"","show_author":"","link_author":"","show_create_date":"","show_modify_date":"","show_publish_date":"","show_item_navigation":"","show_icons":"","show_print_icon":"","show_email_icon":"","show_vote":"","show_hits":"","show_noauth":"","urls_position":"","alternative_readmore":"","article_page_title":"","show_publishing_options":"","show_article_options":"","show_urls_images_backend":"","show_urls_images_frontend":""}';
      $meta = '{"robots":"","author":"","rights":"","xreference":""}';
      
      

      $isi2=array(
          'asset_id' => 1226,
          'title' => $tit,
          'alias' => $altit,
          'introtext' => $asu,
          'fulltext' => $celeng,
          'state' => 1,
          'catid'=>16,
          'created_by'=>428,
          'modified' => $tgl_iki,
          'checked_out' => 428,
          'checked_out_time' => $tgl_iki,
          'publish_up' => $tgl_iki,
          'images' => $imgs,
          'urls' => $urlss,
          'attribs' => $attribs,
          'version' => 9,
          'ordering' => 0,
          'access' => 1,
          'metadata' => $meta
          );
      
        $db2 = new Tambah_sekunder(); 
        $proses2=$db2->tambah_data_sekunder("dmk_content", $isi2);     
      
      

      var_dump($isi2);
    //   echo "<script>
    //             window.location.href = 'https://pa-semarang.go.id/webpip/home_admin.php';
    //         </script>";
    //     exit;
    }

  }

?>
