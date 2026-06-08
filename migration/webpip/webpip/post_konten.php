<?php
  include('_sys_config.php');
  include('_fungsi.php');

  foreach($_POST as $key=>$value) {$$key=$value;}
  
      $perkara_id = 1234;
      $nomor_perkara = '1234/Pdt.G/2023/PA.Smg';
      $jurusita = 'Assefio Alladino, S.H';
      $nama = 'Agus Hayudin, S.Kom.';
      $umur = 45;
      $pekerjaan = 'PNS';
      $agama = 'ISLAM';
      $alamat = 'Kono kae pokoke adohhh';
      $jenis_perkara = 'Cerai Gugat';
      $tgl_putusan = date("Y-m-d H:i:s");
      $nama_dok = 'Relas PIP 1234.pdf';
          


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
          'created' => $tgl_iki,
          'created_by'=>428,
          'created_by_alias'=>'',
          'modified' => $tgl_iki,
          'modified_by' => 0,
          'checked_out' => 0,
          'checked_out_time' => $tgl_iki,
          'publish_up' => $tgl_iki,
          'publish_down' => $tgl_iki,
          'images' => $imgs,
          'urls' => $urlss,
          'attribs' => $attribs,
          'version' => 9,
          'ordering' => 0,
          'metakey' => '',
          'metadesc' => '',
          'access' => 1,
          'hits' => 0,
          'metadata' => $meta,
          'featured' => 0,
          'language' => '*',
          'xreference'=>'',
          'note'=>''
          );
      
        $db2 = new Tambah_sekunder(); 
        $proses2 = $db2->tambah_data_sekunder("dmk_content", $isi2);   


?>