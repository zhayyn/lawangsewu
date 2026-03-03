<?php
  include('_sys_header.php');
  $menu_aktif="akta_cerai_info";
?>  
<div class="loading" id="loader">Loading&#8230;</div>
<!-- Header -->
<div class="w3-top w3-row w3-card-2 w3-padding w3-center w3-white">
  <div class="w3-col" style="width:30px">
    <a href="#" onclick="goBack()" class="headerButton goBack">
      <img src="assets/images/arrow_back-black-24dp.svg" class="w3-left w3-circle w3-margin-right" style="width:30px">
    </a>
  </div>
  <div class="w3-rest w3-cell-middle"><span style="font-size: 18px">INFORMASI AKTA CERAI</span></div>
</div> 
<!-- Header -->
<br>
<br>
<br>
<!-- Isi -->
<div class="w3-row">
    <div class="w3-container" id="informasi_ac_inputan"> 
            Untuk Mengecek penerbitan Akta Cerai pada <?php echo ucwords(strtolower($namapa))?> silahkan : 
    <ol>
      <li>Isikan <b>nomor</b> dan pilih <b>tahun</b> perkara. </li>
      <li>Apabila <b>nomor perkara</b> sudah sesuai, silahkan klik <b>Proses</b></li>
    </ol>
    <div class="w3-row">
      <table align="center" cellpadding="3">
        <tr>
          <td><input id="nomor_perkara" type="number" style="padding:6px 2px;width: 70px" maxlength="5" autocomplete="off"></td>
          <td>/</td>
          <td>
            <select id="kode_perkara" style="padding:6px 2px;">
              <option>Pdt.G</option>
            </select>
          </td>
          <td>/</td>
          <td>
            <select name="tahun" id="tahun" style="padding:6px 2px;"> 
              <?php $thn1=date("Y"); $thn2=2015; 
              for ($i = $thn1; $i >= $thn2; $i=$i-1)
                { ?> <option value="<?php echo $i; ?>" <?php if(@$tahun==$i) {echo "selected";} ?>><?php echo $i; ?></option>'; 
              <?php } 
              ?>
            </select>
            </td>
          <td>/</td>
          <td><?php echo $KodePN?></td>
        </tr>
      </table>

      <div class="w3-center">
        <a href="#" id="informasi_ac_tombol_cek" class="w3-btn w3-btn-block w3-red">Proses</a>
      </div>
    </div>
  </div>
</div>

<div id="informasi_ac_hasil" class="w3-center w3-container"></div>
<!-- * Isi -->

<script type="text/javascript">
  informasi_ac_tombol_cek=document.getElementById("informasi_ac_tombol_cek");
  informasi_ac_tombol_cek.addEventListener('click', function(ev){
    informasi_perkara_cek();
    ev.preventDefault();
  }, false);
  function informasi_perkara_cek(){
    var a=document.getElementById("nomor_perkara").value;
    // var os=document.getElementById("os").value;
    //var ip=document.getElementById("ip").value;
    var kode_perkara=document.getElementById("kode_perkara").value;
    var tahun=document.getElementById("tahun").value;
    var nomor_perkara=a+'/'+kode_perkara+'/'+tahun+'/<?php echo $KodePN?>';
    document.getElementById("loader").style.display=""; 
    var b=new XMLHttpRequest();
    b.open("POST","api.php",true);
    b.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    b.onreadystatechange=function(){
      if(b.readyState==XMLHttpRequest.DONE&&b.status==200)
      {
        var c=b.responseText;
        document.getElementById("informasi_ac_hasil").innerHTML=c;
        //document.getElementById("informasi_perkara_warning").style.display="block";
        //document.getElementById("informasi_ac_hasil").style.display="block";
        document.getElementById("loader").style.display="none";
        document.getElementById("informasi_ac_tombol_cek").focus();
        document.getElementById("informasi_ac_inputan").style.display="none";
      }
    }
    b.send("aksi=Y2VrX3BlbmVyYml0YW5fYWt0YV9jZXJhaQ==&nomor_perkara="+encodeURIComponent(nomor_perkara));
  }
</script>
<?php include('_sys_footer.php');?>