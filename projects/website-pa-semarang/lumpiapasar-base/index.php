<?php
$wa_informasi = '0821-3872-2020';
$wa_pengaduan = '0821-3872-1682';
$useragent=$_SERVER['HTTP_USER_AGENT']; 
if(preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){ 
   //jika menggunakan browser versi mobile maka alihkan ke file web versi mobile anda 
    $ok=1;
}else{ 
    // exit;
     $ok=1;
}
include('_sys_header.php');
$menu_aktif="informasi";

function isDomainAvailible($domain){
  if(!filter_var($domain, FILTER_VALIDATE_URL)){
    return false;
  }

  $curlInit = curl_init($domain);
  curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
  curl_setopt($curlInit,CURLOPT_HEADER,true);
  curl_setopt($curlInit,CURLOPT_NOBODY,true);
  curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

  $response = curl_exec($curlInit);

  curl_close($curlInit);

  if ($response) return true;

  return false;
}
 function get_client_ip()
 {
      $ipaddress = '';
      if (getenv('HTTP_CLIENT_IP'))
          $ipaddress = getenv('HTTP_CLIENT_IP');
      else if(getenv('HTTP_X_FORWARDED_FOR'))
          $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
      else if(getenv('HTTP_X_FORWARDED'))
          $ipaddress = getenv('HTTP_X_FORWARDED');
      else if(getenv('HTTP_FORWARDED_FOR'))
          $ipaddress = getenv('HTTP_FORWARDED_FOR');
      else if(getenv('HTTP_FORWARDED'))
          $ipaddress = getenv('HTTP_FORWARDED');
      else if(getenv('REMOTE_ADDR'))
          $ipaddress = getenv('REMOTE_ADDR');
      else
          $ipaddress = 'UNKNOWN';

      return $ipaddress;
 }
 $ip_jlm="124.158.186.170";
 
//  $ip_pa="202.145.13.125";
 $ip_pa="124.158.186.170";
 $ip=get_client_ip();

//<a href="http://'.$ip_pa.'/lumpiapasar/beranda&sumber='.base64_encode(date("Y-m-d")).'">

// if((isDomainAvailible("http://$ip_pa/lumpiapasar/") AND substr($ip,0,10)<> $ip_jlm) OR $ip==$ip_pa){
  $tombole='
      <li class="w3-padding-16">
         <a href="https://antrian.pa-semarang.go.id">
          
           <img src="assets/images/card_004_antrian.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
           <span class="w3-large">ANTRIAN SIDANG </span><br>
           <span>Antrian persidangan secara online, dilaksanakan pada hari persidangan</span>
         </a>
      </li>';
// }else{
//     $tombole="";
// }
?>  
<link rel="stylesheet" type="text/css" href="assets/plugin/simpleSlider/dist/simpleSlider.min.css" />

<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header --> 
<div class="w3-row w3-padding w3-border" style="margin: auto;">
 <!-- Slider -->
  <div class="simple-slider page-slider">
    <div class="slider-wrapper">
      <?php
        $sql="SELECT *  FROM dt_slide ORDER BY urutan ASC";
         //echo $sql;
        $db = new Tampil_sekunder(); 
        $arrayData = $db->tampil_data_sekunder($sql); 
        $no=0;
        if (count($arrayData)){ 
          foreach ($arrayData as $data){ 
            foreach($data as $key=>$value) {$$key=$value;}
            echo "<div class='slider-slide' style='margin: auto; text-align: center;'><span class='slider-number'><img src='assets/banner/$image' style='width: 100%;'></span></div>";
          }
        }
      ?>

    </div>

 
  </div>
<script src="assets/plugin/simpleSlider/dist/simpleSlider.min.js" type="text/javascript" charset="utf-8"></script>
<script>
  new SimpleSlider('.page-slider', {
    autoplay: true,
    delay: 5000
  });
</script>
  <!-- /Slider -->

</div> 
<!-- Header -->
<br>
<!-- Isi -->

<div class="w3-row" style="display: none;">
    <div class="w3-container w3-white">
        <div class="w3-card w3-yellow w3-round">
            <div class="w3-container">
                <h1>Perhatian!</h1>
                Jika Anda tidak bisa mengambil antrian sidang online, Silahkan ikuti cara berikut:<br>
                <ul>
                    <li>Buka Pengaturan.</li>
                    <li>Gulir ke bawah dan pilih Aplikasi.</li>
                    <li>Ketuk opsi Lihat semua di bagian bawah.</li>
                    <li>Ketuk pilih aplikasi LumpiaPasar.</li>
                    <li>Ketuk Penyimpanan dan cache.</li>
                    <li>Pilih Hapus cache.</li>
                    <li>Ketuk Hapus penyimpanan untuk menghapus data dan cache.</li>
                </ul>
                Sekian dan Terimakasih.<br><br>
            </div>
        </div>
    </div>
</div>
<br>
<div class="w3-row">
  <div class="w3-container w3-white" id="informasi_perkara_inputan"> 
  
    <ul class="w3-ul w3-card-2 w3-round-large">
      <li class="w3-padding-16">
        <a href="informasi.php">
          <img src="assets/images/card_004_transaction.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
          <span class="w3-large">INFORMASI PERKARA</span><br>
          <span>Menampilkan informasi sebuah perkara berdasarkan nomor perkara</span>
        </a>
      </li><li class="w3-padding-16">
        <a href="panjar/index.php">
          <img src="assets/images/card_004_panjar.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
          <span class="w3-large">BIAYA PERKARA</span><br>
          <span>Perhitungan estimasi panjar biaya perkara</span>
        </a>
      </li>
      <?php
        echo $tombole;
      ?>
      <li class="w3-padding-16">
        <a href="informasi_persidangan.php">
          <img src="assets/images/card_004_usage.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
          <span class="w3-large">INFORMASI PERSIDANGAN</span><br>
          <span>Informasi jumlah persidangan, antrian berdasarkan Ruang Sidang</span>
        </a>
      </li>
<!--       <li class="w3-padding-16">
        <a href="https://antrian.pa-semarang.go.id/tv_media_online">
          <img src="assets/images/card_004_val_ac.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
          <span class="w3-large">ANTRIAN SIDANG BERJALAN</span><br>
          <span>Informasi antrian persidangan yang sedang berlangsung</span>
        </a>
      </li>   -->    
      <li class="w3-padding-16">
        <a href="akta_cerai.php">
          <img src="assets/images/card_004_ac.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">
          <span class="w3-large">AKTA CERAI</span><br>
          <span>Informasi dan Validasi Akta Cerai</span>
        </a>
      </li>
     
      
      <!--<li class="w3-padding-16">-->
      <!--  <a href="http://landipa.online/home/satker/400911">-->
      <!--    <img src="assets/images/lan_dipa.svg" class="w3-left w3-circle w3-margin-right" style="width:50px">-->
      <!--    <span class="w3-large">LANDIPA</span><br>-->
      <!--    <span>Layanan Digital Pengadilan Agama Jawa tengah</span>-->
          
      <!--  </a>-->
      <!--</li>-->
      <br>
    </ul>
    <br>
    <ul class="w3-ul w3-card-2 w3-round-large">
        <div align="center">
          <br>
          <p align="center"><b><h3>Aplikasi Layanan lainnya</h3></b></p>
        </div>
      <li class="w3-padding-16">
        <a href="https://tahupetis.pa-semarang.go.id" target="_blank">
          <img src="https://pa-semarang.go.id/images/_WEB_SRC/ANISFUADs/tahupetis.png" class="w3-left w3-circle w3-margin-right" style="width:50px">
          <span class="w3-large">TahuPetis</span><br>
          <span>Pengambilan Akta Cerai COD</span>
        </a>
      </li> 
      <li class="w3-padding-16">
        <a href="https://pacarbaru.pa-semarang.go.id" target="_blank">
          <img src="https://pa-semarang.go.id/images/_WEB_SRC/ANISFUADs/pacarbaru.png" class="w3-left w3-circle w3-margin-right" style="width:50px">
          <span class="w3-large">PacarBaru</span><br>
          <span>Perubahan KTP/KK setelah Perceraian</span>
        </a>
      </li>       
    </ul>
    <div align="center">
        <br>

        <a href="https://play.google.com/store/apps/details?id=id.lumpiapasarbaru&pcampaignid=web_share" rel="external" ><img src="assets/images/getitonplaystore.png" class="img-rounded" alt="Playstore" width="120"></a>
        <br><br>
        <span align='center'>Kunjungi website kami untuk informasi lebih lanjut di<br><b><u><a href='https://pa-semarang.go.id'>https://pa-semarang.go.id</a></u></b></span>
        <br><br>
        <a href="https://wa.me/6282138722020?text=Assalamu%27alaikum." target="_blank">
            <img src="assets/images/SINOFITA_kecil.png" width="90px"><br>
        </a>
        <p>
            Layanan WA Informasi, Konsultasi & Pengaduan: <i class="fa fa-whatsapp"><b> <a href="https://wa.me/6282138722020?text=Assalamu%27alaikum." target="_blank"><?php echo $wa_informasi;?></a></b></i><br>
        </p>
        <hr>
        <p align="center"> Pengadilan Agama Semarang © 2021</p>
        <!--<br>-->
        <br>
    </div>
  </div>
</div>




    <!-- Bottom Bar Fixed - Tidak bisa digeser -->
    <div class="w3-bottom w3-bar w3-card w3-black" style="z-index: 9999;">
        <div class="w3-bar-item w3-padding">
            <div class="w3-center">
                <small>Pengadilan Agama Semarang © 2021</small>
            </div>
        </div>
    </div>

<script>
  document.getElementById("loader").style.display="none";
  function goBack(){
    window.history.back()
  }
</script>
</body> 
</html>