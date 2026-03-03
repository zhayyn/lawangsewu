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
  <div class="w3-rest w3-cell-middle"><span style="font-size: 18px">VALIDASI AKTA CERAI</span></div>
</div> 
<!-- Header -->
<br>
<br>
<br>
<!-- Isi -->
<div class="w3-row">
    <div class="w3-container" id="informasi_ac_inputan"> 
            Untuk memvalidasi Akta Cerai pada <?php echo ucwords(strtolower($namapa))?> silahkan : 
    <ol>
      <li>Isikan <b>nomor</b> dan pilih <b>tahun</b> perkara. </li>
      <li>Isikan <b>nomor Akta Cerai</b> dan <b>Tahun </b></li>
    </ol> 
    <center>
    <div class="w3-row w3-center" style="max-width: 400px">
        <div class="w3-card-4 w3-round-large">
            <header class="w3-container w3-teal"> <h5>Nomor Perkara</h5> </header>
            <div class="w3-container">
                 <table align="center" cellpadding="2">
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
            </div>
        </div>
    </div>
    </center>
    <br>
    <center>
    <div class="w3-row w3-center" style="max-width: 400px">
        <div class="w3-card-4 w3-round-large">
            <header class="w3-container w3-teal"> <h5>Nomor Akta Cerai</h5> </header>
            <div class="w3-container">
                 <table align="center" cellpadding="2">
                   
                  <tr>
                    <td><input id="nomor_ac" type="number" style="padding:6px 2px;width: 70px" maxlength="5" autocomplete="off"></td>
                    <td>/</td>
                    <td>
                      <select id="kode_ac" style="padding:6px 2px;">
                        <option>AC</option>
                      </select>
                    </td>
                    <td>/</td>
                    <td>
                      <select name="tahun_ac" id="tahun_ac" style="padding:6px 2px;"> 
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
            </div>
        </div>
    </div>
    <br><a href="#" id="informasi_ac_tombol_cek" class="w3-btn  w3-red">Proses</a>
    <br>
    </center>
  </div>
</div>

<div id="informasi_ac_hasil" class="w3-center w3-container"></div>
<!-- * Isi -->

<script type="text/javascript">
  informasi_ac_tombol_cek=document.getElementById("informasi_ac_tombol_cek");
  informasi_ac_tombol_cek.addEventListener('click', function(ev){
    informasi_ac_cek();
    ev.preventDefault();
  }, false);
  function informasi_ac_cek(){
    var a=document.getElementById("nomor_perkara").value;
    // var os=document.getElementById("os").value;
    //var ip=document.getElementById("ip").value;
    var kode_perkara=document.getElementById("kode_perkara").value;
    var tahun=document.getElementById("tahun").value;
    var nomor_perkara=a+'/'+kode_perkara+'/'+tahun+'/<?php echo $KodePN?>';

    var nomor_ac=document.getElementById("nomor_ac").value;
    var kode_ac=document.getElementById("kode_ac").value;
    var tahun_ac=document.getElementById("tahun_ac").value;
    var nomor_akta_cerai=nomor_ac+'/'+kode_ac+'/'+tahun_ac+'/<?php echo $KodePN?>';
    

   // var tgl_akta_cerai=document.getElementById("tgl_akta_cerai").value;
     
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
    b.send("aksi=dmFsaWRhc2lfYWt0YV9jZXJhaQ==&nomor_perkara="+encodeURIComponent(nomor_perkara)+"&nomor_akta_cerai="+encodeURIComponent(nomor_akta_cerai));
  }
</script>
<?php include('_sys_footer.php');?>