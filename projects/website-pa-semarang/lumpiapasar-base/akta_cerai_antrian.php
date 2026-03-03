<?php
  include('_sys_header.php');
  $menu_aktif="antri";

function getUserIpAddr(){
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return trim(substr($ip,0,50));
}
?>  
<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header -->
<div class="w3-top w3-row w3-card-2 w3-padding w3-center w3-white">
  <div class="w3-col" style="width:30px">
    <a href="#" onclick="goBack()" class="headerButton goBack">
      <img src="assets/images/arrow_back-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:30px">
    </a>
  </div>
  <div class="w3-rest w3-cell-middle"><span style="font-size: 18px">ANTRIAN AKTA CERAI</span></div>
</div> 
<!-- Header -->
<br>
<br>
<br>
<!-- Isi -->
<div class="w3-row">
    <div class="w3-container" id="antrian_ac_inputan"> 
     <div class="w3-container w3-section w3-green w3-card-8">
            <!--  <span onclick="this.parentElement.style.display='none'" class="w3-closebtn">×</span> -->
            <p>Antrian Akta Cerai Online memberikan layanan antrian Pengambilan Akta Cerai  dan para pihak bisa memilih tanggal pengambilan</p>
            <p>Silahkan isikan <b>Nomor Perkara</b> dan pilih <b>tahun perkara</b> </p>
      </div>
    <div class="w3-row">
      <input id="os" type="hidden">
              <input id="ip" value="<?php echo getUserIpAddr()?>"  type="hidden">
              <script type="text/javascript">
                function getOS() {
                  var userAgent = window.navigator.userAgent,
                  platform = window.navigator.platform,
                  macosPlatforms = ['Macintosh', 'MacIntel', 'MacPPC', 'Mac68K'],
                  windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'],
                  iosPlatforms = ['iPhone', 'iPad', 'iPod'],
                  os = null;

                  if (macosPlatforms.indexOf(platform) !== -1) {
                    os = 'Mac OS';
                  } else if (iosPlatforms.indexOf(platform) !== -1) {
                    os = 'iOS';
                  } else if (windowsPlatforms.indexOf(platform) !== -1) {
                    os = 'Windows';
                  } else if (/Android/.test(userAgent)) {
                    os = 'Android';
                  } else if (!os && /Linux/.test(platform)) {
                    os = 'Linux';
                  }

                  return os;
                }
                document.getElementById("os").value= getOS(); 
              </script>
            <table style="width: 300px" align="center" cellpadding="3">
              <tr>
                <td><input id="nomor_perkara_antrian_aktacerai" type="number" style="padding:8px 2px;width: 70px" maxlength="5" autocomplete="off"></td>
                <td>/</td>
                <td>
                  <select id="kode_perkara" style="padding:8px 2px;">
                    <option>Pdt.G</option>
                  </select>
                </td>
                <td>/</td>
                <td>
                  <select name="tahun" id="tahun"  style="padding:8px 2px;">  
                    <?php $thn1=date("Y"); $thn2=2015; 
                    for ($i = $thn1; $i >= $thn2; $i=$i-1)
                      {  ?> <option value="<?php echo $i; ?>" <?php if(@$tahun==$i) {echo "selected";} ?>><?php echo $i; ?></option>'; 
                    <?php }  
                    ?>
                  </select>
                </td>
                <td>/</td>
                <td><?php echo $KodePN?></td>
              </tr>
            </table>
             
            <span class="w3-row w3-center" id="antrian_aktacerai_loading" style="display: none;"></span>
            <br>
            <?php
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
            ?> 
            
            <?php  
              // Menampilkan status website
             // if(isDomainAvailible($url)){
              ?>
                 <center><button class="w3-btn w3-green w3-card" id="antrian_aktacerai_tombol_cek" style="width: 250px">Cek</button></center>
            <?php 
            //  }
            ?>
           
          </div>
          <hr> 
          <p> </p>
        </div>

        <div class="w3-container" id="antrian_aktacerai_warning" style="display: none">
          <hr>
            <center><button class="w3-red w3-center w3-btn w3-ripple" onclick="antrian_aktacerai_awal()"> <i class="fa fa-arrow-left"> </i> Kembali </button></center>
          <hr>
        </div> 
        <div id="antrian_aktacerai_hasil" class="w3-container"></div>
  </div>
</div>

<div id="informasi_perkara_hasil" class="w3-container"></div>
<!-- * Isi -->
<script type="text/javascript">
      document.getElementById("nomor_perkara_antrian_aktacerai").focus();
      function antrian_aktacerai_awal(){
        document.getElementById("nomor_perkara_antrian_aktacerai").value="";
        document.getElementById("antrian_ac_inputan").style.display="block";
        document.getElementById("antrian_aktacerai_loading").style.display="none";
        document.getElementById("antrian_aktacerai_warning").style.display="none";
        document.getElementById("antrian_aktacerai_hasil").innerHTML="";
        document.getElementById('nomor_perkara_antrian_aktacerai').focus(); 
      }

        
      antrian_aktacerai_modal = document.getElementById('antrian_aktacerai_modal'); 
      nomor_perkara_antrian_aktacerai = document.getElementById('nomor_perkara_antrian_aktacerai'); 
      function antrian_aktacerai_aksi(){
        antrian_aktacerai_awal();
        antrian_aktacerai_modal.style.display="block";
        document.getElementById('nomor_perkara_antrian_aktacerai').focus(); 
      }
      function antrian_aktacerai_aksi_tutup(){
        antrian_aktacerai_modal.style.display="none";
      }
      antrian_aktacerai_tombol_cek=document.getElementById("antrian_aktacerai_tombol_cek");
      antrian_aktacerai_tombol_cek.addEventListener('click', function(ev){
        antrian_aktacerai_cek();
        ev.preventDefault();
      }, false);
      function antrian_aktacerai_cek(){
        var a=document.getElementById("nomor_perkara_antrian_aktacerai").value;
        var os=document.getElementById("os").value;
        var ip=document.getElementById("ip").value;
        var kode_perkara=document.getElementById("kode_perkara").value;
        var tahun=document.getElementById("tahun").value;
        var nomor_perkara_antrian_aktacerai=a+'/'+kode_perkara+'/'+tahun+'/<?php echo $KodePN?>';
        document.getElementById("antrian_aktacerai_loading").style.display="inline";
        document.getElementById("antrian_aktacerai_loading").innerHTML="<h3 class=w3-text-red>Silahkan Tunggu...</h3>";
        document.getElementById("loader").style.display="block";
        var b=new XMLHttpRequest();
        b.open("POST","api",true);
        b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        b.onreadystatechange=function()
        {
          if(b.readyState==XMLHttpRequest.DONE&&b.status==200)
          {

            if(b.responseText.length<=2){
            //alert(c.nomor_akta_cerai);return false;
              document.getElementById("loader").style.display="none";
              var d='<div class="w3-row w3-red w3-padding w3-center">Akta Cerai Belum diterbitkan</div><hr><br><br><br><br>';
            }else
            {

              var c=JSON.parse(b.responseText);  

             // alert(c.nomor_perkara);
              <?php
                if(date('l')=="Friday"){
                  $tanggal_pengambilan=date('Y-m-d', strtotime(' +3 day'));
                }else
                if(date('l')=="Saturday"){
                  $tanggal_pengambilan=date('Y-m-d', strtotime(' +2 day'));
                }else{
                  $tanggal_pengambilan=date('Y-m-d', strtotime(' +2 day'));
                }
              ?>
              var tanggal_pengambilan='<?php echo $tanggal_pengambilan?>';
              var d='<div class="w3-row w3-pale-green w3-padding w3-center"><p>Nomor Perkara : <b>'+c.nomor_perkara+'</b></p><p style="display:none" id="baris_pihak">Pihak : <b><span id="pihak_tampil"></span></b></p><input id="pihak" type="hidden"><input id="p_t" type="hidden"> <input id="nomor_perkara" value="'+c.nomor_perkara+'" type="hidden"><input id="nomor_akta_cerai" value="'+c.nomor_akta_cerai+'" type="hidden"><div id="blok_pihak"><p>Silahkan pilih Pihak :</p> <p><button onclick="pilih_pihak(1,'+"'"+c.pihak1_text+"'"+')"  class="w3-btn w3-green">'+c.pihak1_text+'</button></p> <p><button onclick="pilih_pihak(2,'+"'"+c.pihak2_text+"'"+')" class="w3-btn w3-green">'+c.pihak2_text+'</button></p></div><hr><div id="blok_tanggal" style="display:none">Nomor WhatsApp <font color="red">*</font>)<br><input id="nomor_wa"><br><br>Silahkan Pilih Tanggal Pengambilan: <font color="red">*</font><br><input id="tanggal" type="date" min="'+tanggal_pengambilan+'"><br><br><button class="w3-green w3-btn" onclick="pilih_nomor_antrian()">Antri</button></div><div><hr><br><br><br><br>';
            }
            
            document.getElementById("antrian_aktacerai_hasil").innerHTML=d;
            document.getElementById("antrian_aktacerai_warning").style.display="block";
            document.getElementById("antrian_aktacerai_hasil").style.display="block";
            document.getElementById("antrian_aktacerai_loading").style.display="none";
            document.getElementById("antrian_ac_inputan").style.display="none";
            document.getElementById("loader").style.display="none";
          }
        };
        b.send("nomor_perkara="+encodeURIComponent(btoa(nomor_perkara_antrian_aktacerai))+"&os="+encodeURIComponent(btoa(os))+"&ip="+encodeURIComponent(btoa(ip))+"&aksi="+btoa("info_akta_cerai"));

      }
      function proses_antrian_aktacerai(nomor_perkara){
        var os=document.getElementById("os").value;
        var ip=document.getElementById("ip").value;
        document.getElementById("antrian_aktacerai_loading").style.display="inline";
        document.getElementById("antrian_aktacerai_loading").innerHTML="<h3 class=w3-text-red>Silahkan Tunggu...</h3>";
        document.getElementById("loader").style.display="block";
        var b=new XMLHttpRequest();
        b.open("POST","api",true);
        b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        b.onreadystatechange=function()
        {
          if(b.readyState==XMLHttpRequest.DONE&&b.status==200)
          {
            var c=JSON.parse(b.responseText);
            if(c.status=='red')
            {
              var d='<div class="w3-row w3-red w3-padding w3-center">'+atob(c.message)+'</div><hr><br><br><br><br>';
            }else
            {
              var d=atob(c.respons)+"<hr><br><br><br>";
            }
            document.getElementById("antrian_aktacerai_hasil").innerHTML=d;
            document.getElementById("antrian_aktacerai_warning").style.display="block";
            document.getElementById("antrian_aktacerai_hasil").style.display="block";
            document.getElementById("antrian_aktacerai_loading").style.display="none";
            document.getElementById("antrian_ac_inputan").style.display="none";
            document.getElementById("loader").style.display="none";
          }
        };
        b.send("nomor_perkara="+encodeURIComponent(btoa(nomor_perkara))+"&os="+encodeURIComponent(btoa(os))+"&ip="+encodeURIComponent(btoa(ip))+"&aksi="+btoa("proses_antrian_aktacerai"));
      }

      function pilih_nomor_ac(nomor_antrian){
        document.getElementById("nomor_antrian_dipilih").value=nomor_antrian;
        document.getElementById("nomor_antrian_dipilih_tampil").innerHTML=nomor_antrian;
        document.getElementById("ket").focus();
      }
      function pilih_nomor_antrian(){
        var nomor_akta_cerai=document.getElementById("nomor_akta_cerai").value;
        var nomor_perkara=document.getElementById("nomor_perkara").value;
        var tanggal=document.getElementById("tanggal").value;
        var nama=document.getElementById("pihak").value;
        var pihak=document.getElementById("p_t").value;
        var nomor_wa=document.getElementById("nomor_wa").value;
        
        if(nomor_wa==""){
          notifier.show('<font color=red>Perhatian!</font>' , '<font color=red>Silahkan isikan Nomor WhatsApp anda</font>', '', 'assets/img/high_priority-48.png', 8000);
          return false;
        }else
        if(tanggal=="")
        {
          notifier.show('<font color=red>Perhatian!</font>' , '<font color=red>Silahkan pilih Tanggal Pengambilan</font>', '', 'assets/img/high_priority-48.png', 8000);
          return false;
        }
        document.getElementById("loader").style.display="block";
        var b=new XMLHttpRequest();
        b.open("POST","api",true);
        b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        b.onreadystatechange=function()
        {
          if(b.readyState==XMLHttpRequest.DONE&&b.status==200)
          {
            var c=JSON.parse(b.responseText);
            if(c.status=='ada')
            {
              document.getElementById("loader").style.display="none";
              notifier.show('<font color=red>Perhatian!</font>' , '<font color=red>Nomor Antrian sudah terpilih silahkan pilih Nomor Antrian lain</font>', '', 'assets/img/high_priority-48.png', 8000);
              proses_antrian_aktacerai(nomor_perkara);              
              return false;
            }else
            {
              var d=atob(c.respons)+"<hr><br><br><br>";
            }
            document.getElementById("antrian_aktacerai_hasil").innerHTML=d;
            document.getElementById("antrian_aktacerai_warning").style.display="block";
            document.getElementById("antrian_aktacerai_hasil").style.display="block";
            document.getElementById("antrian_aktacerai_loading").style.display="none";
            document.getElementById("antrian_ac_inputan").style.display="none";
            document.getElementById("loader").style.display="none";
          }
        };
        b.send("nomor_akta_cerai="+encodeURIComponent(btoa(nomor_akta_cerai))+"&nomor_perkara="+encodeURIComponent(btoa(nomor_perkara))+"&pihak="+encodeURIComponent(btoa(pihak))+"&tanggal="+encodeURIComponent(btoa(tanggal))+"&nama="+encodeURIComponent(btoa(nama))+"&nomor_wa="+encodeURIComponent(btoa(nomor_wa))+"&aksi="+btoa("cek_antrian_aktacerai"));
       // alert(ruangan_id+':::'+nomor_antrian);
      }
      function proses_pilih_antri_ac(){
        var nomor_akta_cerai=document.getElementById("nomor_akta_cerai").value;
        var nomor_perkara=document.getElementById("nomor_perkara").value;
        var tanggal=document.getElementById("tanggal").value;
        var nama=document.getElementById("nama").value;
        var pihak=document.getElementById("pihak").value;
        var nomor_antrian_dipilih=document.getElementById("nomor_antrian_dipilih").value;
        if(nomor_antrian_dipilih=="")
        {
          notifier.show('<font color=red>Perhatian!</font>' , '<font color=red>Silahkan pilih Nomor Antrian</font>', '', 'assets/img/high_priority-48.png', 8000);
          return false;
        }
        document.getElementById("loader").style.display="block";
        var b=new XMLHttpRequest();
        b.open("POST","api",true);
        b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        b.onreadystatechange=function()
        {
          if(b.readyState==XMLHttpRequest.DONE&&b.status==200)
          {
            var c=JSON.parse(b.responseText);
            if(c.status=='ada')
            {
              document.getElementById("loader").style.display="none";
              notifier.show('<font color=red>Perhatian!</font>' , '<font color=red>Nomor Antrian sudah terpilih silahkan pilih Nomor Antrian lain</font>', '', 'assets/img/high_priority-48.png', 8000);
              proses_antrian_aktacerai(nomor_perkara);              
              return false;
            }else
            {
              var d=atob(c.respons)+"<hr><br><br><br>";
            }
            document.getElementById("antrian_aktacerai_hasil").innerHTML=d;
            document.getElementById("antrian_aktacerai_warning").style.display="block";
            document.getElementById("antrian_aktacerai_hasil").style.display="block";
            document.getElementById("antrian_aktacerai_loading").style.display="none";
            document.getElementById("antrian_ac_inputan").style.display="none";
            document.getElementById("loader").style.display="none";
          }
        };
        b.send("nomor_akta_cerai="+encodeURIComponent(nomor_akta_cerai)+"&nomor_perkara="+encodeURIComponent(nomor_perkara)+"&nomor_antrian_dipilih="+encodeURIComponent(btoa(nomor_antrian_dipilih))+"&pihak="+encodeURIComponent(pihak)+"&tanggal="+encodeURIComponent(tanggal)+"&nama="+encodeURIComponent(nama)+"&aksi="+btoa("inputkan_antrian_aktacerai"));
       // alert(ruangan_id+':::'+nomor_antrian);
      }
      function pilih_pihak(pihak,nama){
        document.getElementById("pihak").value=nama;
        if(pihak==1){
          pihak='p';
        }else{
          pihak='t';
        }
        document.getElementById("p_t").value=pihak;
        document.getElementById("pihak_tampil").innerHTML=nama;
        document.getElementById("baris_pihak").style.display="block";
        document.getElementById("blok_pihak").style.display="none";
        document.getElementById("blok_tanggal").style.display="block";
      }
    </script>
  <!-- Modal Antrian Sidang --> 

  <link rel="stylesheet" href="assets/plugin/notifier/css/notifier.min.css">
  <script type="text/javascript" src="assets/plugin/notifier/js/notifier.min.js"></script>
<?php include('_sys_footer.php');?>