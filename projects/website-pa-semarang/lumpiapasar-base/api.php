<?php
include('_sys_config.php');
date_default_timezone_set('Asia/Jakarta');
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

function simpan_log($inf){
    $isi=array(
        'ipx' =>getenv("REMOTE_ADDR")
        ,'ket' =>$inf
        ,'wkt' =>date("Y-m-d H:i:s") 
    );
    $db = new Tambah_sekunder(); 
    $proses=$db->tambah_data_sekunder("log_pengguna",$isi);       
}


//$sql = "SELECT * FROM smsku.sentitems ORDER BY SendingDateTime DESC LIMIT 40"; 
///$sql = "SELECT * FROM perkara ORDER BY perkara_id ASC LIMIT 40"; 

 
   // echo $sql;
    //exit;
// $data["req"]=base64_encode($sql);
//echo curl($url_api,$data);
//exit;
function tanggal_indonesia($tanggal){
  $bulan = array (1 =>   'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'); $pecahkan = explode('-', $tanggal);
  return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
} 


foreach($_POST as $key=>$value) {$$key=$value;}
//uji coba

    if($aksi=='simpan_antrian_ac'){
        $sqls = "
            INSERT INTO `ngapak`.`tbl_ambil_ac` (
              `perkara_id`,`nomor_perkara`,`nomor_ac`,`nama_pihak`,`pihak_no`,`tgl_ambil`,`no_hp`,`stat`,
              `ket`,`tgl_input`
            )
            VALUES(
                $perkara_id, '".$nomor_perkara."','".$nomor_akta_cerai."','".$nama."', $pihak_no, DATE('".$tgl_ambil."'), '".$nomor_hp."', 0,
                'Request dari LumpiaPasar', NOW()
            );
        ";
        // echo $sqls;
        $data["req"]=base64_encode($sqls);
        $hasil = curl($url_api, $data);   
        $arr = array(
                'stat'=>'1',
                'pesan'=>'Berhasil disimpan'
            );
            
        echo json_encode($arr);
    } else

    if($aksi==base64_encode('cari_nomor_perkara')){
        $nomor_perkara = base64_decode(trim($_POST["noperk"]));
        $nomor_perkara = preg_replace("/[^A-Za-z0-9 \/.]/", "", $nomor_perkara);
        $nomor_perkara = str_replace(" ", "", $nomor_perkara);
        if($_POST["jenis"]==base64_encode("akta")){
            $sql = "SELECT
            perkara.nomor_perkara as value
            ,perkara_pihak1.nama as pihak1_text
            ,perkara_pihak2.nama as pihak2_text
            ,perkara_pihak1.alamat as pihak1_alamat
            ,perkara_pihak2.alamat as pihak2_alamat
            ,perkara.para_pihak as para_pihak
            ,perkara_akta_cerai.nomor_akta_cerai as nomor_akta_cerai
            ,perkara_akta_cerai.tgl_penyerahan_akta_cerai AS pac 
            ,perkara_akta_cerai.tgl_penyerahan_akta_cerai_pihak2 AS pac2
            ,perkara.perkara_id
            FROM perkara_akta_cerai
            LEFT JOIN  perkara  ON perkara.perkara_id=perkara_akta_cerai.perkara_id
            LEFT JOIN  perkara_pihak1  ON perkara_pihak1.perkara_id=perkara_akta_cerai.perkara_id 
            LEFT JOIN  perkara_pihak2  ON perkara_pihak2.perkara_id=perkara_akta_cerai.perkara_id 
            
            WHERE perkara_akta_cerai.nomor_akta_cerai IS NOT NULL and SUBSTRING_INDEX(nomor_perkara,'/',1) = '$nomor_perkara'
            
            ORDER BY SPLIT_STRING(perkara.nomor_perkara,'/',3) DESC
            , SPLIT_STRING(perkara.nomor_perkara,'/',2) DESC
            , SPLIT_STRING(perkara.nomor_perkara,'/',1) DESC"; 
        }
        $data["req"]=base64_encode($sql);
        echo curl($url_api, $data);   
    }
  else
  if(base64_decode($aksi)=='query_bebas')
  {
    //echo $sql;exit;
    $data["req"]=$sql;
    echo curl($url_api,$data);
      exit;
    
  }
//uji coba
  else
//informasi perkara
  if(base64_decode($aksi)=='informasi_pendaftaran'){
      

    simpan_log('Informasi Perkara');   
      
      
      
      
    $nomor_perkara=trim(base64_decode($_POST["nomor_perkara"]));
    $nomor_perkara= preg_replace("/[^A-Za-z0-9 \/.]/","",$nomor_perkara);
    $nomor_perkara= str_replace(" ","",$nomor_perkara);
    $sql = "SELECT 
            perkara.perkara_id
            ,perkara.nomor_perkara
            ,DATE_FORMAT(perkara.tanggal_pendaftaran,'%d/%m/%Y') AS tglPendaftaran
            ,DATE_FORMAT(perkara_putusan.tanggal_putusan,'%d/%m/%Y') AS tglPutusan
            ,perkara.jenis_perkara_nama
            ,perkara.jenis_perkara_id
            ,perkara.jenis_perkara_nama AS klas
            ,perkara.pihak1_text
            ,perkara.pihak2_text
            ,perkara.proses_terakhir_text
            ,perkara_putusan.amar_putusan
            ,status_putusan.nama as status_putusannya
            FROM perkara 
            LEFT JOIN perkara_putusan ON perkara_putusan.perkara_id=perkara.perkara_id
            LEFT JOIN status_putusan ON status_putusan.id=perkara_putusan.status_putusan_id
            WHERE perkara.nomor_perkara='$nomor_perkara'";
           //echoa $sql;
    $data["req"]=base64_encode($sql);
    if(curl($url_api,$data)=="[]"){
      echo "<p class='w3-text-red'>Data tidak ditemukan, silahkan cek kembali nomor perkara</p><p><button class='w3-btn w3-red' onclick='kembali_cari()'>Cari Perkara Lain</button></p>";
      exit;
    }
    $info_awal= curl($url_api,$data);
    $data_pertama = json_decode($info_awal, true);
     echo "<hr>
      <table  class='w3-table-all'>";
      echo "<tr> <td>Nomor Perkara </td> <td>" . $data_pertama[0]["nomor_perkara"] . "</td> </tr>";
      echo "<tr> <td>Tanggal Pendaftaran </td> <td>" . $data_pertama[0]["tglPendaftaran"] . "</td> </tr>";
      echo "<tr> <td>Jenis Perkara </td> <td>" . $data_pertama[0]["jenis_perkara_nama"]. "</td> </tr>";
      echo "<tr> <td>Status Perkara </td> <td>" . $data_pertama[0]["proses_terakhir_text"] . "</td> </tr>";
      if(strlen($data_pertama[0]["status_putusannya"])>=5)
      {
        echo "<tr> <td>Tanggal Putusan </td> <td>" . $data_pertama[0]["tglPutusan"] . "</td> </tr>";
        echo "<tr> <td>Status Putusan </td> <td>" . $data_pertama[0]["status_putusannya"] . "</td> </tr>";
      }
      echo "
      </table>"; 

      //jadwal sidang
       $sql = "SELECT DATE_FORMAT(tanggal_sidang, '%d/%m/%Y') as tanggale ,agenda ,ruangan FROM perkara_jadwal_sidang WHERE perkara_id=".$data_pertama[0]["perkara_id"]." ORDER  by tanggal_sidang ASC"; //echo $sql;
      $data["req"]=base64_encode($sql);
      //echo curl($url_api,$data);exit;
      if(curl($url_api,$data)<>"[]"){
        $info_sidang= curl($url_api,$data);
        $data_sidang = json_decode($info_sidang, true);
        echo "<br><h5>Informasi Jadwal Sidang</h5> <table  class='w3-table-all'> <tr> <td>No </td> <td>Tanggal </td> <td>Agenda <br>Ruangan </td> </tr>";
        $no=0;
        foreach ($data_sidang as $key => $value) {
          $no++;
          echo "
            <tr>
            <td>" . $no . "
            </td>
            <td>" . $value["tanggale"] . "
            </td>
            <td>" . $value["agenda"] . "
            <br>" .$value["ruangan"] . "
            </td>
            </tr>";
        }
         echo "</table>";
      }
      //jadwal sidang

      //keuangan
      $sql = "SELECT DATE_FORMAT(tanggal_transaksi, '%d/%m/%Y') as tanggale ,uraian ,jumlah ,jenis_transaksi ,if(jenis_transaksi=1,jumlah,0) as pemasukan ,if(jenis_transaksi=1,0,jumlah) as pengeluaran FROM perkara_biaya WHERE tahapan_id<=50 AND perkara_id=".$data_pertama[0]["perkara_id"]." ORDER  by tanggal_transaksi ASC"; 
      $data["req"]=base64_encode($sql);
      if(curl($url_api,$data)<>"[]"){
        $info_keu= curl($url_api,$data);
        $data_keu = json_decode($info_keu, true);
        echo "<br><h5>Informasi Biaya Perkara</h5> <table  class='w3-table-all'> <tr> <td>No </td> <td>Tanggal <br>Uraian <br>Jenis <br>Nominal </td> <td  style='text-align:right'>Saldo </td> </tr>"; 
        $no=0;
        $saldo = 0;
        foreach ($data_keu as $key => $value) {
          $no++;
          $saldo = $saldo + $value["pemasukan"] - $value["pengeluaran"];
          echo "
          <tr>
          <td>" . $no . "
          </td>
          <td>" . $value["tanggale"] . "
          <br>" . $value["uraian"] . "
          <br>";
          if ($value["jenis_transaksi"] == 1)
          {
            echo 'Pemasukan
            <br>' . number_format($value["jumlah"], 0, ',', '.');
          }
          else
          {
            echo 'Pengeluaran
            <br>' . number_format($value["jumlah"], 0, ',', '.');
          }
          echo "
          </td>
          <td style='text-align:right'>" . number_format($saldo, 0, ',', '.') . "
          </td>
          </tr>";
        }
         
      echo "</table><br><br><p><button class='w3-btn w3-red' onclick='kembali_cari()'>Cari Perkara Lain</button></p><br><br><br>"; }
      //keuangan 
  }else
  if(base64_decode($aksi)=='informasi_jadwal_sidang')
  {

    include ('_fungsi.php');
    $tanggal=trim(base64_decode($_POST["tanggal"]));
    simpan_log('Informasi Jadwal Sidang');
    
   $sql = " SELECT 
                tanggal_sidang
                ,perkara.nomor_perkara
                ,sidang_keliling
                ,ruangan
                ,agenda
                ,jenis_perkara_nama
                FROM perkara_jadwal_sidang 
                left JOIN perkara on perkara.perkara_id=perkara_jadwal_sidang.perkara_id
                where tanggal_sidang='$tanggal'
                ORDER BY perkara.alur_perkara_id ASC, ruangan ASC, perkara_jadwal_sidang.perkara_id ASC";
            //echo $sql;
    $data["req"]=base64_encode($sql);
    if(curl($url_api,$data)=="[]"){
      echo "<p class='w3-text-red'>Data tidak ditemukan, silahkan pilih tanggal lain</p>";
      exit;
    } 
        $info_sidange= curl($url_api,$data);
        $data_sidange = json_decode($info_sidange, true);
        echo "<p>Jadwal Sidang ".format_hari_tanggal($tanggal)."</p>";
        echo "<table class='w3-table-all'><tr><td>No</td><td>Nomor Perkara<br>Jenis Perkara<br>Ruangan</td></tr>";
        $no=0;
        foreach ($data_sidange as $key => $value) {
          $no++;
          echo "<tr><td>" . $no . "</td><td>" . $value["nomor_perkara"] . "<br>" . $value["jenis_perkara_nama"] . "<br>" . $value["ruangan"] . "</td></tr>";
        }
         
      echo "</table><br><br><br><br><br>"; 
      //keuangan 
  }
//informasi perkara
//akta cerai 
  else
  if(base64_decode($aksi)=='validasi_akta_cerai')
  {
      simpan_log('Validasi Akta Cerai');
    //variabel nomor_perkara
    $nomor_perkara=trim($_POST["nomor_perkara"]);
    $nomor_perkara= preg_replace("/[^A-Za-z0-9 \/.]/","",$nomor_perkara);
    $nomor_perkara= str_replace(" ","",$nomor_perkara);
    $nomor_akta_cerai=trim($_POST["nomor_akta_cerai"]);
    
    $hari_ini=date("Y-m-d");
    $sql="SELECT perkara.para_pihak FROM perkara_akta_cerai LEFT JOIN perkara ON perkara.perkara_id=perkara_akta_cerai.perkara_id 
    WHERE nomor_perkara='$nomor_perkara' AND nomor_akta_cerai='$nomor_akta_cerai'"; //echo $sql;
    $data["req"]=base64_encode($sql);
    if(strlen(curl($url_api,$data))>=5){
      $para_pihak=curl($url_api,$data);
      $para_pihak=str_replace('[{"para_pihak":"', "",$para_pihak);
      $para_pihak=str_replace('"}]', "",$para_pihak);
      echo "<p class='w3-text-green' style='font-size:20px'>Akta Cerai Valid</p><p class='w3-text-green' >Nomor Perkara :<br>$nomor_perkara</p><p class='w3-text-green' >Nomor Akta Cerai<br>$nomor_akta_cerai</p><p class='w3-text-green' >Para Pihak<br>$para_pihak</p>";

    }else{
      echo "<h3 class='w3-text-red'>MAAF AKTA CERAI CERAI TIDAK DITEMUKAN</h3>";
    }
    exit;
  }else
  if(base64_decode($aksi)=='cek_penerbitan_akta_cerai')
  {
      simpan_log('Informasi Akta Cerai');
    //variabel nomor_perkara
    $nomor_perkara=trim($_POST["nomor_perkara"]);
    $nomor_perkara= preg_replace("/[^A-Za-z0-9 \/.]/","",$nomor_perkara);
    $nomor_perkara= str_replace(" ","",$nomor_perkara);
    
    $hari_ini=date("Y-m-d");
    $sql="SELECT nomor_akta_cerai FROM perkara_akta_cerai LEFT JOIN perkara ON perkara.perkara_id=perkara_akta_cerai.perkara_id WHERE nomor_perkara='$nomor_perkara' AND (nomor_akta_cerai<>'' OR  nomor_akta_cerai IS NOT NULL)"; //echo $sql;
    $data["req"]=base64_encode($sql);
    if(strlen(curl($url_api,$data))>=5){
      echo "<h3 class='w3-text-green'>Nomor Perkara ".$nomor_perkara."<br><br><b>AKTA CERAI SUDAH DICETAK</b></h3>";
      
      echo "<hr>
        <b>PENGAMBILAN AKTA CERAI TIDAK PERLU REPOT</b>
        <hr>
        <table style='border: none;'>
            <tr>
                <td align='left'>
                    <a href='https://pa-semarang.go.id/tahupetis/permohonan' target='_blank'><img style='display: block; margin-left: auto; margin-right: auto;' src='https://pa-semarang.go.id/images/_WEB_SRC/ANISFUADs/tahupetis.png' alt='' class='size-auto'></a>
                </td>
                <td align='left'>
                    <a href='https://pa-semarang.go.id/tahupetis/permohonan' target='_blank'><h4>TAHUPETIS</h4></a>
                </td>
                <td align='right'>
                    <a href='https://pa-semarang.go.id/tahupetis/permohonan' target='_blank'><u>Klik untuk pengajuan</u></a>
                </td>
            </tr>
            <tr>
                <td colspan='3' align='justify'>
                    <p style='font-size:75%;'>Tahu Petis adalah salah satu Inovasi Layanan di Pengadilan Agama Semarang dalam Penyerahan Produk, berupa AKTA CERAI dan Salinan Putusan/ Penetapan melalui Mobil Keliling.
                    Dan saat ini baru mencakup area terjauh yaitu Kecamatan Banyumanik dan Pedurungan. Jadi tidak harus ke Kantor Pengadilan Agama Semarang untuk mengambil Akta Cerai, cukup
                    dengan mengajukan permohonan melalui TahuPetis Anda dapat mengambil di Kantor Kecamatan terdekat dengan waktu yang sudah ditentukan.</p>                
                </td>
            </tr>
            <tr>
                <td align='left'>
                    <a href='https://pa-semarang.go.id/go-acorona/permohonan' target='_blank'><img style='display: block; margin-left: auto; margin-right: auto;' src='	https://pa-semarang.go.id/images/_WEB_SRC/ANISFUADs/goacorona.png' alt='' class='size-auto'></a>
                </td>
                <td align='left'>
                    <a href='https://pa-semarang.go.id/go-acorona/permohonan' target='_blank'><h4>GO-aCORONA</h4></a>
                </td>
                <td align='right'>
                    <a href='https://pa-semarang.go.id/go-acorona/permohonan' target='_blank'><u>Klik untuk pengajuan</u></a>
                </td>
            </tr>
            <tr>
                <td colspan='3' align='justify'>
                    <p style='font-size:75%;'>Go-aCORONA merupakan inovasi Layanan di Pengadilan Agama Semarang terkait Penyerahan Produk, berupa AKTA CERAI dan atau Salinan Putusan/Penetapan melalui GO-JEK.
                    Jadi Anda tidak perlu keluar rumah, Akta Cerai akan diantar langsung ke rumah Anda.</p>                
                </td>
            </tr>            
        </table>
        <hr>
      ";
      echo "<br>Untuk informasi lebih lanjut hubungi WA Informasi di:<br> <a href='https://wa.me/6282138722020?text=Assalamu%27alaikum.' target='_blank'><b><u>0821-3872-2020</u></b></a><br>";
    }else{
      echo "<h3 class='w3-text-red'>MAAF AKTA CERAI CERAI YANG ANDA INGINKAN BELUM ADA</h3>";
    }
    exit;
  }
//akta cerai
//antrian sidang  
  else
  if(base64_decode($aksi)=='antrian_sidang_validasi_nomor_perkara1111')
  { 

    $respon = array('status' => 'red','respons'=>'','message'=>base64_encode('Nomor Perkara Tidak Terdaftar'),'sidang'=>0 );
    $jam_sekarang=(int)date("H");
    $menit=(int)date("i");
    $respon = array();
    if($jam_sekarang<0 OR $jam_sekarang>24)
    { 
    //  $respon = array('status' => 'red','respons'=>'','message'=>base64_encode('Mohon Perhatian... <br>Waktu /P//engambilan Antrian Sidang adalah Jam 06.00 s.d 15.00 WIB'),'sidang'=>0 );
     // echo json_encode($respon);
     // exit;
    }

    //variabel nomor_perkara
    $nomor_perkara=trim(base64_decode($_POST["nomor_perkara"]));
    $nomor_perkara= preg_replace("/[^A-Za-z0-9 \/.]/","",$nomor_perkara);
    $nomor_perkara= str_replace(" ","",$nomor_perkara);
    
    $hari_ini=date("Y-m-d");
    ///edit 2021-12-02
    //$hari_ini="2021-12-02";
    $sql="SELECT nomor_perkara ,para_pihak ,perkara_id ,(SELECT count(perkara_id) FROM perkara_jadwal_sidang WHERE tanggal_sidang='$hari_ini' AND perkara_id=perkara.perkara_id LIMIT 1) AS sidang FROM perkara 
    where nomor_perkara='$nomor_perkara' ";
    //echo $sql;
    $data["req"]=base64_encode($sql);
    $hasil= json_decode(curl($url_api,$data));
    if($hasil==NULL){
        $message='Nomor Perkara '.$nomor_perkara.' tidak ada sidang hari ini';
        $status='red';
        $datane="";
        $sidang="";
        $respon = array('status' => $status,'respons'=>$datane ,'message'=>base64_encode($message),'sidang'=>$sidang);
        echo json_encode($respon);
      exit();
    }
    $datane = array();
    //$sidang=0;
    foreach ($hasil as $key => $rentangan) {
      $nomor_perkara=$rentangan->nomor_perkara;
      $para_pihak=$rentangan->para_pihak;
      $perkara_id=$rentangan->perkara_id;
      $sidang=$rentangan->sidang;
      //echo $sidang;exit();
      if($sidang==1)
      {
        $message='Ada';
        $status='green';
      }else{
        $message='Nomor Perkara '.$nomor_perkara.' tidak ada sidang hari ini';
        $status='red';
      } 

      $datane = array('perkara_id' => base64_encode($perkara_id),'nomor_perkara'=>base64_encode($nomor_perkara),'para_pihak'=>base64_encode($para_pihak) );
      $respon = array('status' => $status,'respons'=>$datane ,'message'=>base64_encode($message),'sidang'=>$sidang);

    
      }
      echo json_encode($respon);

    
    //var_dump($hasil);
     
    //echo json_encode($respon);
  }else
  if(base64_decode($aksi)=='proses_antrian_sidttang111'){
    $message='';
    $nomor_perkara=trim(base64_decode($_POST["nomor_perkara"]));
    $tekek =date("Y-m-d"); 
   ///edit 2021-12-02
   //$tekek="2021-12-02";
   
    $sql=
        "
            SELECT 
            a.id AS id_sidang
            ,a.perkara_id AS id_sipp
            ,REPLACE(REPLACE(REPLACE(REPLACE(b.nomor_perkara,  'Pdt.',''),'/20',''),'/',''),'PA.Smg','') AS no_perk
            , a.tanggal_sidang
            , a.ruangan_id
            , a.ruangan 
            , b.nomor_perkara 
            , IFNULL((SELECT nomor FROM antrian_new_2021.nomorantrian where no_perk=REPLACE(REPLACE(REPLACE(REPLACE(b.nomor_perkara,  'Pdt.',''),'/20',''),'/',''),'PA.Smg','') LIMIT 1 ),0) AS nomor_antrian
            , (SELECT nomor FROM antrian_new_2021.nomorantrian  where  ruang=a.ruangan_id ORDER BY nomor DESC LIMIT 1 ) AS nomor_terakhir
            , (SELECT count(perkara_id) FROM perkara_jadwal_sidang WHERE tanggal_sidang='$tekek' And ruangan_id=a.ruangan_id) AS jumlah_perkara
            ,(SELECT GROUP_CONCAT(nama ORDER BY  urutan ASC SEPARATOR ', ') AS nama_p FROM perkara_pihak1 WHERE perkara_id=a.perkara_id  ) AS penggugat
            ,(SELECT GROUP_CONCAT(nama ORDER BY  urutan ASC SEPARATOR ', ') AS nama_p FROM perkara_pihak2 WHERE perkara_id=a.perkara_id  ) AS tergugat
            ,(SELECT GROUP_CONCAT(alamat ORDER BY  urutan ASC SEPARATOR ', ') AS nama_p FROM perkara_pihak1 WHERE perkara_id=a.perkara_id  ) AS alamatP
            ,(SELECT GROUP_CONCAT(alamat ORDER BY  urutan ASC SEPARATOR ', ') AS nama_p FROM perkara_pihak2 WHERE perkara_id=a.perkara_id  ) AS alamatT
            FROM perkara_jadwal_sidang AS a
            LEFT JOIN perkara AS b ON b.perkara_id=a.perkara_id
            WHERE b.nomor_perkara='$nomor_perkara' AND a.tanggal_sidang='$tekek' ORDER BY a.id ASC LIMIT 1
        ";
      // echo $sql;exit();
    $data["req"]=base64_encode($sql);
    //cek tidak ada
    //$hasile= curl($url_api,$data);
    //echo $hasile;
    //cek tidak ada
    $hasil= json_decode(curl($url_api,$data));
    //var_dump($hasil);exit();
    $datane = array();
    $nomor_antrian=0;
    //$sidang=0;
    foreach ($hasil as $key => $rentangan){
      $ketemu='ya';
      $penggugat=$rentangan->penggugat;
      $tergugat=$rentangan->tergugat;
      $alamatP=$rentangan->alamatP;
      $alamatT=$rentangan->alamatT;
      $perkara_id=$rentangan->id_sipp;
      $ruangan_id=$rentangan->ruangan_id;
      $id_sidang=$rentangan->id_sidang;
      $no_perk=$rentangan->no_perk;
      $nomor_antrian=(int)$rentangan->nomor_antrian;
      $nomor_terakhir=$rentangan->nomor_terakhir;
      $ruangan=$rentangan->ruangan;
      $jumlah_perkara=$rentangan->jumlah_perkara;
      //$tanggal_sidang=$rentangan->tanggal_sidang;
      if($ruangan_id==NULL OR $ruangan_id=='' )
      {
        $respon = array('status' => 'red','respons'=>'','message'=>base64_encode('Mohon Perhatian... <br>Ruangan belum ditentukan sehingga belum bisa mengambil antrian'),'sidang'=>0 );
        echo json_encode($respon);
        exit;
      } 
      if((int)$nomor_antrian<1){
         // echo "Nomor Antrian". (int)$nomor_antrian."<br>--";
        //$antrian_kosong='';
        //echo "kosong";
        //echo "ruangan_id:".$ruangan_id."<br>";
        //echo "jumlah_perkara:".$jumlah_perkara."<br>";
        //$x=1;
       // for ($x = 1; $x <= $jumlah_perkara; $x++) {
        //  $sql="SELECT 999 AS nomor_antrian FROM  antrian AS c   WHERE c.tanggal_sidang='$tekek' AND c.nomor_antrian=$x AND ruang_id=$ruangan_id";
         // echo $sql."<br>";
         // $data["req"]=base64_encode($sql);
         // $hasil= curl($url_api,$data);
          //$hasil=str_replace('[{"nomor_antrian":"', "",$hasil);
          //$hasil=str_replace('"}', "",$hasil);
          //if($hasil=='[]'){
           // $antrian_kosong=$antrian_kosong."<button style='margin-right:3px;width:60px' onclick='pilih_nomor(".$x.",".$ruangan_id.")' class='w3-button w3-grey w3-border'><h3>".$x."</h3></button>";
          //}

        //}
        $nomor_antrian=(int)$nomor_terakhir+1;
        $saiki=date("Y-m-d H:i:s");
		$penggugat=addslashes($penggugat);
		$tergugat=addslashes($tergugat);
		$alamatP=addslashes($alamatP);
		$alamatT=addslashes($alamatT);
        $sql="INSERT INTO antrian_new_2021.nomorantrian (status, nomor, ruang, nama_ruang, no_perk, id_sipp , id_sidang, penggugat, alamatP, tergugat, alamatT, tgl_sidang,keterangan,tanggalipun) VALUES (0, $nomor_antrian, $ruangan_id, '$ruangan', '$no_perk', $perkara_id , $id_sidang, '$penggugat', '$alamatP', '$tergugat', '$alamatT', '$tekek','online LumpiaPasar','$saiki') "; 
        //echo $sql;exit;
        ///menyimpan ke log antrian sidang
        $data["req"]=base64_encode($sql); 
        $hasil= json_decode(curl($url_api,$data)); 
        //echo $hasil;exit;
        ///menyimpan ke log antrian sidang
        
        $isi=array(
            'perkara_id' =>$perkara_id
            ,'nomor_perkara' =>$nomor_perkara
            ,'nomor_antrian' =>$nomor_antrian
            ,'diinput_tanggal' =>date("Y-m-d H:i:s") );
           $tabel="antrian_sidang_data";
            $db = new Tambah_sekunder(); 
            $proses=$db->tambah_data_sekunder($tabel,$isi);
        $respon = array('status' =>"masuk"); 
        echo  json_encode($respon); 
        exit();
        $cetak='<p style="font-size: 11px;font-family:  Arial;text-align:center"> '
              .$namapa.
              '<br><span style="font-size:15px;font-family:  Arial">ANTRIAN SIDANG</span><br>
              Nomor Antrian  <br><span style="font-size: 40px;font-family:  Arial">'.$nomor_antrian.'</span><br> <span style="font-size: 20px;font-family:  Arial"><b>'. $ruangan.'</b>
              
              <br><b>dari '.$jumlah_perkara.' Perkara</b></span><br>Nomor Perkara <br><span style="font-size: 16px;font-family:  Arial"> <b>'. $nomor_perkara .'</b></span>
              <br><span style="font-size: 10px;font-family:  Arial">Terima Kasih Anda Telah Menunggu<br>Dicetak pada <b>'.date("d/m/Y H:i:s").' WIB</b></span></p>
              <hr><div class="w3-row w3-teal"><center><b>Silahkan Screenshot Halaman ini, sebagai pengganti Kertas Antrian</b></center </div><br>
              ' ;
        $respon = array('status' => $ketemu,'respons'=>base64_encode($cetak),'message'=>base64_encode($message) );
        //echo json_encode($respon);  
        exit();
      }else{
        $cetak='<p style="font-size: 11px;font-family:  Arial;text-align:center"> '
              .$namapa.
              '<br><span style="font-size:15px;font-family:  Arial">ANTRIAN SIDANG</span><br>
              Nomor Antrian  <br><span style="font-size: 40px;font-family:  Arial">'.$nomor_antrian.'</span><br> <span style="font-size: 20px;font-family:  Arial"><b>'. $ruangan.'</b>
              
              <br><b>dari '.$jumlah_perkara.' Perkara</b></span><br>Nomor Perkara <br><span style="font-size: 16px;font-family:  Arial"> <b>'. $nomor_perkara .'</b></span>
              <br><span style="font-size: 10px;font-family:  Arial">Terima Kasih Anda Telah Menunggu<br>Dicetak pada <b>'.date("d/m/Y H:i:s").' WIB</b></span></p>
              <hr><div class="w3-row w3-teal"><center><b>Silahkan Screenshot Halaman ini, sebagai pengganti Kertas Antrian</b></center></div><br>
             <div class="w3-row w3-green"><center>Untuk memastikan antrian sudah terinput disistem silahkan <a href="informasi_persidangan"><b>KLIK DISINI</b></a></center></div><br>
              ' ;
        $respon = array('status' => $ketemu,'respons'=>base64_encode($cetak),'message'=>base64_encode($message) );
        echo json_encode($respon);      
        exit;
      }
    }

  }else
  if(base64_decode($aksi)=='proses_pilih_nttttomor_antrian_sidang'){
    $perkara_id=trim(base64_decode($_POST["perkara_id"]));
    $ruang_id=trim(base64_decode($_POST["ruang_id"]));
    $nomor_antrian=trim(base64_decode($_POST["nomor_antrian"]));
    $nomor_perkara=trim(base64_decode($_POST["nomor_perkara"]));
    $jumlah_perkara=trim(base64_decode($_POST["jumlah_perkara"]));
    $ruangan=trim(base64_decode($_POST["ruangan"]));
    $tekek =date("Y-m-d"); 
    $sql="SELECT 'ada' AS nomor_antrian FROM antrian WHERE nomor_antrian=$nomor_antrian AND ruang_id=$ruang_id AND tanggal_sidang='$tekek'"; 
    //echo $sql;exit();
    $data["req"]=base64_encode($sql); 
    
    $hasil= curl($url_api,$data);
    $hasil=str_replace('[{"nomor_antrian":"', "",$hasil);
    $hasil=str_replace('"}', "",$hasil);
    //echo $hasil;exit();
    if($hasil=='[]'){
      $sql="INSERT INTO antrian(nomor_antrian, tanggal_sidang,  ruang_id,  perkara_id,  status,  urutan) VALUES ('$nomor_antrian', '$tekek',  '$ruang_id',  '$perkara_id', 0 ,  '$nomor_antrian') "; 
      $data["req"]=base64_encode($sql); 
      $hasil= json_decode(curl($url_api,$data));
       $cetak='<p style="font-size: 11px;font-family:  Arial;text-align:center"> '
              .$namapa.
              '<br><span style="font-size:15px;font-family:  Arial">ANTRIAN SIDANG</span><br>
              Nomor Antrian  <br><span style="font-size: 40px;font-family:  Arial">'.$nomor_antrian.'</span><br> <span style="font-size: 20px;font-family:  Arial"><b>'. $ruangan.'</b>
              
              <br><b>dari '.$jumlah_perkara.' Perkara</b></span><br>Nomor Perkara <br><span style="font-size: 16px;font-family:  Arial"> <b>'. $nomor_perkara .'</b></span>
              <br><span style="font-size: 10px;font-family:  Arial">Terima Kasih Anda Telah Menunggu<br>Dicetak pada <b>'.date("d/m/Y H:i:s").' WIB</b></span></p>
              <hr><div class="w3-row w3-teal"><center><b>Silahkan Screenshot Halaman ini, sebagai pengganti Kertas Antrian</b></center><br><br>Untuk memastikan antrian sudah terinput disistem silahkan <a href="informasi_persidangan">Klik disini</a></div><br>
              ' ;
        $respon = array('status' => $ketemu,'respons'=>base64_encode($cetak),'message'=>base64_encode($message) );
    }else
    {
       $respon = array('status' =>"ada",'respons'=>"",'message'=>'');
    }
    echo json_encode($respon);      
    exit;
      
    

  }
//antrian sidang

//kondisi sidang
  else
  if(base64_decode($aksi)=='info_persidangan_sipp'){
    $tekek=date("Y-m-d");
   $sql = "  select ruangan, count(perkara_id) jumlah_sidang
      ,(SELECT count(id) FROM antrian WHERE status=0 AND tanggal_sidang=perkara_jadwal_sidang.tanggal_sidang AND ruang_id=perkara_jadwal_sidang.ruangan_id) AS jumlah_aktif
      ,(SELECT count(id) FROM antrian WHERE status=1 AND tanggal_sidang=perkara_jadwal_sidang.tanggal_sidang AND ruang_id=perkara_jadwal_sidang.ruangan_id) AS jumlah_di_skrores
      ,(SELECT count(id) FROM antrian WHERE status=2 AND tanggal_sidang=perkara_jadwal_sidang.tanggal_sidang AND ruang_id=perkara_jadwal_sidang.ruangan_id) AS jumlah_selesai
      ,(SELECT CONCAT('Nomor Antrian ',nomor_antrian) FROM antrian WHERE status=0 AND tanggal_sidang=perkara_jadwal_sidang.tanggal_sidang AND ruang_id=perkara_jadwal_sidang.ruangan_id ORDER BY nomor_antrian ASC LIMIT 1) AS antrian_aktif
      FROM perkara_jadwal_sidang
      WHERE tanggal_sidang='$tekek'
      GROUP BY ruangan_id "; 

      //echo $sql;
      //exit;
      $data["req"]=base64_encode($sql);
      $hasil= curl($url_api,$data);
      $obj = json_decode($hasil);
      
      echo $hasil;
}
//kondisi sidang 
  else
  if(base64_decode($aksi)=='info_persidangan'){
    simpan_log('Informasi Persidangan');  
      
      
    $tekek=date("Y-m-d");
   $sql = "SELECT a.nama_ruang, GROUP_CONCAT(CONCAT(a.nomor,'. ',REPLACE(b.nomor_perkara,  '/PA.Smg',''))ORDER BY a.nomor SEPARATOR '<br>') AS kete from antrian_new_2021.nomorantrian AS a left join perkara AS b on b.perkara_id=a.id_sipp 



GROUP BY a.ruang 
ORDER BY a.ruang ASC, a.nomor ASC "; 

      //echo $sql;
      //exit;
      $data["req"]=base64_encode($sql);
      $hasil= curl($url_api,$data);
      $obj = json_decode($hasil);
      
      echo $hasil;
}
//kondisi sidang 


//antrian ac  
  else
  if(base64_decode($aksi)=='info_akta_cerai'){
      simpan_log('Informasi Akta Cerai');
      
    $nomor_perkara = base64_decode(trim($_POST["nomor_perkara"]));
      $nomor_perkara = preg_replace("/[^A-Za-z0-9 \/.]/", "", $nomor_perkara);
      $nomor_perkara = str_replace(" ", "", $nomor_perkara);
       $sql = "SELECT
                perkara.nomor_perkara as nomor_perkara
                ,perkara_pihak1.nama as pihak1_text
                ,perkara_pihak2.nama as pihak2_text  
                ,perkara_akta_cerai.nomor_akta_cerai as nomor_akta_cerai 
                FROM perkara_akta_cerai
                LEFT JOIN  perkara  ON perkara.perkara_id=perkara_akta_cerai.perkara_id
                LEFT JOIN  perkara_pihak1  ON perkara_pihak1.perkara_id=perkara_akta_cerai.perkara_id 
                LEFT JOIN  perkara_pihak2  ON perkara_pihak2.perkara_id=perkara_akta_cerai.perkara_id 

                WHERE perkara_akta_cerai.nomor_akta_cerai IS NOT NULL and perkara.nomor_perkara = '$nomor_perkara'"; 

      //echo $sql;
      //exit;
      $data["req"]=base64_encode($sql);
      $hasil= curl($url_api,$data);
      $hasil=str_replace("[", "",$hasil);
      $hasil=str_replace("]", "",$hasil);
      echo $hasil;
  }
  else
  if(base64_decode($aksi)=='cek_antrian_aktacerai')
  {
    $nomor_perkara=trim(base64_decode($_POST["nomor_perkara"]));
    $nomor_akta_cerai=trim(base64_decode($_POST["nomor_akta_cerai"]));
    $pihak=trim(base64_decode($_POST["pihak"]));
    $nama=trim(base64_decode($_POST["nama"]));
    $tanggal=trim(base64_decode($_POST["tanggal"]));
    $nomor_wa=trim(base64_decode($_POST["nomor_wa"]));
    $tekek =date("Y-m-d"); 

    if($pihak=="p"){
      $kolomnya="tgl_penyerahan_akta_cerai";
    }else{
      $kolomnya="tgl_penyerahan_akta_cerai_pihak2";
    }
    $sql="SELECT $kolomnya as tanggale FROM perkara_akta_cerai  WHERE nomor_akta_cerai='$nomor_akta_cerai'";
    //echo $sql;exit;
    $data["req"]=base64_encode($sql);
    //cek tidak ada
    $hasile= json_decode(curl($url_api,$data));
    //echo $hasile->tanggale;exit;
    //cek tidak ada
    //$hasile= curl($url_api,$data);
    //$hasile=str_replace('{"tanggale":"', "",$hasile);
    //$hasile=str_replace('"}', "",$hasile);
    //echo $hasile;exit();
    if(strlen($hasile->tanggale)<=3 OR $hasile->tanggale=="0000-00-00"){ 
        $antrian_kosong='';
        //echo "kosong";exit;
        //echo "ruangan_id:".$ruangan_id."<br>";
        //echo "jumlah_perkara:".$jumlah_perkara."<br>";
        
          $sql="SELECT a.nomor_akta_cerai AS nomor_antrian,a.tanggal FROM ptsl.antrian_ac AS a WHERE a.nomor_akta_cerai='$nomor_akta_cerai' AND a.pihak='$pihak' AND a.tanggal='$tanggal' limit 1";
         // echo $sql."<br>";exit;
          $data["req"]=base64_encode($sql);
          $hasil= json_decode(curl($url_api,$data));
          //var_dump($hasil);exit();
          //$hasil=str_replace('{"nomor_antrian":"', "",$hasil);
          //$hasil=str_replace('"}', "",$hasil);
         // echo $x."-".$hasil."<br>";
          //echo ;exit();
          $ketemu="ya";
          if($hasil[0]->nomor_antrian==""){
              $sql="INSERT INTO ptsl.antrian_ac(nomor_akta_cerai,nomor_perkara,pihak,nama,tanggal, nomor_wa, diinput_tanggal) VALUES ('$nomor_akta_cerai','$nomor_perkara','$pihak','$nama','$tanggal','$nomor_wa',now())";
              $data["req"]=base64_encode($sql);
              $hasil=curl($url_api,$data);
              //echo $sql;
          }else{
            $tanggal=$hasil[0]->tanggal;
          }
        
         $cetak='<p style="font-size: 13px;font-family:  Arial;text-align:center"> '
              .$namapa.
              '<br><span style="font-family:  Arial">ANTRIAN ONLINE<br>PRODUK PENGADILAN<br> Nomor Perkara <br> <b>'. $nomor_perkara .'</b><br>Atas Nama <br><b>'.$nama.'</b><br>Nomor WA <br><b>'.$nomor_wa.'</b><br>Tanggal Pengambilan :<br><b>'.tanggal_indonesia($tanggal).'</b>
              <br>Terima Kasih<br>Dicetak pada <b>'.date("d/m/Y H:i:s").' WIB</b></span></p>
              <hr><div class="w3-row w3-teal"><center><b>Silahkan Screenshot Halaman ini, sebagai pengganti Kertas Antrian</b><br>Pengambilan silahkan datang langsung ke '.$namapa.' pada jam kerja </center></div><br>
              ' ;
          $respon = array('status' => $ketemu,'respons'=>base64_encode($cetak),'message'=>base64_encode($message) );
          echo json_encode($respon);  
          exit();
        
      }else{
        $ketemu="red";
        $hasile=str_replace("[", "",$hasile);
        $hasile=str_replace("]", "",$hasile);
        $cetak='<p style="color:red;font-size: 25px;font-family:  Arial;text-align:center">INFORMASI AKTA CERAI<br><br>Nomor Perkara : '.$nomor_perkara.', <br><br>atas nama : '.$nama.'<br><br><b>SUDAH DIAMBIL  pada'.$hasile.'</b><br><br>INFORMASI HUBUNGI '.$namapa.'</p>' ;
        $respon = array('status' => $ketemu,'respons'=>base64_encode($cetak),'message'=>base64_encode($message) );
        echo json_encode($respon);      
        exit;
      } 
  }
  else
  if(base64_decode($aksi)=='cek_antrian_aktacerai_bc')
  {
    $nomor_perkara=trim(base64_decode($_POST["nomor_perkara"]));
    $nomor_akta_cerai=trim(base64_decode($_POST["nomor_perkara"]));
    $pihak=trim(base64_decode($_POST["pihak"]));
    $nama=trim(base64_decode($_POST["nama"]));
    $tanggal=trim(base64_decode($_POST["tanggal"]));
    $tekek =date("Y-m-d"); 
    $sql="SELECT a.data_loket_urutan as nomor_antrian FROM ptsl.data_loket AS a WHERE a.data_loket_jenis=14 AND a.data_loket_keperluan='Akta Cerai'AND a.data_loket_pihak='$pihak' AND a.data_loket_nomor_perkara='$nomor_perkara'AND LEFT(a.data_loket_antri,10)='$tanggal'";
    // echo $sql;exit;
    $data["req"]=base64_encode($sql);
    //cek tidak ada
    //$hasile= curl($url_api,$data);
    //echo $hasile;
    //cek tidak ada
    $hasile= curl($url_api,$data);
    $hasile=str_replace('{"nomor_antrian":"', "",$hasile);
    $hasile=str_replace('"}', "",$hasile);
    //echo $hasil;exit();
    if($hasile=='[]'){ 
        $antrian_kosong='';
        //echo "kosong";
        //echo "ruangan_id:".$ruangan_id."<br>";
        //echo "jumlah_perkara:".$jumlah_perkara."<br>";
        $jumlah_antri=50;
        for ($x = 1; $x <= 50; $x++) {
          $sql="SELECT 999 AS nomor_antrian FROM ptsl.data_loket AS a WHERE a.data_loket_jenis=14 AND a.data_loket_keperluan='Akta Cerai' AND a.data_loket_urutan=$x   AND LEFT(a.data_loket_antri,10)='$tanggal'";
          //echo $sql."<br>";exit;
          $data["req"]=base64_encode($sql);
          $hasil= curl($url_api,$data);
          $hasil=str_replace('{"nomor_antrian":"', "",$hasil);
          $hasil=str_replace('"}', "",$hasil);
         // echo $x."-".$hasil."<br>";
          if($hasil=='[]'){
            $antrian_kosong=$antrian_kosong."<button style='margin-right:3px;width:60px' onclick='pilih_nomor_ac(".$x.")' class='w3-button w3-grey w3-border'><h3>".$x."</h3></button>";
          }
        }
          $cetak="Silahkan Pilih Nomor Antrian Pengambilan : <hr>".$antrian_kosong."<hr><span id='ket'></span>Nomor Perkara : <b>".$nomor_perkara."</b><br><input id='nomor_antrian_dipilih' type='hidden'><input id='nomor_akta_cerai' value='".base64_encode($nomor_akta_cerai)."' type='hidden'><input id='tanggal' value='".base64_encode($tanggal)."' type='hidden'><input id='nomor_perkara' value='".base64_encode($nomor_perkara)."' type='hidden'><input id='pihak' value='".base64_encode($pihak)."' type='hidden'><input id='nama' value='".base64_encode($nama)."' type='hidden'>Nomor antrian dipilih :<b> <span id='nomor_antrian_dipilih_tampil'>Belum dipilih</span></b><br><center><a class='w3-btn w3-green' href='#' onclick='proses_pilih_antri_ac()'>Antri</a></center>";
          $respon = array('status' => $ketemu,'respons'=>base64_encode($cetak),'message'=>base64_encode($message) );
          echo json_encode($respon);  
          exit();
        
      }else{
        $ketemu="ya";
        $hasile=str_replace("[", "",$hasile);
        $hasile=str_replace("]", "",$hasile);
        $cetak='<p style="font-size: 11px;font-family:  Arial;text-align:center"> '
              .$namapa.
              '<br><span style="font-size:15px;font-family:  Arial">ANTRIAN PRODUK PENGADILAN</span><br>
              Nomor Antrian  <br><span style="font-size: 40px;font-family:  Arial">C '.$hasile.'</span><br> Nomor Perkara <br><span style="font-size: 16px;font-family:  Arial"> <b>'. $nomor_perkara .'</b></span>
              <br><span style="font-size: 10px;font-family:  Arial">Terima Kasih Anda Telah Menunggu<br>Tanggal Pengambilan : '.$tanggal.'<br>Dicetak pada <b>'.date("d/m/Y H:i:s").' WIB</b></span></p>
              <hr><div class="w3-row w3-teal"><center><b>Silahkan Screenshot Halaman ini, sebagai pengganti Kertas Antrian</b></center></div><br>
              ' ;
        $respon = array('status' => $ketemu,'respons'=>base64_encode($cetak),'message'=>base64_encode($message) );
        echo json_encode($respon);      
        exit;
      } 
  }
  else
  if(base64_decode($aksi)=='inputkan_antrian_aktacerai')
  {
    $nomor_perkara=trim(base64_decode($_POST["nomor_perkara"]));
    $nomor_akta_cerai=trim(base64_decode($_POST["nomor_akta_cerai"]));
    $pihak=trim(base64_decode($_POST["pihak"]));
    $nama=trim(base64_decode($_POST["nama"]));
    $tanggal=trim(base64_decode($_POST["tanggal"]));
    $nomor_antrian_dipilih=trim(base64_decode($_POST["nomor_antrian_dipilih"]));
    $tekek =date("Y-m-d"); 
    $sql="SELECT a.data_loket_urutan as nomor_antrian FROM ptsl.data_loket AS a WHERE a.data_loket_jenis=14 AND a.data_loket_urutan=$nomor_antrian_dipilih  AND LEFT(a.data_loket_antri,10)='$tanggal'";
    //echo $sql;exit;
    $data["req"]=base64_encode($sql);
    //cek tidak ada
    //$hasile= curl($url_api,$data);
    //echo $hasile;
    //cek tidak ada
    $hasile= curl($url_api,$data);
    $hasile=str_replace('{"nomor_antrian":"', "",$hasile);
    $hasile=str_replace('"}', "",$hasile);
    //echo $hasil;exit();
    if($hasile=='[]'){ 
         
        $ketemu="ya";
        $sql="INSERT INTO ptsl.data_loket(data_loket_jenis,data_loket_antri,data_loket_nomor_perkara,data_loket_keperluan,data_loket_nama,data_loket_pihak,data_loket_urutan,data_loket_nomor_akta_cerai,data_loket_kode,data_loket_nama_loket) VALUES (14,'$tanggal','$nomor_perkara','Akta Cerai','$nama','$pihak','$nomor_antrian_dipilih','$nomor_akta_cerai','C','Produk Pengadilan')";
        $data["req"]=base64_encode($sql);
        $hasil=curl($url_api,$data);
        $cetak='<p style="font-size: 11px;font-family:  Arial;text-align:center"> '
              .$namapa.
              '<br><span style="font-size:15px;font-family:  Arial">ANTRIAN PRODUK PENGADILAN</span><br>
              Nomor Antrian  <br><span style="font-size: 40px;font-family:  Arial">C '.$nomor_antrian_dipilih.'</span><br> Nomor Perkara <br><span style="font-size: 16px;font-family:  Arial"> <b>'. $nomor_perkara .'</b></span>
              <br><span style="font-size: 10px;font-family:  Arial">Terima Kasih Anda Telah Menunggu<br>Tanggal Pengambilan : <b><span style="font-size: 16px;font-family:  Arial">'.substr($tanggal,8,2)."/".substr($tanggal,5,2)."/".substr($tanggal,0,4).'</span></b><br>Dicetak pada <b>'.date("d/m/Y H:i:s").' WIB</b></span></p>
              <hr><div class="w3-row w3-teal"><center><b>Silahkan Screenshot Halaman ini, sebagai pengganti Kertas Antrian</b></center></div><br>
              ' ;
        $respon = array('status' => $ketemu,'respons'=>base64_encode($cetak),'message'=>base64_encode($message) );
        echo json_encode($respon);      
        exit;
        
      } 


  }
//antrian ac
//lainnya  
  else
  {
   echo "Access Forbidden";
  }
//lainnya   
?>